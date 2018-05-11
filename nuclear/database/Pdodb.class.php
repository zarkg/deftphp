<?php

/**
 * 基于PDO的MySQL操作类
 *
 * @package
 * @license     GNU General Public License
 * @author      jizhenya
 * @file        PDODB.class.php 2017-08-17 10:02
 */
class Pdodb implements Dao
{
    private $_pdo; //数据库连接
    private static $_instance; //单例对象

    /**
     * 获取单例对象
     * @param array $configs
     * @return object dao
     */
    public static function getInstance($configs)
    {
        if (!static::$_instance instanceof static) {
            static::$_instance = new static($configs);
        }
        return static::$_instance;
    }

    /**
     * 构造方法
     * @param array $configs
     */
    private function __construct($configs)
    {
        $this->_initPDO($configs);
    }

    /**
     * 连接数据库
     * @param array $configs
     */
    private function _initPDO($configs)
    {
        try {
            $this->_pdo = new PDO(
                sprintf(
                    "mysql:host=%s;port=%s;dbname=%s",
                    $configs['host'],
                    $configs['port'],
                    $configs['dbname']),
                $configs['username'],
                $configs['password'],
                array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $configs['charset']
                )
            );
        } catch (PDOException $e) {
            die('Database connection failed');
        }
    }

    /**
     * 防止克隆
     */
    private function __clone()
    {
    }

    /**
     * 执行非查询类SQL
     * @param string $sql
     * @return int|bool
     */
    public function query($sql)
    {
        return $this->_pdo->exec($sql);
    }

    /**
     * 获取所有查询结果
     * @param string $sql
     * @return array|bool
     */
    public function fetchAll($sql)
    {
        $statement = $this->_pdo->query($sql);
        $result = $statement->fetchAll(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }

    /**
     * 获取一行查询结果
     * @param string $sql
     * @return array|bool
     */
    public function fetchRow($sql)
    {
        $statement = $this->_pdo->query($sql);
        $result = $statement->fetch(PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $result;
    }

    /**
     * 获取一个结果字段值
     * @param string $sql
     * @param int $index
     * @return mixed
     */
    public function fetchValue($sql, $index = 0)
    {
        $statement = $this->_pdo->query($sql);
        $result = $statement->fetchColumn($index);
        $statement->closeCursor();
        return $result;
    }

    /**
     * 转义用户数据防止SQL注入
     * @param string $data
     * @return string
     */
    public function escapeString($data)
    {
        return $this->_pdo->quote($data);
    }
}