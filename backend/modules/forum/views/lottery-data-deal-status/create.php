<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\LotteryDataDealStatus */

$this->title = Yii::t('app', 'Create Lottery Data Deal Status');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Lottery Data Deal Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="lottery-data-deal-status-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
