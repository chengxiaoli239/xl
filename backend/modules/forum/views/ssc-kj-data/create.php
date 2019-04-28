<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SscKjData */

$this->title = Yii::t('app', 'Create Ssc Kj Data');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc Kj Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc-kj-data-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
