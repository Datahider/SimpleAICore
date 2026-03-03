<?php

namespace losthost\SimpleAI\types;

class Parameter {
    
    const TYPE_INT = 'integer';
    const TYPE_STRING = 'string';
    const TYPE_FLOAT = 'number';
    const TYPE_BOOL = 'boolean';
    
    protected string $type;
    protected string $name;
    protected string $description;
    protected bool $is_required;
    
    public function __construct(string $type, string $name, string $description, bool $is_required=false) {
        $this->type = $type;
        $this->name = $name;
        $this->description = $description;
        $this->is_required = $is_required;
    }
    
    static public function create(string $type, string $name, string $description, bool $is_required=false) : static {
        return new static($type, $name, $description, $is_required);
    }
    
    public function getType() : string {
        return $this->type;
    }
    
    public function getName() : string {
        return $this->name;
    }
    
    public function getDescription() : string {
        return $this->description;
    }
    
    public function getIsRequired() : string {
        return $this->is_required;
    }
    
    public function getDefinition() : array {
        return [
            'type' => $this->getType(),
            'description' => $this->getDescription()
        ];
    }
}
