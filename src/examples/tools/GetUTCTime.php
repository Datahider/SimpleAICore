<?php

namespace losthost\SimpleAI\examples\tools;

use losthost\SimpleAI\types\abstract\AbstractAITool;
use losthost\SimpleAI\types\Parameters;
use losthost\DB\DB;

class GetUTCTime extends AbstractAITool {
    
    #[\Override]
    public function getDescription(): string {
        return 'Получает текущие дату и время UTC в формате '. DB::DATE_FORMAT;
    }

    #[\Override]
    public function getName(): string {
        return 'time';
    }

    #[\Override]
    public function getParameters(): \losthost\SimpleAI\types\Parameters {
        return Parameters::create();
    }

    #[\Override]
    public function getResult(\stdClass $params): string {
        $now = date_create();
        $now->setTimezone(new \DateTimeZone('UTC'));
        return $now->format(DB::DATE_FORMAT);
    }
}
