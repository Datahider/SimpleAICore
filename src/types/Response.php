<?php 

namespace losthost\SimpleAI\types;

use losthost\SimpleAI\types\ToolCall;

class Response {

    protected \stdClass $data;

    public function __construct(array|string|\stdClass $input) {
        if (is_string($input)) {
            $decoded = json_decode($input);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException("Invalid JSON string");
            }
            $this->data = $decoded;
        } elseif (is_array($input)) {
            $this->data = json_decode(json_encode($input));
        } elseif ($input instanceof \stdClass) {
            $this->data = $input;
        } else {
            throw new \InvalidArgumentException("Unsupported input type");
        }
    }

    public static function fromResponse(array|string|\stdClass $input): self {
        return new self($input);
    }

    public function __get(string $name) {
        return $this->data->$name ?? null;
    }
    
    public function getId() : string {
        return $this->data->id ?? '';
    }
    
    public function getCreated() : \DateTimeImmutable {
        return \DateTimeImmutable::createFromFormat('U', (string)($this->data->created ?? time()));
    }
    
    public function getPromptTokens() : int {
        return (int)($this->data->usage->prompt_tokens ?? 0);
    }
    
    public function getCompletionTokens() : int {
        return (int)($this->data->usage->completion_tokens ?? 0);
    }

    public function hasContent() : bool {
        return !empty($this->data->choices[0]->message->content);
    }
    
    public function getFinishReason() : string {
        return $this->data->choices[0]->finish_reason ?? 'error';
    }
    
    public function hasToolCall() : bool {
        return !empty($this->data->choices[0]->message->tool_calls[0]);
    }
    
    public function hasError() : bool {
        return !empty($this->data->error);
    }

    /**
     * @return ToolCall[]
     */
    public function getToolCalls() : array {
        $result = [];
        if (!empty($this->data->choices[0]->message->tool_calls)) {
            foreach ($this->data->choices[0]->message->tool_calls as $tool_call) {
                $result[] = ToolCall::create($tool_call);
            }
        }
        return $result;
    }
    
    public function getContent() {
        if ($this->hasContent()) {
            return $this->data->choices[0]->message->content;
        } 
        throw new \RuntimeException('Content missing');
    }
}
