<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\sports\EventsLiveDatas */

$this->title = Yii::t('app', 'Create Events Live Datas');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Events Live Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$sport = 1;
$tpl = $sport.'_'.$sport_type;
?>
<div class="events-live-datas-create wrapper site-min-height">

    <?= $this->render('_form_'.$tpl, [
        'model' => $model,
        'sport_types' => $sport_types,
        'sport_type' => $sport_type,
    ]) ?>

</div>
