<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\TzSystems */

$this->title = Yii::t('app', 'Create Tz Systems');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tz Systems'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tz-systems-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
