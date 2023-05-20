<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\AgentUserBetLogs */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Agent User Bet Logs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="agent-user-bet-logs-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <?= Html::encode($this->title) ?>
                </header>
                <div class="panel-body">
                    <p>
                        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger',
                            'data' => [
                                'confirm' => 'Are you sure you want to delete this item?',
                                'method' => 'post',
                            ],
                        ]) ?>
                    </p>
                    <div class="row">
                        <div class="col-lg-11">
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [

                                    'id',
                                    'access_token',
                                    'uid',
                                    'member_id',
                                    'account',
                                    'bet_logs:ntext',
                                    'bet_logs_n:ntext',
                                    'bet_codes:ntext',
                                    'bet_counts',
                                    'bet_single',
                                    'bet_codes_op:ntext',
                                    'bet_op_counts',
                                    'bet_op_single',
                                    'bet_type',
                                    'playway',
                                    'desc',
                                    'lottery_type',
                                    'qihao',
                                    'status',
                                    'member_bet_time',
                                    'tz_system_id',
                                    'created_at',
                                    'updated_at',
                                    'update_time',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>