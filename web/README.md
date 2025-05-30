# B13 Ollama Chat Web Interface

A modern, responsive web interface for the B13 Ollama PHP library featuring tool/function calling capabilities.

## Features

- 🎨 **Modern UI**: Clean, responsive design that works on desktop and mobile
- 🤖 **Multiple Models**: Support for various Ollama models (Llama, Mistral, CodeLlama, etc.)
- 🔧 **Tool Integration**: Built-in tools for calculations, date/time, string operations, and file handling
- 📊 **Real-time Stats**: Live conversation statistics and message counts
- 💬 **Session Management**: Persistent conversations with session storage
- 🚀 **No External Dependencies**: Pure HTML, CSS, and JavaScript - no frameworks required

## Quick Start

1. **Prerequisites**
   - PHP 8.1 or higher
   - Ollama server running locally
   - Composer dependencies installed

2. **Start the Server**
   ```bash
   cd web
   php server.php 8000
   ```

3. **Open in Browser**
   Navigate to `http://localhost:8000`

## File Structure

```
web/
├── index.html          # Main chat interface
├── api.php            # REST API backend
├── server.php         # Development server
├── config.php         # Configuration settings
└── README.md          # This file
```

## API Endpoints

### POST /api.php

Send a chat message with tool support.

**Request:**
```json
{
    "message": "What's 15 * 23?",
    "model": "llama3.2",
    "tools": ["calculate", "time"],
    "conversation_id": "conv_123456"
}
```

**Response:**
```json
{
    "message": "The result is 345.",
    "model": "llama3.2",
    "tool_calls": [
        {
            "id": "call_123",
            "type": "function",
            "function": {
                "name": "calculate",
                "arguments": {"expression": "15 * 23"}
            },
            "result": "345"
        }
    ],
    "stats": {
        "total_messages": 4,
        "user_messages": 2,
        "assistant_messages": 2,
        "tool_messages": 1
    }
}
```

### Clear Conversations

**Request:**
```json
{
    "action": "clear"
}
```

## Available Tools

- **Calculator**: Mathematical expressions and calculations
- **Date/Time**: Current time, date formatting, timezone operations
- **String Utils**: Text manipulation, encoding, formatting
- **File Operations**: File reading, writing, directory operations

## Configuration

Edit `config.php` to customize:

- Ollama server settings
- Available models
- Default enabled tools
- Session configuration
- Security settings

## Development

The interface uses vanilla JavaScript with a class-based architecture:

- `ChatInterface`: Main application class
- Session-based conversation management
- Real-time UI updates
- Error handling and loading states

## Browser Compatibility

- Chrome/Edge 88+
- Firefox 85+
- Safari 14+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Security Features

- Input validation and sanitization
- Session-based conversation isolation
- Rate limiting capabilities
- XSS protection
- CSRF token support (configurable)

## Troubleshooting

**Common Issues:**

1. **"Failed to send message"**
   - Check if Ollama server is running
   - Verify model is available: `ollama list`
   - Check PHP error logs

2. **Tools not working**
   - Ensure tools are enabled in the sidebar
   - Check if model supports function calling
   - Verify tool registration in API

3. **Session issues**
   - Clear browser cookies/localStorage
   - Restart PHP session
   - Check session configuration

## Customization

### Adding Custom Tools

1. Create tool in `ToolRegistry`
2. Add to `api.php` tool registration
3. Add checkbox to `index.html` sidebar
4. Update JavaScript tool handling

### Styling

All styles are in `index.html` `<style>` section. Key CSS classes:

- `.container`: Main layout
- `.sidebar`: Configuration panel
- `.chat-area`: Message area
- `.message`: Individual messages
- `.loading`: Loading indicator

### API Extensions

Extend `api.php` to add:

- File upload support
- Message history export
- Custom model parameters
- Advanced tool configurations

## Performance

- Lightweight: ~50KB total (HTML + CSS + JS)
- Fast loading: No external dependencies
- Efficient: Session-based state management
- Scalable: Stateless API design

## License

Same as the main B13 Ollama library.