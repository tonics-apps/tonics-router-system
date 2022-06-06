<?php

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