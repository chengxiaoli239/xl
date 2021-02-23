<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\WxMsgTypes */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Wx Msg Types');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="wx-msg-types-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Wx Msg Types'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'uid',
                        'msg:ntext',
                        //'status',
                        ['attribute' => 'status','label'=>'是否开启',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $type_Arr = [0=>'已关闭', 1=>'已开启'];
                                $txt = $type_Arr[$model->status];
                                $url0 = "/forum/wx-msg-types/switch-status?id=".$model->id.'&status=1'; # 切换开启
                                $url1 = "/forum/wx-msg-types/switch-status?id=".$model->id.'&status=0'; # 切换关闭
                                if($model->status == 1){
                                    $txt = "<font color='green'>$txt</font>" ;
                                    return Html::a($txt, $url1, ['title' => '切换关闭']);
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>$txt</font>";
                                    return Html::a($txt, $url0, ['title' => '切换开启']);
                                }
                            }
                        ],
                        'is_name',
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
