<?php

namespace nuclear\Container;

use Closure;
use Exception;
use nuclear\Container;
use nuclear\Factory;

/**
 * 容器服务单元类
 *
 * @package nuclear
 * @author zarkg <admin@zarkg.com>
 */
class Service
{
    /**
     * @var string 服务名称
     */
    protected $_name;

    /**
     * @var mixed 服务定义
     */
    protected $_definition;

    /**
     * @var bool 是否共享
     */
    protected $_shared = false;

    /**
     * @var bool 是否已解析为对象
     */
    protected $_resolved = false;

    /**
     * @var object 共享实例
     */
    protected $_sharedInstance;

    /**
     * Service constructor.
     * @param string $name
     * @param mixed $definition
     * @param bool $shared
     */
    public final function __construct($name, $definition, $shared = false)
    {
        $this->_name = $name;
        $this->_definition = $definition;
        $this->_shared = $shared;
    }

    /**
     * 获取服务名
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * 设置服务是否共享
     * @param $shared
     */
    public function setShared($shared)
    {
        $this->_shared = (bool)$shared;
    }

    /**
     * 判断服务是否共享
     * @return bool
     */
    public function isShared()
    {
        return $this->_shared;
    }

    /**
     * 设置服务的共享实例
     * @param object $sharedInstance
     */
    public function setSharedInstance($sharedInstance)
    {
        $this->_sharedInstance = $sharedInstance;
    }

    /**
     * 设置服务定义
     * @param mixed $definition
     */
    public function setDefinition($definition)
    {
        $this->_definition = $definition;
    }

    /**
     * 获取服务定义
     * @return mixed
     */
    public function getDefinition()
    {
        return $this->_definition;
    }

    /**
     * 判断是否已解析为对象
     * @return bool
     */
    public function isResolved()
    {
        return $this->_resolved;
    }

    /**
     * 为数组定义设置指定位置的实例化参数
     * @param int $position
     * @param mixed $parameter
     * @return $this
     * @throws Exception
     */
    public function setParameter($position, $parameter)
    {
        $definition = $this->_definition;
        if (!is_array($definition)) {
            throw new Exception("Definition must be an array to update its parameters");
        }

        /**
         * 更新参数
         */
        if (isset($definition['arguments'])) {
            $definition['arguments'][$position] = $parameter;
        } else {
            $definition['arguments'] = [$position => $parameter];
        }

        $this->_definition = $definition;

        return $this;
    }

    /**
     * 获取数组定义指定位置的实例化参数
     * @param int $position
     * @return mixed|null
     * @throws Exception
     */
    public function getParameter($position)
    {
        $definition = $this->_definition;
        if (!is_array($definition)) {
            throw new Exception("Definition must be an array to obtain its parameters");
        }

        if (isset($definition['arguments'][$position])) {
            return $definition['arguments'][$position];
        }

        return null;
    }

    public function resolve($parameters = null, Container $dependency = null)
    {
        $definition = $this->_definition;
        $shared = $this->_shared;

        $instance = null;

        /**
         * 如果是共享服务判断是否存在共享实例
         */
        if ($shared) {
            $sharedInstance = $this->_sharedInstance;
            if ($sharedInstance) {
                return $sharedInstance;
            }
        }

        if (is_string($definition) && class_exists($definition)) {
            /**
             * 定义为类名
             */
            $instance = Container::createInstanceByClass($definition, $parameters);

        } else if (is_object($definition)) {
            /**
             * 定义为闭包对象或实例对象
             */
            if ($definition instanceof Closure) {
                /**
                 * 判断是否需要将闭包绑定到依赖对象作用域
                 */
                if (is_object($dependency)) {
                    $definition = Closure::bind($definition, $dependency);
                }

                $instance = Container::createInstanceByClosure($definition, $parameters);
            } else {
                /**
                 * 定义已经是实例对象
                 */
                $instance = $definition;
            }
        } else if (is_array($definition)) {
            $instance = Container::createInstanceByArray($definition, $parameters, $dependency);
        }

        /**
         * 获取实例失败抛出异常
         */
        if (!$instance) {
            throw new Exception("Service '" . $this->_name . "' cannot be resolved");
        }

        /**
         * 判断是共享服务则更新共享实例
         */
        if ($shared) {
            $this->_sharedInstance = $instance;
        }

        $this->_resolved = true;

        return $instance;
    }
}