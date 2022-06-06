<?php

namespace Devsrealm\TonicsRouterSystem\Exceptions;

use Throwable;

class URLNotFound extends \Exception
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}