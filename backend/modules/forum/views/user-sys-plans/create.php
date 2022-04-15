<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */
if( $tz_type >= 17){
    $playway = \backend\models\TzTypes::findOne(['type'=>$tz_type])->playway;
    $tpl = $playway.'_'.$tz_type;
}else{
    $tpl = $playway;
}

if(in_array($tz_type, \Yii::$app->params['IS_XIAN'])){
    $this->title = Yii::t('app', 'Create User Sys Plans '.$playway.'_'.$tz_type.'d');
}else{
    $this->title = Yii::t('app', 'Create User Sys Plans '.$playway.'d');
}
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Sys Plans'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-sys-plans-create wrapper site-min-height">

    <?= $this->render('_form_'.$tpl, [
        'model' => $model,
        'lottery_type' => $lottery_type,
        'kArr' => $kArr,
        'tz_type' => $tz_type,
        'hzArr' => $hzArr,
        'hefen' => $hefen,
        'hefen_pos' => $hefen_pos,
        'code_types' => $code_types,
        'plan_types' => $plan_types,
        'tz_sites_Arr' => $tz_sites_Arr,
        'type_4ds_Arr' => $type_4ds_Arr, # 单双类型
        'type_ds_details_Arr' => $type_ds_details_Arr, # 单双详细类型 1122,2121,2222 等

        # 1、排除前x期
        'is_filters' => $is_filters,
        'filter_pos1' => $filter_pos1,
        'filter_pos2' => $filter_pos2,

        # 2、排除前x天同期
        'is_filter_dates' => $is_filter_dates,
        'filter_date_pos1' => $filter_date_pos1,
        'filter_date_pos2' => $filter_date_pos2,

        # 3、排除期号，比如：058期 则排除 58XX
        'is_filter_qihaos' => $is_filter_qihaos,
        'lottery_types' => $lottery_types,

        'code_filter_types' => $code_filter_types, # 号码过滤类型
    ]) ?>

</div>
