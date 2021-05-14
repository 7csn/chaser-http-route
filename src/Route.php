<?php

declare(strict_types=1);

namespace chaser\http\route;

use Closure;

/**
 * 路由
 *
 * @package chaser\http\route
 */
class Route
{
    /**
     * 路由开关数组
     *
     * @var Route[][][] [$domain => [$method => [$rule => Route]]]
     */
    private static array $taps = [];

    /**
     * 路由正则
     *
     * @var string
     */
    private string $pattern;

    /**
     * 可选参数前缀分隔符对照表
     *
     * @var array [$name => $valuePrefix]
     */
    private array $prefixes = [];

    /**
     * 获取路由开关数组
     *
     * @return Route[][][]
     */
    public static function taps(): array
    {
        return self::$taps;
    }

    /**
     * 清空路由开关数组
     */
    public static function clear(): void
    {
        self::$taps = [];
    }

    /**
     * 检索路由
     *
     * @param string $path
     * @param string $method
     * @param string $domain
     * @return array|null
     */
    public static function search(string $path, string $method = '*', string $domain = '*'): ?array
    {
        $path = trim($path, '/');

        $methods = $method === '*' ? ['*'] : [$method, '*'];
        $domains = $domain === '*' ? ['*'] : [$domain, '*'];

        $search = null;
        foreach ($domains as $domain) {
            foreach ($methods as $method) {
                $search = self::gain($path, $method, $domain);
                if ($search) {
                    break 2;
                }
            }
        }

        return $search;
    }

    /**
     * 转义分隔符
     *
     * @param string $rule
     * @return mixed
     */
    public static function quite(string $rule): string
    {
        return str_replace(['/', '-'], ['\/', '\-'], $rule);
    }

    /**
     * 定义路由：GET 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function get(string $rule, Closure|string $point): Tap
    {
        return new Tap(['GET'], $rule, $point);
    }

    /**
     * 定义路由：POST 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function post(string $rule, Closure|string $point): Tap
    {
        return new Tap(['POST'], $rule, $point);
    }

    /**
     * 定义路由：PUT 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function put(string $rule, Closure|string $point): Tap
    {
        return new Tap(['PUT'], $rule, $point);
    }

    /**
     * 定义路由：PATCH 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function patch(string $rule, Closure|string $point): Tap
    {
        return new Tap(['PATCH'], $rule, $point);
    }

    /**
     * 定义路由：DELETE 方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function delete(string $rule, Closure|string $point): Tap
    {
        return new Tap(['DELETE'], $rule, $point);
    }

    /**
     * 定义路由：不限方法
     *
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function any(string $rule, Closure|string $point): Tap
    {
        return new Tap(['*'], $rule, $point);
    }

    /**
     * 定义路由：方法组
     *
     * @param string[] $methods
     * @param string $rule
     * @param Closure|string $point
     * @return Tap
     */
    public static function many(array $methods, string $rule, Closure|string $point): Tap
    {
        return new Tap(array_map('strtoupper', $methods), $rule, $point);
    }

    /**
     * 添加路由开关
     *
     * @param Route $route
     * @param string $method
     * @param string $domain
     */
    public static function add(self $route, string $method = '*', string $domain = '*'): void
    {
        self::$taps[$domain][$method][$route->rule] = $route;
    }

    /**
     * 批量添加路由开关
     *
     * @param Route $route
     * @param string[]|null $methods
     * @param string[]|null $domains
     */
    public static function addBatch(self $route, array $methods = null, array $domains = null): void
    {
        if ($methods !== null) {
            $methods = ['*'];
        }

        if ($domains !== null) {
            $domains = ['*'];
        }

        foreach ($domains ?: ['*'] as $domain) {
            foreach ($methods as $method) {
                self::add($route, $method, $domain);
            }
        }
    }

    /**
     * 实例化路由对象
     *
     * @param string $rule
     * @param Closure|string $point
     * @param array $where
     * @param array $middleware
     * @param int $cacheDuration
     */
    public function __construct(private string $rule, private Closure|string $point, array $where = [], private array $middleware = [], private int $cacheDuration = 0)
    {
        $this->makePattern($where);
    }

    /**
     * 路由匹配则返回参数列表
     *
     * @param string $path
     * @return array|null
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->pattern, $path, $matches)) {
            $args = [];
            foreach ($matches as $name => $value) {
                if (!is_numeric($name)) {
                    $args[$name] = $this->value($value, $name);
                }
            }
            return $args;
        }

        return null;
    }

    /**
     * 返回路由回调
     *
     * @return Closure|string
     */
    public function getPoint(): Closure|string
    {
        return $this->point;
    }

    /**
     * 字符串化：路由规则
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->rule;
    }

    /**
     * 获取路由
     *
     * @param string $path
     * @param string $method
     * @param string $domain
     * @return array|null
     */
    private static function gain(string $path, string $method, string $domain): ?array
    {
        $gain = null;
        if (isset(self::$taps[$domain][$method])) {
            foreach (self::$taps[$domain][$method] as $rule => $route) {
                $args = $route->match($path);
                if ($args !== null) {
                    $gain = ['point' => $route->getPoint(), 'args' => $args];
                    break;
                }
            }
        }
        return $gain;
    }

    /**
     * 生成路由正则
     *
     * @param array $where
     */
    private function makePattern(array $where)
    {
        $this->pattern = self::quite($this->rule);

        array_walk($where, function ($preg, $name) {
            $this->replace($name, $preg);
        });

        if (preg_match_all('/{(\w+)\??}/', $this->pattern, $match)) {
            array_map([$this, 'replace'], $match[1]);
        }

        $this->pattern = "/^{$this->pattern}$/";
    }

    /**
     * 参数处理
     *
     * @param string $name
     * @param string $preg
     */
    private function replace(string $name, string $preg = '\w+')
    {
        if (str_contains($this->pattern, '\/{' . $name . '?}')) {
            $this->pattern = str_replace('\/{' . $name . '?}', '(?<' . $name . '>(\/' . $preg . ')?)', $this->pattern);
            $this->prefixes[$name] = '/';
        } elseif (str_contains($this->pattern, '\-{' . $name . '?}')) {
            $this->pattern = str_replace('\-{' . $name . '?}', '(?<' . $name . '>(\-' . $preg . ')?)', $this->pattern);
            $this->prefixes[$name] = '-';
        } elseif (str_contains($this->pattern, '{' . $name . '?}')) {
            $this->pattern = str_replace('{' . $name . '?}', '(?<' . $name . '>(' . $preg . ')?)', $this->pattern);
        } else {
            $this->pattern = str_replace('{' . $name . '}', '(?<' . $name . '>' . $preg . ')', $this->pattern);
        }
    }

    /**
     * 处理匹配参数的值
     *
     * @param string $value
     * @param string $name
     * @return string
     */
    private function value(string $value, string $name): string
    {
        return isset($this->prefixes[$name]) ? ltrim($value, $this->prefixes[$name]) : $value;
    }
}
