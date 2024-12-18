# Messages

## Message
```php

class Options {
  $num_keep, $seed, $num_predict, $top_k, $top_p, $min_p, $typical_p, $repeat_last_n, $temperature, $repeat_penalty, 
      $presence_penalty, $frequency_penalty, $mirostat, $mirostat_tau, $mirostat_eta, $penalize_newline, $stop, $numa, 
      $num_ctx, $num_batch, $num_gpu, $main_gpu, $low_vram, $vocab_only, $use_mmap, $use_mlock, $num_thread;
}
class Request {
  $model;
  $format;
  $options;
  $stream;
  $keep_alive;
}
class Generate extends Request {
  $prompt;
  $suffix;
  $images;
  $system;
  $template;
}

class Chat extends Request {
  $messages;
  $tools;
}
class Message {
  $role;
  $content;
  $images;
  $tool_calls;
}
class Response {
  $total_duration;
  $load_duration;
  $prompt_eval_count;
  $prompt_eval_duration;
  $eval_count;
  $eval_duration;
  $context;
  $response;
}
```

----

## POST /api/generate

### Parameters
    -`model`: (**required**) model name
    -`prompt`: prompt to generate a response for
    -`suffix`: text after the model response
    -`images`: (optional) list, base64-encoded

#### Advanced parameters (optional):
    -`format`: format to return a response in. Format can be `json` or a JSON schema
    -`options`: additional model parameters
      num_keep, seed, num_predict, top_k, top_p, min_p, typical_p, repeat_last_n, temperature, repeat_penalty, 
      presence_penalty, frequency_penalty, mirostat, mirostat_tau, mirostat_eta, penalize_newline, stop, numa, 
      num_ctx, num_batch, num_gpu, main_gpu, low_vram, vocab_only, use_mmap, use_mlock, num_thread
    -`system`: system message to (overrides Modelfile)
    -`template`: the prompt template to use (overrides Modelfile)
    -`stream`: if false the response will be returned as a single response object, rather than a stream of objects
    `raw`: if true no formatting applied to prompt. use if specifying full templated prompt
    -`keep_alive`: how long model will stay in memory (default: 5m)

### The final response in the stream also includes additional data about the generation:
    `total_duration`: time spent generating the response
    `load_duration`: time spent in nanoseconds loading the model
    `prompt_eval_count`: number of tokens in the prompt
    `prompt_eval_duration`: time spent in nanoseconds evaluating the prompt
    `eval_count`: number of tokens in the response
    `eval_duration`: time in nanoseconds spent generating the response
    `context`: an encoding of the conversation used in this response, this can be sent in the next request to keep a conversational memory
    `response`: empty if the response was streamed, if not streamed, this will contain the full response

To calculate how fast the response is generated in tokens per second (token/s), divide eval_count / eval_duration * 10^9.


## POST /api/chat

### Parameters
    -`model`: (**required**) the model name
    -`messages`: the messages of the chat, this can be used to keep a chat memory
    -`tools`: tools for the model to use if supported. Requires `stream` to be set to false
#### Advanced parameters (optional):
    -`format`: the format to return a response in. Format can be json or a JSON schema.
    -`options`: additional model parameters listed in the documentation for the Modelfile such as temperature
    -`stream`: if false the response will be returned as a single response object, rather than a stream of objects
    -`keep_alive`: controls how long the model will stay loaded into memory following the request (default: 5m)
    
### The `message` object has the following fields:
    -`role`: the role of the message, either system, user, assistant, or tool
   -- `content`: the content of the message
    -`images` (optional): a list of images to include in the message (for multimodal models such as llava)
    -`tool_calls` (optional): a list of tools the model wants to use
