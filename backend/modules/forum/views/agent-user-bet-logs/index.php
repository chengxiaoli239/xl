<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\AgentUserBetLogs */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Agent User Bet Logs';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="agent-user-bet-logs-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Agent User Bet Logs', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'access_token',
                        'member_id',
                        'account',
                        'bet_logs:ntext',
                        //'bet_codes:ntext',
                        //'bet_codes_op:ntext',
                        //'bet_type',
                        //'planway',
                        //'desc',
                        //'lottery_type',
                        //'qihao',
                        //'status',
                        //'tz_system_id',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
