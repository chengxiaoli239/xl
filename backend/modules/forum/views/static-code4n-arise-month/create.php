<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticCode4nAriseMonth */

$this->title = Yii::t('app', 'Create Static Code4n Arise Month');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Code4n Arise Months'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-code4n-arise-month-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
