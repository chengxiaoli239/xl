<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsPlates */

$this->title = Yii::t('app', 'Create Sports Plates');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports Plates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sports-plates-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
