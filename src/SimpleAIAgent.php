<?php

namespace losthost\SimpleAI;

use losthost\SimpleAI\data\DBContext;
use losthost\SimpleAI\types\Context;
use losthost\SimpleAI\types\ContextItem;
use losthost\DB\DBValue;
use losthost\DB\DBView;
use losthost\SimpleAI\types\Response;
use losthost\SimpleAI\data\DBStatistics;
use losthost\SimpleAI\types\Tools;
use losthost\SimpleAI\types\abstract\AbstractAITool;
use losthost\SimpleAI\types\ProviderRegistry;
use losthost\SimpleAI\types\ProviderInterface;
use losthost\SimpleAI\types\AIOptions;

class SimpleAIAgent {

    public const DEFAULT_PROMPT = '';
    public const DEFAULT_TEMPERATURE = 1.0;
    public const DEFAULT_MAX_TOKENS = 4096;
    public const DEFAULT_TIMEOUT = 30;

    protected string $provider_name;
    protected string $api_key;
    protected ?string $model = null;

    protected ?string $user_id = null;
    protected ?string $dialog_id = null;

    protected bool $logging = false;
    protected int $timeout = self::DEFAULT_TIMEOUT;

    protected string $prompt = self::DEFAULT_PROMPT;
    protected float $temperature = self::DEFAULT_TEMPERATURE;
    protected int $max_tokens = self::DEFAULT_MAX_TOKENS;

    protected Tools $tools;
    protected ProviderInterface $provider;

    protected function __construct(string $api_key, string $provider_name) {
        $this->api_key = $api_key;
        $this->tools = Tools::create();
        $this->setProvider($provider_name);
    }

    public static function build(string $api_key, string $provider_name) : static {
        return new static($api_key, $provider_name);
    }

    public function setProvider(string $provider_name) : static {
        $this->provider_name = $provider_name;
        $this->provider = ProviderRegistry::create($provider_name);
        $this->model = null;
        return $this;
    }

    public function setTimeout(int $timeout) : static { $this->timeout = $timeout; return $this; }
    public function setTemperature(float $temperature) : static { $this->temperature = $temperature; return $this; }
    public function setMaxTokens(int $max_tokens) : static { $this->max_tokens = $max_tokens; return $this; }
    public function setModel(string $model) : static { $this->model = $model; return $this; }
    public function setPrompt(string $prompt) : static { $this->prompt = $prompt; return $this; }
    public function setUserId(?string $user_id) : static { $this->user_id = $user_id; return $this; }
    public function setDialogId(?string $dialog_id) : static { $this->dialog_id = $dialog_id; return $this; }

    public function getTimeout() : int { return $this->timeout; }
    public function getTemperature() : float { return $this->temperature; }
    public function getMaxTokens() : int { return $this->max_tokens; }
    public function getModel() : ?string { return $this->model; }
    public function getPrompt() : string { return $this->prompt; }
    public function getUserId() : ?string { return $this->user_id; }
    public function getDialogId() : ?string { return $this->dialog_id; }
    public function getProviderName() : string { return $this->provider_name; }
    
    protected function postQuery(Context $context) : Response {

        if (empty($this->model)) {
            throw new \LogicException("Model is not set for provider {$this->provider_name}");
        }

        $options = new AIOptions(
            api_key: $this->api_key,
            timeout: $this->timeout,
            model: $this->model,
            temperature: $this->temperature,
            max_tokens: $this->max_tokens,
            logging: $this->logging,
        );

        return $this->provider->request($context, $this->tools, $options);
    }

    public function ask(?string $query=null, bool|string|callable $handle_errors=false) : Context {

        try {
            return $this->dispatchQuery($query);
        } catch (\Throwable $ex) {
            if ($handle_errors === false) {
                throw $ex;
            } elseif ($handle_errors === true) {
                return Context::create()
                        ->add(ContextItem::create($ex->getMessage(), ContextItem::ROLE_ERROR));
            } elseif (is_callable($handle_errors)) {
                $answer = $handle_errors($ex);
                return is_string($answer)
                        ? Context::create()
                            ->add(ContextItem::create($answer, ContextItem::ROLE_ERROR))
                        : $answer;
            } elseif (is_string($handle_errors)) {
                return Context::create()->add(ContextItem::create($handle_errors, ContextItem::ROLE_ERROR));
            }
        }
    }

