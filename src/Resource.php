<?php

namespace chaser\http\route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Resource extends Controller
{
    /**
     * 资源配置
     */
    private const RESTFUL = [
        'index' => ['', ['GET']],
        'create' => ['create', ['GET']],
        'store' => ['', ['POST']],
        'show' => ['{id}', ['GET']],
        'edit' => ['{id}/edit', ['GET']],
        'update' => ['{id}', ['PUT', 'PATCH']],
        'destroy' => ['{id}', ['DELETE']]
    ];

    /**
     * 注册路由资源
     *
     * @param string $name
     * @param string[] $domains
     * @param string[] $middlewares
     * @param array $where
     * @param string $suffix
     * @param int $cacheDuration
     */
    public function register(
        string $name,
        array $domains = [],
        array $middlewares = [],
        array $where = [],
        string $suffix = '',
        int $cacheDuration = 0
    ): void
    {
        if (isset(self::RESTFUL[$name])) {

            $map = new Map(...self::RESTFUL[$name]);

            $tap = $this->action($name, $map, $domains, $middlewares, $where, $suffix, $cacheDuration);

            if (in_array($name, ['show', 'edit', 'update', 'destroy'])) {
                $tap->where(['id' => '[1-9]\d*']);
            }
        }
    }
}

