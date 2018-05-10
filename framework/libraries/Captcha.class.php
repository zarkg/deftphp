<?php

/**
 * 验证码处理工具类
 *
 * @author      jizhenya
 * @email       zhenya.ji@hotmail.com
 * @license     GNU General Public License
 * @file        Captcha.class.php 2017-09-05 14:07
 * @version     1.1
 * @update      2017-09-07  混淆曲线
 */
class Captcha
{
    private $_code_length;  //码值字符个数
    private $_width;        //图像宽度
    private $_height;       //图像高度
    private $_font;         //字体文件
    private $_font_size;    //字号
    private $_font_height;  //字符高度
    private $_font_width;   //字符宽度
    private $_code;         //码值
    private $_img;          //图像资源
    private $_char_list = 'abcdefghjkmnpqrstwxyABCDEFGHJKLMNPQRSTWXYZ123456789';

    public $style = 'classic';

    /**
     * 构造方法
     * @param int $code_length
     * @param int $width
     * @param int $height
     * @param string $font
     */
    public function __construct($width = 150, $height = 35, $code_length = 4, $font = 'elephant')
    {
        $this->_width = $width;
        $this->_height = $height;
        $this->_code_length = $code_length;
        $this->_font = LIB_PATH . 'fonts' . DIRECTORY_SEPARATOR . $font . '.ttf';
        $this->_initFontSize();
    }

    /**
     * 生成验证码方法
     */
    public function generate()
    {
        $this->_applyStyle();
        // 记录码值
        isset($_SESSION) || session_start();
        $_SESSION['captcha'] = $this->_code;
        // 输出验证码
        imagepng($this->_img);
        imagedestroy($this->_img);
    }

    /**
     * 合法性校验方法
     * @param string $code
     * @return bool
     */
    public function verify($code)
    {
        isset($_SESSION) || session_start();
        $result = isset($_SESSION['captcha']) && isset($code) &&
            (0 == strcasecmp($_SESSION['captcha'], $code));
        // 码值验证一次就销毁
        unset($_SESSION['captcha']);
        return $result;
    }

    /**
     * 应用风格方法
     * 可组合因素包括背景颜色、混淆方式以及字体颜色
     */
    private function _applyStyle()
    {
        header('Content-Type: image/png');
        $this->_initCode();
        switch ($this->style) {
            case 'classic' :
                $bgcolor = array('r' => mt_rand(210, 255), 'g' => mt_rand(210, 255), 'b' => mt_rand(210, 255));
                $this->_initImg($bgcolor);
                $this->_mixedUp('pixel', $this->_width * $this->_height / 20);
                $this->_mixedUp('polygon', 3);
                $this->_writeCode();
                break;
            case 'modern' :
                $bgcolor = array('r' => 243, 'g' => 251, 'b' => 254);
                $font_color = array('r' => mt_rand(1, 120), 'g' => mt_rand(1, 120), 'b' => mt_rand(1, 120));
                $this->_initImg($bgcolor);
                $this->_mixedUp('word', 5);
                $this->_mixedUp('curve', 1, $font_color);
                $this->_writeCode($font_color);
                break;
            default :
                $bgcolor = array('r' => 255, 'g' => 255, 'b' => 255);
                $font_color = array('r' => 0, 'g' => 0, 'b' => 0);
                $this->_initImg($bgcolor);
                $this->_mixedUp('curve', 1, $font_color);
                $this->_writeCode($font_color);
        }
    }

    /**
     * 根据图片高度自动调整字体大小
     */
    private function _initFontSize()
    {
        // 从图片尺寸得到初始字号
        $size = min($this->_width, $this->_height);
        $step = 1;
        while ($this->_getFontHeight($size) > ($this->_height * 3 / 4)) {
            $size -= $step;
        }
        while ($this->_getFontWidth($size) > $this->_width / $this->_code_length) {
            $size -= $step;
        }
        $this->_font_size = $size;
        $this->_font_height = $this->_getFontHeight($this->_font_size);
        $this->_font_width = $this->_getFontWidth($this->_font_size);
    }

    /**
     * 获取指定字号的高度
     * @param int $size
     * @return int
     */
    private function _getFontHeight($size)
    {
        $box = imagettfbbox($size, 0, $this->_font, 'I');
        return $box[1] - $box[7];
    }

    /**
     * 获取指定字号的单字符宽度
     * @param int $size
     * @return int
     */
    private function _getFontWidth($size)
    {
        $box = imagettfbbox($size, 0, $this->_font, 'W');
        return $box[2] - $box[0];
    }

    /**
     * 初始化验证码值方法
     */
    private function _initCode()
    {
        $code = '';
        $char_list_length = strlen($this->_char_list);
        for ($i = 0; $i < $this->_code_length; ++$i) {
            $code .= $this->_char_list[mt_rand(0, $char_list_length - 1)];
        }
        $this->_code = $code;
    }

