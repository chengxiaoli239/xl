<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Matchs */

$this->title = Yii::t('app', 'Create Matchs');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Matchs'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="matchs-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
