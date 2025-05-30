<?php
/**
 * Configuration file for B13 Ollama Chat Interface
 */

return [
    // Ollama server configuration
    'ollama' => [
        'host' => 'localhost',
        'port' => 11434,
        'timeout' => 30
    ],
    
    // Available models
    'models' => [
        'llama3.2' => 'Llama 3.2',
        'llama3.1' => 'Llama 3.1', 
        'mistral' => 'Mistral',
        'codellama' => 'Code Llama',
        'phi3' => 'Phi-3'
    ],
    
    // Default tools to enable
    'default_tools' => [
        'calculate',
        'time',
        'string',
        'file'
    ],
    
    // Session configuration
    'session' => [
        'name' => 'b13_ollama_chat',
        'lifetime' => 3600 // 1 hour
    ],
    
    // Security settings
    'security' => [
        'max_message_length' => 10000,
        'max_conversations_per_session' => 10,
        'rate_limit_requests_per_minute' => 60
    ]
];