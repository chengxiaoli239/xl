<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscKjData3num */

$this->title = Yii::t('app', 'Create Ssc Kj Data3num');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Kj Data3nums'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-kj-data3num-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
