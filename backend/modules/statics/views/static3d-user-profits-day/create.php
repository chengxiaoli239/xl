<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\statics\Static3dUserProfitsDay */

$this->title = 'Create Static3d User Profits Day';
$this->params['breadcrumbs'][] = ['label' => 'Static3d User Profits Days', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static3d-user-profits-day-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
