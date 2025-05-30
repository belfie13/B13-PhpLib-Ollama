<?php

namespace B13\Ollama;

require_once 'Message.php';
require_once 'ChatMessages.php';
require_once 'ChatResponse.php';
require_once 'ModelOptions.php';
require_once 'ToolRegistry.php';

use RuntimeException;

/**
 * Main Chat class for interacting with Ollama's chat API
 */
class Chat
{
    private string $baseUrl;
    private ChatMessages $messages;
    private ?ModelOptions $options = null;
    private ?string $format = null;
    private bool $stream = false;
    private string $keepAlive = '5m';
    private ?ToolRegistry $toolRegistry = null;
    private bool $autoExecuteTools = true;

    /**
     * @param string $model The model name to use
     * @param string $baseUrl The Ollama API base URL
     */
    public function __construct(
        public readonly string $model,
        string $baseUrl = 'http://localhost:11434'
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->messages = new ChatMessages();
    }

    /**
     * Set model options/parameters
     */
    public function setOptions(ModelOptions $options): self
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Set response format (e.g., 'json' or a JSON schema)
     */
    public function setFormat(?string $format): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * Enable or disable streaming
     */
    public function setStream(bool $stream): self
    {
        $this->stream = $stream;
        return $this;
    }

    /**
     * Set how long the model stays in memory
     */
    public function setKeepAlive(string $keepAlive): self
    {
        $this->keepAlive = $keepAlive;
        return $this;
    }

    /**
     * Get the messages collection
     */
    public function getMessages(): ChatMessages
    {
        return $this->messages;
    }

    /**
     * Add a system message
     */
    public function system(string $content): self
    {
        $this->messages->addSystem($content);
        return $this;
    }

    /**
     * Add a user message
     */
    public function user(string $content, array $images = []): self
    {
        $this->messages->addUser($content, $images);
        return $this;
    }

    /**
     * Add an assistant message
     */
    public function assistant(string $content, array $tool_calls = []): self
    {
        $this->messages->addAssistant($content, $tool_calls);
        return $this;
    }

    /**
     * Send a message and get a response
     */
    public function send(string $message, array $images = []): ChatResponse
    {
        $this->user($message, $images);
        return $this->chat();
    }

    /**
     * Send the current conversation to Ollama and get a response
     */
    public function chat(): ChatResponse
    {
        $payload = $this->buildPayload();
        $response = $this->makeRequest('/api/chat', $payload);
        
        $chatResponse = ChatResponse::fromArray($response);
        
        // Add the assistant's response to our message history
        $this->messages->append($chatResponse->message);
        
        return $chatResponse;
    }

    /**
     * Clear the conversation history
     */
    public function clear(): self
    {
        $this->messages->clear();
        return $this;
    }

    /**
     * Load messages from an array
     */
    public function loadMessages(array $messages): self
    {
        $this->messages->clear();
        foreach ($messages as $messageData) {
            $message = new Message(
                $messageData['role'],
                $messageData['content'],
                $messageData['images'] ?? [],
                $messageData['tool_calls'] ?? []
            );
            $this->messages->append($message);
        }
        return $this;
    }

    /**
     * Summarize the conversation to compress history while preserving important details
     * 
     * @param int $keepRecentMessages Number of recent messages to keep unchanged
     * @param string $summaryPrompt Custom prompt for summarization
     * @return self
     */
    public function summarizeConversation(
        int $keepRecentMessages = 3,
        string $summaryPrompt = "Summarize the following conversation, preserving all important details, decisions made, and context that would be needed to continue the conversation naturally:"
    ): self {
        $messageCount = count($this->messages);
        
        // If we don't have enough messages to warrant summarization, return as-is
        if ($messageCount <= $keepRecentMessages + 2) {
            return $this;
        }

        // Extract system message (if any)
        $systemMessage = null;
        $startIndex = 0;
        if ($messageCount > 0 && $this->messages[0]->role === Message::ROLE_SYSTEM) {
            $systemMessage = $this->messages[0];
            $startIndex = 1;
        }

        // Calculate how many messages to summarize
        $messagesToSummarize = $messageCount - $keepRecentMessages - $startIndex;
        
        if ($messagesToSummarize <= 0) {
            return $this;
        }

        // Get messages to summarize
        $messagesToSummarizeArray = [];
        for ($i = $startIndex; $i < $startIndex + $messagesToSummarize; $i++) {
            $messagesToSummarizeArray[] = $this->messages[$i];
        }

        // Get recent messages to keep
        $recentMessages = [];
        for ($i = $messageCount - $keepRecentMessages; $i < $messageCount; $i++) {
            $recentMessages[] = $this->messages[$i];
        }

        // Create conversation text for summarization
        $conversationText = $this->formatMessagesForSummary($messagesToSummarizeArray);

        // Create a temporary chat instance for summarization
        $summaryChat = new Chat($this->model, $this->baseUrl);
        if ($this->options) {
            $summaryChat->setOptions($this->options);
        }

        // Generate summary
        $summaryResponse = $summaryChat->send($summaryPrompt . "\n\n" . $conversationText);
        $summary = $summaryResponse->getContent();

        // Rebuild the conversation with summary
        $newMessages = new ChatMessages();
        
        // Add system message if it existed
        if ($systemMessage) {
            $newMessages->add($systemMessage);
        }

        // Add summary as a system message
        $newMessages->add(Message::system("Previous conversation summary: " . $summary));

        // Add recent messages
        foreach ($recentMessages as $message) {
            $newMessages->add($message);
        }

        $this->messages = $newMessages;
        return $this;
    }

