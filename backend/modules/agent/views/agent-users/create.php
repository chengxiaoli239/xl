<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\AgentUsers */

$this->title = Yii::t('app', 'Create Agent Users');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Agent Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="agent-users-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
