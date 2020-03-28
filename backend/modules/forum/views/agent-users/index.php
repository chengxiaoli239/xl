<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\AgentUsers */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Agent Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="agent-users-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Agent Users'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'name',
                        ['attribute'=>'name','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->name;
                            }
                        ],
                        //'desc',
                        //'images',
                        ['attribute'=>'images','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->images;
                            }
                        ],
                        //'balance',
                        ['attribute'=>'balance','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->balance;
                            }
                        ],
                        //'is_tuo',
                        ['attribute'=>'is_tuo','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/agent-users/switch-status?field=is_tuo&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/agent-users/switch-status?field=is_tuo&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->is_tuo == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->is_tuo){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return $model->snid;
                            }
                        ],
                        //'is_chi',
                        ['attribute'=>'is_chi','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/agent-users/switch-status?field=is_chi&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/agent-users/switch-status?field=is_chi&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->is_chi == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->is_chi){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                return '';
                            }
                        ],
                        //'is_cha',
                        ['attribute'=>'is_cha','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/agent-users/switch-status?field=is_cha&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/agent-users/switch-status?field=is_cha&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->is_cha == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->is_cha){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                return '';
                            }
                        ],
                        //'status',
                        ['attribute'=>'status','label'=>'账号状态','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/agent-users/switch-status?field=status&id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/agent-users/switch-status?field=status&id=".$model->id.'&status=0'; # 点击关闭
                                if($model->status == 1){
                                    $txt = "<font color='green'>已激活</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击停用']);
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>已停用</font>";
                                    return Html::a($txt, $url0, ['title' => '点击激活']);
                                }
                                return '';
                            }
                        ],
                        //'all_bet_money',
                        ['attribute'=>'all_bet_money','headerOptions'=>['width'=>'5%'],// 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->all_bet_money;
                            }
                        ],
                        //'is_bind',
                        'bet_url:url',
                        'token',

                        //'created_at',
                        //'updated_at',
                        'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
