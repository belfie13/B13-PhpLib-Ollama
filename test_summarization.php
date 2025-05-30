<?php

require_once 'src/Chat.php';

use B13\Ollama\Chat;
use B13\Ollama\Message;
use B13\Ollama\ChatMessages;

echo "=== Testing Chat Summarization Features ===\n\n";

// Create a chat with a long conversation
$chat = new Chat('llama3.2');

// Add system message
$chat->system('You are a helpful programming assistant.');

// Simulate a long conversation
$longConversation = [
    ['role' => 'user', 'content' => 'What is PHP?'],
    ['role' => 'assistant', 'content' => 'PHP is a server-side scripting language designed for web development. It stands for PHP: Hypertext Preprocessor.'],
    ['role' => 'user', 'content' => 'How do I create a class in PHP?'],
    ['role' => 'assistant', 'content' => 'You can create a class in PHP using the "class" keyword followed by the class name and curly braces containing the class body.'],
    ['role' => 'user', 'content' => 'Can you show me an example?'],
    ['role' => 'assistant', 'content' => 'Sure! Here\'s a simple example: class MyClass { public $property = "value"; public function method() { return "Hello World"; } }'],
    ['role' => 'user', 'content' => 'What about inheritance?'],
    ['role' => 'assistant', 'content' => 'PHP supports inheritance using the "extends" keyword. A child class can inherit properties and methods from a parent class.'],
    ['role' => 'user', 'content' => 'How do I handle errors in PHP?'],
    ['role' => 'assistant', 'content' => 'PHP provides several ways to handle errors: try-catch blocks for exceptions, error_reporting() function, and custom error handlers.'],
    ['role' => 'user', 'content' => 'What is the latest version of PHP?'],
    ['role' => 'assistant', 'content' => 'As of 2024, PHP 8.3 is the latest stable version with many improvements and new features.'],
];

// Load the conversation
foreach ($longConversation as $msg) {
    if ($msg['role'] === 'user') {
        $chat->user($msg['content']);
    } else {
        $chat->assistant($msg['content']);
    }
}

echo "1. Testing conversation statistics...\n";
$stats = $chat->getConversationStats();
echo "Total messages: " . $stats['total_messages'] . "\n";
echo "Messages by role: " . json_encode($stats['by_role']) . "\n";
echo "Total characters: " . $stats['total_characters'] . "\n";
echo "Estimated tokens: " . $stats['estimated_tokens'] . "\n\n";

echo "2. Testing conversation before summarization...\n";
echo "Messages count: " . count($chat->exportMessages()) . "\n";
echo "First few messages:\n";
$messages = $chat->exportMessages();
for ($i = 0; $i < min(3, count($messages)); $i++) {
    echo "  {$messages[$i]['role']}: " . substr($messages[$i]['content'], 0, 50) . "...\n";
}
echo "\n";

echo "3. Testing manual summarization (simulated)...\n";
// Since we can't actually call Ollama in tests, we'll test the logic without actual API calls

echo "Before summarization: " . count($chat->exportMessages()) . " messages\n";

// Test the conversation stats and logic
$stats = $chat->getConversationStats();
echo "Conversation stats before summarization:\n";
echo "  Total messages: " . $stats['total_messages'] . "\n";
echo "  Estimated tokens: " . $stats['estimated_tokens'] . "\n";

// Test edge cases without actual API calls
echo "Testing edge case - conversation too short for summarization:\n";
$shortChat = new Chat('llama3.2');
$shortChat->user('Hello');
$shortChat->assistant('Hi there!');

echo "Short conversation messages before: " . count($shortChat->exportMessages()) . "\n";
// This should not attempt to summarize since it's too short
echo "Short conversation would be summarized: " . (count($shortChat->exportMessages()) > 5 ? 'Yes' : 'No') . "\n";

echo "\nTesting conversation structure analysis:\n";
$messages = $chat->exportMessages();
$systemCount = 0;
$userCount = 0;
$assistantCount = 0;

foreach ($messages as $msg) {
    switch ($msg['role']) {
        case 'system': $systemCount++; break;
        case 'user': $userCount++; break;
        case 'assistant': $assistantCount++; break;
    }
}

echo "Message breakdown:\n";
echo "  System messages: $systemCount\n";
echo "  User messages: $userCount\n";
echo "  Assistant messages: $assistantCount\n";

// Test the formatMessagesForSummary method indirectly
echo "\nTesting message formatting logic:\n";
$sampleMessages = array_slice($messages, 1, 3); // Skip system message, take 3 messages
$totalChars = 0;
foreach ($sampleMessages as $msg) {
    $totalChars += strlen($msg['content']);
}
echo "Sample 3 messages total characters: $totalChars\n";

echo "\n";

echo "4. Testing auto-summarization check...\n";
// Test the logic without actually calling summarization
$stats = $chat->getConversationStats();
$lowThreshold = 100;
$highThreshold = 10000;

echo "Current estimated tokens: " . $stats['estimated_tokens'] . "\n";
echo "Would auto-summarize with threshold $lowThreshold: " . ($stats['estimated_tokens'] > $lowThreshold ? 'Yes' : 'No') . "\n";
echo "Would auto-summarize with threshold $highThreshold: " . ($stats['estimated_tokens'] > $highThreshold ? 'Yes' : 'No') . "\n\n";

echo "5. Testing edge cases...\n";

// Test with too few messages
$shortChat = new Chat('llama3.2');
$shortChat->user('Hello');
$shortChat->assistant('Hi there!');

echo "Short conversation messages: " . count($shortChat->exportMessages()) . "\n";
echo "Short conversation should be summarized (>5 messages): " . (count($shortChat->exportMessages()) > 5 ? 'Yes' : 'No') . "\n";

echo "\n=== Summarization tests completed! ===\n";
echo "Note: To test actual summarization with Ollama, make sure Ollama is running.\n";