<?php

/**
 * 图像处理工具类
 */
class Image
{
    private $path;      //存储路径
    private $src_w;     //原图宽度
    private $src_h;     //原图高度
    private $src_img;   //原图资源
    private $src_file;  //原图地址
    private $mark_w;    //水印宽度
    private $mark_h;    //水印高度
    private $mark_img;  //水印资源
    private $dst_img;   //目标资源
    private $error;     //错误信息

    /**
     * 构造方法
     * @param string $path 查找目录
     */
    public function __construct($path = '.')
    {
        $this->path = rtrim($path, '/') . DIRECTORY_SEPARATOR;
    }

    /**
     * 获取缩略图方法
     * @param string $src_file  源文件名
     * @param int $dst_width    目标宽度
     * @param int $dst_height   目标高度
     * @param string $prefix    目标文件名前缀
     * @param null $path        目标存储路径
     * @return bool|string      目标文件地址
     */
    public function thumb($src_file, $dst_width = 200, $dst_height = 200, $prefix = 'th_', $path = null)
    {
        if ($this->_init($src_file)) {
            // 获取目标尺寸
            list ($dst_w, $dst_h) = $this->_get_scale_info($dst_width, $dst_height);
            // 创建目标资源
            $this->dst_img = imagecreatetruecolor($dst_w, $dst_h);
            // 缩放
            imagecopyresampled(
                $this->dst_img, $this->src_img, 0, 0, 0, 0, $dst_w, $dst_h, $this->src_w, $this->src_h);
            // 保存并返回缩略图地址
            return $this->_save($prefix, $path);
        }
        // 初始化失败
        return false;
    }

    /**
     * 获取裁切图方法
     * @param string $src_file  源文件名
     * @param int $x            剪裁点x轴坐标
     * @param int $y            剪裁点y轴坐标
     * @param int $dst_width    目标宽度
     * @param int $dst_height   目标高度
     * @param string $prefix    目标文件名前缀
     * @param null $path        目标存储路径
     * @return bool|string      目标文件地址
     */
    public function cut($src_file, $x, $y, $dst_width, $dst_height, $prefix = 'cut_', $path = null)
    {
        if ($this->_init($src_file)) {
            if ($x + $dst_width > $this->src_w || $y + $dst_height > $this->src_h) {
                // 超出原图范围
                $this->error = 'New file size out of range';
                return false;
            }
            // 创建目标资源
            $this->dst_img = imagecreatetruecolor($dst_width, $dst_height);
            // 剪裁
            imagecopyresampled(
                $this->dst_img, $this->src_img, 0, 0, $x, $y, $dst_width, $dst_height, $dst_width, $dst_height
            );
            // 保存并返回图像地址
            return $this->_save($prefix, $path);
        }
        // 初始化失败
        return false;
    }

    /**
     * 获取带裁切功能的缩略图方法
     * @param string $src_file  源文件名
     * @param int $dst_width    目标宽度
     * @param int $dst_height   目标高度
     * @param int $cut_type     剪裁位置
     * @param string $prefix    目标文件名前缀
     * @param null $path        目标存储路径
     * @return bool|mixed       目标文件地址
     */
    public function zoom_in_cut($src_file, $dst_width, $dst_height, $cut_type = 1, $prefix = 'th_', $path = null)
    {
        if ($this->_init($src_file)) {
            // 约束新图尺寸不能超过原图
            $dst_width = $this->src_w > $dst_width ? $dst_width : $this->src_w;
            $dst_height = $this->src_h > $dst_height ? $dst_height : $this->src_h;
            // 获取剪裁信息
            list ($w, $h, $x, $y) = $this->_get_cut_info($dst_width, $dst_height, $cut_type);
            // 剪裁后的临时图像资源
            $tmp_img = imagecreatetruecolor($w, $h);
            imagecopyresampled($tmp_img, $this->src_img, 0, 0, $x, $y, $w, $h, $w, $h);
            // 处理缩略图
            $this->dst_img = imagecreatetruecolor($dst_width, $dst_height);
            imagecopyresampled($this->dst_img, $tmp_img, 0, 0, 0, 0, $dst_width, $dst_height, $w, $h);
            // 保存
            return $this->_save($prefix, $path);
        }
        // 初始化失败
        return false;
    }

