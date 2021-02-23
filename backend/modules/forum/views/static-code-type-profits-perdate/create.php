<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticCodeTypeProfitsPerdate */

$this->title = Yii::t('app', 'Create Static Code Type Profits Perdate');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Code Type Profits Perdates'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-code-type-profits-perdate-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
