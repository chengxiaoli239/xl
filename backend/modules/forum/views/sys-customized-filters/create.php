<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\SysCustomizedFilters */

$this->title = Yii::t('app', 'Create Sys Customized Filters');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sys Customized Filters'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sys-customized-filters-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
