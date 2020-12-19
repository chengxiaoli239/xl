<?php

use yii\helpers\BaseStringHelper;
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

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'codes:ntext',
                        ['attribute' => 'codes','label' => '号码',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->codes,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->codes,'alt'=>$model->codes]);
                            }
                        ],
                        //'uid',
                        ['attribute' => 'uid','label' => 'UID',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->uid;
                            }
                        ],
                        //'agent_id',
                        //'account',
                        ['attribute' => 'account','label' => '账号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->account;
                            }
                        ],
                        //'bet_url:url',
                        ['attribute' => 'bet_url','label' => '接口',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->bet_url,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->bet_url,'alt'=>$model->bet_url]);
                            }
                        ],
                        //'bet_headers',
                        ['attribute' => 'bet_headers','label' => '头部',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->bet_headers,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->bet_headers,'alt'=>$model->bet_headers]);
                            }
                        ],
                        //'post_datas:ntext',
                        ['attribute' => 'post_datas','label' => '请求内容',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->post_datas,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->post_datas,'alt'=>$model->post_datas]);
                            }
                        ],
                        //'playway',
                        ['attribute'=>'playway','label'=>'类型',//'headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return \backend\service\FilterEnumeRateService::getPlayWayTxt($model->playway);
                            },
                            //'filter' => \backend\service\FilterEnumeRateService::getPlayWays()
                        ],
                        //'tz_type',
                        ['attribute' => 'tz_type','label' => '类型',
                            'format'=>'raw',
                            'value' => function($model) {
                                return \backend\service\BetService::getTypeNameByTzType($model->tz_type);
                            }
                        ],
                        //'playway_name',
                        //'bet_money',
                        ['attribute' => 'bet_money','label' => '金额',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bet_money;
                            }
                        ],
                        //'single',
                        ['attribute' => 'single','label' => '倍[元]',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        'qihao',
                        //'kj_codes',
                        //'status',
                        ['attribute' => 'status','label' => '状态',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->status == 2 ? '<font color="green">重推成功</font>' : ($model->status == 1 ? '<font color="red">重推失败</a>' : '<font color="red">未推送</a>');
                                $url = "/forum/user-custom-plans/update-status?id=".$model->id;
                                return Html::a($txt, $url, ['title' => '更新状态']);
                            }
                        ],
                        //'sn',
                        //'snid',
                        /*
                        ['attribute' => 'snid','label' => '单号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->snid;
                            }
                        ],
                        */
                        //'plan_id',
                        ['attribute' => 'plan_id','label' => 'planid',
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->plan_id;
                            }
                        ],
                        //'tz_system_id',
                        //'lotteryclass',
                        //'lottery_type',
                        ['attribute' => 'lottery_type','label' => '种类',
                            'format'=>'raw',
                            'value' => function($model) {
                                return \backend\service\BetService::getLotteryName($model->lottery_type);
                            }
                        ],
                        //'post_desc',
                        //'error_desc',
                        ['attribute' => 'error_desc','label' => '错误描述',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->error_desc,15);
                                return Html::a($txt, 'javascript:;', ['title' => $model->error_desc,'alt'=>$model->error_desc]);
                            }
                        ],
                        //'updated_time',
                        //'updated_at',
                        //'created_at',
                        ['attribute' => 'created_at','label' => '时间',
                            'format'=>'raw',
                            'value' => function($model) {
                                return date('m-d H:i', $model->created_at);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
