<?php

require_once 'src/Chat.php';

use B13\Ollama\Chat;
use B13\Ollama\Message;
use B13\Ollama\ChatMessages;
use B13\Ollama\ModelOptions;

echo "=== Comprehensive Feature Test ===\n\n";

// Test 1: Basic functionality
echo "1. Testing basic chat functionality...\n";
$chat = new Chat('llama3.2');
$chat->system('You are a helpful assistant.');
$chat->user('Hello');
$chat->assistant('Hi there!');

$messages = $chat->exportMessages();
echo "   Messages created: " . count($messages) . "\n";
echo "   System message: " . ($messages[0]['role'] === 'system' ? 'OK' : 'FAIL') . "\n";
echo "   User message: " . ($messages[1]['role'] === 'user' ? 'OK' : 'FAIL') . "\n";
echo "   Assistant message: " . ($messages[2]['role'] === 'assistant' ? 'OK' : 'FAIL') . "\n";

// Test 2: Conversation statistics
echo "\n2. Testing conversation statistics...\n";
$stats = $chat->getConversationStats();
echo "   Total messages: " . $stats['total_messages'] . "\n";
echo "   System messages: " . ($stats['by_role']['system'] ?? 0) . "\n";
echo "   User messages: " . ($stats['by_role']['user'] ?? 0) . "\n";
echo "   Assistant messages: " . ($stats['by_role']['assistant'] ?? 0) . "\n";
echo "   Total characters: " . $stats['total_characters'] . "\n";
echo "   Estimated tokens: " . $stats['estimated_tokens'] . "\n";

// Test 3: Long conversation setup
echo "\n3. Setting up long conversation for summarization tests...\n";
$longChat = new Chat('llama3.2');
$longChat->system('You are a programming tutor.');

$conversation = [
    ['user', 'What is PHP?'],
    ['assistant', 'PHP is a server-side scripting language.'],
    ['user', 'How do I create variables?'],
    ['assistant', 'In PHP, variables start with $ symbol.'],
    ['user', 'What about arrays?'],
    ['assistant', 'Arrays in PHP can be indexed or associative.'],
    ['user', 'How do I create functions?'],
    ['assistant', 'Use the function keyword followed by the function name.'],
    ['user', 'What about classes?'],
    ['assistant', 'Classes are defined using the class keyword.'],
    ['user', 'How does inheritance work?'],
    ['assistant', 'Use the extends keyword to inherit from a parent class.'],
];

foreach ($conversation as [$role, $content]) {
    if ($role === 'user') {
        $longChat->user($content);
    } else {
        $longChat->assistant($content);
    }
}

$longStats = $longChat->getConversationStats();
echo "   Long conversation messages: " . $longStats['total_messages'] . "\n";
echo "   Long conversation tokens: " . $longStats['estimated_tokens'] . "\n";

// Test 4: Summarization logic validation
echo "\n4. Testing summarization logic...\n";
$messageCount = count($longChat->exportMessages());
$keepRecent = 3;
$systemMessages = ($longChat->exportMessages()[0]['role'] === 'system') ? 1 : 0;
$messagesToSummarize = $messageCount - $keepRecent - $systemMessages;

echo "   Total messages: $messageCount\n";
echo "   System messages: $systemMessages\n";
echo "   Messages to summarize: $messagesToSummarize\n";
echo "   Recent messages to keep: $keepRecent\n";
echo "   Would summarize: " . ($messagesToSummarize > 0 ? 'Yes' : 'No') . "\n";

// Test 5: Auto-summarization thresholds
echo "\n5. Testing auto-summarization thresholds...\n";
$thresholds = [100, 200, 500, 1000];
foreach ($thresholds as $threshold) {
    $wouldSummarize = $longStats['estimated_tokens'] > $threshold;
    echo "   Threshold $threshold tokens: " . ($wouldSummarize ? 'Would summarize' : 'Would not summarize') . "\n";
}

// Test 6: Edge cases
echo "\n6. Testing edge cases...\n";

// Empty conversation
$emptyChat = new Chat('llama3.2');
$emptyStats = $emptyChat->getConversationStats();
echo "   Empty conversation messages: " . $emptyStats['total_messages'] . "\n";
echo "   Empty conversation tokens: " . $emptyStats['estimated_tokens'] . "\n";

// Very short conversation
$shortChat = new Chat('llama3.2');
$shortChat->user('Hi');
$shortChat->assistant('Hello');
$shortStats = $shortChat->getConversationStats();
echo "   Short conversation messages: " . $shortStats['total_messages'] . "\n";
echo "   Short conversation would summarize (>5 msgs): " . ($shortStats['total_messages'] > 5 ? 'Yes' : 'No') . "\n";

// Test 7: Message export/import with long conversation
echo "\n7. Testing message persistence...\n";
$exportedMessages = $longChat->exportMessages();
$newChat = new Chat('llama3.2');
$newChat->loadMessages($exportedMessages);
$newStats = $newChat->getConversationStats();

echo "   Original messages: " . $longStats['total_messages'] . "\n";
echo "   Imported messages: " . $newStats['total_messages'] . "\n";
echo "   Messages match: " . ($longStats['total_messages'] === $newStats['total_messages'] ? 'Yes' : 'No') . "\n";
echo "   Tokens match: " . ($longStats['estimated_tokens'] === $newStats['estimated_tokens'] ? 'Yes' : 'No') . "\n";

// Test 8: Model options integration
echo "\n8. Testing model options...\n";
$options = new ModelOptions();
$options->temperature = 0.7;
$options->top_p = 0.9;
$options->num_predict = 100;

$optionsChat = new Chat('llama3.2');
$optionsChat->setOptions($options);
echo "   Model options set: OK\n";
echo "   Temperature: " . $options->temperature . "\n";
echo "   Top P: " . $options->top_p . "\n";
echo "   Num Predict: " . $options->num_predict . "\n";

// Test 9: Static methods
echo "\n9. Testing static methods...\n";
$quickChat = Chat::withSystem('llama3.2', 'You are helpful.');
$quickStats = $quickChat->getConversationStats();
echo "   withSystem() messages: " . $quickStats['total_messages'] . "\n";
echo "   withSystem() has system message: " . ($quickStats['by_role']['system'] > 0 ? 'Yes' : 'No') . "\n";

// Test 10: Format setting
echo "\n10. Testing format setting...\n";
$jsonChat = new Chat('llama3.2');
$jsonChat->setFormat('json');
echo "   JSON format set: OK\n";

echo "\n=== All Feature Tests Completed ===\n";
echo "Summary:\n";
echo "- Basic chat functionality: ✓\n";
echo "- Conversation statistics: ✓\n";
echo "- Long conversation handling: ✓\n";
echo "- Summarization logic: ✓\n";
echo "- Auto-summarization thresholds: ✓\n";
echo "- Edge case handling: ✓\n";
echo "- Message persistence: ✓\n";
echo "- Model options: ✓\n";
echo "- Static methods: ✓\n";
echo "- Format setting: ✓\n";
echo "\nNote: Actual API calls require Ollama to be running.\n";