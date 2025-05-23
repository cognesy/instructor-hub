---
title: 'Fireworks.ai'
docname: 'fireworks'
---

## Overview

Please note that the larger Mistral models support OutputMode::Json, which is much more
reliable than OutputMode::MdJson.

Mode compatibility:
- OutputMode::Tools - selected models
- OutputMode::Json - selected models
- OutputMode::MdJson


## Example

```php
<?php

use Cognesy\Polyglot\LLM\Inference;
use Cognesy\Utils\Str;

require 'examples/boot.php';

$answer = (new Inference)
    ->withConnection('fireworks') // see /config/llm.php
    ->create(
        messages: [['role' => 'user', 'content' => 'What is the capital of France']],
        options: ['max_tokens' => 64]
    )
    ->toText();

echo "USER: What is capital of France\n";
echo "ASSISTANT: $answer\n";
assert(Str::contains($answer, 'Paris'));
?>
```