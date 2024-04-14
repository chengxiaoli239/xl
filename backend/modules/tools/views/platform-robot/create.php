<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuthBackend */

$this->title = '机器人';
$this->params['breadcrumbs'][] = ['label' => '机器人', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = 'Create';
?>
<div class="platform-robot-create wrapper site-min-height">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
</div>
