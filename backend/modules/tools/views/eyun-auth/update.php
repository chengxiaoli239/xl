<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuthBackend */

$this->title = '新增/编辑';
$this->params['breadcrumbs'][] = ['label' => 'Eyun Auths', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="eyun-auth-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
