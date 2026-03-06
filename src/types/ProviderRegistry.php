<?php

namespace losthost\SimpleAI\types;

class ProviderRegistry {

    protected static array $factories = [];

    public static function register(string $name, callable $factory) : void {
        self::$factories[$name] = $factory;
    }

    public static function create(string $name) : ProviderInterface {

        if (empty(self::$factories[$name])) {
            throw new \RuntimeException("Provider not registered: $name");
        }

        $provider = (self::$factories[$name])();

        if (!$provider instanceof ProviderInterface) {
            throw new \RuntimeException("Provider factory must return ProviderInterface: $name");
        }

        return $provider;
    }
}
