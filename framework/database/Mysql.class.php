<?php

/**
 * MySQL数据库操作类
 */
class Mysql implements Dao
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $link;              //数据库连接
    private static $instance;   //本类对象

    public $charset;
    public $dbname;

    /**
     * 获取单例对象
     * @param $configs array
     * @return object dao
     */
    public static function getInstance($configs)
    {
        //判断是否已经存在实例化对象
        if (!isset(self::$instance)) {
            //执行构造方法获得新对象
            self::$instance = new self($configs);
        }
        return self::$instance;
    }

    /**
     * 构造函数
     * @param $configs array
     */
    private function __construct($configs)
    {
        //有默认值的属性
        $this->host = $configs['host'] ? $configs['host'] : 'localhost';
        $this->port = $configs['port'] ? $configs['port'] : '3306';
        $this->charset = $configs['charset'] ? $configs['charset'] : 'utf8';
        //必要属性不能为空
        $this->username = $configs['username'] ? $configs['username'] : null;
        $this->password = $configs['password'] ? $configs['password'] : null;
        $this->dbname = $configs['dbname'] ? $configs['dbname'] : null;
        //如果必要属性没有提供则终止执行
        if (
            is_null($this->username) ||
            is_null($this->password) ||
            is_null($this->dbname)
        ) {
            die('dbname info incorrect');
        } else {
            //获取新对象的数据库连接资源
            $this->connect();
            $this->setCharset($this->charset);
            $this->selectDb($this->dbname);
        }
    }

    /**
     * 连接数据库
     * 为本对象的连接资源赋值
     */
    private function connect()
    {
        $host = $this->host . ':' . $this->port;
        $this->link = @mysql_connect($host, $this->username, $this->password)
        or die('db connect failure');
    }

    /**
     * 设置当前对象连接编码
     * 统一使用query方法达到错误处理的一致性
     * @param $charset
     */
    public function setCharset($charset)
    {
        $this->query("set names $charset");
    }

    /**
     * 选择数据库
     * 在得到对象之后可以外部重新选择数据库
     * @param $dbname
     */
    public function selectDb($dbname)
    {
        $this->query("use $dbname");
    }

    /**
     * 序列化方法
     */
    public function __sleep()
    {
        mysql_close($this->link);
        return array('host', 'port', 'charset', 'username', 'password', 'dbname');
    }

    /**
     * 反序列化方法
     */
    public function __wakeup()
    {
        $this->connect();
        $this->setCharset($this->charset);
        $this->selectDb($this->dbname);
    }

    /**
     * 克隆方法
     */
    private function __clone()
    {
    }

    /**
     * 执行基本语句
     * @param $sql string
     * @return resource
     */
    public function query($sql)
    {
        if (!$result = mysql_query($sql, $this->link)) {
            echo "执行失败<br/>";
            echo "失败的语句为：" . $sql . "<br/>";
            echo "出错信息为：" . mysql_error() . "<br/>";
            echo "错误代号为：" . mysql_errno() . "<br/>";
            return false;
        }
        return $result;
    }

    /**
     * 获取多行数据
     * @param $sql string
     * @return array
     */
    public function fetchAll($sql)
    {
        $result = $this->query($sql);
        $array = array();
        //结果数组的键名是字段名
        while ($record = mysql_fetch_assoc($result)) {
            $array[] = $record;
        }
        return $array;
    }

    /**
     * 获取单行数据
     * @param $sql string
     * @return array|bool
     */
    public function fetchRow($sql)
    {
        $result = $this->query($sql);
        //只取一行数据
        if ($record = mysql_fetch_assoc($result)) {
            return $record;
        }
        return false;
    }

    /**
     * 获取第一行第一列的字段值
     * @param $sql string
     * @return bool
     */
    public function fetchValue($sql)
    {
        $result = $this->query($sql);
        //取得第一行结果为数组下标为数字
        $record = mysql_fetch_row($result);
        if ($record === false) {
            return false;
        }
        return $record[0];
    }

    /**
     * 转义用户数据防止SQL注入
     * @param $data string
     * @return string
     */
    public function escapeString($data) {
        return "'" . mysql_real_escape_string($data, $this->link) . "'";
    }
}