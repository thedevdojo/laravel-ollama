# Laravel Ollama

This app uses Laravel, Livewire, and Volt to create a simple interface that generates a response from an AI model using [Ollama](https://ollama.com/).

![Laravel Ollama Screenshot](https://raw.githubusercontent.com/thedevdojo/laravel-ollama/main/public/screenshot.png)

Simply download and install [Ollama](https://ollama.com/). Then use it with any model, like so:

```
ollama pull codellama
```

This application will retreive the response in Laravel by hitting the following endpoint, which is available via Ollama:

```
curl -X POST http://localhost:11434/api/generate -d '{
  "model": "codellama",
  "prompt": "Write me a function that outputs the fibonacci sequence"
}'
```

For testing purposes you may also use the CLI to get a response:

```
ollama run codellama "Write me a function that outputs the fibonacci sequence"
```