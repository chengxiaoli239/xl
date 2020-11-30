<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\LotteryType */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Lottery Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="lottery-type-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Lottery Type'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'sort',
                        //'lottery_type',
                        //'title',
                        ['attribute' => 'title','label'=>'名称',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->title;
                            }
                        ],
                        ['attribute' => 'lottery_type','label'=>'系统lottery_type',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                //return  \backend\service\Config_Base::lotteryTypeLists($model->lottery_type);
                                return  $model->lottery_type;
                            }
                        ],
                        //'shortName',
                        ['attribute' => 'shortName','label'=>'简称',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->shortName;
                            }
                        ],
                        //'enable',
                        ['attribute' => 'enable','label'=>'开启状态','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url0 = "/forum/lottery-type/switch-status?id=".$model->id.'&enable=1'; # 点击开启
                                $url1 = "/forum/lottery-type/switch-status?id=".$model->id.'&enable=0'; # 点击关闭
                                if($model->enable == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->enable){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return \backend\service\Config_Base::dropDown('enable', $model->enable);
                            },
                            'filter' => \backend\service\Config_Base::dropDown('enable'),
                        ],
                        //'isDelete',
                        //'name',
                        //'codeList',
                        ['attribute' => 'codeList','label'=>'号码',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->codeList;
                            }
                        ],
                        //'info',
                        ['attribute' => 'info','label'=>'描述',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->info;
                            }
                        ],
                        //'onGetNoed',
                        ['attribute' => 'onGetNoed','label'=>'事件函数',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->onGetNoed;
                            }
                        ],
                        ['attribute' => 'data_ftime','label'=>'时间间隔(s)',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->data_ftime;
                            }
                        ],
                        //'defaultViewGroup',
                        //'android',
                        //'num',
                        ['attribute' => 'typeGroupName','label'=>'彩种类别',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->typeGroupName;
                            }
                        ],

                        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
