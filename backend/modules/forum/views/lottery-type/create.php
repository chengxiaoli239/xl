<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\LotteryType */

$this->title = Yii::t('app', 'Create Lottery Type');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Lottery Types'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lottery-type-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
