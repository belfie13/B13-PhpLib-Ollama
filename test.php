<?php

require_once 'src/Chat.php';

use B13\Ollama\Chat;
use B13\Ollama\Message;
use B13\Ollama\ChatMessages;
use B13\Ollama\ModelOptions;

echo "=== Testing B13-PhpLib-Ollama Chat Classes ===\n\n";

// Test Message class
echo "1. Testing Message class...\n";
$message = Message::user('Hello, world!');
echo "Created user message: " . json_encode($message->toArray()) . "\n";

$systemMessage = Message::system('You are a helpful assistant');
echo "Created system message: " . json_encode($systemMessage->toArray()) . "\n\n";

// Test ChatMessages class
echo "2. Testing ChatMessages class...\n";
$messages = new ChatMessages();
$messages->addSystem('You are a helpful assistant');
$messages->addUser('What is 2+2?');
$messages->addAssistant('2+2 equals 4');

echo "Messages count: " . $messages->count() . "\n";
echo "First message: " . json_encode($messages->first()->toArray()) . "\n";
echo "Last message: " . json_encode($messages->last()->toArray()) . "\n";
echo "All messages: " . json_encode($messages->toArray()) . "\n\n";

// Test ModelOptions
echo "3. Testing ModelOptions class...\n";
$options = new ModelOptions();
$options->temperature = 0.7;
$options->top_p = 0.9;
$options->num_predict = 100;

echo "Model options array: " . json_encode($options->toArray()) . "\n";
echo "Model options JSON: " . $options->toJson() . "\n\n";

// Test Chat class (without actually calling Ollama)
echo "4. Testing Chat class setup...\n";
$chat = new Chat('llama3.2');
$chat->setOptions($options)
     ->setFormat('json')
     ->setKeepAlive('10m');

$chat->system('You are a helpful assistant')
     ->user('Hello')
     ->assistant('Hi there! How can I help you?');

echo "Chat messages count: " . $chat->getMessages()->count() . "\n";
echo "Exported messages: " . json_encode($chat->exportMessages()) . "\n\n";

// Test conversation export/import
echo "5. Testing conversation export/import...\n";
$exportedMessages = $chat->exportMessages();
$newChat = new Chat('llama3.2');
$newChat->loadMessages($exportedMessages);

echo "Original chat messages: " . $chat->getMessages()->count() . "\n";
echo "New chat messages: " . $newChat->getMessages()->count() . "\n";
echo "Messages match: " . (json_encode($chat->exportMessages()) === json_encode($newChat->exportMessages()) ? 'Yes' : 'No') . "\n\n";

echo "=== All tests completed successfully! ===\n";
echo "Note: To test actual Ollama communication, make sure Ollama is running and use example.php\n";