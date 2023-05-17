<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\AgentUserBetLogs */

$this->title = 'Create Agent User Bet Logs';
$this->params['breadcrumbs'][] = ['label' => 'Agent User Bet Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="agent-user-bet-logs-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
