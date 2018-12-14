<?php

namespace nuclear;

class Loader
{
    /**
     * 类名到文件名的映射
     * @var array
     */
    private static $classMap = [];

    /**
     * 强制加载的文件列表
     * @var array
     */
    private static $files = [];

    /**
     * 符合PSR-4标准的类
     * @var array
     */
    private static $prefixLengthsPsr4 = [];
    private static $prefixDirsPsr4 = [];
    private static $fallbackDirsPsr4 = [];

    /**
     * 符合PSR-0标准的类
     * @var array
     */
    private static $prefixesPsr0 = [];
    private static $fallbackDirsPsr0 = [];

    /**
     * 查找失败的类列表
     * @var array
     */
    private static $missingClasses = [];

    /**
     * composer安装路径
     * @var string
     */
    private static $composerPath;

    /**
     * 是否使用系统包含目录
     * @var bool
     */
    public static $useIncludePath = false;

    /**
     * 获取框架根目录
     * @return string
     */
    public static function getRootPath()
    {
        /** 根据入口文件的绝对路径定位框架根目录 */
        if ('cli' == PHP_SAPI) {
            $entryFile = $_SERVER['argv'][0];
        } else {
            $entryFile = $_SERVER['SCRIPT_FILENAME'];
        }

        $rootPath = realpath(dirname($entryFile));

        if (!file_exists($rootPath . DIRECTORY_SEPARATOR . 'nuclear')) {
            $rootPath = dirname($rootPath);
        }

        return $rootPath . DIRECTORY_SEPARATOR;
    }

    /**
     * 注册自动加载函数
     * @param string|array $autoload
     * @param bool $prepend
     */
    public static function register($autoload = '', $prepend = true)
    {
        // 注册自动加载函数
        spl_autoload_register($autoload ?: __CLASS__ . '::autoload', true, $prepend);

        // 接管composer自动加载
        self::succeedComposerLoader();

        // 注册系统命名空间
        self::addPsr4('nuclear\\', __DIR__);
    }

    /**
     * 注销自动加载函数
     * @param string|array $autoload
     */
    public static function unregister($autoload = '')
    {
        spl_autoload_unregister($autoload ?: __CLASS__ . '::autoload');
    }

    /**
     * 自动加载方法
     * @param string $class 类名
     */
    public static function autoload($class)
    {
        if ($file = self::findFile($class)) {
            globalInclude($file);
        }
    }

    /**
     * 查找类文件
     * @param string $class 类名
     * @return string
     */
    private static function findFile($class)
    {
        // 查找类映射
        if (isset(self::$classMap[$class])) {
            return self::$classMap[$class];
        }

        // 查找非命中记录
        if (isset(self::$missingClasses[$class])) {
            return false;
        }

        // 查找类文件
        $file = self::findFileWithExtension($class, '.php');
        if (false === $file) {
            self::$missingClasses[$class] = true;
        }

        return $file;
    }

