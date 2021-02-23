<?php

namespace backend\assets;

use yii\web\AssetBundle;

/**
 * Main backend application asset bundle.
 */
class ChatAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'chat_statics/css/mend-reset.css',
        'chat_statics/css/mend-weikeniu.css',
        'chat_statics/css/weui.css',
        'chat_statics/css/mescroll.min.css',
        'statics/css/bootstrap.min.css',
    ];
    public $js = [
        'chat_statics/js/jquery-1.8.0.min.js',
        'chat_statics/js/weui.min.js',
        'chat_statics/js/fontSize.js',
        'chat_statics/js/fingerprint2.min.js',
        'chat_statics/js/mescroll.min.js',
        'chat_statics/js/clipboard.min.js',
    ];
    public $depends = [
        'yii\web\YiiAsset',
//        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