    protected function dispatchQuery(?string $query) : Context {
        if (empty($this->user_id)) {
            return $this->simpleQuery($query);
        } else {
            return $this->contextQuery($query);
        }
    }

    protected function simpleQuery(string $query) : Context  {

        $context = Context::create([
                ContextItem::create($this->getPrompt(), ContextItem::ROLE_SYSTEM),
                ContextItem::create($query)
        ]);

        $answer_context = Context::create();
        $this->processContext($context, $answer_context);

        return $answer_context;
    }

    protected function contextQuery(?string $query) : Context {

        $context = $this->getContext($query);

        $answer_context = Context::create();
        $this->processContext($context, $answer_context);

        $this->historyAdd($answer_context);

        return $answer_context;
    }

    protected function processContext(Context $history, Context &$new) {

        $context = Context::create($history->asArray());
        foreach ($new->asArray() as $item) {
            $context->add($item);
        }

        $response = $this->postQuery($context);

        if ($response->hasContent()) {
            $new->add(ContextItem::create($response->getContent(), ContextItem::ROLE_ASSISTANT));
        }

        if ($response->hasToolCall()) {
            foreach ($response->getToolCalls() as $tool_call) {
                $handler = AbstractAITool::getHandler($tool_call->getName());
                $result = $handler->execute($tool_call->getArgs());
                $new->add(ContextItem::create(
                    $result->getResult(),
                    ContextItem::ROLE_TOOL,
                    $tool_call->getId()
                ));
                $this->processContext($history, $new);
            }
        }
    }


    // =======================
    // История и БД
    // =======================

    protected function historyAdd(Context $new) : void {
        foreach ($new->asArray() as $item) {
            DBContext::add(
                $this->user_id,
                $this->dialog_id,
                $item->getRole(),
                $item->getContent(),
                $item->getToolCallId()
            );
        }
    }

    protected function getContextView(string $user_id, string $dialog_id) : DBView {
        return new DBView(<<<FIN
                SELECT role, content, tool_call_id 
                FROM [sai_context] 
                WHERE user_id = ? AND dialog_id = ? 
                ORDER BY id
                FIN, [$user_id, $dialog_id]);
    }

    protected function getContext($query) : Context {

        if (!$this->hasContext()) {
            $this->makeContext();
        }

        if ($query) {
            DBContext::add($this->user_id, $this->dialog_id, 'user', $query);
        }

        $context_view = $this->getContextView($this->user_id, $this->dialog_id);

        $context = new Context();
        while ($context_view->next()) {
            $context->add(
                ContextItem::create(
                    $context_view->content,
                    $context_view->role,
                    $context_view->tool_call_id
                )
            );
        }

        return $context;
    }

    protected function hasContext() {
        DBContext::initDataStructure();
        $context = new DBValue(<<<FIN
                SELECT COUNT(*) AS messages 
                FROM [sai_context] 
                WHERE user_id = ? AND dialog_id = ? 
                FIN, [$this->user_id, $this->dialog_id]);

        return (bool)$context->messages;
    }

    protected function makeContext() {
        $prompt = $this->getPrompt();
        if (!empty($prompt)) {
            DBContext::add($this->user_id, $this->dialog_id, 'system', $prompt);
        }
    }

    protected function collectStatistics(Response $response) : void {

        DBStatistics::add(
            $response->getId(),
            $response->getCreated(),
            $this->user_id,
            $this->dialog_id,
            $response->getPromptTokens(),
            $response->getCompletionTokens()
        );
    }

    public function addTool(AbstractAITool $tool) : static {
        $this->tools->add($tool);
        return $this;
    }

    public function setLogging(bool $enable) : static {
        if (!$enable) {
            $this->log("Logging disabled");
        }
        $this->logging = $enable;
        $this->log("Logging enabled");
        return $this;
    }

    protected function log(mixed $what_to_log, ?string $file_name=null, ?int $line_number=null) {
        if (!$this->logging) {
            return;
        }

        if (is_string($what_to_log)) {
            $log_message = "$what_to_log";
            if ($file_name) {
                $log_message .= " in $file_name";
            }
            if ($line_number) {
                $log_message .= " ($line_number)";
            }
        } else {
            $log_message = print_r($what_to_log, true);
            if ($file_name) {
                $log_message .= "\n in $file_name";
            }
            if ($line_number) {
                $log_message .= " ($line_number)";
            }
        }

        $m = [];
        preg_match("/(\w+)$/", static::class, $m);
        error_log($m[1]. ": ". $log_message);
    }
}
