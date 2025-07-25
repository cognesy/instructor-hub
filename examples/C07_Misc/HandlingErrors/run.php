---
title: 'Handling errors'
docname: 'handling_errors'
---

## Overview

You can create a wrapper class to hold either the result of an operation or an error message.
This allows you to remain within a function call even if an error occurs, facilitating
better error handling without breaking the code flow.

> NOTE: Instructor offers a built-in Maybe wrapper class that you can use to handle errors.
> See the example in Basics section for more details.

## Example

```php
<?php
require 'examples/boot.php';

use Cognesy\Instructor\StructuredOutput;

class UserDetail
{
    public string $name;
    public int $age;
}

class MaybeUser
{
    public ?UserDetail $user = null;
    public bool $noUserData = false;
    /** If no user data, provide reason */
    public ?string $errorMessage = '';

    public function get(): ?UserDetail {
        return $this->noUserData ? null : $this->user;
    }
}

$user = (new StructuredOutput)
    ->withMessages([['role' => 'user', 'content' => 'We don\'t know anything about this guy.']])
    ->withResponseModel(MaybeUser::class)
    ->get();

dump($user);

assert($user->noUserData);
assert(!empty($user->errorMessage));
assert($user->get() === null);
?>
```

