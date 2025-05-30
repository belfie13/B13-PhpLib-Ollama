# B13-PhpLib-Ollama
A PHP based Ollama interaction library.

## Features
- ✅ Chat class for interactive conversations with Ollama models
- ✅ Message class for individual chat messages
- ✅ ChatMessages class for managing conversation history
- ✅ ModelOptions class for configuring model parameters
- ✅ Support for system prompts, user messages, and assistant responses
- ✅ Conversation export/import functionality
- ✅ Performance metrics and token counting
- ✅ PSR-4 autoloading support
- ✅ Composer package ready

## Installation

### Via Composer (Recommended)
```bash
composer require belfie13/b13-phplib-ollama
```

### Manual Installation
1. Clone or download this repository
2. Include the Chat class in your project:
```php
require_once 'src/Chat.php';
```

## Requirements
- PHP 8.1 or higher
- Ollama server running locally or remotely
- cURL extension (for HTTP requests)

## Quick Start

```php
require_once 'src/Chat.php';

use B13\Ollama\Chat;

// Simple one-off message
$response = Chat::quick('llama3.2', 'Hello, how are you?');
echo $response->getContent();

// Conversation with system prompt
$chat = Chat::withSystem('llama3.2', 'You are a helpful assistant.');
$response = $chat->send('What is PHP?');
echo $response->getContent();
```

## Usage Examples

### Basic Chat
```php
use B13\Ollama\Chat;

$chat = new Chat('llama3.2');
$response = $chat->send('Tell me a joke');
echo $response->getContent();
```

### With Model Options
```php
use B13\Ollama\Chat;
use B13\Ollama\ModelOptions;

$options = new ModelOptions();
$options->temperature = 0.7;
$options->top_p = 0.9;

$chat = new Chat('llama3.2');
$chat->setOptions($options);
$response = $chat->send('Write a creative story');
```

### Building Conversations Manually
```php
use B13\Ollama\Chat;

$chat = new Chat('llama3.2');
$chat->system('You are a helpful coding assistant.')
     ->user('What is PHP?')
     ->assistant('PHP is a server-side scripting language...')
     ->user('Show me an example');

$response = $chat->chat();
```

### Export/Import Conversations
```php
use B13\Ollama\Chat;

// Export conversation
$messages = $chat->exportMessages();
file_put_contents('conversation.json', json_encode($messages));

// Import conversation
$messages = json_decode(file_get_contents('conversation.json'), true);
$newChat = new Chat('llama3.2');
$newChat->loadMessages($messages);
```

### Performance Metrics
```php
$response = $chat->send('Hello');
echo "Tokens per second: " . $response->getTokensPerSecond() . "\n";
print_r($response->getMetrics());
```

### Conversation Summarization
Manage long conversations by summarizing older messages while preserving recent context:

```php
use B13\Ollama\Chat;

$chat = new Chat('llama3.2');
// ... build a long conversation ...

// Get conversation statistics
$stats = $chat->getConversationStats();
echo "Total messages: " . $stats['total_messages'] . "\n";
echo "Estimated tokens: " . $stats['estimated_tokens'] . "\n";

// Manual summarization - keep last 3 messages, summarize the rest
$chat->summarizeConversation(3);

// Auto-summarization when conversation gets too long
$wasSummarized = $chat->autoSummarizeIfNeeded(4000); // 4000 token limit

// Custom summarization prompt
$chat->summarizeConversation(
    2, 
    'Create a brief technical summary focusing on key decisions and context:'
);
```

## API Classes

### Chat
The main class for interacting with Ollama. Provides methods for:
- `send(string $message, array $images = [])` - Send a message and get response
- `system(string $content)` - Add system message
- `user(string $content, array $images = [])` - Add user message
- `assistant(string $content, array $tool_calls = [])` - Add assistant message
- `setOptions(ModelOptions $options)` - Configure model parameters
- `setFormat(?string $format)` - Set response format (e.g., 'json')
- `exportMessages()` / `loadMessages(array $messages)` - Save/load conversations
- `summarizeConversation(int $keepRecent, string $prompt)` - Summarize conversation history
- `autoSummarizeIfNeeded(int $maxTokens, int $keepRecent)` - Auto-summarize when needed
- `getConversationStats()` - Get conversation statistics and token estimates

### Message
Represents individual chat messages with roles: system, user, assistant, or tool.

### ChatMessages
Collection class for managing conversation history. Implements `Countable`, `ArrayAccess`, and `IteratorAggregate`.

