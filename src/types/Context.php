<?php

namespace losthost\SimpleAI\types;

use losthost\SimpleAI\types\abstract\AbstractCollection;

class Context extends AbstractCollection {
    
    const FILTER_NONE = 0;
    const FILTER_USER = 1;
    const FILTER_ASSISTANT = 2;
    const FILTER_SYSTEM = 4;
    const FILTER_TOOL = 8;
    const FILTER_ERROR = 16;
    const FILTER_ALL = 255;
    
    #[\Override]
    protected function getAllowedTypes(): array {
        return [ContextItem::class];
    }
    
    #[\Override]
    /**
     * @return ContextItem[]
     */
    public function asArray(int $filter=self::FILTER_ALL): array {
        
        if ($filter == self::FILTER_ALL) {
            return parent::asArray();
        }
        
        $filter_array = $this->getFilterArray($filter);
        $result_array = [];

        foreach (parent::asArray() as $item) {
            if (in_array($item->getRole(), $filter_array, true)) {
                $result_array[] = $item;
            }
        }
        
        return $result_array;
    }
    
    public function asString(int $filter=self::FILTER_ALL, string $delimiter = "\n\n") {
        
        $items = $this->asArray($filter);
        $strings = array_map(fn($item) => $item->getContent(), $items);
        return implode($delimiter, $strings);
    }
    
    protected function getFilterArray(int $filter) {
        $values = [
            self::FILTER_USER => 'user',
            self::FILTER_ASSISTANT => 'assistant',
            self::FILTER_SYSTEM => 'system',
            self::FILTER_TOOL => 'tool',
            self::FILTER_ERROR => 'error',
        ];
        
        $result = [];
        
        foreach ($values as $key=>$value) {
            if ($filter & $key) {
                $result[] = $value;
            }
        }
        
        return $result;
    }
}
