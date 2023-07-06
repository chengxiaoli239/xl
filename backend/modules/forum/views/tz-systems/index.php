<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\TzSystems */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Tz Systems');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="tz-systems-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <!--
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        -->
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Tz Systems'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'name',
                        'system_type_id',
                        //'ssc_domain',
                        ['attribute'=>'ssc_domain', 'label'=>'站点首页',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->ssc_domain;
                            }
                        ],
                        ['attribute'=>'tz_types', 'label'=>'对接玩法',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                return $model->tz_types;
                            }
                        ],
                        //'status',
                        ['attribute'=>'status', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/tz-systems/switch-status?id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/tz-systems/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                if($model->status == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        ['attribute'=>'is_auto_login', 'label'=>'自动登陆',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/tz-systems/switch-is-auto-login?id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/tz-systems/switch-is-auto-login?id=".$model->id.'&status=0'; # 点击关闭
                                if($model->is_auto_login == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->is_auto_login){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                            }
                        ],
                        ['attribute'=>'flow_status', 'label'=>'跟随开关',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url = "/forum/tz-systems/switch-is-auto-login?id=".$model->id.'&field=follow_status&status='.$model->follow_status; # 点击开启
                                if($model->follow_status == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url, ['title' => '点击关闭']);
                                }
                                if(!$model->follow_status){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url, ['title' => '点击开启']);
                                }
                            }
                        ],
                        //'type',
                        //'created_at',
                        //'updated_at',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
