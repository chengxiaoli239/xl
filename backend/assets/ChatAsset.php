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
        'static/static/css/mend-reset.css',
        'static/static/css/mend-weikeniu.css',
        'static/static/css/weui.css',
        'static/static/css/mescroll.min.css',
        'statics/css/bootstrap.min.css',
    ];
    public $js = [
        'static/static/js/jquery-1.8.0.min.js',
        'static/static/js/weui.min.js',
        'static/static/js/fontSize.js',
        'static/static/js/fingerprint2.min.js',
        'static/static/js/mescroll.min.js',
        'static/static/js/clipboard.min.js'
    ];
    public $depends = [
        'yii\web\YiiAsset',
//        'yii\bootstrap\BootstrapAsset',
        'yii\bootstrap\BootstrapPluginAsset',
    ];
}
