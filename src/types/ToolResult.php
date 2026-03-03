<?php

namespace losthost\SimpleAI\types;

class ToolResult {
    
    protected string $tool_result;
    protected ?\DateTimeInterface $result_expires;
    protected string $tool_type;
    protected string $tool_name;
    protected \stdClass $tool_args;


    public function __construct(string $tool_type, string $tool_name, \stdClass $tool_args) {
        $this->tool_type = $tool_type;
        $this->tool_name = $tool_name;
        $this->tool_args = $tool_args;
    }
    
    static public function create(string $tool_type, string $tool_name, \stdClass $tool_args) : static {
        return new static($tool_type, $tool_name, $tool_args);
    }

    public function setResult(string $tool_result) {
        $this->tool_result = $tool_result;
        return $this;
    }
    
    public function setResultExpires(?\DateTimeInterface $result_expires=null) {
        $this->result_expires = $result_expires;
        return $this;
    }
    
    public function getResult() : string {
        $json = json_encode([
            'type' => $this->tool_type,
            'name' => $this->tool_name,
            'arguments' => json_encode($this->tool_args),
            'result' => $this->tool_result,
            'expires' => $this->result_expires
        ]);
        return $json;
    }
    
    public function getExpires() : \DateTimeInterface {
        return $this->result_expires;
    }
}
