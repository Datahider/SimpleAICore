<?php

namespace losthost\SimpleAI\types\abstract;

use losthost\SimpleAI\types\ToolResult;
use losthost\SimpleAI\types\Parameters;

abstract class AbstractAITool {
    
    static protected array $registered_tools = [];
    static protected array $instances = [];


    abstract public function getResult(\stdClass $params) : string;
    abstract public function getName() : string;
    abstract public function getDescription() : string;
    abstract public function getParameters() : Parameters;
    
    protected function __construct() {
        self::$registered_tools[$this->getName()] = static::class;
        self::$instances[static::class] = $this;
    }
    
    static public function getHandler(string $tool_name) : ?static {
        if (self::$registered_tools[$tool_name]) {
            $class = self::$registered_tools[$tool_name];
            return $class::create();
        }
        return null;
    }
    
    static public function create() : static {
        if (self::$instances[static::class] ?? null instanceof static) {
            return self::$instances[static::class];
        }
        return new static();
    }
    
    public function execute(\stdClass $params) : ToolResult {
        $result = ToolResult::create($this->getType(), $this->getName(), $params);
        $result->setResult($this->getResult($params));
        $result->setResultExpires($this->getExpires());
        return $result;
    }
    
    public function getExpires() : ?\DateTimeInterface {
        return null;
    }
    
    public function getType() : string {
        return 'function';
    }
    
    public function getDefinition() {
        return [
            'type' => $this->getType(),
            $this->getType() => [
                'name' => $this->getName(),
                'description' => $this->getDescription(),
                'parameters' => [
                    'type' => 'object',
                    'properties' => $this->getProperties()
                ],
                'required' => $this->getRequired()
            ]
        ];
    }
    
    protected function getProperties() : array {
        $result = [];
        
        foreach ($this->getParameters()->asArray() as $parameter) {
            $result[$parameter->getName()] = $parameter->getDefinition();
        }
        
        if (count($result)) {
            return $result;
        }
        
        return [
            'dummy' => [
                'type' => 'integer',
                'description' => 'must always be 0'
            ]
        ];
    }
    
    protected function getRequired() : array {
        $result = [];
        
        foreach ($this->getParameters()->asArray() as $parameter) {
            if ($parameter->getIsRequired()) {
                $result[] = $parameter->getName();
            }
        }
        
        if (count($result)) {
            return $result;
        }
        return [];
    }
}
