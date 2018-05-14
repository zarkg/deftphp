<?php

/**
 * 平台控制器
 */
class PlatformController extends Controller
{
    // 构造方法
    public function __construct()
    {
        // 调用父类构造方法
        parent::__construct();
        $this->_initSession();
        $this->_isLogin();
    }

    // 开启session机制
    protected function _initSession() {
        if (!isset($_SESSION)) {
            new SessionDB();
        }
    }

    // 验证登陆状态
    protected function _isLogin() {
        // 忽略验证列表
        $no_check_list = array(
            'Login' => array('login', 'check', 'captcha'),
        );
        if (
            array_key_exists(CONTROLLER, $no_check_list)
            && in_array(ACTION, $no_check_list[CONTROLLER])
        ) {
            // 在忽略列表中就跳过验证
            return;
        }
        if (!isset($_SESSION['admin'])) {
            $this->_jump('index.php?p=admin&c=login&a=login');
        }
    }
}