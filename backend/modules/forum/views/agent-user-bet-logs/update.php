<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model backend\models\AgentUserBetLogs */

$this->title = 'Update Agent User Bet Logs: {nameAttribute}';
$this->params['breadcrumbs'][] = ['label' => 'Agent User Bet Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<section class="agent-user-bet-logs-update wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</section>
