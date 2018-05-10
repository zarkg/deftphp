<?php

/**
 * 文件上传工具类
 *
 * @author      jizhenya
 * @email       zhenya.ji@hotmail.com
 * @license     GNU General Public License
 * @file        Upload.class.php 2017-09-04 22:08
 * @version     1.0
 * @update      2017-09-05
 */
class Upload
{
    private $_max_size;
    private $_upload_path;
    private $_prefix;
    private $_allow_ext_list;
    private $_allow_mime_list;
    private $_error;
    private $_error_file;

    // 后缀名与mime的映射关系
    private $_type_map = array(
        // 图片类型
        '.jpg' => array('image/jpeg', 'image/pjpeg'),
        '.jpeg' => array('image/jpeg', 'image/pjpeg'),
        '.png' => array('image/png', 'image/x-png'),
        '.gif' => array('image/gif'),
        '.bmp' => array('image/bmp'),
        '.svg' => array('image/svg+xml'),
        '.ico' => array('image/x-icon'),
        // 文字类型
        '.txt' => array('text/plain'),
        '.php' => array('text/x-php', 'application/octet-stream'),
        '.html' => array('text/html'),
        '.htm' => array('text/html'),
        '.js' => array('text/javascript'),
        '.css' => array('text/css'),
        // 文档类型
        '.doc' => array('application/msword'),
        '.pdf' => array('application/pdf'),
        '.xls' => array('application/vnd.ms-excel'),
        '.msg' => array('application/vnd.ms-outlook'),
        '.ppt' => array('application/vnd.ms-powerpoint'),
        // 压缩文件类型
        '.z' => array('application/x-compress'),
        '.tgz' => array('application/x-compressed'),
        '.gz' => array('application/x-gzip'),
        '.tar' => array('application/x-tar'),
        '.zip' => array('application/zip'),
        // 数字证书类型
        '.p12' => array('application/x-pkcs12'),
        '.pfx' => array('application/x-pkcs12'),
        '.p7b' => array('application/x-pkcs7-certificates'),
        '.cer' => array('application/x-x509-ca-cert'),
        '.crt' => array('application/x-x509-ca-cert'),
        // 音频类型
        '.mp3' => array('audio/mpeg'),
        '.m3u' => array('audio/x-mpegurl'),
        '.wav' => array('audio/x-wav'),
        // 视频类型
        '.mpg' => array('video/mpeg'),
        '.mpeg' => array('video/mpeg'),
        '.mov' => array('video/quicktime'),
        '.avi' => array('video/x-msvideo'),
        '.swf' => array('application/x-shockwave-flash'),
    );

    // 构造方法
    public function __construct()
    {
        $this->_max_size = 1024 * 1024;
        $this->_upload_path = defined('UPLOAD_PATH') ? UPLOAD_PATH : '.';
        $this->_prefix = '';
        $this->_allow_ext_list = array('.jpg', '.png', '.jpeg');
        $this->_initAllowMimeList();
    }

    /**
     * 属性重载
     * @param string $p
     * @param mixed $v
     */
    public function __set($p, $v)
    {
        // 允许外部访问的属性列表
        $allow_access_list = array(
            '_max_size',
            '_upload_path',
            '_prefix',
            '_allow_ext_list',
        );
        // 允许不加下划线
        if (substr($p, 0, 1) != '_') {
            $p = '_' . $p;
        }
        if (in_array($p, $allow_access_list)) {
            $this->$p = $v;
            if ($p == '_allow_ext_list') {
                // 更新允许的mime类型列表
                $this->_initAllowMimeList();
            }
        }
    }

    /**
     * 上传单个文件方法
     * @param array $file
     * @return bool|string
     */
    public function uploadOne($file)
    {
        // 判断是否存在上传错误
        if ($file['error'] != 0) {
            $this->_error = $file['error'];
            $this->_error_file = $file['name'];
            return false;
        }

        // 判断文件大小是否合法
        if ($file['size'] > $this->_max_size) {
            $this->_error = -1;
            $this->_error_file = $file['name'];
            return false;
        }

        // 判断文件类型是否合法
        $ext = strtolower(strrchr($file['name'], '.'));
        $mime = $this->_getMimeType($file['tmp_name']);
        if (!in_array($ext, $this->_allow_ext_list) || !in_array($mime, $this->_allow_mime_list)) {
            $this->_error = -2;
            $this->_error_file = $file['name'];
            return false;
        }

        // 持久化存储上传的文件
        if ($result = $this->_fileSave($file['tmp_name'], $ext)) {
            return $result;
        } else {
            $this->_error = -3;
            $this->_error_file = $file['name'];
            return false;
        }

    }