    /**
     * 添加水印方法
     * @param string $src_file  //源文件地址
     * @param string $mark_file //水印文件地址
     * @param int $pct          //透明度
     * @param int $pos          //水印位置
     * @param float $w_rate     //宽度最大比例默认50%
     * @param float $h_rate     //高度最大比例默认20%
     * @param string $prefix    //目标文件名前缀
     * @param null $path        //目标存储路径
     * @return bool|mixed       //目标文件地址
     */
    public function watermark($src_file, $mark_file, $pct = 80, $pos = 9, $w_rate = 0.5, $h_rate = 0.2, $prefix = 'wm_', $path = null)
    {
        // 初始化原图和水印图片
        if ($this->_init($src_file) && $this->_init_mark($mark_file, $w_rate, $h_rate)) {
            // 获取水印位置信息
            list ($x, $y) = $this->_get_mark_info($pos);
            // 添加水印
            $this->imagecopymerge_alpha($this->src_img, $this->mark_img, $x, $y, 0, 0, $this->mark_w, $this->mark_h, $pct);
            $this->dst_img = $this->src_img;
            // 返回处理后的图片路径
            return $this->_save($prefix, $path);
        }
        // 初始化失败
        return false;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function get_error_info()
    {
        return $this->error;
    }

    /**
     * 初始化源图像
     * @param $src_file
     * @return bool
     */
    private function _init($src_file)
    {
        if (!is_file($src_file)) {
            $src_file = $this->path . $src_file;
        }
        $this->src_file = $src_file;
        list($this->src_w, $this->src_h, $type) = getimagesize($src_file);
        switch ($type) {
            case 1 :
                $this->src_img = imagecreatefromgif($src_file);
                break;
            case 2 :
                $this->src_img = imagecreatefromjpeg($src_file);
                break;
            case 3 :
                $this->src_img = imagecreatefrompng($src_file);
                break;
            default :
                $this->error = 'Source file not exist or unsupported image format';
                return false;
        }
        return true;
    }

    /**
     * 初始化水印图像
     * @param string $mark_file
     * @param float $w_rate 横向最大比例 默认50%
     * @param float $h_rate 纵向最大比例 默认20%
     * @return bool
     */
    private function _init_mark($mark_file, $w_rate = 0.5, $h_rate = 0.2)
    {
        if (!is_file($mark_file)) {
            $mark_file = $this->path . $mark_file;
        }
        list($mark_w, $mark_h, $type) = getimagesize($mark_file);
        $this->mark_w = $mark_w;
        $this->mark_h = $mark_h;
        switch ($type) {
            case 1 :
                $this->mark_img = imagecreatefromgif($mark_file);
                break;
            case 2 :
                $this->mark_img = imagecreatefromjpeg($mark_file);
                break;
            case 3 :
                $this->mark_img = imagecreatefrompng($mark_file);
                break;
            default :
                $this->error = 'Water mark file not exist or unsupported image format';
                return false;
        }
        // 判断水印大小是否超出了预定义比例
        if ($mark_w > $this->src_w * $w_rate || $mark_h > $this->src_h * $h_rate) {
            if ($mark_w > $this->src_w * $w_rate) {
                $scale_rate = $this->src_w * $w_rate / $mark_w;
                $mark_w = round($this->src_w * $w_rate);
                $mark_h = round($mark_h * $scale_rate);
            }
            if ($mark_h > $this->src_h * $h_rate) {
                $scale_rate = $this->src_h * $h_rate / $mark_h;
                $mark_h = round($this->src_h * $h_rate);
                $mark_w = round($mark_w * $scale_rate);
            }
            // 调整后的水印
            $tmp_img = imagecreatetruecolor($mark_w, $mark_h);
            $alpha = imagecolorallocatealpha($tmp_img, 0, 0, 0, 127);
            imagefill($tmp_img, 0, 0, $alpha);
            imagecopyresampled($tmp_img, $this->mark_img, 0, 0, 0, 0, $mark_w, $mark_h, $this->mark_w, $this->mark_h);
            imagesavealpha($tmp_img, true);
            // 更新水印属性
            $this->mark_img = $tmp_img;
            $this->mark_w = $mark_w;
            $this->mark_h = $mark_h;
        }
        return true;
    }

    /**
     * 根据原图比例获取等比例缩放的目标尺寸
     * @param int $dst_w 目标宽度
     * @param int $dst_h 目标高度
     * @return array
     */
    private function _get_scale_info($dst_w, $dst_h)
    {
        $result = array();
        $dst_w = $this->src_w > $dst_w ? $dst_w : $this->src_w;
        $dst_h = $this->src_h > $dst_h ? $dst_h : $this->src_h;
        /**
         * 等比例缩放算法
         * 比值较大的边其长度不变并以此得到另一边的目标长度
         * src_h/dst_h > src_w/dst_w
         * 可演化为乘法形式
         * src_h*$dst_w > src_w*$dst_h
         */
        if ($this->src_h * $dst_w > $this->src_w * $dst_h) {
            // 以高度比率为准目标宽度不变
            $result[] = round($dst_h / $this->src_h * $this->src_w);
            $result[] = $dst_h;
        } else {
            $result[] = $dst_w;
            $result[] = round($dst_w / $this->src_w * $this->src_h);
        }
        return $result;
    }

    /**
     * 根据目标比例获取剪裁信息
     * @param int $dst_width 目标宽度
     * @param int $dst_height 目标高度
     * @param int $cut_type 裁剪类型 0=裁剪左边或上边 1=裁剪两边 2=裁剪右边或下边
     * @return array
     */
    private function _get_cut_info($dst_width, $dst_height, $cut_type)
    {
        $result = array();
        // 获取剪裁点坐标
        if ($this->src_w / $dst_width > $this->src_h / $dst_height) {
            // 横向裁剪
            $tmp_w = round($this->src_h / $dst_height * $dst_width);
            $y = 0;
            switch ($cut_type) {
                case 0 :
                    // 裁掉左边
                    $x = $this->src_w - $tmp_w;
                    break;
                case 1 :
                    // 裁掉两边
                    $x = ($this->src_w - $tmp_w) / 2;
                    break;
                case 2 :
                    // 裁掉右边
                    $x = 0;
                    break;
                default :
                    $x = ($this->src_w - $tmp_w) / 2;
            }
            // 保存剪裁信息
            $result[] = $tmp_w;
            $result[] = $this->src_h;
            $result[] = $x;
            $result[] = $y;
        } else {
            // 纵向裁剪
            $tmp_h = round($this->src_w / $dst_width * $dst_height);
            $x = 0;
            switch ($cut_type) {
                case 0 :
                    // 裁掉上边
                    $y = $this->src_h - $tmp_h;
                    break;
                case 1 :
                    // 裁掉两边
                    $y = ($this->src_h - $tmp_h) / 2;
                    break;
                case 2 :
                    // 裁掉下边
                    $y = 0;
                    break;
                default :
                    $y = ($this->src_h - $tmp_h) / 2;
            }
            // 保存剪裁信息
            $result[] = $this->src_w;
            $result[] = $tmp_h;
            $result[] = $x;
            $result[] = $y;
        }
        return $result;
    }

    /**
     * 根据位置获取水印添加坐标
     * @param int $pos  水印位置
     * @return array
     */
    private function _get_mark_info($pos)
    {
        $result = array();
        $w = $this->src_w;
        $h = $this->src_h;
        switch ($pos) {
            case 1 :
                // 左上
                $x = round($w / 3 / 2 - $w / 3 / 2 / 2);
                $y = round($h / 3 / 2 - $h / 3 / 2 / 2);
                break;
            case 2 :
                // 中上
                $x = round($w / 2 - $this->mark_w / 2);
                $y = round($h / 3 / 2 - $h / 3 / 2 / 2);
                break;
            case 3 :
                // 右上
                $x = round($w - $w / 3 + $w / 3 / 2 + $w / 3 / 2 / 2 - $this->mark_w);
                $y = round($h / 3 / 2 - $h / 3 / 2 / 2);
                break;
            case 4 :
                // 左中
                $x = round($w / 3 / 2 - $w / 3 / 2 / 2);
                $y = round($h / 2 - $this->mark_h / 2);
                break;
            case 5 :
                // 中心
                $x = round($w / 2 - $this->mark_w / 2);
                $y = round($h / 2 - $this->mark_h / 2);
                break;
            case 6 :
                // 右中
                $x = round($w - $w / 3 + $w / 3 / 2 + $w / 3 / 2 / 2 - $this->mark_w);
                $y = round($h / 2 - $this->mark_h / 2);
                break;
            case 7 :
                $x = round($w / 3 / 2 - $w / 3 / 2 / 2);
                $y = round($h - $h / 3 + $h / 3 / 2 + $h / 3 / 2 / 2 - $this->mark_h);
                break;
            case 8 :
                // 中下
                $x = round($w / 2 - $this->mark_w / 2);
                $y = round($h - $h / 3 + $h / 3 / 2 + $h / 3 / 2 / 2 - $this->mark_h);
                break;
            case 9 :
                // 右下
                $x = round($w - $w / 3 + $w / 3 / 2 + $w / 3 / 2 / 2 - $this->mark_w);
                $y = round($h - $h / 3 + $h / 3 / 2 + $h / 3 / 2 / 2 - $this->mark_h);
                break;
            default :
                $x = round($w - $w / 3 + $w / 3 / 2 + $w / 3 / 2 / 2 - $this->mark_w);
                $y = round($h - $h / 3 + $h / 3 / 2 + $h / 3 / 2 / 2 - $this->mark_h);
        }
        $result[] = $x;
        $result[] = $y;
        return $result;
    }

    /**
     * 保存目标为图片文件
     * @param $prefix
     * @param null $path
     * @return mixed
     * @todo 保存为原图格式
     */
    private function _save($prefix, $path = null)
    {
        $info = pathinfo($this->src_file);
        $file_name = $prefix . $info['filename'] . '.' . $info['extension'];
        if (isset($path)) {
            $path = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
        } else {
            $path = $info['dirname'] . DIRECTORY_SEPARATOR;
        }
        $dst_file = $path . $file_name;
        // 保存
        imagejpeg($this->dst_img, $dst_file, 100);
        if (is_file($dst_file)) {
            // 销毁画布
            return $dst_file;
        }
        $this->error = 'File saved error';
        return false;
    }

    /**
     * 带透明度的添加水印方法
     * @param $dst_im
     * @param $src_im
     * @param $dst_x
     * @param $dst_y
     * @param $src_x
     * @param $src_y
     * @param $src_w
     * @param $src_h
     * @param int $pct 透明度
     */
    private function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    {
        $opacity = $pct;
        // getting the watermark width
        $w = imagesx($src_im);
        // getting the watermark height
        $h = imagesy($src_im);

        // creating a cut resource
        $cut = imagecreatetruecolor($src_w, $src_h);
        // copying that section of the background to the cut
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        // inverting the opacity
        //$opacity = 100 - $opacity;

        // placing the watermark now
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $opacity);
    }

    public function __destruct()
    {
        @imagedestroy($this->mark_img);
        @imagedestroy($this->src_img);
        @imagedestroy($this->dst_img);
    }
}