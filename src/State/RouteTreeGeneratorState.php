<?php

namespace Devsrealm\TonicsRouterSystem\State;

use Devsrealm\TonicsRouterSystem\RouteNode;
use Devsrealm\TonicsRouterSystem\RouteTreeGenerator;
use RuntimeException;

class RouteTreeGeneratorState
{
    private static array $routePath = [];

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
        self::insertNode($rtg)?->setStaticParameter(true);
    }

    public static function TonicsRequiredParameterStateHandler(RouteTreeGenerator $rtg): void
    {
        self::$routePath[] = $rtg->getCurrentRoutePath();
        self::insertNode($rtg)?->setRequiredParameter(true);
        $rtg->setIsStatic(false);
    }

    /**
     * @param RouteTreeGenerator $rtg
     * @return RouteNode|null
     */
    private static function insertNode(RouteTreeGenerator $rtg): ?RouteNode
    {
        $rtg->switchRouteTreeGeneratorState(self::TonicsInitialStateHandler);
        $resNode = $rtg->insertNodeInAppropriatePosition(self::$routePath, $rtg->getRouteNodeTree());
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
}