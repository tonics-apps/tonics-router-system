<?php

namespace Devsrealm\TonicsRouterSystem;

trait RouterFindURL
{
    private ?RouteNode $foundURLNode = null;

    private array $foundURLRequiredParams = [];

    /**
     * @return RouteNode|null
     */
    public function getFoundURLNode(): ?RouteNode
    {
        return $this->foundURLNode;
    }

    /**
     * @param RouteNode|null $foundURLNode
     */
    public function setFoundURLNode(?RouteNode $foundURLNode): void
    {
        $this->foundURLNode = $foundURLNode;
    }

    /**
     * @return array
     */
    public function getFoundURLRequiredParams(): array
    {
        return $this->foundURLRequiredParams;
    }

    /**
     * @param array $foundURLRequiredParams
     */
    public function setFoundURLRequiredParams(array $foundURLRequiredParams): void
    {
        $this->foundURLRequiredParams = $foundURLRequiredParams;
    }

    public function hasFoundURLRequiredParam(): bool
    {
        return !empty($this->foundURLRequiredParams);
    }


}