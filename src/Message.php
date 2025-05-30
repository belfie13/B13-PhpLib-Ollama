<?php

namespace B13\Ollama;

/**
 * Represents a single chat message in an Ollama conversation
 */
class Message
{
    public const ROLE_SYSTEM = 'system';
    public const ROLE_USER = 'user';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_TOOL = 'tool';

    /**
     * @param string $role The role of the message (system, user, assistant, or tool)
     * @param string $content The content of the message
     * @param array $images Optional list of base64-encoded images for multimodal models
     * @param array $tool_calls Optional list of tools the model wants to use
     * @param string|null $tool_call_id Optional tool call ID for tool messages
     */
    public function __construct(
        public string $role,
        public string $content,
        public array $images = [],
        public array $tool_calls = [],
        public ?string $tool_call_id = null
    ) {
        $this->validateRole($role);
    }

    /**
     * Validate that the role is one of the allowed values
     */
    private function validateRole(string $role): void
    {
        $allowedRoles = [
            self::ROLE_SYSTEM,
            self::ROLE_USER,
            self::ROLE_ASSISTANT,
            self::ROLE_TOOL
        ];

        if (!in_array($role, $allowedRoles)) {
            throw new InvalidArgumentException(
                "Invalid role '$role'. Must be one of: " . implode(', ', $allowedRoles)
            );
        }
    }

    /**
     * Convert the message to an array suitable for JSON encoding
     */
    public function toArray(): array
    {
        $data = [
            'role' => $this->role,
            'content' => $this->content
        ];

        if (!empty($this->images)) {
            $data['images'] = $this->images;
        }

        if (!empty($this->tool_calls)) {
            $data['tool_calls'] = $this->tool_calls;
        }

        if ($this->tool_call_id !== null) {
            $data['tool_call_id'] = $this->tool_call_id;
        }

        return $data;
    }

    /**
     * Create a system message
     */
    public static function system(string $content): self
    {
        return new self(self::ROLE_SYSTEM, $content);
    }

    /**
     * Create a user message
     */
    public static function user(string $content, array $images = []): self
    {
        return new self(self::ROLE_USER, $content, $images);
    }

    /**
     * Create an assistant message
     */
    public static function assistant(string $content, array $tool_calls = []): self
    {
        return new self(self::ROLE_ASSISTANT, $content, [], $tool_calls);
    }

    /**
     * Create a tool message
     */
    public static function tool(string $toolCallId, string $content): self
    {
        return new self(self::ROLE_TOOL, $content, [], [], $toolCallId);
    }
}