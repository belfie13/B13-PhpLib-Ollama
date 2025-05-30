<?php

namespace B13\Ollama;

/**
 * Represents a tool/function that can be called by the AI
 */
class Tool
{
    private $function;

    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly array $parameters,
        callable $function
    ) {
        $this->function = $function;
    }

    /**
     * Create a tool from a PHP function
     */
    public static function fromFunction(string $name, string $description, array $parameters, callable $function): self
    {
        return new self($name, $description, $parameters, $function);
    }

    /**
     * Create a tool with simple parameters
     */
    public static function create(string $name, string $description, array $parameters, callable $function): self
    {
        // Convert simple parameter definitions to JSON schema format
        $schema = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];

        foreach ($parameters as $paramName => $paramDef) {
            if (is_string($paramDef)) {
                // Simple type definition
                $schema['properties'][$paramName] = ['type' => $paramDef];
                $schema['required'][] = $paramName;
            } elseif (is_array($paramDef)) {
                // Detailed parameter definition
                $schema['properties'][$paramName] = $paramDef;
                if ($paramDef['required'] ?? true) {
                    $schema['required'][] = $paramName;
                }
            }
        }

        return new self($name, $description, $schema, $function);
    }

    /**
     * Execute the tool with given arguments
     */
    public function execute(array $arguments): mixed
    {
        return call_user_func($this->function, ...$arguments);
    }

    /**
     * Execute the tool with named arguments
     */
    public function executeNamed(array $arguments): mixed
    {
        // Get function reflection to match parameter names
        $reflection = new \ReflectionFunction($this->function);
        $params = $reflection->getParameters();
        
        $orderedArgs = [];
        foreach ($params as $param) {
            $paramName = $param->getName();
            if (isset($arguments[$paramName])) {
                $orderedArgs[] = $arguments[$paramName];
            } elseif ($param->isDefaultValueAvailable()) {
                $orderedArgs[] = $param->getDefaultValue();
            } else {
                throw new \InvalidArgumentException("Missing required parameter: {$paramName}");
            }
        }

        return call_user_func($this->function, ...$orderedArgs);
    }

    /**
     * Get tool definition for Ollama API
     */
    public function toArray(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => $this->parameters
            ]
        ];
    }

    /**
     * Validate arguments against parameter schema
     */
    public function validateArguments(array $arguments): bool
    {
        $required = $this->parameters['required'] ?? [];
        
        // Check required parameters
        foreach ($required as $param) {
            if (!isset($arguments[$param])) {
                throw new \InvalidArgumentException("Missing required parameter: {$param}");
            }
        }

        // Check parameter types (basic validation)
        $properties = $this->parameters['properties'] ?? [];
        foreach ($arguments as $name => $value) {
            if (isset($properties[$name])) {
                $expectedType = $properties[$name]['type'] ?? null;
                if ($expectedType && !$this->validateType($value, $expectedType)) {
                    throw new \InvalidArgumentException("Parameter {$name} must be of type {$expectedType}");
                }
            }
        }

        return true;
    }

    /**
     * Basic type validation
     */
    private function validateType(mixed $value, string $expectedType): bool
    {
        return match ($expectedType) {
            'string' => is_string($value),
            'number' => is_numeric($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value) || is_array($value),
            default => true // Unknown types pass validation
        };
    }
}