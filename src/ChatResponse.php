<?php

namespace B13\Ollama;

/**
 * Represents a response from Ollama's chat API
 */
class ChatResponse
{
    public function __construct(
        public readonly string $model,
        public readonly string $created_at,
        public readonly Message $message,
        public readonly bool $done,
        public readonly ?int $total_duration = null,
        public readonly ?int $load_duration = null,
        public readonly ?int $prompt_eval_count = null,
        public readonly ?int $prompt_eval_duration = null,
        public readonly ?int $eval_count = null,
        public readonly ?int $eval_duration = null
    ) {}

    /**
     * Create a ChatResponse from API response data
     */
    public static function fromArray(array $data): self
    {
        $message = new Message(
            $data['message']['role'],
            $data['message']['content'],
            $data['message']['images'] ?? [],
            $data['message']['tool_calls'] ?? []
        );

        return new self(
            model: $data['model'],
            created_at: $data['created_at'],
            message: $message,
            done: $data['done'],
            total_duration: $data['total_duration'] ?? null,
            load_duration: $data['load_duration'] ?? null,
            prompt_eval_count: $data['prompt_eval_count'] ?? null,
            prompt_eval_duration: $data['prompt_eval_duration'] ?? null,
            eval_count: $data['eval_count'] ?? null,
            eval_duration: $data['eval_duration'] ?? null
        );
    }

    /**
     * Get the response content
     */
    public function getContent(): string
    {
        return $this->message->content;
    }

    /**
     * Calculate tokens per second if timing data is available
     */
    public function getTokensPerSecond(): ?float
    {
        if ($this->eval_count === null || $this->eval_duration === null || $this->eval_duration === 0) {
            return null;
        }

        return $this->eval_count / ($this->eval_duration / 1_000_000_000);
    }

    /**
     * Get performance metrics as an array
     */
    public function getMetrics(): array
    {
        return [
            'total_duration' => $this->total_duration,
            'load_duration' => $this->load_duration,
            'prompt_eval_count' => $this->prompt_eval_count,
            'prompt_eval_duration' => $this->prompt_eval_duration,
            'eval_count' => $this->eval_count,
            'eval_duration' => $this->eval_duration,
            'tokens_per_second' => $this->getTokensPerSecond()
        ];
    }
}