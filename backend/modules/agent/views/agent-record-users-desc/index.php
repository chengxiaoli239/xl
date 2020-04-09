<?php

use yii\helpers\BaseStringHelper;
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
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Agent Record Users Desc'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'agent_id',
                        ['attribute'=>'agent_id','label'=>'代理','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->agent_id;
                            },
                        ],
                        //'member_id',
                        //['attribute'=>'member_id','label'=>'会员ID','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                        //    'format'=>'raw',
                        //    'value'=>function($model){
                        //        return $model->member_id;
                        //    },
                        //],
                        //'member_account',
                        ['attribute'=>'member_account','label'=>'会员','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $txt = 'ID:'.$model->member_id;
                                return Html::a($model->member_account, '#', ['title' => $txt,'alt'=>$txt]);
                            },
                        ],
                        //'token',
                        ['attribute'=>'token','label'=>'token','headerOptions'=>['width'=>'8%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->token;
                            },
                        ],
                        'desc',
                        //'return',
                        ['attribute' => 'return',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->return,14);

                                return Html::a($txt, '#', ['title' => $model->return,'alt'=>$model->return]);
                            }
                        ],
                        //'type',
                        ['attribute'=>'type','label'=>'类型','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return \backend\service\ChatCommonBetService::$types[$model->type];
                            },
                            //'filter' => \backend\service\AgentUsersService::getFlowtypes(),
                        ],
                        //'lottery_type',
                        ['attribute'=>'lottery_type','label'=>'彩种','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->lottery_type;
                            },
                        ],
                        //'qihao',
                        ['attribute'=>'qihao','label'=>'期号','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->qihao;
                            },
                        ],
                        //'status',
                        ['attribute'=>'status','label'=>'状态','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->status;
                            },
                        ],
                        //'user_info:ntext',
                        ['attribute'=>'user_info','label'=>'用户信息',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $txt = BaseStringHelper::truncate($model->user_info,24);
                                return Html::a($txt, '#', ['title' => $model->user_info,'alt'=>$model->user_info]);
                            },
                        ],
                        //'created_at',
                        //'updated_at',
                        'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
