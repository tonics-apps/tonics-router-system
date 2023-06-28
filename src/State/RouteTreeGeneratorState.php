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

namespace Devsrealm\TonicsRouterSystem\State;

use Devsrealm\TonicsRouterSystem\RouteNode;
use Devsrealm\TonicsRouterSystem\RouteTreeGenerator;
use RuntimeException;

class RouteTreeGeneratorState
{
    private static array $routePath = [];
    private static string $routePathFlat = '';

    const TonicsInitialStateHandler = 'TonicsInitialStateHandler';
    const TonicsStaticParameterStateHandler = 'TonicsStaticParameterStateHandler';
    const TonicsRequiredParameterStateHandler = 'TonicsRequiredParameterStateHandler';

    public static function TonicsInitialStateHandler(RouteTreeGenerator $rtg): void
    {
        if ($rtg->firstCharIsSolidus()) {
            self::$routePath[] = $rtg->getCurrentRoutePath();
            ## ROOT NOde
            self::insertNode($rtg)?->setStaticParameter(true);
            return;
        }

        if ($rtg->firstCharIsAscii() || $rtg->firstCharIsAsciiDigit()) {
            $rtg->reconsumeIn(self::TonicsStaticParameterStateHandler);
            return;
        }

        if ($rtg->firstCharIsColumn()) {
            $rtg->reconsumeIn(self::TonicsRequiredParameterStateHandler);
            return;
        }

        $option = $rtg->getCurrentRoutePath()[0];
        throw new RuntimeException("Option `$option` not supported");
    }

    public static function TonicsStaticParameterStateHandler(RouteTreeGenerator $rtg): void
    {
        self::$routePath[] = $rtg->getCurrentRoutePath();
        self::addPathToFlat($rtg->getCurrentRoutePath());
        self::insertNode($rtg)?->setStaticParameter(true);
    }

    public static function TonicsRequiredParameterStateHandler(RouteTreeGenerator $rtg): void
    {
        self::$routePath[] = $rtg->getCurrentRoutePath();
        self::addPathToFlat($rtg->getCurrentRoutePath());
        $node = self::insertNode($rtg);
        if ($node){
            $node->setRequiredParameter(true)->parentNode()->setPositionOfLastAddedRequiredParamChildNode($node->getRouteName());
            $rtg->setIsStatic(false);
        }
    }

    /**
     * @param RouteTreeGenerator $rtg
     * @return RouteNode|null
     */
    private static function insertNode(RouteTreeGenerator $rtg): ?RouteNode
    {

       $rtg->switchRouteTreeGeneratorState(self::TonicsInitialStateHandler);
        $resNode = $rtg->insertNodeInAppropriatePosition([$rtg->getCurrentRoutePath()], $rtg->getLastAddedParentRouteNode() ?? $rtg->getRouteNodeTree());
        if ($resNode !== null) {
            $rtg->setLastAddedRouteNode($resNode);
        }
        return $resNode;
    }

    /**
     * @return array
     */
    public static function getRoutePath(): array
    {
        return self::$routePath;
    }

    /**
     * @param array $routePath
     */
    public static function setRoutePath(array $routePath): void
    {
        self::$routePath = $routePath;
    }

    public static function addPathToFlat(string $path): void
    {
        self::$routePathFlat .= '/' . $path;
    }

    /**
     * @return string
     */
    public static function getRoutePathFlat(): string
    {
        return self::$routePathFlat;
    }

    /**
     * @param string $routePathFlat
     */
    public static function setRoutePathFlat(string $routePathFlat): void
    {
        self::$routePathFlat = $routePathFlat;
    }
}