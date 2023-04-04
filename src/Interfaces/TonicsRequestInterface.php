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

interface TonicsRequestInterface
{
    /**
     * The action that the client wants the server to perform on the particular resource.
     * @return string
     */
    public function getRequestMethod(): string;

    /**
     * Get path to the url resource without the hostname or the query string e.g, say example.com/api/media/file?query=me,
     * - The path to the url is /api/media/file (this is what you'll get)
     * - The hostname is example.com (you won't get this part)
     * - the query string is ?query=me (you won't get this part)
     * @return string
     */
    public function getRequestURL(): string;

    /**
     * Get path to the url with the query string without the hostname e.g, say example.com/api/media/file?query=me,
     * - The path to the url with the query string is /api/media/file?query=me (this is what you'll get)
     * - The hostname is example.com (you won't get this part)
     * @return string
     */
    public function getRequestURLWithQueryString(): string;

    /**
     * Blocks of arbitrary data from the client, note that some body data might be empty
     * @return mixed
     */
    public function getEntityBody(): mixed;
}