<?php

declare(strict_types=1);

namespace chaser\http\route;

use Closure;

/**
 * 路由开关
 *
 * @package chaser\http\route
 */
class Tap
{
    /**
     * 路由正则条件
     *
     * @var array [参数名 => 正则]
     */
    private array $where = [];

    /**
     * 路径前缀列表
     *
     * @var string[] [... 前缀路径]
     */
    private array $prefixes = [];

    /**
     * 路径后缀
     *
     * @var string
     */
    private string $suffix = '';

    /**
     * 开放域名：空为不限制
     *
     * @var string[]
     */
    private array $domains = [];

    /**
     * 中间件
     *
     * @var array
     */
    private array $middlewares = [];

    /**
     * 路由缓存时间
     *
     * @var int
     */
    private int $cacheDuration = 0;

    /**
     * 实例化开关：请求方法组、路径规则、回调结构
     *
     * @param string[] $methods
     * @param string $rule
     * @param Closure|string $point
     */
    public function __construct(private array $methods, private string $rule, private Closure|string $point)
    {
        $this->rule = trim($rule, '/');
    }

    /**
     * 添加路由规则
     *
     * @param array $where
     * @return $this
     */
    public function where(array $where): self
    {
        foreach ($where as $name => $preg) {
            $this->where[$name] = $preg;
        }
        return $this;
    }

    /**
     * 添加路径前缀
     *
     * @param string $prefix
     * @return $this
     */
    public function prefix(string $prefix): self
    {
        $this->prefixes[] = trim($prefix, '/');
        return $this;
    }

    /**
     * 设置路径后缀
     *
     * @param string $suffix
     * @return $this
     */
    public function suffix(string $suffix): self
    {
        $this->suffix = $suffix;
        return $this;
    }

    /**
     * 添加域名配置
     *
     * @param string ...$domains
     * @return $this
     */
    public function domains(string ...$domains): self
    {
        array_push($this->domains, ...$domains);
        return $this;
    }

    /**
     * 添加中间件配置
     *
     * @param array $middlewares
     * @return $this
     */
    public function middlewares(array $middlewares): self
    {
        foreach ($middlewares as $key => $value) {
            if (is_numeric($key)) {
                $name = $value;
                $values = [];
            } else {
                $name = $key;
                $values = explode(',', $value);
            }
            $this->middlewares[$name] = $values;
        }
        return $this;
    }

    /**
     * 指定路由缓存时间
     *
     * @param int $duration
     * @return $this
     */
    public function cache(int $duration): self
    {
        $this->cacheDuration = $duration;
        return $this;
    }

    /**
     * 注册路由开关
     */
    public function install(): void
    {
        $route = new Route($this->completeRule(), $this->point, $this->where, $this->middlewares, $this->cacheDuration);
        Route::addBatch($route, $this->methods ?: null, $this->domains ?: null);
    }

    /**
     * 获取完整路径规则
     *
     * @return string
     */
    private function completeRule(): string
    {
        $paths = array_filter(array_merge($this->prefixes, [$this->rule, $this->suffix]));
        return join('/', $paths);
    }
}
