<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscDsYl */

$this->title = Yii::t('app', 'Create Ssc Ds Yl');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Ds Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-ds-yl-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
