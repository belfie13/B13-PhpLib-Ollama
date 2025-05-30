<?php

require_once 'vendor/autoload.php';

use B13\Ollama\Chat;
use B13\Ollama\Tool;
use B13\Ollama\ToolRegistry;

echo "=== Tool/Function Calling Example ===\n\n";

// Create a chat instance
$chat = new Chat('llama3.2');
$chat->system('You are a helpful assistant with access to various tools. Use them when needed to help the user.');

// Create a tool registry and register some useful functions
$registry = new ToolRegistry();

// Register common tools
$registry->registerCommonTools();

// Register custom tools
$registry->registerFunction(
    'weather_info',
    'Get weather information for a city (simulated)',
    [
        'city' => ['type' => 'string', 'description' => 'City name'],
        'units' => ['type' => 'string', 'required' => false, 'description' => 'Temperature units (celsius/fahrenheit)']
    ],
    function(string $city, string $units = 'celsius'): array {
        // Simulate weather API call
        $temperatures = ['celsius' => rand(15, 30), 'fahrenheit' => rand(59, 86)];
        $conditions = ['sunny', 'cloudy', 'rainy', 'partly cloudy'];
        
        return [
            'city' => $city,
            'temperature' => $temperatures[$units],
            'units' => $units,
            'condition' => $conditions[array_rand($conditions)],
            'humidity' => rand(30, 80) . '%'
        ];
    }
);

