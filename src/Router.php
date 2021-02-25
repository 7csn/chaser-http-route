<?php

declare(strict_types=1);

namespace chaser\http\route;

use chaser\http\route\component\{Cache, Domains, Middlewares, Suffix, Where};
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

/**
 * 路由器
 *
 * @package chaser\http\route
 */
class Router
{
    /**
     * 注册类记录
     *
     * @var true[] [$classname => true]
     */
    private static array $installClasses = [];

    /**
     * 注册方法记录
     *
     * @var true[] [$functionName => true]
     */
    private static array $installFunctions = [];

    /**
     * 类路由组件注解信息
     *
     * @var array[]
     */
    private static array $classComponents = [];

    /**
     * 类方法路由组件注解信息
     *
     * @var array[][]
     */
    private static array $methodComponents = [];

    /**
     * 加载类注解路由
     *
     * @param ReflectionClass $class
     */
    public static function installClass(ReflectionClass $class): void
    {
        if (isset(self::$installClasses[$class->name])) {
            return;
        }

        self::$installClasses[$class->name] = true;

        $components = self::getClassComponents($class);

        $methods = $class->getMethods(ReflectionMethod::IS_PUBLIC);

        // 安装资源路由
        $attributes = $class->getAttributes(Resource::class);
        if (!empty($attributes)) {
            $resource = new Resource(...$attributes[0]->getArguments());
            $resource->init($class->name, ...$components);
            foreach ($methods as $method) {
                $resource->register($method->name, ...self::getMethodComponents($method));
            }
            $resource->loading();
        }

        // 安装控制器路由
        $attributes = $class->getAttributes(Controller::class);
        if (!empty($attributes)) {
            $controller = new Controller(...$attributes[0]->getArguments());
            $controller->init($class->name, ...$components);
            foreach ($methods as $method) {
                $mapAttributes = $method->getAttributes(Map::class);
                if (empty($mapAttributes)) {
                    $map = new Map($method->name);
                    $controller->action($method->name, $map, ...self::getMethodComponents($method));
                } else {
                    foreach ($mapAttributes as $mapAttribute) {
                        $map = new Map(...$mapAttribute->getArguments());
                        $controller->action($method->name, $map, ...self::getMethodComponents($method));
                    }
                }
            }
            $controller->loading();
        }

        unset(self::$classComponents[$class->name], self::$methodComponents[$class->name]);
    }

    /**
     * 加载函数注解路由
     *
     * @param ReflectionFunction $function
     */
    public static function installFunction(ReflectionFunction $function): void
    {
        $name = $function->name;

        $anonymous = $name === '{closure}';

        if ($anonymous || !isset(self::$installFunctions[$name])) {

            if (!$anonymous) {
                self::$installFunctions[$name] = true;
            }

            $point = $anonymous ? $function->getClosure() : $name;

            $components = self::getComponents($function);

            $mapAttributes = $function->getAttributes(Map::class);

            if (!empty($mapAttributes)) {
                foreach ($mapAttributes as $mapAttribute) {
                    $mapAttribute->newInstance()->tap($point, ...$components)->install();
                }
            } elseif (!$anonymous) {
                (new Map($name))->tap($point, ...$components)->install();
            }
        }
    }

    /**
     * 提取类路由组件注解信息
     *
     * @param ReflectionClass $reflection
     * @return array
     */
    public static function getClassComponents(ReflectionClass $reflection): array
    {
        return self::$classComponents[$reflection->name] ??= self::getComponents($reflection);
    }

    /**
     * 提取类方法路由组件注解信息
     *
     * @param ReflectionMethod $reflection
     * @return array
     */
    public static function getMethodComponents(ReflectionMethod $reflection): array
    {
        return self::$methodComponents[$reflection->class][$reflection->name] ??= self::getComponents($reflection);
    }

    /**
     * 获取组件信息
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @return array
     */
    public static function getComponents(ReflectionClass|ReflectionMethod|ReflectionFunction $reflection): array
    {
        $domains = self::getDomains($reflection);
        $middlewares = self::getMiddlewares($reflection);
        $where = self::getWhere($reflection);
        $suffix = self::getSuffix($reflection);
        $cacheDuration = self::getCacheDuration($reflection);
        return [$domains, $middlewares, $where, $suffix, $cacheDuration];
    }

    /**
     * 提取开放域名组
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @return string[]
     */
    public static function getDomains(ReflectionClass|ReflectionMethod|ReflectionFunction $reflection): array
    {
        return self::getComponentValue($reflection, Domains::class, 'domains', []);
    }

    /**
     * 提取中间件组
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @return string[]
     */
    public static function getMiddlewares(ReflectionClass|ReflectionMethod|ReflectionFunction $reflection): array
    {
        return self::getComponentValue($reflection, Middlewares::class, 'middlewares', []);
    }

    /**
     * 提取条件
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @return array
     */
    public static function getWhere(ReflectionClass|ReflectionMethod|ReflectionFunction $reflection): array
    {
        return self::getComponentValue($reflection, Where::class, 'where', []);
    }

    /**
     * 提取后缀
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @return string
     */
    public static function getSuffix(ReflectionClass|ReflectionMethod|ReflectionFunction $reflection): string
    {
        return self::getComponentValue($reflection, Suffix::class, 'suffix', '');
    }

    /**
     * 提取缓存时限
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @return int
     */
    public static function getCacheDuration(ReflectionClass|ReflectionMethod|ReflectionFunction $reflection): int
    {
        return self::getComponentValue($reflection, Cache::class, 'duration', 0);
    }

    /**
     * 提取组件数据
     *
     * @param ReflectionClass|ReflectionMethod|ReflectionFunction $reflection
     * @param string $attribute
     * @param string $method
     * @param mixed $default
     * @return mixed
     */
    private static function getComponentValue(
        ReflectionClass|ReflectionMethod|ReflectionFunction $reflection,
        string $attribute,
        string $method,
        mixed $default
    ): mixed
    {
        $attributes = $reflection->getAttributes($attribute);
        return empty($attributes) ? $default : $attributes[0]->newInstance()->{$method}();
    }
}
