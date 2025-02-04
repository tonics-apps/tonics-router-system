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
        if (empty($this->foundURLRequiredParams)){
            $diff = [];
            if ($this->getFoundURLNode()){
                $diff = array_diff($this->getRequestURLS(), $this->getFoundURLNode()->getIndexToGetToPosition());
            }
            $this->setFoundURLRequiredParams(array_values($diff));
        }
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