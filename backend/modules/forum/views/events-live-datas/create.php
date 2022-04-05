<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\sports\EventsLiveDatas */

$this->title = Yii::t('app', 'Create Events Live Datas');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Events Live Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="events-live-datas-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
