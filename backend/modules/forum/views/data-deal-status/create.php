<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\DataDealStatus */

$this->title = Yii::t('app', 'Create Data Deal Status');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Data Deal Statuses'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="data-deal-status-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
