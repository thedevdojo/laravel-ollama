<?php
 
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;

new class extends Component {
    public $prompt = '';
    public $response = '';
 
    public function submit()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        header('X-Livewire-Stream: true');

        ob_start();
        $client = new GuzzleHttp\Client(); 
        $response = $client->request('POST', 'http://localhost:11434/api/generate', [
            'stream' => true,
            'json' => [
                'model' => 'codellama',
                'prompt' => $this->prompt
            ]
        ]);

        if ($response->getStatusCode() === 200) {
            $body = $response->getBody();
            $buffer = '';
            // Stream the response body as SSE
            while (!$body->eof()) {

                $buffer .= $body->read(1024); // Append chunk to buffer

                // Try to decode JSON from buffer
                while (($pos = strpos($buffer, "\n")) !== false) {
                    $jsonString = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 1);

                    $data = json_decode($jsonString, true);

                    if (isset($data['response'])) {
                        $this->response .= $data['response'];

                        $this->stream(
                            to: 'response',
                            content: $this->response,
                            replace: true
                        );
                    }
                }
            }

            if (!empty($buffer)) {
                $data = json_decode($buffer, true);

                if (isset($data['response'])) {
                    $this->response .= $data['response'];
                    $this->stream(
                        to: 'response',
                        content: this->response,
                        replace: true
                    );
                }
            }

            $body->close();
        } else {
            echo "data: Error - HTTP Status Code: " . $response->getStatusCode() . "\n\n";
            ob_flush();
            flush();
        }
    }
}
 
?>
 
<x-layouts.empty>
    @volt('ai-prompt')
        <div x-data="{ response: @entangle('response') }" class="flex flex-col flex-1 justify-between items-center py-5 mx-auto w-full max-w-xl h-full">

            <div class="relative">
                <div wire:stream="response"></div>
                <div>{{ $response }}</div>
            </div>
            
            <div class="relative bottom-0 left-1/2 w-full max-w-xl -translate-x-1/2">
                <label for="aiPromt" for="aiPromt" class="sr-only">ai prompt</label>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 fill-black dark:fill-white">
                    <path fill-rule="evenodd" d="M5 4a.75.75 0 0 1 .738.616l.252 1.388A1.25 1.25 0 0 0 6.996 7.01l1.388.252a.75.75 0 0 1 0 1.476l-1.388.252A1.25 1.25 0 0 0 5.99 9.996l-.252 1.388a.75.75 0 0 1-1.476 0L4.01 9.996A1.25 1.25 0 0 0 3.004 8.99l-1.388-.252a.75.75 0 0 1 0-1.476l1.388-.252A1.25 1.25 0 0 0 4.01 6.004l.252-1.388A.75.75 0 0 1 5 4ZM12 1a.75.75 0 0 1 .721.544l.195.682c.118.415.443.74.858.858l.682.195a.75.75 0 0 1 0 1.442l-.682.195a1.25 1.25 0 0 0-.858.858l-.195.682a.75.75 0 0 1-1.442 0l-.195-.682a1.25 1.25 0 0 0-.858-.858l-.682-.195a.75.75 0 0 1 0-1.442l.682-.195a1.25 1.25 0 0 0 .858-.858l.195-.682A.75.75 0 0 1 12 1ZM10 11a.75.75 0 0 1 .728.568.968.968 0 0 0 .704.704.75.75 0 0 1 0 1.456.968.968 0 0 0-.704.704.75.75 0 0 1-1.456 0 .968.968 0 0 0-.704-.704.75.75 0 0 1 0-1.456.968.968 0 0 0 .704-.704A.75.75 0 0 1 10 11Z" clip-rule="evenodd" />
                </svg>
                <input wire:model="prompt" wire:keydown.enter="submit" type="text" class="px-2 py-2.5 pr-24 pl-10 w-full text-sm rounded-md border border-outline bg-neutral-50 border-neutral-300 text-neutral-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black disabled:cursor-not-allowed disabled:opacity-75 dark:border-neutral-700 dark:bg-neutral-900/50 dark:text-neutral-300 dark:focus-visible:outline-white" name="prompt" placeholder="Ask AI ..." />
                <button wire:click="submit" type="button" class="absolute right-3 top-1/2 px-2 py-1 text-xs tracking-wide bg-black rounded-md transition -translate-y-1/2 cursor-pointer text-neutral-100 hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:bg-white dark:text-black dark:focus-visible:outline-white">Generate</button>
            </div>

        </div>
    @endvolt
</x-layouts.empty>