<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\wechat\Odds */

$this->title = 'Create Odds';
$this->params['breadcrumbs'][] = ['label' => 'Odds', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="odds-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
