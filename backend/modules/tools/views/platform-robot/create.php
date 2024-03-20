<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\open\PlatformRobot */

$this->title = 'Create Platform Robot';
$this->params['breadcrumbs'][] = ['label' => 'Platform Robots', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="platform-robot-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
