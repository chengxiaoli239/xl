<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\BtCrontabs */

$this->title = Yii::t('app', 'Create Bt Crontabs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Bt Crontabs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="bt-crontabs-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
