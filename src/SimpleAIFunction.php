<?php

namespace losthost\SimpleAI;

use losthost\SimpleAI\types\abstract\AbstractAITool;
use losthost\SimpleAI\types\ToolResult;
use losthost\SimpleAI\types\Parameters;
use losthost\SimpleAI\types\Parameter;

class SimpleAIFunction extends AbstractAITool {
    
    #[\Override]
    public function getDescription(): string {
        return <<<FIN
                Тестовая функция для проверки API. Вызывай ее по просьбе 
                пользователя и сообщай какой результат она вернула.
                FIN;
    }

    #[\Override]
    public function getName(): string {
        return 'simple_function';
    }

    #[\Override]
    public function getParameters(): Parameters {
        return Parameters::create([
            Parameter::create(Parameter::TYPE_INT, 'random_int', 'Случайное цело число в диапазоне от 0 до 999. Придумай его сам любое при вызове этой функции', true),
            Parameter::create(Parameter::TYPE_STRING, 'text', 'Передавай сюда текст для тестирования, который попросит пользователь (если попросит)'),
        ]);
    }

    #[\Override]
    public function getResult(\stdClass $params): string {
        return "Пользователь должен написать фразу: \"$params->text\" $params->random_int раз";
    }
}
