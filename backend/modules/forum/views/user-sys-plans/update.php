<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\UserSysPlans */

//$this->title = Yii::t('app', 'Update User Sys Plans: {nameAttribute}', [ 'nameAttribute' => $model->id, ]);
$this->title = Yii::t('app', 'Update User Sys Plans');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Sys Plans'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');

if( $tz_type >= 17){
    $playway = \backend\models\TzTypes::findOne(['type'=>$tz_type])->playway;
    $tpl = $playway.'_'.$tz_type;
}else{
    $tpl = $playway;
}

?>
<section class="user-sys-plans-update wrapper site-min-height">

    <?= $this->render('_form_'.$tpl, [
        'model' => $model,
        'lottery_type' => $lottery_type,
        'tz_sites_Arr' => $tz_sites_Arr,
        'kArr' => $kArr,
        'tz_type' => $model->tz_type,
        'plan_types' => $plan_types,
        'code_types' => $code_types,
        'get_arise' => $get_arise,
        'hefen' => $hefen,
        'hefen_pos' => $hefen_pos,
        'sel_pos' => $sel_pos,
        'remove_arise' => $remove_arise,
        'hzArr' => $hzArr,
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
        # 动态过滤
        'filter_dynamic_typesArr' => $filter_dynamic_typesArr,

        # 3、排除期号，比如：058期 则排除 58XX
        'is_filter_qihaos' => $is_filter_qihaos,

        'code_filter_types' => $code_filter_types, # 号码过滤类型
    ]) ?>

</section>
