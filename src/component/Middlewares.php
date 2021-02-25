<?php

namespace chaser\http\route\component;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
class Middlewares
{
    /**
     * 初始化中间件组
     *
     * @param array $middlewares
     */
    public function __construct(private array $middlewares)
    {
    }

    /**
     * 获取中间件组
     *
     * @return array
     */
    public function middlewares(): array
    {
        return $this->middlewares;
    }
}
