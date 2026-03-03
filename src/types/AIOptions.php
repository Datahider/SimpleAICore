<?php

namespace losthost\SimpleAI\types;

class AIOptions {

    public function __construct(
        public readonly string $api_key,
        public readonly int $timeout,
        public readonly string $model,
        public readonly float $temperature = 1.0,
        public readonly int $max_tokens = 4096,
        public readonly bool $logging = false,
    ) {}
}
