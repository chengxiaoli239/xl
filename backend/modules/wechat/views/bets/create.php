<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Bets */

$this->title = 'Create Bets';
$this->params['breadcrumbs'][] = ['label' => 'Bets', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="bets-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
