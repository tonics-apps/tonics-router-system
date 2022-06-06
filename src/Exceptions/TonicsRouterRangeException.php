<?php

namespace Devsrealm\TonicsRouterSystem\Exceptions;

use JetBrains\PhpStorm\Pure;
use Throwable;

class TonicsRouterRangeException extends \RangeException
{
    #[Pure] public function __construct($message = "Out Or Over Array Index", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}