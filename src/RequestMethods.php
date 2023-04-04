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

namespace Devsrealm\TonicsRouterSystem;

final class RequestMethods
{
    public const REQUEST_TYPE_GET = 'GET';
    public const REQUEST_TYPE_POST = 'POST';
    public const REQUEST_TYPE_PUT = 'PUT';
    public const REQUEST_TYPE_PATCH = 'PATCH';
    public const REQUEST_TYPE_DELETE = 'DELETE';
    public const REQUEST_TYPE_HEAD = 'HEAD';
    public const REQUEST_TYPE_OPTIONS = 'OPTIONS';

    /**
     * @var string[]
     */
    public static array $requestMethods = [
        self::REQUEST_TYPE_GET => self::REQUEST_TYPE_GET,
        self::REQUEST_TYPE_POST => self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT => self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH => self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_DELETE => self::REQUEST_TYPE_DELETE,
        self::REQUEST_TYPE_HEAD =>  self::REQUEST_TYPE_HEAD,
        self::REQUEST_TYPE_OPTIONS => self::REQUEST_TYPE_OPTIONS,
    ];

    public static array $requestTypesPost = [
        self::REQUEST_TYPE_POST => self::REQUEST_TYPE_POST,
        self::REQUEST_TYPE_PUT => self::REQUEST_TYPE_PUT,
        self::REQUEST_TYPE_PATCH => self::REQUEST_TYPE_PATCH,
        self::REQUEST_TYPE_DELETE => self::REQUEST_TYPE_DELETE,
    ];
}