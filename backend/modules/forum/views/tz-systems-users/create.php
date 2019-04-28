<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsUsers */

$this->title = Yii::t('app', 'Create Tz Systems Users');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tz Systems Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tz-systems-users-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
