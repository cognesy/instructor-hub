---
title: 'Use custom configuration providers'
docname: 'config_providers'
---

## Overview

You can inject your own configuration providers to StructuredOutput class.
This is useful for integration with your preferred framework (e.g. Symfony,
Laravel).

## Example

```php
<?php
require 'examples/boot.php';

use Adbar\Dot;
use Cognesy\Config\Contracts\CanProvideConfig;
use Cognesy\Config\Env;
use Cognesy\Dynamic\Structure;
use Cognesy\Events\Dispatchers\EventDispatcher;
use Cognesy\Http\HttpClientBuilder;
use Cognesy\Instructor\StructuredOutput;
use Cognesy\Polyglot\Inference\Enums\OutputMode;

class CustomConfigProvider implements CanProvideConfig
{
    private Dot $dot;

    public function __construct(array $config = []) {
        $this->dot = new Dot($config);
    }

    public function get(string $path, mixed $default = null): mixed {
        return $this->dot->get($path, $default);
    }

    public function has(string $path): bool {
        return $this->dot->has($path);
    }
}

$configData = [
    'http' => [
        'defaultPreset' => 'symfony',
        'presets' => [
            'symfony' => [
                'driver' => 'symfony',
                'connectTimeout' => 10,
                'requestTimeout' => 30,
                'idleTimeout' => -1,
                'maxConcurrent' => 5,
                'poolTimeout' => 60,
                'failOnError' => true,
            ],
            // Add more HTTP presets as needed
        ],
    ],
    'debug' => [
        'defaultPreset' => 'off',
        'presets' => [
            'off' => [
                'httpEnabled' => false,
            ],
            'on' => [
                'httpEnabled' => true,
                'httpTrace' => true,
                'httpRequestUrl' => true,
                'httpRequestHeaders' => true,
                'httpRequestBody' => true,
                'httpResponseHeaders' => true,
                'httpResponseBody' => true,
                'httpResponseStream' => true,
                'httpResponseStreamByLine' => true,
            ],
        ],
    ],
    'llm' => [
        'defaultPreset' => 'deepseek',
        'presets' => [
            'deepseek' => [
                'apiUrl' => 'https://api.deepseek.com',
                'apiKey' => Env::get('DEEPSEEK_API_KEY'),
                'endpoint' => '/chat/completions',
                'model' => 'deepseek-chat',
                'maxTokens' => 128,
                'driver' => 'deepseek',
                'httpClientPreset' => 'symfony',
            ],
            'openai' => [
                'apiUrl' => 'https://api.openai.com',
                'apiKey' => Env::get('OPENAI_API_KEY'),
                'endpoint' => '/v1/chat/completions',
                'model' => 'gpt-4',
                'maxTokens' => 256,
                'driver' => 'openai',
                'httpClientPreset' => 'symfony',
            ],
        ],
    ],
    'structured' => [
        'defaultPreset' => 'tools',
        'presets' => [
            'tools' => [
                'outputMode' => OutputMode::Tools,
                'useObjectReferences' => true,
                'maxRetries' => 3,
                'retryPrompt' => 'Please try again ...',
                'modePrompts' => [
                    OutputMode::MdJson->value => "Response must validate against this JSON Schema:\n<|json_schema|>\n. Respond correctly with strict JSON object within a ```json {} ``` codeblock.\n",
                    OutputMode::Json->value => "Response must follow JSON Schema:\n<|json_schema|>\n. Respond correctly with strict JSON object.\n",
                    OutputMode::JsonSchema->value => "Response must follow provided JSON Schema. Respond correctly with strict JSON object.\n",
                    OutputMode::Tools->value => "Extract correct and accurate data from the input using provided tools.\n",
                ],
                'schemaName' => 'user_schema',
                'toolName' => 'user_tool',
                'toolDescription' => 'Tool to extract user information ...',
                'chatStructure' => [
                    'system',
                    'pre-cached',
                        'pre-cached-prompt', 'cached-prompt', 'post-cached-prompt',
                        'pre-cached-examples', 'cached-examples', 'post-cached-examples',
                        'cached-messages',
                    'post-cached',
                    'pre-prompt', 'prompt', 'post-prompt',
                    'pre-examples', 'examples', 'post-examples',
                    'pre-messages', 'messages', 'post-messages',
                    'pre-retries', 'retries', 'post-retries'
                ],
                // defaultOutputClass is not used in this example
                'outputClass' => Structure::class,
            ]
        ]
    ]
];

$events = new EventDispatcher();
$configProvider = new CustomConfigProvider($configData);

$customClient = (new HttpClientBuilder(
        events: $events,
        configProvider: $configProvider,
    ))
    ->withConfigProvider($configProvider)
    ->withPreset('symfony')
    ->create();

$structuredOutput = (new StructuredOutput(
        events: $events,
        configProvider: $configProvider,
    ))
    ->withHttpClient($customClient);

// Call with custom model and execution mode

class User {
    public int $age;
    public string $name;
}

$user = $structuredOutput
    ->using('deepseek') // Use 'deepseek' preset defined in our config provider
    ->wiretap(fn($e) => $e->print())
    ->withMessages("Our user Jason is 25 years old.")
    ->withResponseClass(User::class)
    ->withOutputMode(OutputMode::Tools)
    ->withStreaming()
    ->get();

dump($user);

assert(isset($user->name));
assert(isset($user->age));
?>
```
