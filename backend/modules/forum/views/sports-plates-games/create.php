<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsPlatesGames */

$this->title = Yii::t('app', 'Create Sports Plates Games');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports Plates Games'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sports-plates-games-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
