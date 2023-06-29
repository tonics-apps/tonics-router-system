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

use Devsrealm\TonicsRouterSystem\Exceptions\TonicsRouterRangeException;
use Devsrealm\TonicsRouterSystem\Interfaces\TonicsRouterFoundURLMethodsInterface;
use Devsrealm\TonicsRouterSystem\State\RouteTreeGeneratorState;
use JetBrains\PhpStorm\Pure;

/**
 * The following is the recommended way for matching route url:
 *
 * - 1) First, check if url exist in the staticURLS property of the $routeNodeTree, if it does,
 * good for you, return it, and you are done matching, if it doesn't, then we gotta walk the tree (goto step 2):
 *
 * - 2) Before you walk the tree, trim the REQUEST_URL (the one in GLOBAL $_SERVER) by solidus, e.g, if you have:
 * /home/in/ then the trimmed result should be home/in, having done that, split the trimmed result by solidus, which
 * should give you [home], [in], once that is done, prepend a solidus to the split result, giving [/], [home], [in].
 * Now, let's walk the tree (step 3):
 *
 * - 3) Loop the nodes in $routeNodeTree, if the first nodename matches [/], check if the immediateChildRequired is true,
 * if it is, check if the children of the nodes is mare than one, if it isn't then check if the route has more element after
 * [/], if it has, then the immediate child in the node is
 */
class RouteTreeGenerator implements TonicsRouterFoundURLMethodsInterface
{
    use RouterFindURL;

    private int $currentRouteKey = 0;
    private string $currentRoutePath = '';
    private array $currentRouteSettings = [];
    private string $currentRouteTreeGeneratorState;
    private RouteTreeGeneratorState $routeTreeGeneratorState;

    private RouteNode $routeNodeTree;
    private ?RouteNode $lastAddedRouteNode = null;
    private ?RouteNode $lastAddedParentRouteNode = null;

    private array $requestURLS = []; // only used when matching URL, not for building tree

    private bool $isStatic = true;

    #[Pure] public function __construct(RouteTreeGeneratorState $routeTreeGeneratorState, RouteNode $routeNode)
    {
        $this->routeTreeGeneratorState = $routeTreeGeneratorState;
        $this->currentRouteTreeGeneratorState = RouteTreeGeneratorState::TonicsInitialStateHandler;
        $this->routeNodeTree = $routeNode;
        $this->routeNodeTree->{'staticURLS'} = [];
        $this->routeNodeTree->{'alias'} = [];
    }

    public function reset(): void
    {
        $this->routeTreeGeneratorState::setRoutePath([]);
        $this->routeTreeGeneratorState::setRoutePathFlat('');
        $this->isStatic = true;
        $this->currentRouteKey = 0;
        $this->currentRoutePath = '';

        $this->lastAddedParentRouteNode = null;

        $this->currentRouteTreeGeneratorState = RouteTreeGeneratorState::TonicsInitialStateHandler;
    }

    public function initRouteTreeGeneratorState(array $routeSettings): void
    {
        $this->currentRouteSettings = [
            'url' => $routeSettings['url'],
            // 'methods' => $routeSettings['methods'],
            'requestInterceptors' => $routeSettings['requestInterceptors'],
            'class' => $routeSettings['class'],
            'callback' => $routeSettings['callback'],
            'moreSettings' => $routeSettings['moreSettings']
        ];

        ## PRE-PROCESS TONICS ROUTE TREE GENERATOR STATE:
        $this->currentRouteSettings['url'] = (empty($this->currentRouteSettings['url'])) ? [] : explode('/', $this->currentRouteSettings['url']);
        $this->currentRouteSettings['flat'] = '/' . implode('/', $this->currentRouteSettings['url']);
        array_unshift($this->currentRouteSettings['url'], '/');

        # INIT
        $this->reset();
        $i = $this->currentRouteKey;
        $this->currentRoutePath = $this->currentRouteSettings['url'][$i];

        $len = count($this->currentRouteSettings['url']);
        for ($this->currentRouteKey = $i; $this->currentRouteKey < $len; ++$this->currentRouteKey) {
            $k = $this->currentRouteKey;
            $this->currentRoutePath = $this->currentRouteSettings['url'][$k];
            $this->dispatchRouteTreeGeneratorState($this->currentRouteTreeGeneratorState);
        }

        $this->recursivelyUpdateTheNestedNodeUpUntilNoSiblingNode($this->lastAddedRouteNode);
        $this->routeNodeTree->setTeleportNode([])->setLastTeleportNodeKey(null)->setTeleportNodeShortestPath(null);

        $methods = $routeSettings['methods'];

        ## Check url from array to flat string
        $this->lastAddedRouteNode?->setFullRoutePath($this->currentRouteSettings['flat']);
        foreach ($methods as $method) {
            $this->lastAddedRouteNode?->setSettings($method, $this->currentRouteSettings);
        }

        ## Check and set Static routes for faster lookup
        if ($this->isStatic()) {
            $this->routeNodeTree->staticURLS[$this->currentRouteSettings['flat']] = $this->lastAddedRouteNode;
        }

        ## For Route Alias
        if ($routeSettings['alias'] && $this->lastAddedRouteNode !== null){
            $this->lastAddedRouteNode->setNodeAlias($routeSettings['alias']);
            $this->routeNodeTree->alias[$routeSettings['alias'] ] = $this->lastAddedRouteNode;
        }
    }


