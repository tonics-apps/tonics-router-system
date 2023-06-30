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

use Generator;
use JetBrains\PhpStorm\Pure;

/**
 * @property $staticURLS
 * @property $potentialStaticURLS
 * @property $alias
 */
#[\AllowDynamicProperties]
class RouteNode
{
    private string $routeName;
    private ?RouteNode $parentNode = null;
    private array $teleportNode = [];
    private ?RouteNode $teleportNodeShortestPath = null;
    private mixed $lastTeleportNodeKey = null;
   // private ?RouteNode $teleportNode = null;
    private array $settings = [];
    private string $fullRoutePath = '';
    // Could contain a list of child nodes of a current node,
    private array $nodes = [];
    private bool $optionalParameter = false;
    private bool $requiredParameter = false;
    private bool $staticParameter = false;
    private mixed $indexKey = null;
    private array $indexToGetToPosition = [];

    private mixed $positionOfLastAddedRequiredParamChildNode = null;
    private string $nodeAlias = '';

    public function __construct($routeName = 'tree')
    {
        $this->routeName = $routeName;
    }

    /**
     * @return string
     */
    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function parentNode(): ?RouteNode
    {
        return $this->parentNode;
    }

    /**
     * @param RouteNode|null $parentNode
     * @return RouteNode
     */
    public function setParentNode(?RouteNode $parentNode): RouteNode
    {
        $this->parentNode = $parentNode;
        if ($this->hasParent()){
            $position = [...$this->parentNode->indexToGetToPosition, $this->indexKey];
            $this->indexToGetToPosition = $position;
        } else { // root
            $this->indexToGetToPosition = [$this->indexKey];
        }

        return $this;
    }

    /**
     * Updates the oldKey with the newKey name including the nodes associative key,
     * and the indexToGetToPosition
     * @param $oldKey
     * @param $newKey
     * @param $indexToGetToPosition
     * @return void
     */
    public function updateChildNodeKey($oldKey, $newKey, $indexToGetToPosition): void
    {
        if ($oldKey === $newKey){
            return;
        }
        if (isset($this->nodes[$oldKey])){
            $this->nodes[$newKey] = $this->nodes[$oldKey];
            $this->nodes[$newKey]->setRouteName($newKey);
            $this->nodes[$newKey]->indexToGetToPosition[$indexToGetToPosition] = $newKey;
            unset($this->nodes[$oldKey]);
        }
    }

    /**
     * Alias of getNodes method
     * @return array
     */
    #[Pure] public function childNodes(): array
    {
        return $this->getNodes();
    }

    /**
     * Could contain a list of child nodes of a current RouteNode,
     * @return array
     */
    private function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * @param array $nodes
     */
    public function setNodes(array $nodes): void
    {
        $this->nodes = $nodes;
    }

    /**
     * Would append array of RouteNode nodes
     * @param array $childrenTags
     * @return $this
     */
    public function appendChildren(array $childrenTags): static
    {
        $this->setNodes([...$this->childNodes(), ...$childrenTags]);
        return $this;
    }

    public function hasNodeAlias(): bool
    {
        return !empty($this->nodeAlias);
    }

    /**
     * Return true if there is children in $this->nodes, else false
     * @return bool
     */
    public function hasChildren(): bool
    {
        return !empty($this->nodes);
    }

    /**
     * Return true if node has parent
     * @return bool
     */
    public function hasParent(): bool
    {
        return !empty($this->parentNode);
    }

    /**
     * Return true if there is no children in node, else, false
     * @return bool
     */
    public function hasNoChildren(): bool
    {
        return empty($this->nodes);
    }


