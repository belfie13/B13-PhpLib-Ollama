<?php

require_once 'src/Chat.php';

use B13\Ollama\Chat;
use B13\Ollama\ModelOptions;

echo "=== Chat Summarization Example ===\n\n";

try {
    // Create a chat instance
    $chat = new Chat('llama3.2');
    $chat->system('You are a helpful programming assistant.');

    // Simulate a long conversation by adding multiple messages
    echo "Building a long conversation...\n";
    
    $conversation = [
        ['user', 'What is object-oriented programming?'],
        ['assistant', 'Object-oriented programming (OOP) is a programming paradigm based on the concept of objects, which contain data and code.'],
        ['user', 'What are the main principles of OOP?'],
        ['assistant', 'The main principles are: Encapsulation, Inheritance, Polymorphism, and Abstraction.'],
        ['user', 'Can you explain encapsulation?'],
        ['assistant', 'Encapsulation is the bundling of data and methods that operate on that data within a single unit or class.'],
        ['user', 'What about inheritance?'],
        ['assistant', 'Inheritance allows a class to inherit properties and methods from another class, promoting code reuse.'],
        ['user', 'How does polymorphism work?'],
        ['assistant', 'Polymorphism allows objects of different types to be treated as instances of the same type through a common interface.'],
        ['user', 'What is abstraction in OOP?'],
        ['assistant', 'Abstraction hides complex implementation details and shows only the necessary features of an object.'],
        ['user', 'Can you give me a PHP example of a class?'],
        ['assistant', 'Sure! Here\'s a simple PHP class: class Car { private $brand; public function __construct($brand) { $this->brand = $brand; } public function getBrand() { return $this->brand; } }'],
    ];

    // Add all messages to the conversation
    foreach ($conversation as [$role, $content]) {
        if ($role === 'user') {
            $chat->user($content);
        } else {
            $chat->assistant($content);
        }
    }

    // Show conversation statistics
    echo "\n--- Conversation Statistics ---\n";
    $stats = $chat->getConversationStats();
    echo "Total messages: " . $stats['total_messages'] . "\n";
    echo "Messages by role: " . json_encode($stats['by_role']) . "\n";
    echo "Total characters: " . $stats['total_characters'] . "\n";
    echo "Estimated tokens: " . $stats['estimated_tokens'] . "\n\n";

    // Show current conversation structure
    echo "--- Current Conversation Structure ---\n";
    $messages = $chat->exportMessages();
    foreach ($messages as $i => $msg) {
        $preview = substr($msg['content'], 0, 60) . (strlen($msg['content']) > 60 ? '...' : '');
        echo sprintf("%2d. %-9s: %s\n", $i + 1, $msg['role'], $preview);
    }
    echo "\n";

    // Test auto-summarization check
    echo "--- Auto-Summarization Check ---\n";
    echo "Current estimated tokens: " . $stats['estimated_tokens'] . "\n";
    
    $thresholds = [200, 500, 1000];
    foreach ($thresholds as $threshold) {
        $wouldSummarize = $stats['estimated_tokens'] > $threshold;
        echo "Would auto-summarize with {$threshold} token threshold: " . ($wouldSummarize ? 'Yes' : 'No') . "\n";
    }
    echo "\n";

    // Demonstrate what summarization would do (without actual API call)
    echo "--- Summarization Simulation ---\n";
    echo "If we were to summarize keeping the last 3 messages:\n";
    
    $keepRecent = 3;
    $totalMessages = count($messages);
    $systemMessage = ($messages[0]['role'] === 'system') ? 1 : 0;
    $messagesToSummarize = $totalMessages - $keepRecent - $systemMessage;
    
    echo "- Total messages: $totalMessages\n";
    echo "- System messages: $systemMessage\n";
    echo "- Messages to summarize: $messagesToSummarize\n";
    echo "- Recent messages to keep: $keepRecent\n";
    echo "- Result would be: " . ($systemMessage + 1 + $keepRecent) . " messages (system + summary + recent)\n\n";

    if ($messagesToSummarize > 0) {
        echo "Messages that would be summarized:\n";
        $startIdx = $systemMessage;
        $endIdx = $startIdx + $messagesToSummarize;
        
        for ($i = $startIdx; $i < $endIdx; $i++) {
            $msg = $messages[$i];
            $preview = substr($msg['content'], 0, 50) . '...';
            echo "  " . ($i + 1) . ". {$msg['role']}: $preview\n";
        }
        echo "\n";
        
        echo "Recent messages that would be kept:\n";
        for ($i = $totalMessages - $keepRecent; $i < $totalMessages; $i++) {
            $msg = $messages[$i];
            $preview = substr($msg['content'], 0, 50) . '...';
            echo "  " . ($i + 1) . ". {$msg['role']}: $preview\n";
        }
    }

    echo "\n--- Usage Examples ---\n";
    echo "To actually perform summarization (requires Ollama running):\n";
    echo "\$chat->summarizeConversation(3); // Keep last 3 messages\n";
    echo "\$chat->autoSummarizeIfNeeded(4000); // Auto-summarize if > 4000 tokens\n\n";

    echo "Custom summarization prompt:\n";
    echo "\$chat->summarizeConversation(2, 'Create a brief summary focusing on key concepts:');\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "=== Example completed ===\n";
echo "Note: This example demonstrates the summarization logic without making actual API calls.\n";
echo "To test with real summarization, ensure Ollama is running and call the summarization methods.\n";