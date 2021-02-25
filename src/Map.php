<?php

declare(strict_types=1);

namespace chaser\http\route;

use Attribute;
use Closure;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION)]
class Map
{
    /**
     * 初始化路由规则、开放方法组
     *
     * @param string $rule
     * @param string[] $methods
     */
    public function __construct(private string $rule, private array $methods = ['GET', 'POST'])
    {
    }

    /**
     * 获取路由开关
     *
     * @param Closure|string $point
     * @param string[] $domains
     * @param array $middlewares
     * @param array $where
     * @param string $suffix
     * @param int $cacheDuration
     * @return Tap
     */
    public function tap(Closure|string $point, array $domains, array $middlewares, array $where, string $suffix, int $cacheDuration): Tap
    {
        $tap = Route::many($this->methods, $this->rule, $point);

        if (!empty($domains)) {
            $tap->domains(...$domains);
        }

        if (!empty($middlewares)) {
            $tap->middlewares($middlewares);
        }

        if (!empty($where)) {
            $tap->where($where);
        }

        if ($suffix !== '') {
            $tap->suffix($suffix);
        }

        if ($cacheDuration !== 0) {
            $tap->cache($cacheDuration);
        }

        return $tap;
    }
}