    public function dispatchRouteTreeGeneratorState(string $stateHandler): void
    {
        $this->routeTreeGeneratorState::$stateHandler($this);
    }

    /**
     * @param string $reconsumeState
     * @return $this
     */
    public function reconsumeIn(string $reconsumeState): static
    {
        if ($this->currentRouteKey === 0) {
            throw new TonicsRouterRangeException();
        }

        $i = $this->prevCharacterKey();
        $this->currentRouteKey = $i;
        $this->currentRoutePath = $this->currentRouteSettings['url'][$i];
        $this->currentRouteTreeGeneratorState = $reconsumeState;
        return $this;
    }

    /**
     * This decrements the CharactersPointer position by 1,
     * updates the char pointer key
     * and return the key
     * @return int
     */
    public function prevCharacterKey(): int
    {
        $key = $this->currentRouteKey - 1;
        if (!key_exists($key, $this->currentRouteSettings['url'])) {
            throw new TonicsRouterRangeException();
        }
        $this->currentRouteKey = $key;
        return $key;
    }

    public function firstCharIsAscii($var = null): bool
    {
        if ($var === null) {
            $var = $this->currentRoutePath[0] ?? false;
        }
        return preg_match("/^[a-z]/i", $var) === 1;
    }

    /**
     * A solidus is /
     * @param null $var
     * @return bool
     */
    public function firstCharIsSolidus($var = null): bool
    {
        if ($var === null) {
            $var = $this->currentRoutePath[0] ?? null;
        }
        return $var === '/';
    }

    /**
     * A question mark is ?
     * @param null $var
     * @return bool
     */
    public function firstCharIsQuestionMark($var = null): bool
    {
        if ($var === null) {
            $var = $this->currentRoutePath[0] ?? null;
        }
        return $var === '?';
    }

    /**
     * A column is :
     * @param null $var
     * @return bool
     */
    public function firstCharIsColumn($var = null): bool
    {
        if ($var === null) {
            $var = $this->currentRoutePath[0] ?? null;
        }
        return $var === ':';
    }

    public function firstCharIsAsciiDigit($var = null): bool
    {
        if ($var === null) {
            $var = $this->currentRoutePath[0] ?? false;
        }
        return trim($var, '0..9') == '';
    }

    public function currentRouteMethods(): array
    {
        if (isset($this->currentRouteSettings['method'])) {
            return $this->currentRouteSettings['method'];
        }

        return [];
    }

    public function currentRouteRequestInterceptors(): array
    {
        if (isset($this->currentRouteSettings['requestInterceptors'])) {
            return $this->currentRouteSettings['requestInterceptors'];
        }

        return [];
    }

    public function currentRouteClass(): string
    {
        if (isset($this->currentRouteSettings['class'])) {
            return $this->currentRouteSettings['class'];
        }

        return '';
    }

