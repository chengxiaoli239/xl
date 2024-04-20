<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\statics\Ssc1numsYl */

$this->title = 'Create Ssc1nums Yl';
$this->params['breadcrumbs'][] = ['label' => 'Ssc1nums Yls', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc1nums-yl-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
