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

interface TonicsRouterRequestInputInterface
{
    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromPost(array $data = []): TonicsRouterRequestInputMethodsInterface;

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromGet(array $data = []) : TonicsRouterRequestInputMethodsInterface;

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromFile(array $data = []) : TonicsRouterRequestInputMethodsInterface;

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromServer(array $data = []) : TonicsRouterRequestInputMethodsInterface;

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromCookie(array $data = []) : TonicsRouterRequestInputMethodsInterface;
}