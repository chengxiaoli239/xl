<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscDwHzStatic */

$this->title = Yii::t('app', 'Create Ssc Dw Hz Static');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Dw Hz Statics'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-dw-hz-static-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
