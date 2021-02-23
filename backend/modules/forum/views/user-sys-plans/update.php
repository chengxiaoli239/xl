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
        'tz_sites_Arr' => $tz_sites_Arr,
        'kArr' => $kArr,
        'tz_type' => $model->tz_type,
        'plan_types' => $plan_types,
        'code_types' => $code_types,
        'get_arise' => $get_arise,
        'hefen' => $hefen,
        'hefen_pos' => $hefen_pos,
        'remove_arise' => $remove_arise,
        'hzArr' => $hzArr,
        'type_4ds_Arr' => $type_4ds_Arr,
    ]) ?>

</section>