    /**
     * 上传多个关联文件方法
     * @param array $files
     * @return bool|array
     */
    public function uploadGroup($files)
    {
        $file_list = array();
        $saved_file_list = array();
        // 拼凑单个文件属性
        foreach ($files['error'] as $key => $value) {
            $file['name'] = $files['name'][$key];
            $file['type'] = $files['type'][$key];
            $file['tmp_name'] = $files['tmp_name'][$key];
            $file['error'] = $files['error'][$key];
            $file['size'] = $files['size'][$key];
            $file_list[] = $file;
            // 关联文件上传作为事务处理错误则终止
            if ($file['error'] != 0) {
                // 记录错误文件
                $this->_error = -4;
                $this->_error_file = $file['name'];
                return false;
            }
        }

        // 调用单个文件上传方法
        foreach ($file_list as $file) {
            if ($result = $this->uploadOne($file)) {
                // 记录上传成功的文件
                $saved_file_list[] = $result;
            } else {
                // 存在不成功的文件则删除所有已上传文件
                foreach ($saved_file_list as $todel) {
                    unlink($this->_upload_path . DIRECTORY_SEPARATOR . $todel);
                }
                return false;
            }
        }

        return $saved_file_list;
    }

    /**
     * 获取错误信息方法
     * @return string
     */
    public function getErrorInfo()
    {
        switch ($this->_error) {
            case -1 :
                return "文件{$this->_error_file}的大小超出程序限制";
            case -2 :
                return "文件{$this->_error_file}的类型非法";
            case -3 :
                return "服务器转储文件{$this->_error_file}失败";
            case -4 :
                return "关联文件中存在上传错误而被系统终止";
            case 1 :
                return "文件{$this->_error_file}的大小超出服务器限制";
            case 2 :
                return "文件{$this->_error_file}的大小超出表单限制";
            case 3 :
                return "文件{$this->_error_file}上传不完整";
            case 4 :
                return "未选择上传文件";
            case 5 :
                return "文件{$this->_error_file}的长度为0";
            case 6 :
                return "服务器临时文件写入失败";
            case 7 :
                return "服务器内部错误";
            default :
                return "未知错误";
        }
    }

    /**
     * 初始化允许的mime类型列表
     */
    private function _initAllowMimeList()
    {
        $allow_mime_list = array();
        foreach ($this->_allow_ext_list as $ext) {
            // 根据映射关系合并允许的mime类型列表
            $allow_mime_list = array_merge($allow_mime_list, $this->_type_map[$ext]);
        }
        // 去重
        $this->_allow_mime_list = array_unique($allow_mime_list);
    }

    /**
     * 获取文件mime类型方法
     * @param string $file
     * @return string
     */
    private function _getMimeType($file)
    {
        // 使用FInfo检测文件mime类型
        $info = new FInfo(FILEINFO_MIME_TYPE);
        $mime = $info->file($file);
        return $mime;
    }

    /**
     * 存储文件方法
     * @param string $file
     * @param string $ext
     * @return bool|string
     */
    private function _fileSave($file, $ext)
    {
        // 判断当日子目录是否存在
        $sub_path = date('Ymd');
        $upload_path = $this->_upload_path . DIRECTORY_SEPARATOR . $sub_path;
        if (!is_dir($upload_path)) {
            // 不存在则创建
            mkdir($upload_path);
        }
        // 生成文件名
        $name = uniqid($this->_prefix, true) . $ext;
        // 转储文件
        if (move_uploaded_file($file, $upload_path . DIRECTORY_SEPARATOR . $name)) {
            return $sub_path . DIRECTORY_SEPARATOR . $name;
        } else {
            return false;
        }
    }
}