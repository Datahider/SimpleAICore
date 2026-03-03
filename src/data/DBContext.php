<?php

namespace losthost\SimpleAI\data;

use losthost\DB\DBObject;
use losthost\DB\DB;

class DBContext extends DBObject {
    const METADATA = [
        'id' => 'BIGINT NOT NULL AUTO_INCREMENT',
        'user_id' => 'VARCHAR(50) NOT NULL',
        'dialog_id' => 'VARCHAR(50) NOT NULL',
        'role' => 'ENUM("system", "user", "assistant", "tool")',
        'tool_call_id' => 'VARCHAR(50)',
        'content' => 'TEXT',
        'date_time' => 'DATETIME NOT NULL',
        'PRIMARY KEY' => 'id',
        'UNIQUE INDEX USER_DIALOG_ROLE_ID' => ['user_id', 'dialog_id', 'role', 'id'],
    ];
    
    public static function tableName() {
        return DB::$prefix. 'sai_context';
    }
    
    static public function add(string $user_id, string $dialog_id, string $role, string $content, string $tool_call_id=null) : static {
        
        $me = new static();
        $me->user_id = $user_id;
        $me->dialog_id = $dialog_id;
        $me->role = $role;
        $me->content = $content;
        $me->tool_call_id = $tool_call_id;
        $me->date_time = date_create();
        $me->write();
        
        return $me;
    }
}
