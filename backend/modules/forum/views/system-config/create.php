<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SystemConfig */

$this->title = Yii::t('app', 'Create System Config');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'System Configs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="system-config-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
