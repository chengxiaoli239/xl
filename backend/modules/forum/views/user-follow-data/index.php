<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use backend\models\SscDwHzYl;
use backend\models\SscDwHzStatic;
use yii\helpers\BaseStringHelper;
use backend\service\SscDataService;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\UserFollowData */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'User Follow Datas');
$this->params['breadcrumbs'][] = $this->title;
$txt = Yii::t('app', 'Create tz plans');
?>
<section class="user-follow-data-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <?= Html::encode($txt) ?> ：
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 1'), ['create','playway'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 2 Plans'), ['create', 'playway'=>2], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 3 Plans'), ['create', 'playway'=>3], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'playway 10'), ['create','playway'=>10], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

    <?php Pjax::begin(['timeout' =>5000]); ?>
                <?php  //echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'account',
                        'position',
                        'codes_hezhi',
                        ['attribute' => 'playway','label' => '投注方式',
                            'value' => function($model) {
                                return backend\service\BetService::lotteryClass($model->playway);
                            }
                        ],
                        ['attribute' => 'code',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->code,25);
                                return Html::a($txt, 'javascript:;', ['title' => $model->code,'alt'=>$model->code]);
                            }
                        ],
                        ['attribute' => 'current_miss', 'label'=>'当前遗漏',
                            'format'=>'raw',
                            'value'=>function($model){
                                if($model->playway == 1){
                                    # 二字定
                                    $value = SscDwHzYl::findOne(['positions'=>$model->position,'zhi'=>$model->codes_hezhi])['current_miss'];
                                    if($value > 60){
                                        $value = '<font color="red">'.$value.'</font>';
                                    }elseif ($value > 50){
                                        $value = '<font color="#a52a2a">'.$value.'</font>';
                                    }
                                }elseif (in_array($model->playway, [2, 3])) {
                                    # 三字定 # 四字定
                                    $value = \backend\service\BaseNumService::getCodesYL($model->code, $model->playway);
                                }
                                return $value;
                            }
                        ],
                        ['attribute' => 'position', 'label'=>'近200期(次)',
                            'format'=>'raw',
                            'value'=>function($model){
                                if($model->codes_hezhi){
                                    $value = SscDwHzStatic::findOne(['positions'=>$model->position,'periods'=>200])['hz_'.$model->codes_hezhi];
                                    if($value <= 12){
                                        $value = '<font color="red">'.$value.'</font>';
                                    }elseif ($value < 14){
                                        $value = '<font color="#a52a2a">'.$value.'</font>';
                                    }
                                }else{
                                    $value = '';
                                }
                                return $value;
                            }
                        ],
                        //'reference_codes',
                        //'is_follow',
                        //'status',
                        ['attribute' => 'status','label' => '状态',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = $model->status ? '<font color="green">已开启</font>' : '<font color="red">已关闭</a>';
                                $status = $model->status == 1 ? 0 : 1;
                                $url = "/forum/user-follow-data/update-status?id=".$model->id.'&status='.$status;
                                return Html::a($txt, $url, ['title' => '更新状态']);
                            }
                        ],
                        ['attribute'=>'is_simulate','label'=>'类型(正常、模拟)',
                            'format'=>'raw',
                            'value'=>function($model){
                                $txt = Yii::t('app', 'plan type '.$model->plan_type);
                                $txt .= $model->is_simulate ? '(<font color="green">模拟</font>)' : '(<font color="red">正常</font>)';
                                $url = '#';
                                return Html::a($txt, $url, ['title' => '用户类型']);
                            }
                        ],
                        ['label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                return Html::a(Yii::t('app', 'tz now'), ['tz-now','id'=>$model->id], ['class'=>'btn btn-success', 'style'=>'margin-bottom:15px;']);
                            }
                        ],
                        'single',
                        'updated_at:datetime',

                        ['label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                return Html::a(Yii::t('app', 'edit'), ['update','id'=>$model->id], ['class'=>'btn btn-success', 'style'=>'margin-bottom:15px;']);
                            }
                        ],
                        //['class' => 'yii\grid\ActionColumn','template'=>'{update}'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
