<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BetErrorPlansTask */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Bet Error Plans Tasks');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bet-error-plans-task-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Bet Error Plans Task'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'codes:ntext',
                        'uid',
                        'agent_id',
                        'account',
                        //'bet_url:url',
                        //'bet_headers',
                        //'post_datas:ntext',
                        //'playway',
                        //'tz_type',
                        //'playway_name',
                        //'bet_money',
                        //'single',
                        //'qihao',
                        //'kj_codes',
                        //'status',
                        //'sn',
                        //'snid',
                        //'plan_id',
                        //'tz_system_id',
                        //'lotteryclass',
                        //'lottery_type',
                        //'post_desc',
                        //'error_desc',
                        //'updated_time',
                        //'updated_at',
                        //'created_at',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
