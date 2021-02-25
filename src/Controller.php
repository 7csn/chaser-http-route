<?php

namespace chaser\http\route;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
    /**
     * 路由开关数组
     *
     * @var Tap[][] [$method => [...Tap]]
     */
    protected array $taps = [];

    /**
     * 类名
     *
     * @var string
     */
    protected string $classname;

    /**
     * 公共前缀
     *
     * @var string
     */
    protected string $prefix;

    /**
     * 公共开放域名组
     *
     * @var string[]
     */
    protected array $domains = [];

    /**
     * 公共中间件
     *
     * @var array
     */
    protected array $middlewares = [];

    /**
     * 公共条件
     *
     * @var array
     */
    protected array $where = [];

    /**
     * 公共后缀
     *
     * @var string
     */
    protected string $suffix = '';

    /**
     * 缓存时限
     *
     * @var int
     */
    protected int $cacheDuration = 0;

    /**
     * 初始化公共路由前缀
     *
     * @param string|int $prefixOrLevel
     */
    public function __construct(private string|int $prefixOrLevel = 1)
    {
    }

    /**
     * 初始化公共路由组件信息
     *
     * @param string $classname
     * @param array $domains
     * @param array $middlewares
     * @param array $where
     * @param string $suffix
     * @param int $cacheDuration
     */
    public function init(string $classname, array $domains, array $middlewares, array $where, string $suffix, int $cacheDuration)
    {
        $this->prefix = is_int($this->prefixOrLevel)
            ? join('/', array_map('lcfirst', array_slice(explode('\\', $classname), -$this->prefixOrLevel)))
            : $this->prefixOrLevel;
        $this->classname = $classname;
        $this->domains = $domains;
        $this->middlewares = $middlewares;
        $this->where = $where;
        $this->suffix = $suffix;
        $this->cacheDuration = $cacheDuration;
    }

    /**
     * 注册路由资源
     *
     * @param string $name
     * @param Map $map
     * @param string[] $domains
     * @param array $middlewares
     * @param array $where
     * @param string $suffix
     * @param int $cacheDuration
     * @return Tap
     */
    public function action(string $name, Map $map, array $domains, array $middlewares, array $where, string $suffix, int $cacheDuration): Tap
    {
        $point = $this->point($name);

        if (empty($domains) && $domains !== $this->domains) {
            $domains = $this->domains;
        }

        $middlewares = array_merge($this->middlewares, $middlewares);

        $where = array_merge($this->where, $where);

        if ($suffix === '' && $suffix !== $this->suffix) {
            $suffix = $this->suffix;
        }

        if ($cacheDuration === 0 && $cacheDuration !== $this->cacheDuration) {
            $cacheDuration = $this->cacheDuration;
        }

        $this->taps[$name][] = $tap = $map->tap($point, $domains, $middlewares, $where, $suffix, $cacheDuration)->prefix($this->prefix);

        return $tap;
    }

    /**
     * 加载路由资源
     */
    public function loading(): void
    {
        if (!empty($this->taps)) {
            foreach ($this->taps as $taps) {
                foreach ($taps as $tap) {
                    $tap->install();
                }
            }
            $this->taps = [];
        }
    }

    /**
     * 获取指向调用名
     *
     * @param string $method
     * @return string
     */
    protected function point(string $method): string
    {
        return $this->classname . '::' . $method;
    }
}
