<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\BetErrorPlansTask */

$this->title = Yii::t('app', 'Create Bet Error Plans Task');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Bet Error Plans Tasks'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="bet-error-plans-task-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
