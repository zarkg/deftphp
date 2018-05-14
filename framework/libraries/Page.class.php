<?php

/**
 * 分页工具类
 */
class Page
{
    private $_total_records;    //记录总数
    private $_pagesize;         //每页记录数
    private $_current_page;     //当前页
    private $_total_pages;      //总页数
    private $_url;              //基址

    /**
     * 构造方法
     * @param int $total_records
     * @param int $pagesize
     * @param int $current_page
     * @param string $script 处理分页的脚本名称
     * @param array $params 关联数组存储请求参数
     */
    public function __construct($total_records, $pagesize, $current_page, $script, $params)
    {
        $this->_total_records = $total_records;
        $this->_pagesize = $pagesize;
        $this->_current_page = $current_page;
        $this->_total_pages = ceil($this->_total_records / $this->_pagesize);
        $this->_url = $this->_initUrl($script, $params);
    }

    /**
     * 构造基础地址方法
     * @param $script
     * @param $params
     * @return string
     */
    private function _initUrl($script, $params) {
        $tmp = array();
        foreach ($params as $param => $value) {
            $tmp[] = "$param=$value";
        }
        $url = $script . '?' . implode('&', $tmp) . '&page=';
        return $url;
    }

    /**
     * 获取页面跳转超链接
     * @param $page
     * @return bool|string
     */
    private function _page_url($page) {
        switch ($page) {
            case 'first' :
                if ($this->_current_page == 1) {
                    return '首页';
                } else {
                    return '<a href="' . $this->_url . '1">首页</a>';
                }
                break;
            case 'pre' :
                if ($this->_current_page == 1) {
                    return "上一页";
                } else {
                    return '<a href="' . $this->_url . ($this->_current_page - 1) . '">上一页</a>';
                }
                break;
            case 'next' :
                if ($this->_current_page == $this->_total_pages) {
                    return "下一页";
                } else {
                    return '<a href="' . $this->_url . ($this->_current_page + 1) . '">下一页</a>';
                }
                break;
            case 'last' :
                if ($this->_current_page == $this->_total_pages) {
                    return "尾页";
                } else {
                    return '<a href="' . $this->_url . $this->_total_pages . '">尾页</a>';
                }
                break;
            default :
                return false;
        }
    }

    public function getPageInfo() {
        $info = sprintf(
            '共%s条记录，当前第%s/%s页，%s %s %s %s',
            $this->_total_records,
            $this->_current_page,
            $this->_total_pages,
            $this->_page_url('first'),
            $this->_page_url('pre'),
            $this->_page_url('next'),
            $this->_page_url('last')
        );

        return $info;
    }
}