    /**
     * Format messages for summarization
     */
    private function formatMessagesForSummary(array $messages): string
    {
        $formatted = [];
        foreach ($messages as $message) {
            $role = ucfirst($message->role);
            $content = $message->content;
            
            // Truncate very long messages
            if (strlen($content) > 1000) {
                $content = substr($content, 0, 1000) . "... [truncated]";
            }
            
            $formatted[] = "{$role}: {$content}";
        }
        return implode("\n\n", $formatted);
    }

    /**
     * Get conversation statistics
     */
    public function getConversationStats(): array
    {
        $stats = [
            'total_messages' => count($this->messages),
            'by_role' => [],
            'total_characters' => 0,
            'estimated_tokens' => 0
        ];

        foreach ($this->messages as $message) {
            $role = $message->role;
            if (!isset($stats['by_role'][$role])) {
                $stats['by_role'][$role] = 0;
            }
            $stats['by_role'][$role]++;
            
            $contentLength = strlen($message->content);
            $stats['total_characters'] += $contentLength;
            
            // Rough token estimation (1 token ≈ 4 characters for English)
            $stats['estimated_tokens'] += intval($contentLength / 4);
        }

        // Add convenience accessors for common roles
        $stats['system_messages'] = $stats['by_role']['system'] ?? 0;
        $stats['user_messages'] = $stats['by_role']['user'] ?? 0;
        $stats['assistant_messages'] = $stats['by_role']['assistant'] ?? 0;
        $stats['tool_messages'] = $stats['by_role']['tool'] ?? 0;

        return $stats;
    }

    /**
     * Auto-summarize if conversation exceeds token limit
     * 
     * @param int $maxTokens Maximum estimated tokens before auto-summarization
     * @param int $keepRecentMessages Number of recent messages to preserve
     * @return bool True if summarization was performed
     */
    public function autoSummarizeIfNeeded(int $maxTokens = 4000, int $keepRecentMessages = 3): bool
    {
        $stats = $this->getConversationStats();
        
        if ($stats['estimated_tokens'] > $maxTokens) {
            $this->summarizeConversation($keepRecentMessages);
            return true;
        }
        
        return false;
    }

    /**
     * Set the tool registry for function calling
     */
    public function setToolRegistry(ToolRegistry $registry): self
    {
        $this->toolRegistry = $registry;
        return $this;
    }

    /**
     * Get the current tool registry
     */
    public function getToolRegistry(): ?ToolRegistry
    {
        return $this->toolRegistry;
    }

    /**
     * Register a tool/function
     */
    public function registerTool(Tool $tool): self
    {
        if (!$this->toolRegistry) {
            $this->toolRegistry = new ToolRegistry();
        }
        $this->toolRegistry->register($tool);
        return $this;
    }

    /**
     * Register a simple function as a tool
     */
    public function registerFunction(string $name, string $description, array $parameters, callable $function): self
    {
        if (!$this->toolRegistry) {
            $this->toolRegistry = new ToolRegistry();
        }
        $this->toolRegistry->registerFunction($name, $description, $parameters, $function);
        return $this;
    }

