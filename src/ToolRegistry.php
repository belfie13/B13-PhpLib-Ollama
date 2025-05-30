<?php

namespace B13\Ollama;

require_once 'Tool.php';

/**
 * Registry for managing available tools/functions
 */
class ToolRegistry
{
    /** @var Tool[] */
    private array $tools = [];

    /**
     * Register a tool
     */
    public function register(Tool $tool): self
    {
        $this->tools[$tool->name] = $tool;
        return $this;
    }

    /**
     * Register a simple function as a tool
     */
    public function registerFunction(string $name, string $description, array $parameters, callable $function): self
    {
        $tool = Tool::create($name, $description, $parameters, $function);
        return $this->register($tool);
    }

    /**
     * Get a tool by name
     */
    public function get(string $name): ?Tool
    {
        return $this->tools[$name] ?? null;
    }

    /**
     * Check if a tool exists
     */
    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    /**
     * Get all registered tools
     */
    public function getAll(): array
    {
        return $this->tools;
    }

    /**
     * Get tool definitions for Ollama API
     */
    public function getToolDefinitions(): array
    {
        return array_map(fn(Tool $tool) => $tool->toArray(), $this->tools);
    }

    /**
     * Execute a tool by name
     */
    public function execute(string $name, array $arguments): mixed
    {
        $tool = $this->get($name);
        if (!$tool) {
            throw new \InvalidArgumentException("Tool not found: {$name}");
        }

        $tool->validateArguments($arguments);
        return $tool->executeNamed($arguments);
    }

    /**
     * Remove a tool
     */
    public function unregister(string $name): self
    {
        unset($this->tools[$name]);
        return $this;
    }

    /**
     * Clear all tools
     */
    public function clear(): self
    {
        $this->tools = [];
        return $this;
    }

    /**
     * Get count of registered tools
     */
    public function count(): int
    {
        return count($this->tools);
    }

    /**
     * Register common utility functions
     */
    public function registerCommonTools(): self
    {
        // Math functions
        $this->registerFunction(
            'calculate',
            'Perform basic mathematical calculations',
            [
                'expression' => ['type' => 'string', 'description' => 'Mathematical expression to evaluate']
            ],
            function(string $expression): float {
                // Simple and safe math evaluation
                $expression = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expression);
                if (empty($expression)) {
                    throw new \InvalidArgumentException('Invalid mathematical expression');
                }
                
                try {
                    return eval("return $expression;");
                } catch (Throwable $e) {
                    throw new \InvalidArgumentException('Error evaluating expression: ' . $e->getMessage());
                }
            }
        );

        // Date/time functions
        $this->registerFunction(
            'get_current_time',
            'Get the current date and time',
            [
                'format' => ['type' => 'string', 'required' => false, 'description' => 'Date format (default: Y-m-d H:i:s)']
            ],
            function(string $format = 'Y-m-d H:i:s'): string {
                return date($format);
            }
        );

        // File system functions
        $this->registerFunction(
            'read_file',
            'Read contents of a text file',
            [
                'filename' => ['type' => 'string', 'description' => 'Path to the file to read']
            ],
            function(string $filename): string {
                if (!file_exists($filename)) {
                    throw new \InvalidArgumentException("File not found: {$filename}");
                }
                if (!is_readable($filename)) {
                    throw new \InvalidArgumentException("File not readable: {$filename}");
                }
                return file_get_contents($filename);
            }
        );

        // String functions
        $this->registerFunction(
            'string_length',
            'Get the length of a string',
            [
                'text' => ['type' => 'string', 'description' => 'Text to measure']
            ],
            function(string $text): int {
                return strlen($text);
            }
        );

        return $this;
    }
}