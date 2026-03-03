<?php

namespace losthost\SimpleAI\types;

class FakeProvider implements ProviderInterface {

    protected array $queue;

    public function __construct(array $queue = []) {
        $this->queue = $queue;
    }

    public function push(array|string|\stdClass $response) : void {
        $this->queue[] = $response;
    }

    public function request(Context $context, Tools $tools, AIOptions $options) : Response {

        if (!$this->queue) {
            throw new \RuntimeException("FakeProvider queue is empty");
        }

        $next = array_shift($this->queue);
        return new Response($next);
    }
}
