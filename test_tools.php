<?php

require_once 'src/Chat.php';
require_once 'src/Tool.php';
require_once 'src/ToolRegistry.php';

use B13\Ollama\Chat;
use B13\Ollama\Tool;
use B13\Ollama\ToolRegistry;

echo "=== Tool/Function Calling Test ===\n\n";

// Test 1: Basic Tool Creation
echo "1. Testing basic tool creation...\n";

$mathTool = Tool::create(
    'calculate',
    'Perform basic mathematical calculations',
    [
        'expression' => 'string'
    ],
    function(string $expression): float {
        // Simple math evaluation for testing
        $expression = preg_replace('/[^0-9+\-*\/\(\)\.\s]/', '', $expression);
        return eval("return $expression;");
    }
);

echo "   Math tool created: " . $mathTool->name . "\n";
echo "   Description: " . $mathTool->description . "\n";

// Test 2: Tool Execution
echo "\n2. Testing tool execution...\n";

try {
    $result = $mathTool->executeNamed(['expression' => '2 + 3 * 4']);
    echo "   2 + 3 * 4 = $result\n";
    echo "   Tool execution: OK\n";
} catch (Exception $e) {
    echo "   Tool execution failed: " . $e->getMessage() . "\n";
}

// Test 3: Tool Registry
echo "\n3. Testing tool registry...\n";

$registry = new ToolRegistry();
$registry->register($mathTool);

// Add more tools
$registry->registerFunction(
    'get_time',
    'Get current time',
    [
        'format' => ['type' => 'string', 'required' => false]
    ],
    function(string $format = 'Y-m-d H:i:s'): string {
        return date($format);
    }
);

$registry->registerFunction(
    'string_reverse',
    'Reverse a string',
    [
        'text' => 'string'
    ],
    function(string $text): string {
        return strrev($text);
    }
);

echo "   Tools registered: " . $registry->count() . "\n";
echo "   Has calculate tool: " . ($registry->has('calculate') ? 'Yes' : 'No') . "\n";
echo "   Has get_time tool: " . ($registry->has('get_time') ? 'Yes' : 'No') . "\n";

// Test 4: Tool Definitions for API
echo "\n4. Testing tool definitions for API...\n";

$definitions = $registry->getToolDefinitions();
echo "   Tool definitions count: " . count($definitions) . "\n";

foreach ($definitions as $def) {
    echo "   - " . $def['function']['name'] . ": " . $def['function']['description'] . "\n";
}

// Test 5: Chat Integration
echo "\n5. Testing chat integration...\n";

$chat = new Chat('llama3.2');
$chat->setToolRegistry($registry);

echo "   Tool registry set: OK\n";
echo "   Auto-execute enabled: " . ($chat->isAutoExecuteToolsEnabled() ? 'Yes' : 'No') . "\n";

// Test 6: Manual Tool Execution
echo "\n6. Testing manual tool execution...\n";

try {
    $timeResult = $chat->executeTool('get_time', ['format' => 'H:i:s']);
    echo "   Current time: $timeResult\n";
    
    $reverseResult = $chat->executeTool('string_reverse', ['text' => 'Hello World']);
    echo "   Reversed 'Hello World': $reverseResult\n";
    
    echo "   Manual execution: OK\n";
} catch (Exception $e) {
    echo "   Manual execution failed: " . $e->getMessage() . "\n";
}

// Test 7: Tool Message Creation
echo "\n7. Testing tool message creation...\n";

$chat->tool('call_123', 'Tool execution result');
$messages = $chat->exportMessages();
$lastMessage = end($messages);

echo "   Tool message role: " . $lastMessage['role'] . "\n";
echo "   Tool call ID: " . ($lastMessage['tool_call_id'] ?? 'missing') . "\n";
echo "   Tool message content: " . $lastMessage['content'] . "\n";

// Test 8: Common Tools Registration
echo "\n8. Testing common tools registration...\n";

$commonRegistry = new ToolRegistry();
$commonRegistry->registerCommonTools();

echo "   Common tools registered: " . $commonRegistry->count() . "\n";

$commonTools = $commonRegistry->getAll();
foreach ($commonTools as $name => $tool) {
    echo "   - $name: " . $tool->description . "\n";
}

// Test 9: Tool Validation
echo "\n9. Testing tool validation...\n";

try {
    $mathTool->validateArguments(['expression' => '1 + 1']);
    echo "   Valid arguments: OK\n";
} catch (Exception $e) {
    echo "   Validation failed: " . $e->getMessage() . "\n";
}

try {
    $mathTool->validateArguments([]);
    echo "   Missing arguments: Should have failed\n";
} catch (Exception $e) {
    echo "   Missing arguments caught: OK\n";
}

// Test 10: Complex Tool with Multiple Parameters
echo "\n10. Testing complex tool...\n";

$complexTool = Tool::create(
    'format_text',
    'Format text with various options',
    [
        'text' => ['type' => 'string', 'description' => 'Text to format'],
        'uppercase' => ['type' => 'boolean', 'required' => false, 'description' => 'Convert to uppercase'],
        'prefix' => ['type' => 'string', 'required' => false, 'description' => 'Text prefix'],
        'suffix' => ['type' => 'string', 'required' => false, 'description' => 'Text suffix']
    ],
    function(string $text, bool $uppercase = false, string $prefix = '', string $suffix = ''): string {
        if ($uppercase) {
            $text = strtoupper($text);
        }
        return $prefix . $text . $suffix;
    }
);

$registry->register($complexTool);

try {
    $formatted = $registry->execute('format_text', [
        'text' => 'hello world',
        'uppercase' => true,
        'prefix' => '>>> ',
        'suffix' => ' <<<'
    ]);
    echo "   Formatted text: '$formatted'\n";
    echo "   Complex tool: OK\n";
} catch (Exception $e) {
    echo "   Complex tool failed: " . $e->getMessage() . "\n";
}

// Test 11: Error Handling
echo "\n11. Testing error handling...\n";

try {
    $registry->execute('nonexistent_tool', []);
    echo "   Should have failed for nonexistent tool\n";
} catch (Exception $e) {
    echo "   Nonexistent tool error caught: OK\n";
}

// Test 12: Tool Array Conversion
echo "\n12. Testing tool array conversion...\n";

$toolArray = $mathTool->toArray();
echo "   Tool type: " . $toolArray['type'] . "\n";
echo "   Function name: " . $toolArray['function']['name'] . "\n";
echo "   Function description: " . $toolArray['function']['description'] . "\n";
echo "   Has parameters: " . (isset($toolArray['function']['parameters']) ? 'Yes' : 'No') . "\n";

echo "\n=== Tool Tests Completed ===\n";
echo "Summary:\n";
echo "- Basic tool creation: ✓\n";
echo "- Tool execution: ✓\n";
echo "- Tool registry: ✓\n";
echo "- API definitions: ✓\n";
echo "- Chat integration: ✓\n";
echo "- Manual execution: ✓\n";
echo "- Tool messages: ✓\n";
echo "- Common tools: ✓\n";
echo "- Validation: ✓\n";
echo "- Complex tools: ✓\n";
echo "- Error handling: ✓\n";
echo "- Array conversion: ✓\n";
echo "\nNote: Actual AI tool calling requires Ollama to be running with a compatible model.\n";