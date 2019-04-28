<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\PlaywaysAuth */

$this->title = Yii::t('app', 'Create Playways Auth');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Playways Auths'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="playways-auth-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