    public function currentRouteCallback()
    {
        if (isset($this->currentRouteSettings['callback'])) {
            return $this->currentRouteSettings['callback'];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getCurrentRouteSettings(): array
    {
        return $this->currentRouteSettings;
    }

    /**
     * @param string|null $state
     */
    public function switchRouteTreeGeneratorState(?string $state): void
    {
        $this->currentRouteTreeGeneratorState = $state;
    }

    /**
     * @return RouteTreeGeneratorState
     */
    public function getRouteTreeGeneratorState(): RouteTreeGeneratorState
    {
        return $this->routeTreeGeneratorState;
    }

    /**
     * @return string
     */
    public function getCurrentRouteTreeGeneratorState(): string
    {
        return $this->currentRouteTreeGeneratorState;
    }

    /**
     * @return string
     */
    public function getCurrentRoutePath(): string
    {
        return $this->currentRoutePath;
    }

    /**
     * @param string $routePath
     * @return RouteTreeGenerator
     */
    public function currentRoutePath(string $routePath): RouteTreeGenerator
    {
        $this->currentRoutePath = $routePath;
        return $this;
    }

    /**
     * @return RouteNode
     */
    public function getRouteNodeTree(): RouteNode
    {
        return $this->routeNodeTree;
    }

    /**
     * @param RouteNode $routeNodeTree
     * @return RouteTreeGenerator
     */
    public function setRouteNodeTree(RouteNode $routeNodeTree): static
    {
        $this->routeNodeTree = $routeNodeTree;
        return $this;
    }

    /**
     * Update the nested node and steps to nested node properties in the parent nodes.
     *
     * This method recursively traverses the parent nodes starting from the given node,
     * updating the nested node and steps to nested node properties based on the presence of siblings.
     *
     * @param RouteNode $node The node to start the update from.
     */
    public function recursivelyUpdateTheNestedNodeUpUntilNoSiblingNode(RouteNode $node): void
    {
        $parent = $node?->parentNode();

        while ($parent !== null) {
            if (count($parent->childNodes()) > 1) {
                foreach ($parent->getParentRecursive($parent) as $parentNode) {
                    if (empty($parentNode->getLastTeleportNodeKey())){
                        continue;
                    }
                    if ($parent->teleportNodeExist($parentNode->getLastTeleportNodeKey())){
                        $parentNode->removeTeleportNode($parentNode->getLastTeleportNodeKey())
                            ->addTeleportNode($parent);
                    }
                }
                $parent->setTeleportNode([])->setLastTeleportNodeKey(null)->setTeleportNodeShortestPath(null);
                break;
            } else {
                $parent->addTeleportNode($node);
            }
            $parent = $parent->parentNode();
        }
    }

    /**
     * @param array $paths
     * @param RouteNode $parentNode
     * @return RouteNode|null
     */
    public function insertNodeInAppropriatePosition(array $paths, RouteNode $parentNode): ?RouteNode
    {
        $node = null;
        foreach ($paths as $path){
            $findNode = $parentNode->findAppropriatePosToInsertOrUpdateNode($path, $parentNode);

            if ($findNode !== null){
                $parentNode = $findNode;
                // if found node is a requiredParameter, then the $path is also a required type, and it's on the same
                // level, so, we update its settings, e.g /home/name/:in is same as /home/name/:me, `:me` would replace `:in`
                if ($findNode->isRequiredParameter()){
                   $findNode->parentNode()->updateChildNodeKey($findNode->getRouteName(), $path, $this->currentRouteKey);
                }

                // this is useful if maybe the new path has some new settings
                // setting it below allow for that to reflect
                $node = $findNode;
            } else {
                $node = new RouteNode($path);
                $parentNode->addNode($node);
                $node->setParentNode($parentNode);
                $parentNode = $node;
            }
        }

        $this->lastAddedParentRouteNode = $parentNode;
        return $node;
    }

    /**
     * @param $url
     * @return mixed|null
     */
    private function canMatchStatic($url): mixed
    {
        $findNode = null;
        if (isset($this->routeNodeTree->staticURLS[$url])){
            $findNode = $this->routeNodeTree->staticURLS[$url];
        }

        return $findNode;
    }

    /**
     * Matches URL and return the foundNode, otherwise, it returns null
     * @param string $url
     * @param bool $removeLeadingSlash
     * @return RouteNode|null
     */
    public function match(string $url, bool $removeLeadingSlash = true): ?RouteNode
    {
        return $this->findURL($url, $removeLeadingSlash)?->getFoundURLNode();
    }

    /**
     * @param string $url
     * @param bool $removeLeadingSlash
     * @return TonicsRouterFoundURLMethodsInterface|null
     */
    public function findURL(string $url, bool $removeLeadingSlash = true): ?TonicsRouterFoundURLMethodsInterface
    {
        if ($removeLeadingSlash){
            $url = $this->removingLeadingSlash($url);
        }

        $findNode = $this->canMatchStatic($url);

        if ($findNode === null){
            $urlPaths =  explode('/', $url);
            $urlPaths[0] = '/';
            $root = $this->routeNodeTree;

            $len = count($urlPaths);
            for ($i = 0; $i < $len; ++$i) {
                $path = $urlPaths[$i];
                $findNode = $this->routeNodeTree->findNodeByRouteNameOrRequired($path, $root);

                if ($findNode === null){
                    break;
                }

                # Nothing to do no more
               if ($len === count($findNode->getIndexToGetToPosition())){ break; }
                
                # If so, skip.
                if (
                    ($findNode?->teleportNodesExist() && $teleportNode = $findNode->getTeleportNode($len))
                    ||
                    ($teleportNode = $findNode?->getTeleportNodeShortestPath())
                )
                {
                    $lastIndex = array_key_last($teleportNode->getIndexToGetToPosition());
                    if ($teleportNode->isStaticParameter()){
                        if (isset($urlPaths[$lastIndex]) && $teleportNode->getIndexToGetToPosition()[$lastIndex] === $urlPaths[$lastIndex]){
                            if (str_starts_with($url, $findNode->getFullRoutePath())){
                                $i = $lastIndex;
                                $findNode = $teleportNode;
                                $findNode->teleported($teleportNode);
                            }
                        }
                    } else {
                        $i = $lastIndex;
                        $findNode = $teleportNode;
                        $findNode->teleported($teleportNode);
                    }
                }

                $root = $findNode;
            }

            if ($findNode !== null){
                if ($len != count($findNode->getIndexToGetToPosition())){
                    $findNode = null;
                }
            }

            $this->requestURLS = $urlPaths;
        }
        $this->setFoundURLNode($findNode);
        return $this;
    }

    /**
     * @param string $namedAlias
     * The named alias, e.g posts.show
     * @param array $parameters
     * E.g If the url has "/admin/password/reset/:token1/:token2",
     * then pass [':token1' => 3373, ':token2' => 28232], you should make sure
     * the url has a unique paramname, having /home/:name/:name would replace both :name,
     * here is a trick for that, use a numbered array to overcome that limitation
     */
    public function namedURL(string $namedAlias, array $parameters = [])
    {
        if (key_exists($namedAlias, $this->routeNodeTree->alias)){
            /**
             * @var RouteNode $routeNode
             */
            $routeNode = $this->routeNodeTree->alias[$namedAlias];
            $url = $routeNode->getFullRoutePath();

            if (!empty($parameters)){
                ## For Numerical Params
                if (key_exists(0, $parameters)){
                    $urlPaths =  explode('/', trim($url, '/'));
                    array_unshift($urlPaths, '/');
                    ## Apply Param:
                    foreach ($urlPaths as $k => &$path){
                        if (str_starts_with($path, ':')){
                            $paramVal = array_shift($parameters);
                            if ($paramVal !== null){
                                $urlPaths[$k] = $paramVal;
                            }
                        }
                    }
                    # Total nonsense, figure out a way to clean this, I don't think we need any cleanURL...
                    $url = '/' . $this->cleanURLForNameURLegacy(implode('/', $urlPaths));
                }
                ## For Assoc Params
                else {
                    $realParam = [];
                    foreach ($parameters as $key => $parameter){
                        $realParam[':' .trim($key, ':')] = $parameter;
                    }
                    $url = strtr($url, $realParam);
                }
            }
            return $url;
        }
        return '';
    }

    public function cleanURLForNameURLegacy(string $url)
    {
        ## D preg_replace converts multiple slashes to one.
        ## FILTER_SANITIZE_URL remove illegal chars from the url
        ## rtrim remove slash from the end e.g /name/book/ becomes  /name/book
        return trim(filter_var(preg_replace("#//+#", "\\1/", $url), FILTER_SANITIZE_URL), '/');
    }
    /**
     * @param string $url
     * @return string
     */
    public function replaceMultipleSlashesToOne(string $url): string
    {
        return preg_replace("#//+#", "\\1/", $url);
    }

    public function removingLeadingSlash(string $url): string
    {
        return rtrim($url, '/');
    }

    /**
     * @return RouteNode|null
     */
    public function getLastAddedRouteNode(): ?RouteNode
    {
        return $this->lastAddedRouteNode;
    }

    /**
     * @param RouteNode|null $lastAddedRouteNode
     * @return RouteTreeGenerator
     */
    public function setLastAddedRouteNode(?RouteNode $lastAddedRouteNode): RouteTreeGenerator
    {
        $this->lastAddedRouteNode = $lastAddedRouteNode;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentRouteKey(): int
    {
        return $this->currentRouteKey;
    }

    /**
     * @param int $currentRouteKey
     */
    public function setCurrentRouteKey(int $currentRouteKey): void
    {
        $this->currentRouteKey = $currentRouteKey;
    }

    /**
     * @return bool
     */
    public function isStatic(): bool
    {
        return $this->isStatic;
    }

    /**
     * @param bool $isStatic
     */
    public function setIsStatic(bool $isStatic): void
    {
        $this->isStatic = $isStatic;
    }

    /**
     * @return RouteNode|null
     */
    public function getLastAddedParentRouteNode(): ?RouteNode
    {
        return $this->lastAddedParentRouteNode;
    }

    /**
     * @return array
     */
    public function getRequestURLS(): array
    {
        return $this->requestURLS;
    }

    /**
     * @param array $requestURLS
     * @return RouteTreeGenerator
     */
    public function setRequestURLS(array $requestURLS): RouteTreeGenerator
    {
        $this->requestURLS = $requestURLS;
        return $this;
    }
}