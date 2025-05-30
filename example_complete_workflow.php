<?php

require_once 'vendor/autoload.php';

use B13\Ollama\Chat;
use B13\Ollama\Tool;
use B13\Ollama\ToolRegistry;

echo "=== Complete Tool/Function Calling Workflow ===\n\n";

// Step 1: Create Chat and Tool Registry
echo "Step 1: Setting up Chat and Tools\n";
echo str_repeat("-", 40) . "\n";

$chat = new Chat('llama3.2');
$chat->system('You are a helpful assistant with access to various tools. Use them when appropriate to help users.');

$registry = new ToolRegistry();

// Register common tools
$registry->registerCommonTools();

// Register custom business logic tools
$registry->registerFunction(
    'get_user_info',
    'Get user information from database (simulated)',
    [
        'user_id' => ['type' => 'integer', 'description' => 'User ID to lookup']
    ],
    function(int $user_id): array {
        // Simulate database lookup
        $users = [
            1 => ['name' => 'John Doe', 'email' => 'john@example.com', 'role' => 'admin'],
            2 => ['name' => 'Jane Smith', 'email' => 'jane@example.com', 'role' => 'user'],
            3 => ['name' => 'Bob Wilson', 'email' => 'bob@example.com', 'role' => 'moderator']
        ];
        
        return $users[$user_id] ?? ['error' => 'User not found'];
    }
);

$registry->registerFunction(
    'send_email',
    'Send an email (simulated)',
    [
        'to' => ['type' => 'string', 'description' => 'Recipient email address'],
        'subject' => ['type' => 'string', 'description' => 'Email subject'],
        'body' => ['type' => 'string', 'description' => 'Email body']
    ],
    function(string $to, string $subject, string $body): array {
        // Simulate email sending
        return [
            'status' => 'sent',
            'message_id' => 'msg_' . uniqid(),
            'to' => $to,
            'subject' => $subject,
            'sent_at' => date('Y-m-d H:i:s')
        ];
    }
);

$registry->registerFunction(
    'log_activity',
    'Log user activity (simulated)',
    [
        'user_id' => ['type' => 'integer', 'description' => 'User ID'],
        'action' => ['type' => 'string', 'description' => 'Action performed'],
        'details' => ['type' => 'string', 'required' => false, 'description' => 'Additional details']
    ],
    function(int $user_id, string $action, string $details = ''): array {
        return [
            'log_id' => uniqid(),
            'user_id' => $user_id,
            'action' => $action,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s'),
            'ip_address' => '192.168.1.100'
        ];
    }
);

$chat->setToolRegistry($registry);

echo "✓ Chat instance created with model: llama3.2\n";
echo "✓ Tool registry created with " . $registry->count() . " tools\n";
echo "✓ Available tools:\n";
foreach ($registry->getAll() as $name => $tool) {
    echo "  - $name: {$tool->description}\n";
}

// Step 2: Simulate a complex user request
echo "\n" . str_repeat("=", 50) . "\n";
echo "Step 2: Simulating Complex User Request\n";
echo str_repeat("-", 40) . "\n";

$userRequest = "I need to send a welcome email to user ID 2. Please get their information first, then send them a welcome email, and log this activity.";

echo "User Request: $userRequest\n\n";

// Step 3: Manual tool execution workflow (simulating what AI would do)
echo "Step 3: Tool Execution Workflow\n";
echo str_repeat("-", 40) . "\n";

$chat->user($userRequest);

echo "1. Getting user information...\n";
try {
    $userInfo = $chat->executeTool('get_user_info', ['user_id' => 2]);
    echo "   User info retrieved: " . json_encode($userInfo, JSON_PRETTY_PRINT) . "\n";
    
    // Add tool result to conversation
    $chat->tool('call_user_info_1', json_encode($userInfo));
    
    if (isset($userInfo['error'])) {
        echo "   ❌ User not found, stopping workflow\n";
        exit;
    }
    
    echo "   ✓ User found: {$userInfo['name']} ({$userInfo['email']})\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Error getting user info: " . $e->getMessage() . "\n";
    exit;
}

echo "2. Sending welcome email...\n";
try {
    $emailResult = $chat->executeTool('send_email', [
        'to' => $userInfo['email'],
        'subject' => 'Welcome to our platform!',
        'body' => "Hello {$userInfo['name']},\n\nWelcome to our platform! We're excited to have you on board.\n\nBest regards,\nThe Team"
    ]);
    echo "   Email result: " . json_encode($emailResult, JSON_PRETTY_PRINT) . "\n";
    
    // Add tool result to conversation
    $chat->tool('call_email_1', json_encode($emailResult));
    
    echo "   ✓ Email sent successfully (ID: {$emailResult['message_id']})\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Error sending email: " . $e->getMessage() . "\n";
    exit;
}

