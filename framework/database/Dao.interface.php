<?php
/**
 * DAO层操作接口
 */
interface Dao {
    // 单例对象
    public static function getInstance($configs);

    // 执行基本语句
    public function query($sql);

    // 获取所有结果
    public function fetchAll($sql);

    // 获取单行结果
    public function fetchRow($sql);

    // 获取单个数据
    public function fetchValue($sql);

    // 转义用户数据
    public function escapeString($data);
}