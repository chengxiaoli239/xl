<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\BaseStringHelper;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\UserCustomPlans */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'User Custom Plans');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="user-custom-plans-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 1 Plans'), ['create', 'playway'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 2 Plans'), ['create', 'playway'=>2], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 3 Plans'), ['create', 'playway'=>3], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 10 Plans'), ['create', 'playway'=>10], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

    <?php Pjax::begin(['timeout'=>5000]); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'account',
                        ['attribute' => 'playway','label' => '投注方式',
                            'value' => function($model) {
                                return backend\service\BetService::lotteryClass($model->playway);
                            }
                        ],
                        //'codes',
                        ['attribute' => 'codes',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->codes,25);
                                return Html::a($txt, '#', ['title' => $model->codes,'alt'=>$model->codes]);
                            }
                        ],
                        //'hezhis',
                        'positions',
                        //'periods_open',
                        'threshold_open',
                        //'periods_open',
                        //'periods_close',
                        'threshold_close',
                        //'periods_close',
                        'single',
                        ['attribute' => 'status','label' => '状态',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->status ? '<font color="green">已开启</font>' : '<font color="red">已关闭</a>';
                                $url = "/forum/user-custom-plans/update-status?id=".$model->id;
                                return Html::a($txt, $url, ['title' => '更新状态']);
                            }
                        ],
                        'is_simulate',
                        ['attribute'=>'hezhis','label'=>'描述',
                            'format'=>'raw',
                            'value' => function($model){
                                if( $model->playway == 1){
                                    $value = '和值：'.$model->hezhis;
                                }else{
                                    $value = '单双值：'.$model->hezhis;
                                }
                                return $value;
                            }
                        ],
                        //'created_at',
                        'updated_at:datetime',
                        ['label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                return Html::a(Yii::t('app', 'edit'), ['update','id'=>$model->id], ['class'=>'btn btn-success', 'style'=>'margin-bottom:15px;']);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
