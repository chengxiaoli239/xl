<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Static3numArisePerdate */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static3num Arise Perdates');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static3num-arise-perdate-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Static3num Arise Perdate'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'date',
                        //'codes_289',
                        ['attribute'=>'codes_289','label'=>'289',
                            'value'=>function($model){
                                return $model->codes_289 ? $model->codes_289 : '';
                            }
                        ],
                        //'codes_046',
                        ['attribute'=>'codes_046','label'=>'046',
                            'value'=>function($model){
                                return $model->codes_046 ? $model->codes_046 : '';
                            }
                        ],
                        //'codes_456',
                        ['attribute'=>'codes_456','label'=>'456',
                            'value'=>function($model){
                                return $model->codes_456 ? $model->codes_456 : '';
                            }
                        ],
                        //'codes_125',
                        ['attribute'=>'codes_125','label'=>'125',
                            'value'=>function($model){
                                return $model->codes_125 ? $model->codes_125 : '';
                            }
                        ],
                        //'codes_589',
                        ['attribute'=>'codes_589','label'=>'589',
                            'value'=>function($model){
                                return $model->codes_589 ? $model->codes_589 : '';
                            }
                        ],
                        //'codes_025',
                        ['attribute'=>'codes_025','label'=>'025',
                            'value'=>function($model){
                                return $model->codes_025 ? $model->codes_025 : '';
                            }
                        ],
                        //'codes_467',
                        ['attribute'=>'codes_467','label'=>'467',
                            'value'=>function($model){
                                return $model->codes_467 ? $model->codes_467 : '';
                            }
                        ],
                        //'codes_256',
                        ['attribute'=>'codes_256','label'=>'256',
                            'value'=>function($model){
                                return $model->codes_256 ? $model->codes_256 : '';
                            }
                        ],
                        //'codes_128',
                        ['attribute'=>'codes_128','label'=>'128',
                            'value'=>function($model){
                                return $model->codes_128 ? $model->codes_128 : '';
                            }
                        ],
                        //'codes_347',
                        ['attribute'=>'codes_347','label'=>'347',
                            'value'=>function($model){
                                return $model->codes_347 ? $model->codes_347 : '';
                            }
                        ],
                        //'codes_134',
                        ['attribute'=>'codes_134','label'=>'134',
                            'value'=>function($model){
                                return $model->codes_134 ? $model->codes_134 : '';
                            }
                        ],
                        //'codes_258',
                        ['attribute'=>'codes_258','label'=>'258',
                            'value'=>function($model){
                                return $model->codes_258 ? $model->codes_258 : '';
                            }
                        ],
                        //'codes_124',
                        ['attribute'=>'codes_124','label'=>'124',
                            'value'=>function($model){
                                return $model->codes_124 ? $model->codes_124 : '';
                            }
                        ],
                        //'codes_014',
                        ['attribute'=>'codes_014','label'=>'014',
                            'value'=>function($model){
                                return $model->codes_014 ? $model->codes_014 : '';
                            }
                        ],
                        //'codes_147',
                        ['attribute'=>'codes_147','label'=>'147',
                            'value'=>function($model){
                                return $model->codes_147 ? $model->codes_147 : '';
                            }
                        ],
                        //'codes_345',
                        ['attribute'=>'codes_345','label'=>'345',
                            'value'=>function($model){
                                return $model->codes_345 ? $model->codes_345 : '';
                            }
                        ],
                        //'codes_678',
                        ['attribute'=>'codes_678','label'=>'678',
                            'value'=>function($model){
                                return $model->codes_678 ? $model->codes_678 : '';
                            }
                        ],
                        //'codes_238',
                        ['attribute'=>'codes_238','label'=>'238',
                            'value'=>function($model){
                                return $model->codes_238 ? $model->codes_238 : '';
                            }
                        ],
                        //'codes_239',
                        ['attribute'=>'codes_239','label'=>'239',
                            'value'=>function($model){
                                return $model->codes_239 ? $model->codes_239 : '';
                            }
                        ],
                        //'codes_028',
                        ['attribute'=>'codes_028','label'=>'028',
                            'value'=>function($model){
                                return $model->codes_028 ? $model->codes_028 : '';
                            }
                        ],
                        //'codes_268',
                        ['attribute'=>'codes_268','label'=>'268',
                            'value'=>function($model){
                                return $model->codes_268 ? $model->codes_268 : '';
                            }
                        ],
                        //'codes_389',
                        ['attribute'=>'codes_389','label'=>'389',
                            'value'=>function($model){
                                return $model->codes_389 ? $model->codes_389 : '';
                            }
                        ],
                        //'codes_348',
                        ['attribute'=>'codes_348','label'=>'348',
                            'value'=>function($model){
                                return $model->codes_348 ? $model->codes_348 : '';
                            }
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
