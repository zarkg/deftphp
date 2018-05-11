<?php

/**
 * 基础控制器类
 */
class Controller
{
    // 公共的Smarty对象
    protected $_smarty;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->_initContentType();
        $this->_initSmarty();
    }

    /**
     * 初始化Smarty
     */
    protected function _initSmarty()
    {
        $this->_smarty = new Smarty();
        $this->_smarty->setTemplateDir(CUR_VIEW_PATH);
        $this->_smarty->setCompileDir(CUR_COMPILE_PATH);
    }

    /**
     * 初始化Content-Type
     */
    protected function _initContentType()
    {
        header("Content-Type: text/html; charset=utf-8");
    }

    /**
     * 跳转
     * @param $url string 目标url
     * @param $info string 提示信息
     * @param $wait int 等待时间 (单位秒)
     */
    protected function _jump($url, $info = null, $wait = 3) {
        if (is_null($info)) {
            // 立即跳转
            header("Location: $url");
        } else {
            // 提示跳转
            header("Refresh: $wait; URL=$url");
            echo $info;
        }
        // 终止当前脚本
        exit();
    }

    /**
     * 获取post数据
     * @param $field string
     * @return mixed
     */
    protected function _post($field) {
        $data = $_POST[$field];
        // 滤掉空白字符
        $trimed_data = is_array($data) ? array_map('trim', $data) : trim($data);
        if (false !== strpos('id', $field)) {
            // 自动转换为整型
            $trimed_data = $this->_id2Int($trimed_data);
        }
        // 实体转义
        $trimed_data = $this->_entities($trimed_data);
        return $trimed_data;
    }

    /**
     * 获取get数据
     * @param $field string
     * @return mixed
     */
    protected function _get($field) {
        $data = $_GET[$field];
        // 滤掉空白字符
        $trimed_data = is_array($data) ? array_map('trim', $data) : trim($data);
        if (false !== strpos('id', $field)) {
            // 自动转换为整型
            $trimed_data = $this->_id2Int($trimed_data);
        }
        // 实体转义
        $trimed_data = $this->_entities($trimed_data);
        return $trimed_data;
    }

    /**
     * 转义HTML实体
     * @param $data mixed
     * @return mixed
     */
    protected function _entities($data) {
        return is_array($data) ?
            array_map(array($this, '_entities'), $data) : htmlentities($data);
    }

    /**
     * id类信息自动转为整型
     * @param $data mixed
     * @return mixed
     */
    protected function _id2Int($data) {
        return is_array($data) ?
            array_map(array($this, '_id2Int'), $data) : $data + 0;
    }
}