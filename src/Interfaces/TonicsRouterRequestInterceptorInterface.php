<?php

namespace Devsrealm\TonicsRouterSystem\Interfaces;

use Devsrealm\TonicsRouterSystem\Events\OnRequestProcess;

/**
 * Could be used to intercept the request before the request is handled
 */
interface TonicsRouterRequestInterceptorInterface
{
    /**
     * @param OnRequestProcess $request
     */
    public function handle(OnRequestProcess $request): void;
}