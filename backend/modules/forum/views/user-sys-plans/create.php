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

$this->title = Yii::t('app', 'Create User Sys Plans '.$playway.'d');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Sys Plans'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-sys-plans-create wrapper site-min-height">

    <?= $this->render('_form_'.$tpl, [
        'model' => $model,
        'kArr' => $kArr,
        'tz_type' => $tz_type,
        'hzArr' => $hzArr,
        'tz_sites_Arr' => $tz_sites_Arr,
    ]) ?>

</div>
