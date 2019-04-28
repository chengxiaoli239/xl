<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\User */

$this->title = Yii::t('app', 'Create Tz Systems');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-create wrapper site-min-height">

    <?= $this->render('open-systems-create', [
        'model' => $model,
        'allSystems' => $allSystems,
        'allTzTypes' => $allTzTypes,
        'uid' => $uid,
    ]) ?>

</div>
