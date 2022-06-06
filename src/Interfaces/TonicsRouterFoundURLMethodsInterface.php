<?php

namespace Devsrealm\TonicsRouterSystem\Interfaces;

use Devsrealm\TonicsRouterSystem\RouteNode;

interface TonicsRouterFoundURLMethodsInterface
{
    public function getFoundURLNode(): ?RouteNode;

    public function getFoundURLRequiredParams(): array;

    public function hasFoundURLRequiredParam(): bool;
}