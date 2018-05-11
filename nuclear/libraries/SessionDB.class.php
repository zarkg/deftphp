<?php

/**
 * Session入库工具类
 */
class SessionDB
{
    protected $_dao;

    /**
     * 构造函数
     */
    public function __construct()
    {
        // 设置session处理器
        ini_set('session.save_handler','user');
        session_set_save_handler(
            array($this, 'userSessionBegin'),
            array($this, 'userSessionEnd'),
            array($this, 'userSessionRead'),
            array($this, 'userSessionWrite'),
            array($this, 'userSessionDelete'),
            array($this, 'userSessionGC')
        );
        // 开启session机制
        session_start();
    }

    /**
     * 开始操作
     * 时机：session机制开启时最早执行
     * 功能：初始化存储操作的相关资源
     */
    public function userSessionBegin() {
        // 初始化DAO
        $configs = $GLOBALS['config']['db'];
        $this->_dao = Pdodb::getInstance($configs);
    }

    /**
     * 读操作
     * 时机：session机制开启过程中
     * 功能：从当前数据区读取数据内容
     * @param $s_id string
     * @return string
     */
    public function userSessionRead($s_id) {
        $sql = "SELECT session_content FROM `session` WHERE session_id='$s_id'";
        return (string) $this->_dao->fetchValue($sql);
    }

    /**
     * 写操作
     * 时机：脚本周期结束时
     * 功能：将当前脚本整理好的session数据持久化存储到数据库中并记录时间戳
     * @param $s_id string
     * @param $s_content string
     * @return bool
     */
    function userSessionWrite($s_id, $s_content) {
        $sql = "REPLACE INTO `session` VALUES ('$s_id','$s_content',unix_timestamp())";
        return $this->_dao->query($sql);
    }

    /**
     * 删除操作
     * 时机：使用session_destroy销毁存储区过程中
     * 功能：删除当前会话对应的存储区
     * @param $s_id
     * @return bool
     */
    function userSessionDelete($s_id) {
        $sql = "DELETE FROM `session` WHERE session_id='$s_id'";
        return $this->_dao->query($sql);
    }

    /**
     * 垃圾回收
     * 时机：开启session机制过程中有概率的执行
     * 功能：删除所有过期session记录
     * @param $max_lifetime int 最长有效期（单位秒）
     * @return bool
     */
    function userSessionGC($max_lifetime) {
        $sql = "DELETE FROM `session` WHERE last_access<unix_timestamp()-$max_lifetime";
        return $this->_dao->query($sql);
    }

    /**
     * 结束操作
     * 时机：session机制结束时最后一次执行的操作
     * 功能：收尾性工作一般没有实质性内容
     * @return bool
     */
    function userSessionEnd() {
        return true;
    }
}