echo "3. Logging activity...\n";
try {
    $logResult = $chat->executeTool('log_activity', [
        'user_id' => 2,
        'action' => 'welcome_email_sent',
        'details' => "Welcome email sent to {$userInfo['email']} (Message ID: {$emailResult['message_id']})"
    ]);
    echo "   Log result: " . json_encode($logResult, JSON_PRETTY_PRINT) . "\n";
    
    // Add tool result to conversation
    $chat->tool('call_log_1', json_encode($logResult));
    
    echo "   ✓ Activity logged (Log ID: {$logResult['log_id']})\n\n";
    
} catch (Exception $e) {
    echo "   ❌ Error logging activity: " . $e->getMessage() . "\n";
}

// Step 4: Generate final response
echo "Step 4: Generating Final Response\n";
echo str_repeat("-", 40) . "\n";

$finalResponse = "I've successfully completed your request:\n\n" .
    "1. ✓ Retrieved information for user ID 2: {$userInfo['name']} ({$userInfo['email']})\n" .
    "2. ✓ Sent welcome email with subject 'Welcome to our platform!' (Message ID: {$emailResult['message_id']})\n" .
    "3. ✓ Logged the activity (Log ID: {$logResult['log_id']})\n\n" .
    "The welcome email has been delivered to {$userInfo['email']} and all actions have been properly logged.";

$chat->assistant($finalResponse);

echo "AI Response:\n";
echo $finalResponse . "\n\n";

// Step 5: Show conversation summary
echo "Step 5: Conversation Summary\n";
echo str_repeat("-", 40) . "\n";

$stats = $chat->getConversationStats();
echo "Conversation Statistics:\n";
echo "- Total messages: {$stats['total_messages']}\n";
echo "- System messages: {$stats['system_messages']}\n";
echo "- User messages: {$stats['user_messages']}\n";
echo "- Assistant messages: {$stats['assistant_messages']}\n";
echo "- Tool messages: {$stats['tool_messages']}\n";
echo "- Total characters: {$stats['total_characters']}\n";
echo "- Estimated tokens: {$stats['estimated_tokens']}\n\n";

echo "Message Flow:\n";
$messages = $chat->exportMessages();
foreach ($messages as $i => $message) {
    $role = ucfirst($message['role']);
    $preview = substr($message['content'], 0, 60) . (strlen($message['content']) > 60 ? '...' : '');
    echo sprintf("%2d. [%-9s] %s\n", $i + 1, $role, $preview);
    if (isset($message['tool_call_id'])) {
        echo "    └─ Tool Call ID: {$message['tool_call_id']}\n";
    }
}

// Step 6: Demonstrate additional features
echo "\n" . str_repeat("=", 50) . "\n";
echo "Step 6: Additional Features Demo\n";
echo str_repeat("-", 40) . "\n";

echo "1. Tool validation:\n";
try {
    $chat->executeTool('get_user_info', []); // Missing required parameter
} catch (Exception $e) {
    echo "   ✓ Validation caught missing parameter: " . $e->getMessage() . "\n";
}

echo "\n2. Error handling:\n";
try {
    $chat->executeTool('nonexistent_tool', []);
} catch (Exception $e) {
    echo "   ✓ Error caught for nonexistent tool: " . $e->getMessage() . "\n";
}

echo "\n3. Tool registry management:\n";
echo "   - Total tools: " . $registry->count() . "\n";
echo "   - Has 'calculate' tool: " . ($registry->has('calculate') ? 'Yes' : 'No') . "\n";
echo "   - Has 'send_email' tool: " . ($registry->has('send_email') ? 'Yes' : 'No') . "\n";

echo "\n4. Auto-execute settings:\n";
echo "   - Auto-execute enabled: " . ($chat->isAutoExecuteToolsEnabled() ? 'Yes' : 'No') . "\n";

$chat->setAutoExecuteTools(false);
echo "   - Auto-execute after disable: " . ($chat->isAutoExecuteToolsEnabled() ? 'Yes' : 'No') . "\n";

$chat->setAutoExecuteTools(true);
echo "   - Auto-execute after enable: " . ($chat->isAutoExecuteToolsEnabled() ? 'Yes' : 'No') . "\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "Workflow Complete!\n\n";

echo "This example demonstrated:\n";
echo "✓ Setting up Chat with tool registry\n";
echo "✓ Registering custom business logic tools\n";
echo "✓ Complex multi-step tool execution workflow\n";
echo "✓ Proper tool message handling with call IDs\n";
echo "✓ Error handling and validation\n";
echo "✓ Conversation statistics and message flow\n";
echo "✓ Tool registry management features\n";
echo "✓ Auto-execute configuration\n\n";

echo "To use with real AI models:\n";
echo "1. Start Ollama: ollama serve\n";
echo "2. Use a model that supports function calling (e.g., llama3.2)\n";
echo "3. Replace manual tool execution with \$chat->sendWithTools()\n";
echo "4. The AI will automatically detect when to call tools and execute them\n";