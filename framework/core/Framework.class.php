<?php
// 核心框架类
class Framework {
	// 启动项目
	public static function run() {
        self::init();
        self::config();
		self::autoload();
		self::router();
	}

	// 初始化方法
	public static function init() {
		// 初始化路径常量
		define('DS', DIRECTORY_SEPARATOR);
		define('ROOT', getcwd() . DS);
		define('FRAMEWORK_PATH', ROOT . 'framework' . DS);
		define('APPLICATION_PATH', ROOT . 'application' . DS);
		define('PUBLIC_PATH', ROOT . 'public' . DS);
		define('MODEL_PATH', APPLICATION_PATH . 'models' . DS);
		define('VIEW_PATH', APPLICATION_PATH . 'views' . DS);
		define('CONTROLLER_PATH', APPLICATION_PATH . 'controllers' . DS);
        define('CONFIG_PATH', APPLICATION_PATH . 'config' . DS);
		define('CORE_PATH', FRAMEWORK_PATH . 'core' . DS);
		define('DB_PATH', FRAMEWORK_PATH . 'database' . DS);
		define('HELPER_PATH', FRAMEWORK_PATH . 'helpers' . DS);
		define('LIB_PATH', FRAMEWORK_PATH . 'libraries' . DS);
		define('SMARTY_PATH', FRAMEWORK_PATH . 'smarty' . DS);
		define('COMPILE_PATH', APPLICATION_PATH . 'compile' . DS);
        define('UPLOAD_PATH', PUBLIC_PATH . 'uploads' . DS);
		// 确定分发参数
		define('PLATFORM', isset($_REQUEST['p']) ? $_REQUEST['p'] : 'home');
		define('CONTROLLER', isset($_REQUEST['c']) ? ucfirst($_REQUEST['c']) : 'Index');
		define('ACTION', isset($_REQUEST['a']) ? $_REQUEST['a'] : 'index');
		// 初始化当前请求相关路径常量
		define('CUR_CONTROLLER_PATH', CONTROLLER_PATH . PLATFORM . DS);
		define('CUR_VIEW_PATH', VIEW_PATH . PLATFORM . DS);
		define('CUR_COMPILE_PATH', COMPILE_PATH . PLATFORM . DS);

	}

    // 初始化配置
    public static function config() {
        $GLOBALS['config'] = require CONFIG_PATH . 'config.php';
    }

	// 路由方法
	public static function router() {
		// 确定类名和方法名
		$controller_name = CONTROLLER . 'Controller';
		$action_name = ACTION . 'Action';
		// 实例化控制器对象并调用控制器动作
		$controller = new $controller_name;
		$controller->$action_name();
	}

	// 注册自动加载方法
	public static function autoload() {
		spl_autoload_register(array(__CLASS__, 'load'));
	}

	// 自动加载方法
	public static function load($class) {
        // 框架核心类列表
        $framework_classes = array(
            'Controller'    => CORE_PATH . 'Controller.class.php',
            'PlatformController' => CORE_PATH . 'PlatformController.class.php',
            'Factory'       => CORE_PATH . 'Factory.class.php',
            'Model'         => CORE_PATH . 'Model.class.php',
            'Dao'           => DB_PATH . 'Dao.interface.php',
            'Mysql'         => DB_PATH . 'Mysql.class.php',
            'Pdodb'         => DB_PATH . 'Pdodb.class.php',
            'Captcha'       => LIB_PATH . 'Captcha.class.php',
            'Image'         => LIB_PATH . 'Image.class.php',
            'Upload'        => LIB_PATH . 'Upload.class.php',
            'SessionDB'     => LIB_PATH . 'SessionDB.class.php',
            'Page'          => LIB_PATH . 'Page.class.php',
            'Smarty'        => SMARTY_PATH . 'Smarty.class.php',
        );
        // 自动加载框架核心类
        if (array_key_exists($class, $framework_classes)) {
            require $framework_classes[$class];
        }
        // 自动加载项目控制器和模型类
		else if (substr($class, -10) == 'Controller') {
			require CUR_CONTROLLER_PATH . $class . '.class.php';
		} elseif (substr($class, -5) == 'Model') {
			require MODEL_PATH . $class . '.class.php';
		}
	}
}