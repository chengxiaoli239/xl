<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscSdHzYl */

$this->title = Yii::t('app', 'Create Ssc Sd Hz Yl');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Sd Hz Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-sd-hz-yl-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
