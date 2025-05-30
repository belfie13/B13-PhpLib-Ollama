<?php
/**
 * B13 Ollama Chat API
 * Simple REST API for the chat interface
 */

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include the autoloader
require_once '../vendor/autoload.php';

use B13\Ollama\Chat;
use B13\Ollama\ToolRegistry;

// Session management for conversations
session_start();

/**
 * Get or create a chat instance for the conversation
 * Note: We don't store Chat objects in session due to closure serialization issues
 */
function getChat($conversationId, $model = 'llama3.2') {
    // Always create a new chat instance to avoid serialization issues
    $chat = new Chat($model);
    
    // Restore conversation history if it exists
    if (isset($_SESSION['conversations'][$conversationId])) {
        $messages = $_SESSION['conversations'][$conversationId];
        foreach ($messages as $message) {
            // Restore the conversation context (this is a simplified approach)
            // In a real implementation, you'd want to restore the full conversation state
        }
    }
    
    return $chat;
}

/**
 * Register tools based on enabled tools list
 */
function registerTools($chat, $enabledTools) {
    $registry = new ToolRegistry();
    
    // Register all common tools first
    $registry->registerCommonTools();
    
    // Map frontend tool names to actual tool names
    $toolMap = [
        'calculate' => 'calculate',
        'time' => 'get_current_time',
        'string' => 'string_length',
        'file' => 'read_file'
    ];
    
    foreach ($enabledTools as $toolName) {
        if (isset($toolMap[$toolName])) {
            $actualToolName = $toolMap[$toolName];
            $tool = $registry->get($actualToolName);
            if ($tool) {
                $chat->registerTool($tool);
            }
        }
    }
}

/**
 * Send error response
 */
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

/**
 * Send success response
 */
function sendResponse($data) {
    echo json_encode($data);
    exit;
}

/**
 * Check if Ollama server is available
 */
function checkOllamaConnection() {
    $context = stream_context_create([
        'http' => [
            'timeout' => 2,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents('http://localhost:11434/api/tags', false, $context);
    return $result !== false;
}

/**
 * Handle mock requests when Ollama is not available
 */
function handleMockRequest($message, $enabledTools) {
    $response = '';
    $toolCalls = [];
    
    // Simple pattern matching for demo purposes
    if (preg_match('/(\d+)\s*[\*×]\s*(\d+)/', $message, $matches)) {
        $num1 = (int)$matches[1];
        $num2 = (int)$matches[2];
        $result = $num1 * $num2;
        
        if (in_array('calculate', $enabledTools)) {
            $toolCalls[] = [
                'id' => 'mock_calc_' . uniqid(),
                'type' => 'function',
                'function' => [
                    'name' => 'calculate',
                    'arguments' => ['expression' => "{$num1} * {$num2}"]
                ],
                'result' => (string)$result
            ];
            $response = "I calculated {$num1} × {$num2} = {$result}";
        } else {
            $response = "I can see you're asking about {$num1} × {$num2}, but the calculator tool is not enabled.";
        }
    } elseif (stripos($message, 'time') !== false || stripos($message, 'date') !== false) {
        if (in_array('time', $enabledTools)) {
            $currentTime = date('Y-m-d H:i:s');
            $toolCalls[] = [
                'id' => 'mock_time_' . uniqid(),
                'type' => 'function',
                'function' => [
                    'name' => 'get_current_time',
                    'arguments' => ['format' => 'Y-m-d H:i:s']
                ],
                'result' => $currentTime
            ];
            $response = "The current time is {$currentTime}";
        } else {
            $response = "I can help with time-related questions, but the time tool is not enabled.";
        }
    } elseif (stripos($message, 'hello') !== false || stripos($message, 'hi') !== false) {
        $response = "Hello! I'm a demo version of the B13 Ollama Chat interface. The Ollama server is not currently running, but I can still demonstrate the interface functionality.";
    } else {
        $response = "This is a mock response since Ollama is not available. Your message was: \"{$message}\". In a real scenario, this would be processed by the AI model with access to the enabled tools.";
    }
    
    // Mock conversation stats
    $stats = [
        'total_messages' => 2,
        'user_messages' => 1,
        'assistant_messages' => 1,
        'tool_messages' => count($toolCalls)
    ];
    
    return [
        'message' => $response,
        'model' => 'mock-demo',
        'tool_calls' => $toolCalls,
        'stats' => $stats
    ];
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST requests are allowed', 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendError('Invalid JSON input');
}

// Handle clear action
if (isset($input['action']) && $input['action'] === 'clear') {
    $_SESSION['conversations'] = [];
    sendResponse(['success' => true, 'message' => 'Conversations cleared']);
}

// Validate required fields
if (!isset($input['message']) || !isset($input['conversation_id'])) {
    sendError('Missing required fields: message, conversation_id');
}

$message = trim($input['message']);
$conversationId = $input['conversation_id'];
$model = $input['model'] ?? 'llama3.2';
$enabledTools = $input['tools'] ?? [];

if (empty($message)) {
    sendError('Message cannot be empty');
}

try {
    // Check if this is a demo/mock mode (when Ollama is not available)
    $mockMode = !checkOllamaConnection();
    
    if ($mockMode) {
        // Mock response for demonstration
        $responseData = handleMockRequest($message, $enabledTools);
        sendResponse($responseData);
        return;
    }
    
    // Get or create chat instance
    $chat = getChat($conversationId, $model);
    
    // Register enabled tools
    registerTools($chat, $enabledTools);
    
    // Send message and get response
    $response = $chat->send($message);
    
    // Store conversation history
    if (!isset($_SESSION['conversations'][$conversationId])) {
        $_SESSION['conversations'][$conversationId] = [];
    }
    $_SESSION['conversations'][$conversationId][] = [
        'role' => 'user',
        'content' => $message,
        'timestamp' => time()
    ];
    $_SESSION['conversations'][$conversationId][] = [
        'role' => 'assistant', 
        'content' => $response->getMessage(),
        'timestamp' => time()
    ];
    
    // Prepare response data
    $responseData = [
        'message' => $response->getMessage(),
        'model' => $response->getModel(),
        'stats' => $chat->getConversationStats()
    ];
    
    // Add tool calls if any
    $toolCalls = $response->getToolCalls();
    if (!empty($toolCalls)) {
        $responseData['tool_calls'] = [];
        foreach ($toolCalls as $toolCall) {
            $responseData['tool_calls'][] = [
                'id' => $toolCall['id'] ?? uniqid(),
                'type' => 'function',
                'function' => [
                    'name' => $toolCall['function']['name'],
                    'arguments' => $toolCall['function']['arguments']
                ],
                'result' => $toolCall['result'] ?? 'No result'
            ];
        }
    }
    
    sendResponse($responseData);
    
} catch (Exception $e) {
    error_log("Chat API Error: " . $e->getMessage());
    
    // If Ollama connection fails, fall back to mock mode
    if (strpos($e->getMessage(), 'Failed to connect') !== false) {
        $responseData = handleMockRequest($message, $enabledTools);
        $responseData['message'] = "⚠️ Ollama server not available. Mock response: " . $responseData['message'];
        sendResponse($responseData);
    } else {
        sendError('Failed to process message: ' . $e->getMessage(), 500);
    }
}