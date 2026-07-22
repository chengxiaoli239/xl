<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\helpers\BaseStringHelper;
use backend\models\SscKjData;
use common\widgets\Alert;
$newRecord = SscKjData::find()->select(['qihao','code_str'])->orderBy('id DESC')->asArray()->limit(1)->one();
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BettingRecords */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'System Betting Records');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="betting-records-index wrapper site-min-height">
    <!-- page start-->
    <?= Alert::widget() ?>
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title);echo '['.$newRecord['qihao'].':'.$newRecord['code_str'].'] '; ?>
            <?= Html::a(Yii::t('app', 'playway 2 Plans'), ['sys-tz-list', 'BettingRecords[playway]'=>2], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
            <?= Html::a(Yii::t('app', 'playway 3 Plans'), ['sys-tz-list', 'BettingRecords[playway]'=>3], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Betting Records', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'qihao',
                        ['attribute' => 'qihao','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->qihao;
                            }
                        ],
                        ['attribute' => 'codes',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = BaseStringHelper::truncate($model->codes,25);
                                return Html::a($txt, 'javascript:;', ['title' => $model->codes,'alt'=>$model->codes]);
                            }
                        ],
                        ['attribute' => 'kj_codes','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->kj_codes ? $model->kj_codes : '待开奖';
                            }
                        ],
                        //'account',
                        //'betting_money',
                        ['attribute' => 'betting_money','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->betting_money;
                            }
                        ],
                        //'bonus',
                        ['attribute' => 'bonus','headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                return $model->bonus>0 ? '<font color="green">'.$model->bonus.'</font>' : ' ';
                            }
                        ],
                        //'single',
                        ['attribute' => 'single','headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        'profits',
                        /*
                        ['attribute' => 'profits','headerOptions' => ['width' => '5%'],
                            'value' => function($model) {
                                return $model->profits;
                            }
                        ],
                        */
                        'kj_codes',
                        //'position',
                        //'status',
                        //'sn',
                        //'snid',
                        ['attribute'=>'snid', 'label'=>'操作',
                            'format'=>'raw',
                            'value'=>function($model){
                                $url1 = "/forum/betting-records/cancel-order?bet_id=".$model->id;
                                $url2 = "/forum/betting-records/tz-now?id=".$model->id; # 追加下注
                                $url3 = "/forum/betting-records/reverse-tz-now?id=".$model->id; # 反买
                                if($model->is_simulate){
                                    $str = '模拟';
                                    $str .= ' | '.Html::a('下注', $url2, ['title' => '下注'.$model->id,'alt'=>$model->id]);
                                    if($model->playway == 2){
                                        $str .= ' | '.Html::a('反买', $url3, ['title' => '反买'.$model->snid,'alt'=>$model->snid]);
                                    }
                                    return $str;
                                }
                                if($model->cancel_status == 1){
                                    //return '<font color="red">已撤单</font> | '.Html::a('点击反买', $url3, ['title' => '点击反买:'.$model->snid,'alt'=>$model->snid]) ;
                                    return '<font color="red">已撤单</font> ';//.Html::a('点击反买', $url3, ['title' => '点击反买:'.$model->snid,'alt'=>$model->snid]) ;
                                }
                                if(!$model->status){
                                    $str = Html::a('点击撤单', $url1, ['title' => '点击撤单:'.$model->snid,'alt'=>$model->snid]);
                                    //$str .= ' | '.Html::a('点击下注', $url2, ['title' => '点击下注:'.$model->id,'alt'=>$model->id]);
                                    return $str;
                                }
                                return $model->snid;
                            }
                        ],
                        //'playway',
                        'playway_name',
                        //'is_simulate',
                        //'lotteryclass',
                        //'createtime:datetime',
                        'create_time',

                        //['class' => 'yii\grid\ActionColumn','headerOptions' => ['width' => '5%'],'template'=>'{view}  {delete}'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
