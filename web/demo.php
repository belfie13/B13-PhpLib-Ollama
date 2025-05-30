<?php
/**
 * Demo script to test the B13 Ollama Chat Interface
 * This script demonstrates the API functionality without the web interface
 */

require_once '../vendor/autoload.php';

use B13\Ollama\Chat;
use B13\Ollama\ToolRegistry;

echo "B13 Ollama Chat Interface Demo\n";
echo "==============================\n\n";

try {
    // Create a new chat instance
    $chat = new Chat('llama3.2');
    echo "✓ Chat instance created with model: llama3.2\n";
    
    // Register tools
    $registry = new ToolRegistry();
    $chat->registerTool($registry->getCalculatorTool());
    $chat->registerTool($registry->getTimeTool());
    $chat->registerTool($registry->getStringTool());
    echo "✓ Tools registered: Calculator, Time, String\n\n";
    
    // Test messages
    $testMessages = [
        "Hello! Can you help me with some calculations?",
        "What's 15 * 23 + 100?",
        "What time is it now?",
        "Can you convert 'hello world' to uppercase?"
    ];
    
    foreach ($testMessages as $i => $message) {
        echo "Test " . ($i + 1) . ": {$message}\n";
        echo str_repeat("-", 50) . "\n";
        
        try {
            $response = $chat->send($message);
            echo "Response: " . $response->getMessage() . "\n";
            
            $toolCalls = $response->getToolCalls();
            if (!empty($toolCalls)) {
                echo "Tool calls:\n";
                foreach ($toolCalls as $call) {
                    echo "  - {$call['function']['name']}: " . json_encode($call['function']['arguments']) . "\n";
                    echo "    Result: " . ($call['result'] ?? 'No result') . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    // Show conversation stats
    $stats = $chat->getConversationStats();
    echo "Conversation Statistics:\n";
    echo "- Total messages: {$stats['total_messages']}\n";
    echo "- User messages: {$stats['user_messages']}\n";
    echo "- Assistant messages: {$stats['assistant_messages']}\n";
    echo "- Tool messages: {$stats['tool_messages']}\n";
    
} catch (Exception $e) {
    echo "Demo failed: " . $e->getMessage() . "\n";
    echo "Make sure Ollama is running and the model is available.\n";
    echo "Run: ollama pull llama3.2\n";
}