<?php

namespace losthost\SimpleAI\data;

use losthost\DB\DBObject;
use losthost\DB\DB;

class DBStatistics extends DBObject {
    
    const METADATA = [
        'id' => 'BIGINT NOT NULL AUTO_INCREMENT',
        'external_id' => 'VARCHAR(100) NOT NULL',
        'created' => 'DATETIME NOT NULL',
        'user_id' => 'VARCHAR(50)',
        'dialog_id' => 'VARCHAR(50)',
        'prompt_tokens' => 'BIGINT NOT NULL',
        'completion_tokens' => 'BIGINT NOT NULL',
        'PRIMARY KEY' => 'id',
        
    ];

    static public function create($data = [], $create = false) {
        return new static($data, $create);
    }
    
    public static function tableName() {
        return DB::$prefix. 'sai_statistics';
    }
    
    static public function add(string $external_id, \DateTime|\DateTimeImmutable $created, 
            string $user_id, string $dialog_id, int $prompt_tokens, int $completion_tokens) {
        
        $me = static::create();
        $me->external_id = $external_id;
        $me->created = $created;
        $me->user_id = $user_id;
        $me->dialog_id = $dialog_id;
        $me->prompt_tokens = $prompt_tokens;
        $me->completion_tokens = $completion_tokens;
        $me->write();
    }
}
