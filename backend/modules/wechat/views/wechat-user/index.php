<?php

use yii\helpers\BaseStringHelper;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\wechat\WechatUser */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '代理列表';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="wechat-user-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Wechat User', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>
                -->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn', 'headerOptions'=>['width'=>'2%']],

                        #'id',
                        #'user_id',
                        //'userName',
                        #['attribute' => 'userName','label'=>'微信ID','headerOptions'=>['width'=>'3%'],
                        #    'format'=>'raw',
                        #    'value' => function($model) {
                        #        return BaseStringHelper::truncate($model->userName,18);
                        #    }
                        #],
                        ['attribute' => 'smallHead', 'label'=>'头像','headerOptions'=>['width'=>'3%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return Html::img($model->smallHead, ['width' => '30px']);
                            },
                        ],

                        ['attribute' => 'nickName','label'=>'昵称','headerOptions'=>['width'=>'10%'], //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return BaseStringHelper::truncate($model->nickName,10);
                            }
                        ],
                        ['attribute' => 'userName', 'label'=>'微信ID','headerOptions'=>['width'=>'3%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return $model->userName;
                            },
                        ],
                        #'aliasName',
                        //'balance',
                        //'is_credit',
                        //'bigHead',
                        #'smallHead',
                        //'labelList',
                        //'remark',
                        ['attribute' => 'remark','label'=>'备注',//'headerOptions'=>['width'=>'20%'], //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->remark;
                            }
                        ],
                        ['attribute'=>'status', 'label'=>'状态','headerOptions'=>['width'=>'16%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "#"; # 点击开启
                                $url1 = "#"; # 点击关闭
                                if(true OR \Yii::$app->user->id == $model->uid){
                                    $url0 = "/wechat/wechat-user/switch-status?id=".$model->id.'&status=1'; # 点击开启
                                    $url1 = "/wechat/wechat-user/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                }
                                if($model->status == 1){
                                    $txt = "<strong><font color='green'>已开启</font></strong>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭消息接收', 'alt'=>'点击关闭消息接收']);
                                }
                                if(!$model->status){
                                    $txt = "<strong><font color='red'>已关闭</font></strong>";
                                    return Html::a($txt, $url0, ['title' => '点击开启消息接收', 'alt'=>'点击开启消息接收']);
                                }
                                //return $model->snid;
                            }
                        ],

                        #'expire_time:datetime',
                        //'created_at',
                        //'updated_at',
                        ['attribute' => 'update_at','label'=>'时间',//'headerOptions'=>['width'=>'20%'], //'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return substr($model->update_at, 5, 11);
                            }
                        ],

                        #['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
