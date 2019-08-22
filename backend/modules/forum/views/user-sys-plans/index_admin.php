<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\BaseStringHelper;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\UserSysPlans */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'User Sys Plans');
$this->params['breadcrumbs'][] = $this->title;
$sys_profits_3d = \backend\models\VPerdateProfits::findOne(['playway'=>2, 'tz_date'=>date('Y-m-d')])->profits;
$sys_profits_3d = $sys_profits_3d ? $sys_profits_3d : 0.00;
$profits_desc = date('Y-m-d').'系统投注利润：三定 : '.$sys_profits_3d;
?>
<section class="user-sys-plans-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title).'    ['.$profits_desc.']'; ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'uid',
                        //'playway',
                        ['attribute' => 'playway','headerOptions'=>['width'=>'5%'],'label'=>'投注类型',
                            'value' => function($model) {
                                $playway_Arr = [1=>'二字定', 2=>'三字定', 3=>'四字定', 6=>'三字现'];
                                return $playway_Arr[$model->playway];
                            }
                        ],

                        //'tz_type',
                        ['attribute' => 'tz_type','label'=>'购买类型', # 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                if($model->playway == 2 && in_array($model->tz_type, [1,2,3])){
                                    //投注类型:1大小单双三字定2大小三字定3单双三字定
                                    $tz_type_Arr = [1=>'大小单双三字定', 2=>'大小三字定', 3=>'单双三字定'];
                                    $typeName = $tz_type_Arr[$model->tz_type];
                                }elseif($model->playway == 3 OR in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                                    $typeName = \backend\service\BetService::getTypeNameByTzType($model->tz_type);
                                }
                                return $typeName;
                            }
                        ],
                        //'buy_type',
                        ['attribute' => 'buy_type','label'=>'正买/反买',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $buy_type_Arr = [0=>'反买', 1=>'正买'];
                                $txt = $buy_type_Arr[$model->buy_type];
                                $url0 = "/forum/user-sys-plans/switch-buy-type?id=".$model->id.'&status=1'; # 切换正买
                                $url1 = "/forum/user-sys-plans/switch-buy-type?id=".$model->id.'&status=0'; # 切换反买
                                if($model->playway == 3){
                                    return Html::a($txt, '#', ['title' => '正买']);
                                }
                                if($model->buy_type == 1){
                                    $txt = "<font color='green'>$txt</font>" ;
                                    return Html::a($txt, $url1, ['title' => '切换反买']);
                                }
                                if(!$model->buy_type){
                                    $txt = "<font color='red'>$txt</font>";
                                    return Html::a($txt, $url0, ['title' => '切换正买']);
                                }
                            }
                        ],
                        //'status',
                        ['attribute'=>'status', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "#"; # 点击开启
                                $url1 = "#"; # 点击关闭
                                if($model->status == 1){
                                    $txt = "<font color='green'>已开启</font>" ;
                                    return Html::a($txt, $url1, ['title' => '点击关闭']);
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>已关闭</font>";
                                    return Html::a($txt, $url0, ['title' => '点击开启']);
                                }
                                //return $model->snid;
                            }
                        ],
                        'single',
                        /*
                        ['attribute' => 'tz_type','label'=>'操作', # 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url = "/forum/user-sys-plans/tz-now?id=".$model->id; # 立即下注
                                return Html::a('立即下注', $url, ['title' => '立即下注'.$model->id,'alt'=>$model->id]);
                            }
                        ],
                        */
                        //'tz_sites',
                        ['attribute' => 'tz_sites','label'=>'计划投注站点',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $site_ids = explode(',',$model->tz_sites);
                                $str = '';
                                foreach ($site_ids as $site_id){
                                    $str .= ','.\backend\models\TzSystems::findOne($site_id)->name;
                                }
                                return trim($str,',');
                            }
                        ],
                        'account',
                        //'nums',
                        //'hz_Arr',
                        ['attribute' => 'hz_Arr','label'=>'扩展【部分投注】',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if(in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                                    $str = \backend\models\ImportPlanCodes::findOne(['plan_id'=>$model->id])->codes;
                                    $txt = BaseStringHelper::truncate($str,25);
                                    $str = Html::a($txt, '#', ['title' => $str,'alt'=>$str]);
                                }elseif($model->tz_type == 25){
                                    $str = \backend\service\NumService::getDescByKuaixuan(json_decode($model->hz_Arr, true));
                                }else{
                                    $str = $model->hz_Arr;
                                    $txt = BaseStringHelper::truncate($str,25);
                                    $str = Html::a($txt, '#', ['title' => $str,'alt'=>$str]);
                                }
                                return $str;
                            }
                        ],
                        /*
                        ['attribute' => 'nums','headerOptions'=>['width'=>'5%'],'label'=>'描述',
                            'value' => function($model) {
                                $desc = \backend\service\StaticService::$kArr[$model->tz_type];
                                if($model->tz_type == 20){
                                    $desc .= '和值范围：'.$model->hz_Arr;
                                }
                                return $desc;
                            }
                        ],
                        */
                        //'created_at',
                        //'updated_at',
                        'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