$registry->registerFunction(
    'create_todo',
    'Create a new todo item',
    [
        'task' => ['type' => 'string', 'description' => 'Task description'],
        'priority' => ['type' => 'string', 'required' => false, 'description' => 'Priority level (low/medium/high)'],
        'due_date' => ['type' => 'string', 'required' => false, 'description' => 'Due date in YYYY-MM-DD format']
    ],
    function(string $task, string $priority = 'medium', ?string $due_date = null): array {
        $todo = [
            'id' => uniqid(),
            'task' => $task,
            'priority' => $priority,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        if ($due_date) {
            $todo['due_date'] = $due_date;
        }
        
        return $todo;
    }
);

$registry->registerFunction(
    'search_web',
    'Search the web for information (simulated)',
    [
        'query' => ['type' => 'string', 'description' => 'Search query'],
        'limit' => ['type' => 'integer', 'required' => false, 'description' => 'Number of results to return']
    ],
    function(string $query, int $limit = 3): array {
        // Simulate web search results
        $results = [];
        for ($i = 1; $i <= $limit; $i++) {
            $results[] = [
                'title' => "Search result $i for: $query",
                'url' => "https://example.com/result-$i",
                'snippet' => "This is a simulated search result snippet for '$query'. It contains relevant information about the topic."
            ];
        }
        return $results;
    }
);

// Set the tool registry
$chat->setToolRegistry($registry);

echo "Registered tools:\n";
foreach ($registry->getAll() as $name => $tool) {
    echo "- $name: {$tool->description}\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Example 1: Basic tool usage
echo "Example 1: Manual tool execution\n";
echo str_repeat("-", 30) . "\n";

try {
    echo "Current time: " . $chat->executeTool('get_current_time', []) . "\n";
    echo "String length of 'Hello World': " . $chat->executeTool('string_length', ['text' => 'Hello World']) . "\n";
    
    $weather = $chat->executeTool('weather_info', ['city' => 'Paris', 'units' => 'celsius']);
    echo "Weather in Paris: " . json_encode($weather, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Example 2: Simulated conversation with tools
echo "Example 2: Simulated conversation with tool calls\n";
echo str_repeat("-", 30) . "\n";

// Simulate what would happen if the AI wanted to use tools
echo "User: What's the weather like in London and can you create a todo to check it again tomorrow?\n\n";

// Simulate AI response with tool calls
echo "AI would call tools:\n";
echo "1. weather_info(city='London', units='celsius')\n";
echo "2. create_todo(task='Check London weather', due_date='" . date('Y-m-d', strtotime('+1 day')) . "')\n\n";

// Execute the tools manually to show results
try {
    $weather = $chat->executeTool('weather_info', ['city' => 'London', 'units' => 'celsius']);
    echo "Weather result: " . json_encode($weather, JSON_PRETTY_PRINT) . "\n\n";
    
    $todo = $chat->executeTool('create_todo', [
        'task' => 'Check London weather',
        'due_date' => date('Y-m-d', strtotime('+1 day'))
    ]);
    echo "Todo created: " . json_encode($todo, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "AI response: The weather in London is currently {$weather['temperature']}°C and {$weather['condition']} with {$weather['humidity']} humidity. I've created a todo item (ID: {$todo['id']}) to check the weather again tomorrow.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Example 3: Tool message flow
echo "Example 3: Tool message flow\n";
echo str_repeat("-", 30) . "\n";

// Add user message
$chat->user("Calculate 15 * 8 + 32 and tell me the current time");

// Simulate AI response with tool calls
echo "1. User asks for calculation and time\n";
echo "2. AI would call: calculate(expression='15 * 8 + 32') and get_current_time()\n";

// Add tool results to conversation
$calcResult = $chat->executeTool('calculate', ['expression' => '15 * 8 + 32']);
$timeResult = $chat->executeTool('get_current_time', []);

$chat->tool('call_calc_1', (string)$calcResult);
$chat->tool('call_time_1', $timeResult);

echo "3. Tool results added to conversation\n";
echo "   - Calculation: $calcResult\n";
echo "   - Time: $timeResult\n";

// Simulate final AI response
$chat->assistant("The calculation 15 * 8 + 32 equals $calcResult. The current time is $timeResult.");

echo "4. AI provides final response with tool results\n\n";

// Show conversation history
echo "Conversation messages:\n";
$messages = $chat->exportMessages();
foreach ($messages as $i => $message) {
    $role = ucfirst($message['role']);
    $content = substr($message['content'], 0, 100) . (strlen($message['content']) > 100 ? '...' : '');
    echo "   $i. [$role] $content\n";
    if (isset($message['tool_call_id'])) {
        echo "      (Tool call ID: {$message['tool_call_id']})\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Example 4: Complex tool with validation
echo "Example 4: Complex tool with validation\n";
echo str_repeat("-", 30) . "\n";

try {
    // Valid call
    $searchResults = $chat->executeTool('search_web', ['query' => 'PHP programming', 'limit' => 2]);
    echo "Search results: " . json_encode($searchResults, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test validation
    echo "Testing validation...\n";
    $chat->executeTool('search_web', []); // Should fail - missing required parameter
    
} catch (Exception $e) {
    echo "Validation error (expected): " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Example 5: Tool registry management
echo "Example 5: Tool registry management\n";
echo str_repeat("-", 30) . "\n";

echo "Total tools: " . $registry->count() . "\n";
echo "Has 'calculate' tool: " . ($registry->has('calculate') ? 'Yes' : 'No') . "\n";

// Add a new tool dynamically
$registry->registerFunction(
    'random_number',
    'Generate a random number',
    [
        'min' => ['type' => 'integer', 'required' => false],
        'max' => ['type' => 'integer', 'required' => false]
    ],
    function(int $min = 1, int $max = 100): int {
        return rand($min, $max);
    }
);

echo "Added random_number tool. Total tools now: " . $registry->count() . "\n";
echo "Random number: " . $chat->executeTool('random_number', ['min' => 10, 'max' => 50]) . "\n";

echo "\n" . str_repeat("=", 50) . "\n\n";

echo "Tool/Function calling examples completed!\n";
echo "\nKey features demonstrated:\n";
echo "- Tool creation and registration\n";
echo "- Manual tool execution\n";
echo "- Tool validation\n";
echo "- Complex tools with multiple parameters\n";
echo "- Tool message flow in conversations\n";
echo "- Dynamic tool registry management\n";
echo "- Error handling and validation\n";
echo "\nTo use with actual AI models:\n";
echo "1. Start Ollama: ollama serve\n";
echo "2. Use a model that supports function calling\n";
echo "3. Call \$chat->sendWithTools() instead of manual execution\n";