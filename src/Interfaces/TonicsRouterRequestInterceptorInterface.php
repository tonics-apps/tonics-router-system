<?php
/*
 * Copyright 2023 Ahmed Olayemi F. <olayemi@tonics.app or devsrealmer@gmail.com>
 *    Licensed under the Apache License, Version 2.0 (the "License");
 *    you may not use this file except in compliance with the License.
 *    You may obtain a copy of the License at
 *
 *        http://www.apache.org/licenses/LICENSE-2.0
 *
 *    Unless required by applicable law or agreed to in writing, software
 *    distributed under the License is distributed on an "AS IS" BASIS,
 *    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *    See the License for the specific language governing permissions and
 *    limitations under the License.
 */

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