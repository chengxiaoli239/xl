<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticPeiShuCodeTrueFalse */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static Pei Shu Code True Falses');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-pei-shu-code-true-false-index wrapper site-min-height">
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
                        <?= Html::a('Create Static Pei Shu Code True False', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>
                -->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>
                <?php include(dirname(__FILE__).'/index_tab.php'); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'date',
                        'qihao',
                        'kj_code',
                        //'code_147_369',
                        ['attribute' => 'code_147_369',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->code_147_369==1 ? "<font color='green'>√</font>" : "<font color='red'>X</font>";
                                return Html::a($txt, 'javascript:;', ['title' => '√对X错']);
                            }
                        ],
                        ['attribute' => 'code_259_369',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->code_259_369==1 ? "<font color='green'>√</font>" :"<font color='red'>X</font>";
                                return Html::a($txt, 'javascript:;', ['title' => '√对X错']);
                            }
                        ],
                        //'code_019_368',
                        ['attribute' => 'code_019_368',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->code_019_368==1 ? "<font color='green'>√</font>" :"<font color='red'>X</font>";
                                return Html::a($txt, 'javascript:;', ['title' => '√对X错']);
                            }
                        ],
                        //'code_123_678',
                        ['attribute' => 'code_123_678',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->code_123_678==1 ? "<font color='green'>√</font>" :"<font color='red'>X</font>";
                                return Html::a($txt, 'javascript:;', ['title' => '√对X错']);
                            }
                        ],
                        //'code_147_258',
                        ['attribute' => 'code_147_258',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->code_147_258==1 ? "<font color='green'>√</font>" :"<font color='red'>X</font>";
                                return Html::a($txt, 'javascript:;', ['title' => '√对X错']);
                            }
                        ],
                        //'code_017_348',
                        ['attribute' => 'code_017_348',//'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->code_017_348==1 ? "<font color='green'>√</font>" :"<font color='red'>X</font>";
                                return Html::a($txt, 'javascript:;', ['title' => '√对X错']);
                            }
                        ],
                        'code_456_789',
                        'code_012_789',
                        'code_345_678',
                        'code_357_019',
                        //'code_3b',
                        ['attribute' => 'code_3b','label'=>'三兄',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->code_3b;
                            }
                        ],
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',
                        ['attribute' => 'update_time','label'=>'时间',//'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return substr($model->update_time, 10,9);
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
