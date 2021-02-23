<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\WxFriends */

$this->title = Yii::t('app', 'Create Wx Friends');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Wx Friends'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="wx-friends-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
