<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscDsStatic */

$this->title = Yii::t('app', 'Create Ssc Ds Static');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Ds Statics'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-ds-static-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
