<?php
class Common{

    public static function p($data,$exit = true){
        header("content-type:text/html;charset=utf-8");
        echo '<pre>';
        print_r($data);
        $exit && exit;
    }

    public static function d($data, $exit = true){
        header("content-type:text/html;charset=utf-8");
        echo '<pre>';
        var_dump($data);
        $exit && exit;

    }

}
