<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\UserFollowData */

$this->title = Yii::t('app', 'Update playway '.$model->playway.' plan').'（ID：'.$model->id.'）';
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'User Follow Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="user-follow-data-update wrapper site-min-height">

    <?= $this->render('_form_'.$model->playway, [
        'model' => $model,
    ]) ?>

</section>
