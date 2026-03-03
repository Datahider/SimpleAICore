<?php

namespace losthost\SimpleAI\types;

use losthost\SimpleAI\types\abstract\AbstractCollection;
use losthost\SimpleAI\types\abstract\AbstractAITool;

class Tools extends AbstractCollection {
    
    #[\Override]
    protected function getAllowedTypes(): array {
        return [AbstractAITool::class];
    }
}
