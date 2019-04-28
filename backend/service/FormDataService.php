<?php
/**
 * @decription 解析html页面元素类
 * Created by PhpStorm.
 * User: wangyegao
 * Date: 2018/02/05
 * Time: 09:40
 */

namespace backend\service;
use  yii;
use common\tools\Util;

class FormDataService extends BaseService{

    /**
     * @decription 获取表单数据
     * @param $arr_form
     */
    public static function handlFormData($arr_form){
        if(empty($arr_form))
        {
            echo '抱歉！未匹配到 form 表单元素';
        }else{
            foreach($arr_form as $k => $v)
            {
                echo 'form'.($k+1).':<br />';
                if(!empty($v['action']))
                {
                    echo '----action：<br />';
                    echo '--------'.$v['action'].'<br />';
                }
                if(!empty($v['method']))
                {
                    echo '----method：<br />';
                    echo '--------'.$v['method'].'<br />';
                }
                if(!empty($v['inputs']))
                {
                    echo '----inputs：<br />';
                    foreach($v['inputs'] as $key => $value)
                    {
                        echo '--------name：'.$value['name'].' type：'.$value['type'].' value：'.$value['value'].'<br />';
                    }
                }
                if(!empty($v['textarea']))
                {
                    echo '----textarea：<br />';
                    foreach($v['textarea'] as $key => $value)
                    {
                        echo '--------name：'.$value['name'].' value：'.$value['value'].'<br />';
                    }
                }
                if(!empty($v['select']))
                {
                    echo '----select：<br />';
                    for($m = 0;$m < count($v['select']);$m ++)
                    {
                        echo '--------name：'.$v['select'][$m]['name'].'<br />';
                        if(!empty($v['select'][$m]['option']))
                        {
                            foreach ($v['select'][$m]['option'] as $key => $value)
                            {
                                echo '------------value：'.$value.'<br />';
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * @decription 获取页面html内容
     * @param $username
     * @param $password
     */
    public static function get_page_form_data($content){
        $arr_form = array();
        $form = FormDataService::regular_form_tags($content);
        for($i = 0;$i < count($form[0]);$i ++)
        {
            $arr_form[$i]['action'] = FormDataService::regular_form_action($form[1][$i]);
            $arr_form[$i]['method'] = FormDataService::regular_form_method($form[1][$i]);
            $input = FormDataService::regular_input_tags($form[2][$i]);
            for($j = 0;$j < count($input[0]);$j ++)
            {
                $arr_form[$i]['inputs'][$j]['name'] = FormDataService::regular_input_name($input[0][$j]);
                $arr_form[$i]['inputs'][$j]['type'] = FormDataService::regular_input_type($input[0][$j]);
                $arr_form[$i]['inputs'][$j]['value'] = FormDataService::regular_input_value($input[0][$j]);
            }
            $textarea = FormDataService::regular_textarea_tags($form[2][$i]);
            for($k = 0;$k < count($textarea);$k ++)
            {
                $arr_form[$i]['textarea'][$k]['name'] = FormDataService::regular_textarea_name($textarea[$k]);
                $arr_form[$i]['textarea'][$k]['value'] = FormDataService::regular_textarea_value($textarea[$k]);
            }
            $select = FormDataService::regular_select_tags($form[2][$i]);
            for($l = 0;$l < count($select[0]);$l ++)
            {
                $arr_form[$i]['select'][$l]['name'] = FormDataService::regular_select_name($select[1][$l]);
                $option = FormDataService::regular_option_tags($select[2][$l]);
                for($n = 0;$n < count($option[$l]);$n ++)
                {
                    $arr_form[$i]['select'][$l]['option'][$n] = FormDataService::regular_option_value($option[$l][$n]);
                }
            }

        }
        return $arr_form;

    }

    // 正则匹配 form 标签
    public static function regular_form_tags($string)
    {
        $pattern = '/<form(.*?)>(.*?)<\/form>/si';
        preg_match_all($pattern,$string,$result);
        return $result;
    }

    // 正则匹配 form 标签的 action 属性值
    public static function regular_form_action($string)
    {
        $pattern = '/action[\s]*?=[\s]*?([\'\"])(.*?)\1/';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

    // 正则匹配 form 标签的 method 属性值
    public static function regular_form_method($string)
    {
        $pattern = '/method[\s]*?=[\s]*?([\'\"])(.*?)\1/';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

    // 正则匹配 input 标签
    public static function regular_input_tags($string)
    {
        $pattern = '/<input.*?\/?>/si';
        if(preg_match_all($pattern,$string,$result))
        {
            return $result;
        }
        return null;
    }

    // 正则匹配 input 标签的 name 属性值
    public static function regular_input_name($string)
    {
        $pattern = '/name[\s]*?=[\s]*?([\'\"])(.*?)\1/';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;

    }

    // 正则匹配 input 标签的 type 属性值
    public static function regular_input_type($string)
    {
        $pattern = '/type[\s]*?=[\s]*?([\'\"])(.*?)\1/';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

    // 正则匹配 input 标签的 value 属性值
    public static function regular_input_value($string)
    {
        $pattern = '/value[\s]*?=[\s]*?([\'\"])(.*?)\1/';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

    // 正则匹配 textarea 标签
    public static function regular_textarea_tags($string)
    {
        $pattern = '/(<textarea.*?>.*?<\/textarea[\s]*?>)/si';
        if(preg_match_all($pattern,$string,$result))
        {
            return $result[1];
        }
        return null;
    }

    // 正则匹配 textarea 标签的 name 属性值
    public static function regular_textarea_name($string)
    {
        $pattern = '/name[\s]*?=[\s]*?([\'\"])(.*?)\1/si';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

    // 正则匹配 textarea 标签的 name 属性值
    public static function regular_textarea_value($string)
    {
        $pattern = '/<textarea.*?>(.*?)<\/textarea>/si';
        if(preg_match($pattern,$string,$result))
        {
            return $result[1];
        }
        return null;
    }

    // 正则匹配 select 标签
    public static function regular_select_tags($string)
    {
        $pattern = '/<select(.*?)>(.*?)<\/select[\s]*?>/si';
        preg_match_all($pattern,$string,$result);
        return $result;
    }

    // 正则匹配 select 标签的 option 子标签
    public static function regular_option_tags($string)
    {
        $pattern = '/<option(.*?)>.*?<\/option[\s]*?>/si';
        preg_match_all($pattern,$string,$result);
        return $result;
    }

    // 正则匹配 select 标签的 name 属性值
    public static function regular_select_name($string)
    {
        $pattern = '/name[\s]*?=[\s]*?([\'\"])(.*?)\1/si';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

    // 正则匹配 select 的子标签 option 的 value 属性值
    public static function regular_option_value($string)
    {
        $pattern = '/value[\s]*?=[\s]*?([\'\"])(.*?)\1/si';
        if(preg_match($pattern,$string,$result))
        {
            return $result[2];
        }
        return null;
    }

}