    /**
     * 根据扩展名查找类文件
     * @param string $class 类名
     * @param string $ext 扩展名
     * @return string
     */
    private static function findFileWithExtension($class, $ext = '.php')
    {
        $firstLetter = $class[0];

        // PSR-4
        $logicalPsr4Path = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;

        // 查找PSR-4前缀
        if (isset(self::$prefixLengthsPsr4[$firstLetter])) {
            foreach (self::$prefixLengthsPsr4[$firstLetter] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach (self::$prefixDirsPsr4[$prefix] as $dir) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPsr4Path, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }

        // 查找PSR-4全局匹配目录
        foreach (self::$fallbackDirsPsr4 as $dir) {
            if ($file = $dir . DIRECTORY_SEPARATOR . $logicalPsr4Path) {
                return $file;
            }
        }


        // PSR-0
        if (false !== $pos = strrpos($class, '\\')) {
            // 包含命名空间单独替换类名中的下划线
            $logicalPsr0Path = substr($logicalPsr4Path, 0, $pos + 1) .
                strtr(substr($logicalPsr4Path, $pos + 1), '_', DIRECTORY_SEPARATOR);
        } else {
            $logicalPsr0Path = strtr($logicalPsr4Path, '_', DIRECTORY_SEPARATOR);
        }

        // 查找PSR-0前缀
        if (isset(self::$prefixesPsr0[$firstLetter])) {
            foreach (self::$prefixesPsr0[$firstLetter] as $prefix) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($prefix as $dir) {
                        // PSR-0前缀对应
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . $logicalPsr0Path)) {
                            return $file;
                        }
                    }
                }
            }
        }

        // 查找PSR-0全局目录
        foreach (self::$fallbackDirsPsr0 as $dir) {
            if (is_file($file = $dir . DIRECTORY_SEPARATOR . $logicalPsr0Path)) {
                return $file;
            }
        }

        // 查找全局包含目录
        if (self::$useIncludePath) {
            if (is_file($file = stream_resolve_include_path($logicalPsr4Path))) {
                return $file;
            }
            if (is_file($file = stream_resolve_include_path($logicalPsr0Path))) {
                return $file;
            }
        }

        return false;
    }

    /**
     * 接管composer自动加载机制
     */
    private static function succeedComposerLoader()
    {
        $rootPath = self::getRootPath();

        self::$composerPath = $rootPath . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR;

        if (file_exists(self::$composerPath . 'autoload_static.php')) {
            // 使用静态类加载
            require_once self::$composerPath . 'autoload_static.php';

            // 获取composer静态加载类名
            $classes = get_declared_classes();
            $composerClass = array_pop($classes);
            if (0 !== strpos($composerClass, 'Composer\\Autoload\\ComposerStaticInit')) {
                foreach ($classes as $class) {
                    if (0 === strpos($class, 'Composer\\Autoload\\ComposerStaticInit')) {
                        $composerClass = $class;
                        break;
                    }
                }
            }

            // 合并composer属性
            $properties = ['classMap','files','prefixLengthsPsr4','prefixDirsPsr4','fallbackDirsPsr4','prefixesPsr0','fallbackDirsPsr0'];
            foreach ($properties as $property) {
                if (property_exists($composerClass, $property)) {
                    self::${$property} = $composerClass::${$property};
                }
            }
        } else {
            // 使用分类文件加载
            if (is_file(self::$composerPath . 'autoload_namespaces.php')) {
                // 添加PSR-0映射
                $psr0 = require self::$composerPath . 'autoload_namespaces.php';
                foreach ($psr0 as $namespace => $path) {
                    self::addPsr0($namespace, $path);
                }
            }

            if (is_file(self::$composerPath . 'autoload_psr4.php')) {
                // 添加PSR-4映射
                $psr4 = require self::$composerPath . 'autoload_psr4.php';
                foreach ($psr4 as $namespace => $path) {
                    self::addPsr4($namespace, $path);
                }
            }

            if (is_file(self::$composerPath . 'autoload_classmap.php')) {
                // 添加类文件映射
                $classMap = require self::$composerPath . 'autoload_classmap.php';
                self::addClassMap($classMap);
            }

            if (is_file(self::$composerPath . 'autoload_files.php')) {
                // 添加需要预加载的文件列表
                self::$files = require self::$composerPath . 'autoload_files.php';
            }
        }

        // 载入预加载文件
        foreach (self::$files as $fileIdentifier => $file) {
            if (empty($GLOBALS['__composer_autoload_files'][$fileIdentifier]) && is_file($file)) {
                globalRequire($file);
                $GLOBALS['__composer_autoload_files'][$fileIdentifier] = true;
            }
        }
    }

    /**
     * 添加PSR-0映射或查找目录
     * @param string $prefix 命名空间前缀
     * @param $path
     * @return bool
     */
    public static function addPsr0($prefix, $path)
    {
        // 不提供命名空间则添加fallback dir
        if (!$prefix) {
            self::$fallbackDirsPsr0 = array_merge(self::$fallbackDirsPsr0, (array) $path);
            return true;
        }

        // 提供命名空间则添加prefixes
        $firstLetter = $prefix[0];
        if (!isset(self::$prefixesPsr0[$firstLetter][$prefix])) {
            self::$prefixesPsr0[$firstLetter][$prefix] = [];
        }
        self::$prefixesPsr0[$firstLetter][$prefix] = array_merge(
            self::$prefixesPsr0[$firstLetter][$prefix],
            (array) $path
        );
        return true;
    }

    /**
     * 添加PSR-4映射或查找目录
     * @param string $prefix 命名空间前缀
     * @param $path
     * @return bool
     */
    public static function addPsr4($prefix, $path)
    {
        // 不提供命名空间则添加fallback dir
        if (!$prefix) {
            self::$fallbackDirsPsr4 = array_merge(self::$fallbackDirsPsr4, (array) $path);
            return true;
        }

        // 提供命名空间则处理prefix
        $firstLetter = $prefix[0];
        $prefixLength = strlen($prefix);

        if ('\\' != $prefix[$prefixLength - 1]) {
            throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
        }

        self::$prefixLengthsPsr4[$firstLetter][$prefix] = $prefixLength;

        if (!isset(self::$prefixDirsPsr4[$prefix])) {
            self::$prefixDirsPsr4[$prefix] = [];
        }

        self::$prefixDirsPsr4[$prefix] = array_merge(self::$prefixDirsPsr4[$prefix], (array) $path);
        return true;
    }

    /**
     * 添加类名类文件映射
     * @param string|array $class 类名|类名类文件映射数组
     * @param string $map 类文件
     */
    public static function addClassMap($class, $map = '')
    {
        if (is_array($class)) {
            self::$classMap = array_merge(self::$classMap, $class);
        } else if ($map) {
            self::$classMap[$class] = $map;
        }
    }

    /**
     * 设置是否查找包含路径标识
     * @param bool $useIncludePath
     */
    public static function setUseIncludePath($useIncludePath = false)
    {
        self::$useIncludePath = (bool) $useIncludePath;
    }

    /**
     * 获取是否查找包含路径标识
     * @return bool
     */
    public static function getUseIncludePath()
    {
        return self::$useIncludePath;
    }


}

// 隔离作用域加载文件

function globalRequire($file)
{
    require $file;
}

function globalInclude($file)
{
    include $file;
}