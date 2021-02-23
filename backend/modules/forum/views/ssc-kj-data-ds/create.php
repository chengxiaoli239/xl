<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscKjDataDs */

$this->title = Yii::t('app', 'Create Ssc Kj Data Ds');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Kj Data Ds'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-kj-data-ds-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
