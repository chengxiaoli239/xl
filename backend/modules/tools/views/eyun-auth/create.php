<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuthBackend */

$this->title = '新建平台';
$this->params['breadcrumbs'][] = ['label' => 'Eyun Auths', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['breadcrumbs'][] = 'Create';
?>
<div class="eyun-auth-create wrapper site-min-height">
    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
