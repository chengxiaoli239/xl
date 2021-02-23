<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\BettingRecords */

$this->title = 'Create Betting Records';
$this->params['breadcrumbs'][] = ['label' => 'Betting Records', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="betting-records-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
