<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\UserFollowData */

$this->title = Yii::t('app', 'Create playway '.$playway.' plan');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Follow Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-follow-data-create wrapper site-min-height">

    <?= $this->render('_form_'.$playway, [
        'model' => $model,
    ]) ?>

</div>
