<?php

namespace losthost\SimpleAI\types\abstract;

abstract class AbstractCollection {

    protected array $data;
    protected int $cursor;
    
    abstract protected function getAllowedTypes() : array;
    
    public function __construct(?array $data=null) {
        $this->data = [];
        
        if (is_null($data)) {
            return;
        }
        
        foreach ($data as $item) {
            if (!$this->isItemAllowed($item)) {
                throw new \RuntimeException('This type of item is not allowed by ::getAllowedTypes()');
            }
            $this->data[] = $item;
        }
    }
    
    static public function create(?array $data=null) : static {
        return new static($data);
    }

    protected function isItemAllowed(mixed $item) : bool {
        
        foreach ($this->getAllowedTypes() as $type) {
            if ($item instanceof $type) {
                return true;
            }
        }
        return false;
    }
    
    public function add(mixed $item) : static {
        if (!$this->isItemAllowed($item)) {
            throw new \RuntimeException('This type of item is not allowed by ::getAllowedTypes()');
        }
        $this->data[] = $item;
        return $this;
    }
    
    public function get(?int $index=null) : mixed {
        if (is_null($index)) {
            return $this->data[$this->cursor];
        }
        return $this->data[$index];
    }
    
    public function getCount() {
        return count($this->data);
    }

    public function reset() : static {
        $this->cursor = 0;
        return $this;
    }
    
    public function next() : mixed {
        $this->cursor++;
        if ($this->cursor >= $this->getCount()) {
            return false;
        }
        return $this->get();
    }
    
    public function asArray() : array {
        return $this->data;
    }
    
}
