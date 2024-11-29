# B13-PhpLib-Ollama
A PHP based Ollama interaction.

## TODO
- [ ] Look at what software licence to put this under.
- [ ] create a Message class and possibly Request/Response classes or Generate/Chat Requests
- [ ] create a MessageList class to hold a line of chat messages
- [ ] create a configuration class to setup model/parameters
- [ ] write out a workflow

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
