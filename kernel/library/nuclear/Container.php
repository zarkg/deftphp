<?php

namespace nuclear;

use ArrayAccess;
use Closure;
use Exception;
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
class Container implements ArrayAccess
{
    private $services = [];
    private $shared_instances = [];

    // PSR-11
    public function get($name, $params, $shared = false)
    {

        // 如果定义了服务就根据定义的服务生成
        // 没有定义则尝试生成新的实例
        // 如果需要共享的实例则将新实例加入实例列表
    }

    public function resolve($name, $params)
    {

    }

    public function has($id)
    {

    }

    public function set($name, $definition, $shared = false)
    {

    }

    public static function createInstanceByClass($className, $parameters = [])
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

    public static function createInstanceByClosure(Closure $closure, $parameters)
    {

    }

    private static function buildParameters(ReflectionFunctionAbstract $reflect, $parameters)
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
                $parameters[$position] = self::createInstanceByClass($class->name);
            } else {
                throw new Exception("Parameter '" . $arg->getName() . "' not match");
            }
        }

        return $parameters;
    }

    // ArrayAccess
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
    }

    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.
    }

    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
    }

    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
    }

}