    /**
     * Enable or disable automatic tool execution
     */
    public function setAutoExecuteTools(bool $autoExecute): self
    {
        $this->autoExecuteTools = $autoExecute;
        return $this;
    }

    /**
     * Check if auto tool execution is enabled
     */
    public function isAutoExecuteToolsEnabled(): bool
    {
        return $this->autoExecuteTools;
    }

    /**
     * Execute a tool call manually
     */
    public function executeTool(string $toolName, array $arguments): mixed
    {
        if (!$this->toolRegistry) {
            throw new RuntimeException("No tool registry configured");
        }

        return $this->toolRegistry->execute($toolName, $arguments);
    }

    /**
     * Send a message with tool support
     */
    public function sendWithTools(string $message, array $images = []): ChatResponse
    {
        $this->user($message, $images);
        
        $maxIterations = 10; // Prevent infinite loops
        $iteration = 0;
        
        while ($iteration < $maxIterations) {
            $response = $this->chat();
            
            // Check if the response contains tool calls
            if ($this->hasToolCalls($response)) {
                $toolCalls = $this->extractToolCalls($response);
                
                if ($this->autoExecuteTools && $this->toolRegistry) {
                    // Execute tools and add results to conversation
                    foreach ($toolCalls as $toolCall) {
                        try {
                            $result = $this->toolRegistry->execute(
                                $toolCall['function']['name'],
                                $toolCall['function']['arguments']
                            );
                            
                            // Add tool result as a tool message
                            $this->tool($toolCall['id'], json_encode($result));
                            
                        } catch (\Exception $e) {
                            // Add error as tool result
                            $this->tool($toolCall['id'], "Error: " . $e->getMessage());
                        }
                    }
                    
                    // Continue the conversation to get the final response
                    $iteration++;
                    continue;
                } else {
                    // Return response with tool calls for manual handling
                    return $response;
                }
            }
            
            // No tool calls, return the response
            return $response;
        }
        
        throw new RuntimeException("Maximum tool execution iterations reached");
    }

    /**
     * Add a tool message to the conversation
     */
    public function tool(string $toolCallId, string $content): self
    {
        $this->messages->append(Message::tool($toolCallId, $content));
        return $this;
    }

    /**
     * Check if response contains tool calls
     */
    private function hasToolCalls(ChatResponse $response): bool
    {
        $message = $response->getMessage();
        return !empty($message->tool_calls);
    }

    /**
     * Extract tool calls from response
     */
    private function extractToolCalls(ChatResponse $response): array
    {
        $message = $response->getMessage();
        return $message->tool_calls ?? [];
    }

    /**
     * Export messages to an array
     */
    public function exportMessages(): array
    {
        return $this->messages->toArray();
    }

    /**
     * Build the request payload
     */
    private function buildPayload(): array
    {
        $payload = [
            'model' => $this->model,
            'messages' => $this->messages->toArray(),
            'stream' => $this->stream,
            'keep_alive' => $this->keepAlive
        ];

        if ($this->format !== null) {
            $payload['format'] = $this->format;
        }

        if ($this->options !== null) {
            $payload['options'] = $this->options->toArray();
        }

        // Add tools if available
        if ($this->toolRegistry && $this->toolRegistry->count() > 0) {
            $payload['tools'] = $this->toolRegistry->getToolDefinitions();
        }

        return $payload;
    }

    /**
     * Make an HTTP request to the Ollama API
     */
    private function makeRequest(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . $endpoint;
        $jsonPayload = json_encode($payload);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonPayload)
                ],
                'content' => $jsonPayload
            ]
        ]);

        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new RuntimeException("Failed to connect to Ollama API at $url");
        }

        $decodedResponse = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException("Invalid JSON response from Ollama API: " . json_last_error_msg());
        }

        if (isset($decodedResponse['error'])) {
            throw new RuntimeException("Ollama API error: " . $decodedResponse['error']);
        }

        return $decodedResponse;
    }

    /**
     * Create a new Chat instance with a system prompt
     */
    public static function withSystem(string $model, string $systemPrompt, string $baseUrl = 'http://localhost:11434'): self
    {
        $chat = new self($model, $baseUrl);
        $chat->system($systemPrompt);
        return $chat;
    }

    /**
     * Quick method to send a single message without maintaining conversation history
     */
    public static function quick(string $model, string $message, string $baseUrl = 'http://localhost:11434'): ChatResponse
    {
        $chat = new self($model, $baseUrl);
        return $chat->send($message);
    }
}