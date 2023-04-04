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

    private bool $isStatic = true;

    #[Pure] public function __construct(RouteTreeGeneratorState $routeTreeGeneratorState, RouteNode $routeNode)
    {
        $this->routeTreeGeneratorState = $routeTreeGeneratorState;
        $this->currentRouteTreeGeneratorState = RouteTreeGeneratorState::TonicsInitialStateHandler;
        $this->routeNodeTree = $routeNode;
        $this->routeNodeTree->{'staticURLS'} = [];
        $this->routeNodeTree->{'alias'} = [];
    }

    public function reset()
    {
        $this->routeTreeGeneratorState::setRoutePath([]);
        $this->isStatic = true;
        $this->currentRouteKey = 0;
        $this->currentRouteTreeGeneratorState = RouteTreeGeneratorState::TonicsInitialStateHandler;
    }

    public function initRouteTreeGeneratorState(array $routeSettings)
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
            $routePath = $this->currentRouteSettings['url'][$k];
            $this->currentRoutePath = $routePath;
            $this->dispatchRouteTreeGeneratorState($this->currentRouteTreeGeneratorState);
        }
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
    public function switchRouteTreeGeneratorState(?string $state)
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
     * @param array $paths
     * @param RouteNode $root
     * @return RouteNode|null
     */
    public function insertNodeInAppropriatePosition(array $paths, RouteNode $root): ?RouteNode
    {
        $node = null;
        foreach ($paths as $k => &$path){
            $findNode = $root->findAppropriatePosToInsertOrUpdateNode($path, $root);

            if ($findNode !== null){
                $root = $findNode;
                // if found node is a requiredParameter, then the $path is also a required type and it's on the same
                // level, so, we update its settings, e.g /home/name/:in is same as /home/name/:me, `:me` would replace `:in`
                if ($findNode->isRequiredParameter()){
                    $findNode->setRouteName($path);
                  //  $findNode->setFullRoutePath(ltrim(implode('/', $paths), '/'));
                }
                // this is useful if maybe the new path has some new settings
                // setting it below allow for that to reflect
                $node = $findNode;
            } else {
                $node = new RouteNode($path);
                $root->addNode($node);
                $node->setParentNode($root);
                $root = $node;
            }
        }

        return $node;
    }

    /**
     * @param string $url
     * @return TonicsRouterFoundURLMethodsInterface|null
     */
    public function findURL(string $url): ?TonicsRouterFoundURLMethodsInterface
    {
        $params = [];
        $url = '/' . $this->cleanUrl($url); $findNode = null;
        if (key_exists($url, $this->routeNodeTree->staticURLS)){
            $findNode = $this->routeNodeTree->staticURLS[$url];
        } else {
            $urlPaths =  explode('/', trim($url, '/'));
            array_unshift($urlPaths, '/');
            $root = $this->routeNodeTree;
            foreach ($urlPaths as $k => &$path){
                $findNode = $this->routeNodeTree->findNodeByRouteNameOrRequired($path, $root);
                if ($findNode === null){
                    break;
                }
                if ($findNode->isRequiredParameter()){
                    $params[] = $path;
                }
                $root = $findNode;
            }
        }

        $this->setFoundURLNode($findNode);
        $this->setFoundURLRequiredParams($params);
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
                    $url = '/' . $this->cleanUrl(implode('/', $urlPaths));
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

    public function cleanUrl(string $url): string
    {
        ## D preg_replace converts multiple slashes to one.
        ## FILTER_SANITIZE_URL remove illegal chars from the url
        ## rtrim remove slash from the end e.g /name/book/ becomes  /name/book
        return trim(filter_var(preg_replace("#//+#", "\\1/", $url), FILTER_SANITIZE_URL), '/');
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
}