    /**
     * @param RouteNode $node
     * @param int|null $position
     * If you specify position,
     * it would insert RouteNode node in that position and push down the former node using the position,
     * otherwise, it would be pushed to the bottom of the stack
     * @return $this
     */
    public function addNode(RouteNode $node, int $position = null): static
    {
        if ($position !== null) {
            $array = $this->nodes;
            array_splice($array, $position, 0, [$node]);
            $this->nodes = $array;
        } else {
            $this->nodes[$node->getRouteName()] = $node;
            $node->indexKey = $node->getRouteName();
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getFullRoutePath(): string
    {
        return $this->fullRoutePath;
    }

    public function appendToFullRoutePath(string $fullRoutePath): RouteNode
    {
        $this->fullRoutePath .= $fullRoutePath;
        return $this;
    }

    /**
     * @param string $fullRoutePath
     * @return RouteNode
     */
    public function setFullRoutePath(string $fullRoutePath): RouteNode
    {
        $this->fullRoutePath = $fullRoutePath;
        return $this;
    }

    /**
     * @return bool
     */
    public function isOptionalParameter(): bool
    {
        return $this->optionalParameter;
    }

    /**
     * @param bool $optionalParameter
     * @return RouteNode
     */
    public function setOptionalParameter(bool $optionalParameter): RouteNode
    {
        $this->optionalParameter = $optionalParameter;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequiredParameter(): bool
    {
        return $this->requiredParameter;
    }

    /**
     * @param bool $requiredParameter
     * @return RouteNode
     */
    public function setRequiredParameter(bool $requiredParameter): RouteNode
    {
        $this->requiredParameter = $requiredParameter;
        return $this;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array|null[] $settings
     */
    public function setSettings(string $method, array $settings): void
    {
        $this->settings[$method] = $settings;
    }

    /**
     * @param string $requestMethod
     * @return bool
     */
    public function requestMethodExist(string $requestMethod): bool
    {
        return key_exists($requestMethod, $this->settings);
    }

    /**
     * This first checks if the request method exist. and if it does, it checks
     * if the requestInterceptor array is not empty. You get true if the both returns true,
     * otherwise false.
     *
     * @param string $requestMethod
     * @return bool
     */
    public function requestMethodHasRequestInterceptors(string $requestMethod): bool
    {
        return $this->requestMethodExist($requestMethod) === true  && !empty($this->settings[$requestMethod]['requestInterceptors']);
    }

    /**
     * @param $requestMethod
     * @return mixed
     */
    public function getClass($requestMethod): mixed
    {
        return $this->settings[$requestMethod]['class'];
    }

    /**
     * @param $requestMethod
     * @return mixed
     */
    public function getMoreSettings($requestMethod): mixed
    {
        return $this->settings[$requestMethod]['moreSettings'];
    }

    /**
     * @param $requestMethod
     * @return mixed
     */
    public function getCallback($requestMethod): mixed
    {
        return $this->settings[$requestMethod]['callback'];
    }

    /**
     * @param $requestMethod
     * @return mixed
     */
    public function getRequestInterceptors($requestMethod): mixed
    {
        return $this->settings[$requestMethod]['requestInterceptors'];
    }

    /**
     * Get parent recursively
     * @param RouteNode $node
     * @return Generator
     */
    public function getParentRecursive(RouteNode $node) : \Generator
    {
        $parent = $node?->parentNode();
        while ($parent !== null) {
            /**@var RouteNode $parent */
            yield $parent;
            $parent = $parent?->parentNode();
        }
    }


    /**
     * To use this function, simply test if the $node has a children first, okay?
     * @param RouteNode $node
     * @return Generator
     */
    public function getChildrenRecursive(RouteNode $node): \Generator
    {
        // yield $node;
        foreach ($node->childNodes() as $childNode) {
            /**@var RouteNode $childNode */
            yield $childNode;
            if ($childNode->hasChildren()) {
                yield from $this->getChildrenRecursive($childNode);
            }
        }
    }

    /**
     * @param string $routeName
     * @param RouteNode $node
     * Where to start searching from
     * @return RouteNode|null
     */
    public function findAppropriatePosToInsertOrUpdateNode(string $routeName, RouteNode $node): ?RouteNode
    {
        $resNode = null;
        ## This is for required parameter
        if (isset($routeName[0]) && $routeName[0] === ':'){
            if ($node->positionOfLastAddedRequiredParamChildNode){
                $resNode = $node->childNodes()[$node->positionOfLastAddedRequiredParamChildNode] ?? null;
            }

            return $resNode;
        }

        return $node->childNodes()[$routeName] ?? null;
    }

    /**
     * @param RouteNode $node
     * @return mixed|null
     */
    public function nodeChildHasARequiredParameter(RouteNode $node): mixed
    {
        $res = null;
        foreach ($node->childNodes() as $childNode)
        {
            if ($childNode->isRequiredParameter()){
                $res = $childNode;
            }
        }
        return $res;
    }

    /**
     * @param string $routeName
     * @param RouteNode $node
     * @return RouteNode|null
     */
    public function findNodeByRouteNameOrRequired(string $routeName, RouteNode $node): ?RouteNode
    {
        $resNode = null;
        $childNodes = $node->childNodes();
        # If we can match asap, we do and return
        # otherwise, we check the childNodes that have a requiredParam if there is any, and return that instead
        if (isset($childNodes[$routeName])){
            $resNode =  $childNodes[$routeName];
        } else {
            $resNode = $node->childNodes()[$node->positionOfLastAddedRequiredParamChildNode] ?? null;
        }
        return $resNode;
    }

    /**
     * @return bool
     */
    public function isStaticParameter(): bool
    {
        return $this->staticParameter;
    }

    /**
     * @param bool $staticParameter
     * @return RouteNode
     */
    public function setStaticParameter(bool $staticParameter): RouteNode
    {
        $this->staticParameter = $staticParameter;
        return $this;
    }

    /**
     * @param string $routeName
     * @return RouteNode
     */
    public function setRouteName(string $routeName): RouteNode
    {
        $this->routeName = $routeName;
        return $this;
    }

    /**
     * @return string
     */
    public function getNodeAlias(): string
    {
        return $this->nodeAlias;
    }

    /**
     * @param string $nodeAlias
     */
    public function setNodeAlias(string $nodeAlias): void
    {
        $this->nodeAlias = $nodeAlias;
    }

    /**
     * Index To Loop Through until we get to the position, this is like a shortcut
     * @return array
     */
    public function getIndexToGetToPosition(): array
    {
        return $this->indexToGetToPosition;
    }

    /**
     * @return mixed
     */
    public function getPositionOfLastAddedRequiredParamChildNode(): mixed
    {
        return $this->positionOfLastAddedRequiredParamChildNode;
    }

    /**
     * @param mixed $positionOfLastAddedRequiredParamChildNode
     */
    public function setPositionOfLastAddedRequiredParamChildNode(mixed $positionOfLastAddedRequiredParamChildNode): void
    {
        $this->positionOfLastAddedRequiredParamChildNode = $positionOfLastAddedRequiredParamChildNode;
    }

    /**
     * This would return the very last child node until the moment where there are no sibling nodes,
     * this is useful for jumping to this very node, superfast when retrieving long route that doesn't have much
     * sibling node down the road.
     * @return array
     */
    public function getTeleportNodes(): array
    {
        return $this->teleportNode;
    }

    /**
     * @param $key
     * @return RouteNode|null
     */
    public function getTeleportNode($key): ?RouteNode
    {
        if ($this->teleportNodeExist($key)){
            return $this->teleportNode[$key];
        }
        return null;
    }

    /**
     * @return bool
     */
    public function teleportNodesExist(): bool
    {
        return !empty($this->teleportNode);
    }

    /**
     * @param $key
     * @return bool
     */
    public function teleportNodeExist($key): bool
    {
        return isset($this->teleportNode[$key]);
    }

    /**
     * @param RouteNode|null $teleportNode
     * @return RouteNode
     */
    public function addTeleportNode(?RouteNode $teleportNode): RouteNode
    {
        $key = count($teleportNode->getIndexToGetToPosition());
        $this->teleportNode[$key] = $teleportNode;
        $this->setLastTeleportNodeKey($key);

        $this->updateTeleportShortedNodePath();
        return $this;
    }

    private function updateTeleportShortedNodePath(): void
    {
        if (!empty($this->teleportNode)){
            // $this->setTeleportNodeShortestPath($this->teleportNode[min(array_keys($this->teleportNode))]);
            $this->setTeleportNodeShortestPath($this->teleportNode[array_key_first($this->teleportNode)]);
        }
    }

    /**
     * This would remove $teleportNode and update the shortestTeleportNodePath
     * @param RouteNode|null $teleportNode
     * @return void
     */
    public function teleported(?RouteNode $teleportNode): void
    {
        $key = count($teleportNode->getIndexToGetToPosition());
        if ($this->teleportNodeExist($key)){
            $this->removeTeleportNode($key);
            $this->updateTeleportShortedNodePath();
        }
    }

    /**
     * @param $key
     * @return $this
     */
    public function removeTeleportNode($key): static
    {
        unset($this->teleportNode[$key]);
        return $this;
    }

    /**
     * @param array $nodes
     * @return $this
     */
    public function setTeleportNode(array $nodes): static
    {
        $this->teleportNode = $nodes;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLastTeleportNodeKey(): mixed
    {
        return $this->lastTeleportNodeKey;
    }

    /**
     * @param mixed $lastTeleportNodeKey
     * @return RouteNode
     */
    public function setLastTeleportNodeKey(mixed $lastTeleportNodeKey): RouteNode
    {
        $this->lastTeleportNodeKey = $lastTeleportNodeKey;
        return $this;
    }

    /**
     * @return RouteNode|null
     */
    public function getTeleportNodeShortestPath(): ?RouteNode
    {
        return $this->teleportNodeShortestPath;
    }

    /**
     * @param RouteNode|null $teleportNodeShortestPath
     * @return RouteNode
     */
    public function setTeleportNodeShortestPath(?RouteNode $teleportNodeShortestPath): RouteNode
    {
        $this->teleportNodeShortestPath = $teleportNodeShortestPath;
        return $this;
    }


}