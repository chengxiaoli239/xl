<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuth */

$this->title = 'Update Eyun Auth: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Eyun Auths', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="eyun-auth-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
