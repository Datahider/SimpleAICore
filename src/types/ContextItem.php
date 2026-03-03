<?php

namespace losthost\SimpleAI\types;

class ContextItem {
    
    const ROLE_ASSISTANT = 'assistant';
    const ROLE_SYSTEM = 'system';
    const ROLE_USER = 'user';
    const ROLE_TOOL = 'tool';
    const ROLE_ERROR = 'error';
    
    protected string $content;
    protected string $role;
    protected ?string $tool_call_id;


    public function __construct(string $content, string $role=self::ROLE_USER, ?string $tool_call_id=null) {
        $this->content = $content;
        $this->role = $role;
        $this->tool_call_id = $tool_call_id;
    }
    
    static public function create(string $content, string $role=self::ROLE_USER, ?string $tool_call_id=null) {
        return new static($content, $role, $tool_call_id);
    }
    
    public function getContent() {
        return $this->content;
    }
    
    public function getRole() {
        return $this->role;
    }
    
    public function getToolCall() : array {
        if (!$this->getToolCallId()) {
            return [];
        }
        
        $tool_data = json_decode($this->getContent());
        return [
            [
                'id' => $this->getToolCallId(),
                'type' => $tool_data->type,
                $tool_data->type => [
                    'name' => $tool_data->name,
                    'arguments' => $tool_data->arguments
                ]
            ]
        ];
    }
    
    public function getToolResult() : string {
        if (!$this->getToolCallId()) {
            return '';
        }
        
        $tool_data = json_decode($this->getContent());
        return $tool_data->result;
    }


    public function getToolCallId() {
        return $this->tool_call_id;
    }
}
