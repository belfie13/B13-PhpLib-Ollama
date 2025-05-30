<?php

namespace B13\Ollama;

require_once 'Message.php';

use Countable;
use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use InvalidArgumentException;

/**
 * Manages a collection of chat messages for Ollama conversations
 * Implements Countable and ArrayAccess for easy manipulation
 */
class ChatMessages implements Countable, ArrayAccess, IteratorAggregate
{
    private array $messages = [];

    /**
     * Add a message to the conversation
     */
    public function append(Message $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Add a message by role and content
     */
    public function addMessage(string $role, string $content, array $images = [], array $tool_calls = []): void
    {
        $this->append(new Message($role, $content, $images, $tool_calls));
    }

    /**
     * Add a system message
     */
    public function addSystem(string $content): void
    {
        $this->append(Message::system($content));
    }

    /**
     * Add a user message
     */
    public function addUser(string $content, array $images = []): void
    {
        $this->append(Message::user($content, $images));
    }

    /**
     * Add an assistant message
     */
    public function addAssistant(string $content, array $tool_calls = []): void
    {
        $this->append(Message::assistant($content, $tool_calls));
    }

    /**
     * Add a tool message
     */
    public function addTool(string $toolCallId, string $content): void
    {
        $this->append(Message::tool($toolCallId, $content));
    }

    /**
     * Get all messages
     */
    public function getAll(): array
    {
        return $this->messages;
    }

    /**
     * Convert all messages to array format suitable for API requests
     */
    public function toArray(): array
    {
        return array_map(fn(Message $message) => $message->toArray(), $this->messages);
    }

    /**
     * Clear all messages
     */
    public function clear(): void
    {
        $this->messages = [];
    }

    /**
     * Get the last message
     */
    public function last(): ?Message
    {
        return empty($this->messages) ? null : end($this->messages);
    }

    /**
     * Get the first message
     */
    public function first(): ?Message
    {
        return empty($this->messages) ? null : reset($this->messages);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->messages);
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->messages[$offset]);
    }

    public function offsetGet(mixed $offset): Message
    {
        return $this->messages[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!$value instanceof Message) {
            throw new InvalidArgumentException('Value must be an instance of Message');
        }

        if ($offset === null) {
            $this->messages[] = $value;
        } else {
            $this->messages[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->messages[$offset]);
    }

    // IteratorAggregate implementation
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->messages);
    }
}