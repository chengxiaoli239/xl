<?php

namespace common\service\thirdD;

class OperateApiDataService extends CommonBaseService
{

    /**
     * 直选
     * @param string $code
     */
    public static function runZhiXuan(string &$code='')
    {
        if(empty($code)){
            throw_info('号码不能为空');
        }
    }

    /**
     * 组选：组三、组六
     * @param string $code
     */
    public static function runZuXuan(string $code=''){
        if(empty($code)){
            throw_info('号码不能为空');
        }
    }

    /**
     * 独胆
     * @param string $code
     */
    public static function runDuDan(string $code=''){
        if(empty($code)){
            throw_info('号码不能为空');
        }

    }

    public static function runShuangFen(string &$code='')
    {
        if(empty($code)){
            throw_info('记录不能为空');
        }
    }

    /**
     * 一码定位
     * @param string $code
     */
    public static function runYiMaDing(string &$code=''){
        if(empty($code)){
            throw_info('记录不能为空');
        }
    }

    /**
     * 二码定位
     * @param string $code
     */
    public static function runErMaDing(string &$code='')
    {
        if(empty($code)){
            throw_info('记录不能为空');
        }

    }

    /**
     * 豹子全包
     * @param string $code
     */
    public static function runBaoZiQB(string &$code=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $code = '豹子全包';
    }

    /**
     * 对子全包
     * @param string $code
     */
    public static function runDuiZiQB(string $code='')
    {
        if(empty($code)){
            throw_info('记录不能为空');
        }
    }

    /**
     * 组六x码
     * @param string $code
     */
    public static function runZuLiuXMa(string &$code=''){
        if(empty($code)){
            throw_info('号码不能为空');
        }

    }

    /**
     * 组三x码
     * @param string $code
     */
    public static function runZuSanXMa(string &$code=''){
        if(empty($code)){
            throw_info('号码不能为空');
        }

    }

    /**
     * 组三、组六全包
     * @param string $code
     */
    public static function runZuXuanQuanBao(string &$code=''){
        if(empty($code)){
            throw_info('号码不能为空');
        }
    }

    /**
     * 跨度
     */
    public static function runKuaDu(string &$code=''){
        if(empty($code)){
            throw_info('号码不能为空');
        }
    }

    /**
     * 一拖x组六
     * @param string $code
     */
    public static function runYiTuoZuLiu(string &$code='')
    {
        if(empty($code)){
            throw_info('号码不能为空');
        }
        if(preg_match('/\((\d)\)(\d+)/', $code, $ms)){
            $code = $ms[1].'拖'.$ms[2].'_组六';
        }
    }

    /**
     * 一拖x组三
     * @param string $code
     * @return string
     */
    public static function runYiTuoZuSan(string &$code='')
    {
        if(empty($code)){
            throw_info('号码不能为空');
        }
        if(preg_match('/\((\d)\)(\d+)/', $code, $ms)){
            $code = $ms[1].'拖'.$ms[2].'_组三';
        }
    }

    /**
     * 复式三...九
     * @param string $code
     * @return string
     */
    public static function runFuShiX(string &$code=''){
        if(empty($betRow)){
            throw_info('记录不能为空');
        }
        $code = trim(str_replace(['跨度', '跨'], '', $code));
    }

    /**
     * 和值0...27
     * @param string $code
     */
    public static function runHeZhi(string &$code=''){
        if(empty($code)){
            throw_info('记录不能为空');
        }

        $code = trim(str_replace(['和值', '和'], '', $code));
    }

    /**
     * 和值大小单双
     * @param string $kjCode 2,3,4
     */
    public static function runHeZhiDxDs(string &$code='')
    {
        if(empty($code)){
            throw_info('记录不能为空');
        }
        $code = trim(str_replace(['大', '小', '单', '双'], '', $code));
    }

    /**
     * 直选复式定位
     * @param string $code
     */
    public static function runHeZhiXuanFuShiDw(string $code=''){
        if(empty($code)){
            throw_info('记录不能为空');
        }

    }

    /**
     * 全倒
     * @param string $code
     */
    public static function runQuanDao(string &$code=''){
        if(empty($code)){
            throw_info('记录不能为空');
        }
    }

    /**
     * 直选复式  跟全倒的匹配逻辑一样
     * @param string $kjCode 2,3,4
     */
    public static function runZhiXuanFuShi(string $code=''){
        if(empty($code)){
            throw_info('记录不能为空');
        }
    }

}
