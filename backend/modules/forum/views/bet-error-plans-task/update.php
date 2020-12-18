<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\BetErrorPlansTask */

$this->title = Yii::t('app', 'Update Bet Error Plans Task: {nameAttribute}', [
    'nameAttribute' => $model->id,
]);
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Bet Error Plans Tasks'), 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = Yii::t('app', 'Update');
?>
<section class="bet-error-plans-task-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
