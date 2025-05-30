<?php
/**
 * Health check script for the B13 Ollama Chat Interface
 * Checks if all dependencies and services are working
 */

header('Content-Type: application/json');

$health = [
    'status' => 'ok',
    'timestamp' => date('c'),
    'checks' => []
];

// Check PHP version
$health['checks']['php_version'] = [
    'status' => version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'error',
    'value' => PHP_VERSION,
    'required' => '8.1.0+'
];

// Check if autoloader exists
$health['checks']['autoloader'] = [
    'status' => file_exists('../vendor/autoload.php') ? 'ok' : 'error',
    'message' => file_exists('../vendor/autoload.php') ? 'Found' : 'Missing - run composer install'
];

// Check if classes can be loaded
try {
    require_once '../vendor/autoload.php';
    $health['checks']['classes'] = [
        'status' => 'ok',
        'message' => 'B13\Ollama classes loaded successfully'
    ];
} catch (Exception $e) {
    $health['checks']['classes'] = [
        'status' => 'error',
        'message' => 'Failed to load classes: ' . $e->getMessage()
    ];
}

// Check Ollama connection
if (function_exists('curl_init')) {
    try {
        $ch = curl_init('http://localhost:11434/api/tags');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $models = json_decode($response, true);
            $health['checks']['ollama'] = [
                'status' => 'ok',
                'message' => 'Ollama server is running',
                'models_count' => count($models['models'] ?? [])
            ];
        } else {
            $health['checks']['ollama'] = [
                'status' => 'warning',
                'message' => "Ollama server responded with HTTP {$httpCode}"
            ];
        }
    } catch (Exception $e) {
        $health['checks']['ollama'] = [
            'status' => 'error',
            'message' => 'Cannot connect to Ollama server: ' . $e->getMessage()
        ];
    }
} else {
    $health['checks']['ollama'] = [
        'status' => 'error',
        'message' => 'Cannot check Ollama - cURL extension missing'
    ];
}

// Check session support
$health['checks']['sessions'] = [
    'status' => session_status() !== PHP_SESSION_DISABLED ? 'ok' : 'error',
    'message' => session_status() !== PHP_SESSION_DISABLED ? 'Sessions enabled' : 'Sessions disabled'
];

// Check JSON support
$health['checks']['json'] = [
    'status' => function_exists('json_encode') ? 'ok' : 'error',
    'message' => function_exists('json_encode') ? 'JSON support available' : 'JSON extension missing'
];

// Check cURL support
$health['checks']['curl'] = [
    'status' => function_exists('curl_init') ? 'ok' : 'error',
    'message' => function_exists('curl_init') ? 'cURL support available' : 'cURL extension missing'
];

// Determine overall status
$hasErrors = false;
foreach ($health['checks'] as $check) {
    if ($check['status'] === 'error') {
        $hasErrors = true;
        break;
    }
}

$health['status'] = $hasErrors ? 'error' : 'ok';

// Set appropriate HTTP status code
http_response_code($hasErrors ? 503 : 200);

echo json_encode($health, JSON_PRETTY_PRINT);