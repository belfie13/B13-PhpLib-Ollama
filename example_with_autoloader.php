<?php

// This example shows how to use the library with Composer autoloading
// Run: composer install
// Then: php example_with_autoloader.php

require_once 'vendor/autoload.php';

use B13\Ollama\Chat;
use B13\Ollama\ModelOptions;

// Example usage of the Chat class with autoloading

try {
    // Basic usage - quick single message
    echo "=== Quick Chat Example ===\n";
    $response = Chat::quick('llama3.2', 'Hello, how are you?');
    echo "Response: " . $response->getContent() . "\n";
    echo "Tokens per second: " . ($response->getTokensPerSecond() ?? 'N/A') . "\n\n";

    // Conversation with system prompt
    echo "=== Conversation Example ===\n";
    $chat = Chat::withSystem('llama3.2', 'You are a helpful coding assistant.');
    
    $response1 = $chat->send('What is PHP?');
    echo "Q: What is PHP?\n";
    echo "A: " . $response1->getContent() . "\n\n";
    
    $response2 = $chat->send('Can you show me a simple PHP class example?');
    echo "Q: Can you show me a simple PHP class example?\n";
    echo "A: " . $response2->getContent() . "\n\n";

    // Using model options
    echo "=== With Model Options ===\n";
    $options = new ModelOptions();
    $options->temperature = 0.7;
    $options->top_p = 0.9;
    $options->num_predict = 100;

    $chat3 = new Chat('llama3.2');
    $chat3->setOptions($options)
          ->setFormat('json');
    
    $response4 = $chat3->send('Generate a JSON object with a person\'s name and age');
    echo "JSON Response: " . $response4->getContent() . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure Ollama is running and the model is available.\n";
    echo "You can start Ollama with: ollama serve\n";
    echo "And pull a model with: ollama pull llama3.2\n";
}