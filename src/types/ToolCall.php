<?php

namespace losthost\SimpleAI\types;

class ToolCall {
    
    protected int $index;
    protected string $id;
    protected string $type;
    protected string $function_name;
    protected \stdClass $function_args;
    
    public function __construct(\stdClass $call_item) {
        $this->index = $call_item->index ?? 0;
        $this->id = $call_item->id;
        $this->type = $call_item->type;
        $this->function_name = $call_item->function->name;
        $this->function_args = json_decode($call_item->function->arguments);
    }
    
    static public function create(\stdClass $call_item) : static {
        return new static($call_item);
    }
    
    public function getIndex() : int {
        return $this->index;
    }
    
    public function getId() : string {
        return $this->id;
    }
    
    public function getType() : string {
        return $this->type;
    }
    
    public function getName() : string {
        if ($this->getType() == 'function') {
            return $this->function_name;
        }
        throw new \RuntimeException('Unknown tool type: '. $this->getType());
    }
    
    public function getArgs() : \stdClass {
        if ($this->getType() == 'function') {
            return $this->function_args;
        }
        throw new \RuntimeException('Unknown tool type: '. $this->getType());
    }
}