### ChatResponse
Response object containing the model's reply and performance metrics.

### ModelOptions
Configuration class for model parameters like temperature, top_p, etc.

## TODO
- [ ] Look at what software licence to put this under.
- [x] create a Message class and possibly Request/Response classes or Generate/Chat Requests
- [x] create a MessageList class to hold a line of chat messages
- [x] create a configuration class to setup model/parameters
- [ ] write out a workflow
- [ ] Add streaming support
- [ ] Add tool/function calling support
- [ ] Add image support for multimodal models

## Overview
- all stream parameters should be set to false to receive the whole
- [ ] investigate if we can yield (return from generator functions) each token in a stream (useful for cancelling model response early if it's going off track)
- [ ] can AJAX be used to get a stream of responses from a php script?
- [ ] update B13\Ds\PriorityQueue to include inserting new priorities (eg, we have lists for each priority but we want to insert a new list at a priority)

Setup: model (name, parameters) and system
Chat: send a chat message to a model 
- add a chat message to a sequence
- add a chat response to a sequence
- save/load chat message sequence with model configuration

## Aux tasks

```php
use Countable;
use ArrayAccess;
class ChatMessages implements Countable, ArrayAccess
 {
    public const KEY_ROLE = 'role';
    public const KEY_CONTENT = 'content';
    private iterable $messages = [];
    public function append(string $role, string $content)
     {
        # add any sanitization here
        $this->messages[] = [self::KEY_ROLE => $role, self::KEY_CONTENT => $content];
     }
    public function getAll(): iterable
     {
        return $this->messages;
     }
    public function count(): int
     {
        return count($this->messages);
     }
 }
```

## Tool/Function Calling

The library supports tool/function calling, allowing AI models to execute PHP functions during conversations.

### Basic Tool Usage

```php
use B13\Ollama\Chat;
use B13\Ollama\ToolRegistry;

$chat = new Chat('llama3.2');
$registry = new ToolRegistry();

// Register common tools
$registry->registerCommonTools();

// Register custom function
$registry->registerFunction(
    'get_weather',
    'Get weather information',
    ['city' => 'string'],
    function(string $city): array {
        return ['city' => $city, 'temp' => '22°C', 'condition' => 'sunny'];
    }
);

$chat->setToolRegistry($registry);

// Manual tool execution
$result = $chat->executeTool('get_weather', ['city' => 'Paris']);

// Send message with automatic tool execution
$response = $chat->sendWithTools('What\'s the weather in Paris?');
```

### Tool Creation

```php
use B13\Ollama\Tool;

// Create a tool manually
$mathTool = Tool::create(
    'calculate',
    'Perform mathematical calculations',
    [
        'expression' => ['type' => 'string', 'description' => 'Math expression']
    ],
    function(string $expression): float {
        return eval("return $expression;");
    }
);

// Register with registry
$registry->register($mathTool);
```

### Tool Registry

```php
$registry = new ToolRegistry();

// Register common tools (calculate, get_current_time, read_file, string_length)
$registry->registerCommonTools();

// Register custom function
$registry->registerFunction(
    'create_todo',
    'Create a todo item',
    [
        'task' => ['type' => 'string', 'description' => 'Task description'],
        'priority' => ['type' => 'string', 'required' => false]
    ],
    function(string $task, string $priority = 'medium'): array {
        return [
            'id' => uniqid(),
            'task' => $task,
            'priority' => $priority,
            'created_at' => date('Y-m-d H:i:s')
        ];
    }
);

// Check available tools
echo "Tools: " . $registry->count();
echo "Has calculate: " . ($registry->has('calculate') ? 'Yes' : 'No');
```

### Tool Messages

```php
// Add tool result to conversation
$chat->tool('call_123', 'Tool execution result');

// Tool messages include call ID for proper conversation flow
$messages = $chat->exportMessages();
// Last message will have role='tool' and tool_call_id='call_123'
```

### Auto-execution

```php
// Enable/disable automatic tool execution
$chat->setAutoExecuteTools(true);  // Default: true

// Send message with tools - AI can call tools automatically
$response = $chat->sendWithTools('Calculate 15 * 8 and get current time');
```

## Predefined versions

v0.1.0
configure a model, parameters, system
Model: Ds\KeyMap (string => value)
- dynamic properties
- predefined properties
  - model name
  - system
  - num_ctx
  - ...

v0.2.0
generate: send a generate request message and output the response without streaming.

v0.2.0
chat: save a chat to a message