    /**
     * 初始化图像资源方法
     * @param array $color
     */
    private function _initImg($color = null)
    {
        $this->_img = imagecreatetruecolor($this->_width, $this->_height);
        imagefill($this->_img, 0, 0, $this->_getColor($color));
    }

    /**
     * 图像混淆方法
     * @param string $style
     * @param int $times
     * @param array $color
     */
    private function _mixedUp($style, $times, $color = null)
    {
        switch ($style) {
            // 像素点
            case 'pixel' :
                for ($i = 0; $i < $times; ++$i) {
                    $pixel_color = $this->_getColor($color);
                    $x = mt_rand(0, $this->_width);
                    $y = mt_rand(0, $this->_height);
                    imagesetpixel($this->_img, $x, $y, $pixel_color);
                }
                break;

            // 干扰线
            case 'line' :
                for ($i = 0; $i < $times; ++$i) {
                    // 随机两个坐标点
                    $x1 = mt_rand(0, $this->_width - 1);
                    $x2 = mt_rand(0, $this->_width - 1);
                    $y1 = mt_rand(0, $this->_height - 1);
                    $y2 = mt_rand(0, $this->_height - 1);
                    // 随机线条颜色
                    $line_color = $this->_getColor($color);
                    imageline($this->_img, $x1, $y1, $x2, $y2, $line_color);
                }
                break;

            // 多边形
            case 'polygon' :
                for ($i = 0; $i < $times; ++$i) {
                    // 随机线条风格
                    $c1 = $this->_getColor($color);
                    $c2 = $this->_getColor($color);
                    $line_style = array($c1, $c1, $c1, $c1, $c1, $c2, $c2, $c2, $c2, $c2);
                    imagesetstyle($this->_img, $line_style);
                    // 随机顶点个数
                    $vertexs = mt_rand(3, 9);
                    // 初始化顶点坐标
                    $points = array();
                    for ($j = 1; $j <= $vertexs; ++$j) {
                        $points[] = mt_rand(0, $this->_width);
                        $points[] = mt_rand(0, $this->_height);
                    }
                    imagepolygon($this->_img, $points, $vertexs, IMG_COLOR_STYLED);
                }
                break;

            // 干扰字符
            case 'word' :
                for ($i = 0; $i < $times; ++$i) {
                    // 字符颜色
                    $word_color = $this->_getColor($color);
                    // 字号
                    $word_size = $this->_font_size / 8 > 2 ? floor($this->_font_size / 5) : 2;
                    for ($j = 0; $j < 5; ++$j) {
                        $word = chr(mt_rand(41, 176));
                        $x = mt_rand(0, $this->_width - 1);
                        $y = mt_rand(0, $this->_height - 1);
                        imagestring($this->_img, $word_size, $x, $y, $word, $word_color);
                    }
                }
                break;

            // 干扰曲线
            case 'curve' :
                for ($i = 0; $i < $times; ++$i) {
                    $curve_color = $this->_getColor($color);
                    // 正弦函数
                    $a = mt_rand($this->_height / 4, $this->_height / 2); //波幅
                    $t = mt_rand($this->_width, $this->_width * 1.5); //周期
                    $w = M_PI * 2 / $t; //角频率
                    $h = mt_rand($this->_height / 4, $this->_height / 4 * 3); //y轴偏移量
                    for ($x = 0; $x < $this->_width; ++$x) {
                        $y = $a * sin($x * $w) + $h;
                        // 控制曲线粗细
                        $k = $this->_font_size / 8;
                        while ($k > 0) {
                            imagesetpixel($this->_img, $x + $k, $y + $k, $curve_color);
                            -- $k;
                        }
                    }
                }
                break;
        }
    }

    /**
     * 分配颜色方法
     * @param array $color
     * @return int
     */
    private function _getColor($color = null)
    {
        return isset($color) ?
            imagecolorallocate($this->_img, $color['r'], $color['g'], $color['b']) :
            imagecolorallocate($this->_img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
    }

    /**
     * 写入码值方法
     * @param array $color
     */
    private function _writeCode($color = null)
    {
        // 单个字符区间宽度
        $char_width = $this->_width / $this->_code_length;
        // x轴写入边界
        $x_edge = 0;
        // 写入码值
        for ($i = 0; $i < $this->_code_length; ++$i) {
            // 字符颜色
            if (!isset($color)) {
                $rcolor = array('r' => mt_rand(0, 180), 'b' => mt_rand(0, 180), 'g' => mt_rand(0, 180));
                $font_color = $this->_getColor($rcolor);
            } else {
                $font_color = $this->_getColor($color);
            }
            // 随机角度
            $angle = mt_rand(-30, 30);
            // 写入位置
            $x = mt_rand($x_edge, $x_edge + $char_width - 0.5 * $this->_font_width);
            $y = mt_rand($this->_font_height, $this->_height * 7 / 8);
            imagettftext($this->_img, $this->_font_size, $angle, $x, $y, $font_color, $this->_font, $this->_code[$i]);
            // 更新x轴写入边界
            $x_edge += $char_width;
        }

    }
}