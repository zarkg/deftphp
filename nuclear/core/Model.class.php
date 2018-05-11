<?php

/**
 * 基础模型类
 * @todo 构造方法传入逻辑表名用于构造真实表名
 */
class Model
{
    protected $_dao; //存储dao数据库操作对象
    protected $_table; //真实表名
    protected $_primary_key; //主键

    /**
     * 构造方法
     */
    public function __construct()
    {
        $this->_initDAO();
        $this->_initTableName();
        $this->_initPrimaryKey();
    }

    /**
     * 初始化实际表名
     */
    protected function _initTableName() {
        $this->_table =
            '`' . $GLOBALS['config']['app']['table_prefix'] . $this->_logic_table . '`';
    }

    /**
     * 初始化DAO
     */
    protected function _initDAO() {
        // 获得DAO初始化参数
        $configs = $GLOBALS['config']['db'];
        // 实例化DAO对象
        $this->_dao = Pdodb::getInstance($configs);
    }

    /**
     * 确定主键
     * @todo 不支持联合主键
     */
    protected function _initPrimaryKey() {
        $sql = "desc $this->_table";
        $desc = $this->_dao->fetchAll($sql);
        foreach ($desc as $column) {
            if ($column['Key'] == 'PRI') {
                $this->_primary_key = $column['Field'];
                break;
            }
        }
    }

    /**
     * 数组数据集中转义方法
     * @param $data array
     * @return array
     */
    protected function _escapeString($data) {
        return is_array($data) ?
            array_map(array($this, '_escapeString'), $data) :
            $this->_dao->escapeString($data);
    }

    /**
     * 记录sql执行日志方法
     * @param $sql string
     * @param $logfile = 'sql.log' string
     */
    protected function _log($sql, $logfile = 'sql.log') {
        $content = '[' . date('Y-m-d H:i:s') . '] ' . $sql . PHP_EOL;
        file_put_contents($logfile, $content, FILE_APPEND);
    }

    /**
     * 数据自动插入方法
     * 关联数组的键名对应数据表的字段名
     * @param $data array 关联数组
     * @return int|bool 成功返回生成的主键失败返回false
     */
    public function insertRow($data) {
        $column_list = ''; //字段名列表
        $value_list = ''; //数据列表
        // 集中转义
        $data = $this->_escapeString($data);

        // 拼凑字段列表与值列表
        foreach ($data as $column => $value) {
            $column_list .= '`' . $column . '`' . ',';
            $value_list .= $value . ',';
        }
        // 去除多余逗号
        $column_list = rtrim($column_list, ',');
        $value_list = rtrim($value_list, ',');

        $sql = "insert into $this->_table ($column_list) values ($value_list)";

        if ($this->_dao->query($sql)) {
            $sql = "select $this->_primary_key from $this->_table order by $this->_primary_key desc limit 1";
            $id = $this->_dao->fetchValue($sql);
            return $id;
        }
        return false;
    }

    /**
     * 数据自动更新方法
     * @param $data array
     * @return bool
     */
    public function updateRow($data) {
        $id = $data[$this->_primary_key];
        $update_list = '';
        // 集中转义
        $data = $this->_escapeString($data);
        foreach ($data as $column => $value) {
            if ($column !== $this->_primary_key) {
                $update_list .= $column . '=' . $value . ',';
            }
        }
        // 去除多余逗号
        $update_list = rtrim($update_list, ',');
        $sql = "update $this->_table set $update_list where $this->_primary_key=$id";
        return $this->_dao->query($sql);
    }

    /**
     * 根据id获取记录方法
     * @param $id int
     * @return array|bool
     */
    public function getRow($id) {
        $sql = "select * from $this->_table where $this->_primary_key=$id";
        return $this->_dao->fetchRow($sql);
    }

    /**
     * 获取所有记录方法
     * @return array
     */
    public function getAll() {
        $sql = "select * from $this->_table;";
        return $this->_dao->fetchAll($sql);
    }

    /**
     * 根据id删除记录方法
     * @param $id int
     * @return bool
     */
    public function deleteRow($id) {
        $sql = "delete from $this->_table where $this->_primary_key=$id";
        return $this->_dao->query($sql);
    }

    /**
     * 分页获取记录方法
     * @param int $offset
     * @param int $pagesize
     * @return array|bool
     */
    public function getPage($offset, $pagesize) {
        $sql = "select * from $this->_table
                order by $this->_primary_key limit $offset,$pagesize;";
        return $this->_dao->fetchAll($sql);
    }

    /**
     * 获取总记录数方法
     * @param string $condition = true 查询条件默认为所有记录
     * @return int
     */
    public function totalRecords($condition = 'true') {
        $sql = "select count(*) from $this->_table where {$condition}";
        return $this->_dao->fetchValue($sql);
    }
}