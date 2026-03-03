<?php

namespace losthost\SimpleAI\types;

use losthost\SimpleAI\types\abstract\AbstractCollection;

class Parameters extends AbstractCollection {
    
    #[\Override]
    protected function getAllowedTypes(): array {
        return [Parameter::class];
    }
}
