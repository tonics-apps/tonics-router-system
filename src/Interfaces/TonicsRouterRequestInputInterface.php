<?php

namespace Devsrealm\TonicsRouterSystem\Interfaces;

interface TonicsRouterRequestInputInterface
{
    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromPost(array $data = []): TonicsRouterRequestInputMethodsInterface;

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromGet(array $data = []) : TonicsRouterRequestInputMethodsInterface;

    /**
     * @param array $data
     * @return TonicsRouterRequestInputMethodsInterface
     */
    public function fromFile(array $data = []) : TonicsRouterRequestInputMethodsInterface;
}