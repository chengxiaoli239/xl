<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BtCrontabs */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Bt Crontabs');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bt-crontabs-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Bt Crontabs'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        ['attribute' => 'uid','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->uid;
                            }
                        ],
                        //'p_id',
                        //'name',
                        ['attribute' => 'name','headerOptions'=>['width'=>'20%'],
                            'value' => function($model) {
                                return $model->name;
                            }
                        ],
                        //'sName',
                        //'sType',
                        //'status',
                        ['attribute'=>'status', 'label'=>'状态','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/forum/bt-crontabs/switch-status?id=".$model->id.'&status=1'; # 点击开启
                                $url1 = "/forum/bt-crontabs/switch-status?id=".$model->id.'&status=0'; # 点击关闭
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
                        //'domain',
                        //'echo',
                        //'cycle',
                        //'backupTo',
                        //'save',
                        //'where_minute',
                        //'where_hour',
                        //'where1',
                        'sBody',
                        //'type_desc',
                        //'urladdress',
                        //'addtime',
                        ['attribute'=>'backupTo', 'label'=>'操作','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $txt = "<font color='green'>执行</font>" ;
                                return Html::a($txt, 'javascript:;', ['title' => '点击关闭']);
                            }
                        ],
                        ['attribute' => 'addtime','label' => '更新时间',
                            'format'=>'raw',
                            'value' => function($model) {
                                return substr($model->addtime, 5, 11);
                            }
                        ],
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
