<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model common\models\QueueLog */

$this->title = Yii::t('app', 'Create Queue Log');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Queue Logs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="queue-log-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
