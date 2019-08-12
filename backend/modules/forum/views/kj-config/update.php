<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\KjConfig */

$this->title = 'Update Kj Config: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Kj Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->title, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="kj-config-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
        'lottery_type_arr' => $lottery_type_arr,
    ]) ?>

</section>
