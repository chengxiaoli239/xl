<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\sports\SportsRelated */

$this->title = Yii::t('app', 'Create Sports Related');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Sports Relateds'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="sports-related-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
