<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsInPlay */

$this->title = Yii::t('app', 'Create Sports In Play');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports In Plays'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sports-in-play-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
