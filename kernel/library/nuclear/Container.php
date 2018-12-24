<?php

namespace nuclear;

use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionException;
use nuclear\Container\Service;

/**
 * 容器类
 *
 * @package nuclear
 * @author zarkg <admin@zarkg.com>
 */
class Container
{
    /**
     * @var array 已注册的服务对象列表
     */
    private $services = [];

    /**
     * @var object 当前容器类的实例对象
     */
    private static $container;

    /**
     * 注册服务
     * @param string $name 服务名
     * @param mixed $definition 服务定义
     * @param bool $shared 是否注册为共享服务
     * @return Service
     */
    public static function set($name, $definition, $shared = true)
    {
        $service = new Service($name, $definition, $shared);
        self::getContainer()->services[$name] = $service;
        return $service;
    }

    /**
     * 移除已注册的服务
     * @param string $name
     */
    public static function remove($name)
    {
        unset(self::getContainer()->services[$name]);
    }

    /**
     * 获取服务描述的对象
     * @param string $name 服务名
     * @param array $params 实例化参数
     * @return mixed
     */
    public static function get($name, $params = [])
    {
        $container = self::getContainer();

        if (!isset($container->services[$name])) {
            $container->services[$name] = new Service($name, $name, false);
        }

        return $container->services[$name]->resolve($params);
    }

    /**
     * 判断服务是否已注册
     * @param string $name 服务名
     * @return bool
     */
    public static function has($name)
    {
        return isset(self::getContainer()->services[$name]);
    }

    /**
     * 获取当前容器类的对象
     * @return Container|object
     */
    private static function getContainer()
    {
        if (!self::$container) {
            self::$container = new self();
        }

        return self::$container;
    }

    /**
     * 创建指定类的对象
     * @param $className
     * @param array $parameters
     * @return bool|object
     */
    public static function createInstance($className, $parameters = [])
    {
        try {
            $reflect = new ReflectionClass($className);
            $constructor = $reflect->getConstructor();

            $args = $constructor ? self::buildParameters($constructor, $parameters) : [];

            return $reflect->newInstanceArgs($args);
        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * 执行函数
     * @param callable $function
     * @param array $parameters
     * @return bool|object
     */
    public static function invokeFunction(callable $function, $parameters = [])
    {
        try {
            if (!is_callable($function)) {
                return false;
            }

            $reflect = new ReflectionFunction($function);
            $parameters = self::buildParameters($reflect, $parameters);

            if ($parameters) {
                return $reflect->invokeArgs($parameters);
            } else {
                return $reflect->invoke();
            }

        } catch (ReflectionException $e) {
            return false;
        }
    }

    /**
     * 构造函数或方法参数
     * @param ReflectionFunctionAbstract $reflect
     * @param array $parameters
     * @return array
     * @throws ReflectionException
     */
    private static function buildParameters(ReflectionFunctionAbstract $reflect, $parameters = [])
    {
        $number = $reflect->getNumberOfParameters();
        if (0 == $number) {
            /**
             * 不需要参数
             */
            return [];
        }

        $requireParameters = $reflect->getParameters();

        for ($position = 0; $position < $number; $position++) {
            $arg = $requireParameters[$position];
            $class = $arg->getClass();
            $isOptional = $arg->isOptional();

            if (isset($parameters[$position])) {
                continue;
            } elseif ($isOptional) {
                $parameters[$position] = $arg->getDefaultValue();
            } elseif ($class) {
                $parameters[$position] = self::createInstance($class->name);
            } else {
                throw new ReflectionException("Parameter '" . $arg->getName() . "' not match");
            }
        }

        return $parameters;
    }

    /**
     * 合并参数
     * @param array $parameters
     * @param array $reference
     * @return mixed
     */
    public static function mergeParameters($parameters, $reference)
    {
        $count = count($parameters) > count($reference) ? count($parameters) : count($reference);

        for ($i = 0; $i < $count; $i++) {
            if (!isset($parameters[$i]) && isset($reference[$i])) {
                $parameters[$i] = $reference[$i];
            }
        }

        return $parameters;
    }
}