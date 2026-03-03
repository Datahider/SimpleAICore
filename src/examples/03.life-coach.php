<?php

use losthost\SimpleAI\SimpleAIAgent;
use losthost\DB\DB;
use losthost\SimpleAI\data\DBContext;

require '../../vendor/autoload.php';
require '../../etc/config.php'; // set $deepseek_api_key

DB::connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PREFIX);

$agent = SimpleAIAgent::build(OPENAI_API_KEY)
        ->setUserId(1)
        ->setDialogId(2)
        ->setTimeout(1)
        ->setPrompt(<<<FIN
                You are a top-tier life coach specializing in financial management. 
                Introduce yourself, greet me, and ask me the necessary questions 
                    one by one (not all at once) to understand if I need your help. 
                If I do, let's get started and see how you can assist me.
                FIN);

echo "Press <Enter> 3 times to send your message:\n";
while (true) {
    
    $input = '';
    echo "\n> ";
    while (substr($input, -3) != "\n\n\n") {
        $input .= readline(). "\n";
    }
    
    echo "\033[1;34mthinking...\033[0m\n";
    $retry_count = 2;
    echo "\033[34m". $agent->ask($input, fn($e) => retryOnTimeout($e, $agent, $retry_count))->asString(). "\033[0m\n";
}

function retryOnTimeout(\Throwable $e, SimpleAIAgent $agent, int $retry_count) {
    $error_text = $e->getMessage();
    if (preg_match("/^cURL error: Operation timed out after/", $error_text)) {
        if ($retry_count <=0) {
            throw $e;
        }
        $retry_count--;

        error_log("Retrying...");
        $agent->setTimeout(120);
        return $agent->ask(null, fn($e) => retryOnTimeout($e, $agent, $retry_count));
    } else {
        throw $e;
    }
}
