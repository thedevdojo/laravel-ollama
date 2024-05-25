<?php
 
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

new class extends Component {
    public $prompt = '';
    public $response = '';
    public $model = '';
    public $models = [];
    public $promptAppend = '. Make sure your response is in Markdown format';

    public function mount(){
        $this->listModels();
        if(!$this->models[0]){
            die("Be sure to add a model to Ollama before running");
            return;
        }
        $this->model == $this->models[0];
    }

    public function modelUpdated(){
        $this->response = '';
    }

    public function listModels()
    {
        $command = 'ollama list';
        $output = [];
        $returnVar = null;
        exec($command, $output, $returnVar);
        if ($returnVar === 0) {
            $this->models = $output;
        } else {
            $this->models = ['Error: Unable to fetch models'];
        }

        $modelsFiltered = [];
        foreach($this->models as $index => $model){
            if($index != 0){
                $modelParts = explode(':', $model);
                array_push($modelsFiltered, $modelParts[0]);
            }
        }
        $this->models = $modelsFiltered;
    }
 
    public function submit()
    {
        ob_start();
        $client = new GuzzleHttp\Client(); 
        $response = Http::withOptions(['stream' => true])
            ->withHeaders([
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'X-Livewire-Stream' => 'true',
            ])
            ->post('http://localhost:11434/api/generate', [
                'model' => 'codellama',
                'prompt' => $this->prompt . $this->promptAppend
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
                            content: Str::markdown($this->response),
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
                        content: Str::markdown(this->response),
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
        <div class="overflow-hidden relative w-screen h-screen max-w-screen">
            <div class="flex absolute flex-col pt-2 pl-2">
                <label for="model" class="text-xs font-bold">Select Your Model</label>
                <select wire:model="model" id="model" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                    @foreach($models as $model)
                        <option>{{ $model }}</option>
                    @endforeach
                </select>
            </div>
            <div x-data="{ response: @entangle('response'), showResponse: false }" class="flex overflow-y-scroll flex-col flex-1 justify-between items-center pt-5 pb-24 mx-auto w-full h-full" style="overflow-y:scroll">

                <div x-show="showResponse" class="p-5 w-full h-auto rounded-lg border border-zinc-200 bg-zinc-50 prose prose-md">
                    <div wire:stream="response"></div>
                    <div>{!! Str::markdown($response) !!}</div>
                </div>
                
                <div class="flex fixed bottom-0 justify-center items-center w-full">
                    <div class="absolute bottom-0 left-0 z-20 w-full h-32 bg-gradient-to-t from-gray-100 via-gray-100"></div>
                    <div class="relative z-30 w-full max-w-2xl -translate-y-5">
                        <label for="aiPromt" for="aiPromt" class="sr-only">ai prompt</label>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" class="absolute left-3 top-1/2 -translate-y-1/2 size-4 fill-black dark:fill-white">
                            <path fill-rule="evenodd" d="M5 4a.75.75 0 0 1 .738.616l.252 1.388A1.25 1.25 0 0 0 6.996 7.01l1.388.252a.75.75 0 0 1 0 1.476l-1.388.252A1.25 1.25 0 0 0 5.99 9.996l-.252 1.388a.75.75 0 0 1-1.476 0L4.01 9.996A1.25 1.25 0 0 0 3.004 8.99l-1.388-.252a.75.75 0 0 1 0-1.476l1.388-.252A1.25 1.25 0 0 0 4.01 6.004l.252-1.388A.75.75 0 0 1 5 4ZM12 1a.75.75 0 0 1 .721.544l.195.682c.118.415.443.74.858.858l.682.195a.75.75 0 0 1 0 1.442l-.682.195a1.25 1.25 0 0 0-.858.858l-.195.682a.75.75 0 0 1-1.442 0l-.195-.682a1.25 1.25 0 0 0-.858-.858l-.682-.195a.75.75 0 0 1 0-1.442l.682-.195a1.25 1.25 0 0 0 .858-.858l.195-.682A.75.75 0 0 1 12 1ZM10 11a.75.75 0 0 1 .728.568.968.968 0 0 0 .704.704.75.75 0 0 1 0 1.456.968.968 0 0 0-.704.704.75.75 0 0 1-1.456 0 .968.968 0 0 0-.704-.704.75.75 0 0 1 0-1.456.968.968 0 0 0 .704-.704A.75.75 0 0 1 10 11Z" clip-rule="evenodd" />
                        </svg>
                        <input wire:model="prompt" @keyup.enter="showResponse=true" wire:keydown.enter="submit" type="text" class="px-2 py-2.5 pr-24 pl-10 w-full text-sm rounded-md border border-outline bg-neutral-50 border-neutral-300 text-neutral-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black disabled:cursor-not-allowed disabled:opacity-75 dark:border-neutral-700 dark:bg-neutral-900/50 dark:text-neutral-300 dark:focus-visible:outline-white" name="prompt" placeholder="Ask AI ..." />
                        <button wire:click="submit" x-on:click="showResponse=true" type="button" class="absolute right-3 top-1/2 px-2 py-1 text-xs tracking-wide bg-black rounded-md transition -translate-y-1/2 cursor-pointer text-neutral-100 hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black active:opacity-100 active:outline-offset-0 dark:bg-white dark:text-black dark:focus-visible:outline-white">
                            <span wire:loading.class="invisible">Generate</span>
                            <span wire:loading.flex class="flex absolute top-0 left-0 justify-center items-center w-full h-full">
                                <svg class="w-3 h-3 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    @endvolt
</x-layouts.empty>