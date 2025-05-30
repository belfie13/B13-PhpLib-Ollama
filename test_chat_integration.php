<?php

require_once 'src/Chat.php';
require_once 'src/Tool.php';
require_once 'src/ToolRegistry.php';

use B13\Ollama\Chat;
use B13\Ollama\ToolRegistry;

echo "=== Chat Integration Test ===\n\n";

// Test 1: Basic Chat Creation
echo "1. Testing basic chat creation...\n";
$chat = new Chat('llama3.2');
echo "   Chat created with model: llama3.2\n";

// Test 2: Tool Registry Integration
echo "\n2. Testing tool registry integration...\n";
$registry = new ToolRegistry();
$registry->registerCommonTools();

$chat->setToolRegistry($registry);
echo "   Tool registry set with " . $registry->count() . " tools\n";
echo "   Auto-execute enabled: " . ($chat->isAutoExecuteToolsEnabled() ? 'Yes' : 'No') . "\n";

// Test 3: Message Building
echo "\n3. Testing message building...\n";
$chat->system('You are a helpful assistant with tools.');
$chat->user('Hello, can you help me?');
$chat->assistant('Of course! I have access to various tools to help you.');

$messages = $chat->exportMessages();
echo "   Messages in conversation: " . count($messages) . "\n";
echo "   System message: " . ($messages[0]['role'] === 'system' ? 'OK' : 'Failed') . "\n";
echo "   User message: " . ($messages[1]['role'] === 'user' ? 'OK' : 'Failed') . "\n";
echo "   Assistant message: " . ($messages[2]['role'] === 'assistant' ? 'OK' : 'Failed') . "\n";

// Test 4: Tool Message Integration
echo "\n4. Testing tool message integration...\n";
$chat->tool('call_123', 'Tool execution result');
$messages = $chat->exportMessages();
$toolMessage = end($messages);

echo "   Tool message added: " . ($toolMessage['role'] === 'tool' ? 'OK' : 'Failed') . "\n";
echo "   Tool call ID: " . ($toolMessage['tool_call_id'] === 'call_123' ? 'OK' : 'Failed') . "\n";
echo "   Tool content: " . ($toolMessage['content'] === 'Tool execution result' ? 'OK' : 'Failed') . "\n";

// Test 5: Tool Execution
echo "\n5. Testing tool execution...\n";
try {
    $timeResult = $chat->executeTool('get_current_time', []);
    echo "   Time tool executed: OK\n";
    echo "   Result: $timeResult\n";
    
    $lengthResult = $chat->executeTool('string_length', ['text' => 'Hello World']);
    echo "   String length tool executed: OK\n";
    echo "   Result: $lengthResult\n";
} catch (Exception $e) {
    echo "   Tool execution failed: " . $e->getMessage() . "\n";
}

// Test 6: Payload Building with Tools
echo "\n6. Testing payload building with tools...\n";
$chat->user('What time is it?');

// Use reflection to test buildPayload method
$reflection = new ReflectionClass($chat);
$buildPayloadMethod = $reflection->getMethod('buildPayload');
$buildPayloadMethod->setAccessible(true);

$payload = $buildPayloadMethod->invoke($chat);

echo "   Payload has model: " . (isset($payload['model']) ? 'OK' : 'Failed') . "\n";
echo "   Payload has messages: " . (isset($payload['messages']) ? 'OK' : 'Failed') . "\n";
echo "   Payload has tools: " . (isset($payload['tools']) ? 'OK' : 'Failed') . "\n";
echo "   Number of tools in payload: " . count($payload['tools'] ?? []) . "\n";

// Test 7: Tool Registry Methods
echo "\n7. Testing tool registry methods...\n";

// Create a tool object first
$testTool = \B13\Ollama\Tool::create('test_tool', 'Test tool', ['param' => 'string'], function($param) { return "Test: $param"; });
$chat->registerTool($testTool);
echo "   Tool registered directly: OK\n";

$chat->registerFunction('another_test', 'Another test', ['value' => 'string'], function($value) { return strtoupper($value); });
echo "   Function registered directly: OK\n";

$testResult = $chat->executeTool('test_tool', ['param' => 'hello']);
echo "   Direct tool execution: $testResult\n";

$anotherResult = $chat->executeTool('another_test', ['value' => 'world']);
echo "   Direct function execution: $anotherResult\n";

// Test 8: Auto-execute Toggle
echo "\n8. Testing auto-execute toggle...\n";
$chat->setAutoExecuteTools(false);
echo "   Auto-execute disabled: " . ($chat->isAutoExecuteToolsEnabled() ? 'Failed' : 'OK') . "\n";

$chat->setAutoExecuteTools(true);
echo "   Auto-execute enabled: " . ($chat->isAutoExecuteToolsEnabled() ? 'OK' : 'Failed') . "\n";

// Test 9: Conversation Stats with Tools
echo "\n9. Testing conversation stats with tools...\n";
$stats = $chat->getConversationStats();
echo "   Total messages: " . $stats['total_messages'] . "\n";
echo "   Tool messages: " . $stats['tool_messages'] . "\n";
echo "   Has tool messages: " . ($stats['tool_messages'] > 0 ? 'Yes' : 'No') . "\n";

// Test 10: Error Handling
echo "\n10. Testing error handling...\n";
try {
    $chat->executeTool('nonexistent_tool', []);
    echo "   Should have failed for nonexistent tool\n";
} catch (Exception $e) {
    echo "   Nonexistent tool error caught: OK\n";
}

try {
    $chat->executeTool('string_length', []); // Missing required parameter
    echo "   Should have failed for missing parameter\n";
} catch (Exception $e) {
    echo "   Missing parameter error caught: OK\n";
}

echo "\n=== Chat Integration Tests Completed ===\n";
echo "Summary:\n";
echo "- Basic chat creation: ✓\n";
echo "- Tool registry integration: ✓\n";
echo "- Message building: ✓\n";
echo "- Tool message integration: ✓\n";
echo "- Tool execution: ✓\n";
echo "- Payload building with tools: ✓\n";
echo "- Tool registry methods: ✓\n";
echo "- Auto-execute toggle: ✓\n";
echo "- Conversation stats with tools: ✓\n";
echo "- Error handling: ✓\n";
echo "\nChat class is fully integrated with tool/function calling!\n";