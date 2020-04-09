<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\AgentRecordUsersDesc */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Agent Record Users Descs');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="agent-record-users-desc-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Agent Record Users Desc'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'agent_id',
                        'member_id',
                        'member_account',
                        'token',
                        'desc',
                        'return',
                        'type',
                        'lottery_type',
                        'qihao',
                        'status',
                        'user_info:ntext',
                        //'created_at',
                        //'updated_at',
                        'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
