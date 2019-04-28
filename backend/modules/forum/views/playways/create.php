<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Playways */

$this->title = Yii::t('app', 'Create Playways');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Playways'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="playways-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
