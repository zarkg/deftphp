<?php

/**
 * 项目中的工厂类
 */
class Factory
{
    /**
     * 生成模型的单例对象
     *
     * @param $model_name string
     * @return object
     */
    public static function M($model_name) {
        // 存储实例化好的模型对象列表
        static $model_list = array();

        if(!isset($model_list[$model_name])) {
            // 没有实例化过
            $model_list[$model_name] = new $model_name;
        }

        return $model_list[$model_name];
    }
}