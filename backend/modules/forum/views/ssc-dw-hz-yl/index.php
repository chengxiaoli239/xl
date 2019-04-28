<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use backend\models\SscKjData;
use backend\models\SscDwHzStatic;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscDwHzYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = $zhi.Yii::t('app', 'Ssc Dw Hz Yls');
$this->params['breadcrumbs'][] = $this->title;
$newRecord = SscKjData::find()->select(['qihao','code_str'])->orderBy('id DESC')->asArray()->limit(1)->one();
?>
<section class="ssc-dw-hz-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title);echo '['.$newRecord['qihao'].':'.$newRecord['code_str'].']'; ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Dw Hz Yl'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

    <?php Pjax::begin(); ?>
                <?php  echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'positions',
                        'zhi',

                        'current_miss',
                        'yl_records',
                        //['attribute'=>'current_miss','label'=>'本期遗漏','value'=>function($model){
                        //    return $model->current_miss > 20 ? "<a href='#' color='red'>".$model->current_miss."</a>" : $model->current_miss;
                        //}],
                        //'last_time_miss',
                        ['attribute' => 'position', 'label'=>'近200期(次)',
                            'format'=>'raw',
                            'value'=>function($model){
                                $value = SscDwHzStatic::findOne(['positions'=>$model->positions,'periods'=>200])['hz_'.$model->zhi];
                                if($value <= 13){
                                    $value = '<font color="red">'.$value.'</font>';
                                }elseif ($value < 15){
                                    $value = '<font color="#a52a2a">'.$value.'</font>';
                                }
                                return $value;
                            }
                        ],
                        ['attribute' => 'position', 'label'=>'近5000期(次)',
                            'format'=>'raw',
                            'value'=>function($model){
                                $value = SscDwHzStatic::findOne(['positions'=>$model->positions,'periods'=>5000])['hz_'.$model->zhi];
                                if($value <= 450){
                                    $value = '<font color="red">'.$value.'</font>';
                                }elseif ($value < 470){
                                    $value = '<font color="#a52a2a">'.$value.'</font>';
                                }
                                return $value;
                            }
                        ],
                        'last_time_miss_range',
                        'max_miss',
                        'max_range',
                        'history_max_miss',
                        //'qihao',
                        [ 'attribute'=>'update_time','label'=>'更新时间',
                            'value'=>function($model){
                                return $model->update_time;
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn', 'template'=>'{view}'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
