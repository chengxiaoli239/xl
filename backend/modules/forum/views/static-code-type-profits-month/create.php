<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticCodeTypeProfitsMonth */

$this->title = Yii::t('app', 'Create Static Code Type Profits Month');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Code Type Profits Months'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-code-type-profits-month-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
