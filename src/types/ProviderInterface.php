<?php

namespace losthost\SimpleAI\types;

interface ProviderInterface {

    public function request(
        Context $context,
        Tools $tools,
        AIOptions $options
    ) : Response;
}
