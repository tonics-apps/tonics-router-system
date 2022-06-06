<?php

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