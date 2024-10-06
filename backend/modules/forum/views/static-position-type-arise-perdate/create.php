<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\statics\StaticPositionTypeArisePerdate */

$this->title = 'Create Static Position Type Arise Perdate';
$this->params['breadcrumbs'][] = ['label' => 'Static Position Type Arise Perdates', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-position-type-arise-perdate-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
