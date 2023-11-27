<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\EyunAuth */

$this->title = 'Create Eyun Auth';
$this->params['breadcrumbs'][] = ['label' => 'Eyun Auths', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="eyun-auth-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
