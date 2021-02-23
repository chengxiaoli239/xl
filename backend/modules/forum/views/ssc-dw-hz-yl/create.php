<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzYl */

$this->title = Yii::t('app', 'Create Ssc Dw Hz Yl');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dw Hz Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-dw-hz-yl-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
