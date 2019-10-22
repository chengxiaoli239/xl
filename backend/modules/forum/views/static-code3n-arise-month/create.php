<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\StaticCode3nAriseMonth */

$this->title = Yii::t('app', 'Create Static Code3n Arise Month');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Static Code3n Arise Months'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="static-code3n-arise-month-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
