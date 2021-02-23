<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Static4dProfitsPerdate */

$this->title = Yii::t('app', 'Create Static4d Profits Perdate');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static4d Profits Perdates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static4d-profits-perdate-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
