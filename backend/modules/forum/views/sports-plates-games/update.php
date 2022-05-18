<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsPlatesGames */

$this->title = Yii::t('app', 'Update Sports Plates Games: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports Plates Games'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="sports-plates-games-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
