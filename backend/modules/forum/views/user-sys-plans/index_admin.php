<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\BaseStringHelper;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\UserSysPlans */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'User Sys Plans');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="user-sys-plans-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title); ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <span><strong>添加计划：</strong></span>
                    <!--div class="btn-group">
                        <?= Html::a(Yii::t('app', 'User Sys Plans 2d'), ['create', 'playway'=>2, 'tz_type'=>16, 'lottery_type'=>$lottery_type], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div-->

                    <!--div class="btn-group">
                        <?= Html::a(Yii::t('app', 'User Sys Plans 3d'), ['create', 'playway'=>3, 'lottery_type'=>$lottery_type], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div-->
                    <?php foreach ($myTzTypes as $typeData):?>
                    <div class="btn-group">
                        <?= Html::a($typeData['type_name'], ['create', 'tz_type'=>$typeData['tz_type'], 'lottery_type'=>$typeData['lottery_type']], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                    <?endforeach;?>
                </div>
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
                                $playway_Arr = [1=>'二字定', 2=>'三字定', 3=>'四字定', 4=>'一字定', 6=>'三字现'];
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
                                }elseif(in_array($model->playway, [1,2,3,4]) OR in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                                    $typeName = \backend\service\BetService::getTypeNameByTzType($model->tz_type);
                                }
                                return $typeName;
                            }
                        ],
                        ['attribute' => 'plan_type','label'=>'计划类型',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = \backend\service\TzService::getTzPlanTypes($model->plan_type);
                                if($model->is_test) $txt .= '_模拟';
                                return $txt;
                            }
                        ],
                        //'buy_type',
                        ['attribute' => 'buy_type','label'=>'正/反',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $buy_type_Arr = [0=>'反买', 1=>'正买'];
                                $txt = $buy_type_Arr[$model->buy_type];
                                $url0 = "/forum/user-sys-plans/switch-buy-type?id=".$model->id.'&status=1'; # 切换正买
                                $url1 = "/forum/user-sys-plans/switch-buy-type?id=".$model->id.'&status=0'; # 切换反买
                                if(!in_array($model->tz_type, \Yii::$app->params['can_change_buy_type'])){
                                    //return Html::a($txt, '#', ['title' => '正买']);
                                    return '';
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
                                if(true OR \Yii::$app->user->id == $model->uid){
                                    $url0 = "/forum/user-sys-plans/switch-status?id=".$model->id.'&status=1'; # 点击开启
                                    $url1 = "/forum/user-sys-plans/switch-status?id=".$model->id.'&status=0'; # 点击关闭
                                }
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
                        //'single',
                        ['attribute' => 'single','headerOptions'=>['width'=>'5%'],'label'=>'倍(元)',
                            'value' => function($model) {
                                return $model->single;
                            }
                        ],
                        ['attribute' => 'current_profits','label'=>'当前',
                            'format'=>'raw',
                            'value' => function($model) {
                                if(in_array($model->plan_type,[1, 3]) OR ($model->take_profits>0 OR $model->stop_loss)){
                                    $txt = '止盈:'.floatval($model->take_profits)." 止损:".floatval($model->stop_loss) .' 当前:'.round($model->current_profits, 2) ;
                                }else{
                                    $txt = '';
                                }
                                return $txt;
                            }
                        ],
                        ['attribute' => 'tz_type','label'=>'操作', # 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url = "/forum/user-sys-plans/tz-now?id=".$model->id; # 立即下注
                                $txt = Html::a('立即下注', $url, ['title' => '立即下注'.$model->id,'alt'=>$model->id]);
                                if(in_array($model->plan_type,[1, 3]) OR ($model->take_profits>0 OR $model->stop_loss)){
                                    $url1 = "/forum/user-sys-plans/re-calculate-profits?id=".$model->id; # 重新计算盈利
                                    $txt .= ' | '.Html::a('重算盈利', $url1, ['title' => '重算盈利'.$model->id,'alt'=>$model->id]);
                                }

                                return $txt;
                            }
                        ],
                        //'tz_sites',
                        ['attribute' => 'tz_sites','label'=>'站点',#'headerOptions'=>['width'=>'5%'],
                            'value' => function($model) {
                                $site_ids = explode(',',$model->tz_sites);
                                $str = '';
                                foreach ($site_ids as $site_id){
                                    $str .= ','.\backend\models\TzSystems::findOne($site_id)->name;
                                }
                                return trim($str,',');
                            }
                        ],
                        //'account',
                        ['attribute' => 'account','headerOptions'=>['width'=>'5%'],'label'=>'账号',
                            'format'=>'raw',
                            'value' => function($model) {
                                return Html::a($model->account, '/forum/user-sys-plans/index?UserSysPlans[account]='.$model->account);
                            }
                        ],
                        //'nums',
                        //'hz_Arr',
                        ['attribute' => 'hz_Arr','label'=>'扩展',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                if(\backend\service\BaseService::is_json($model->hz_Arr) OR in_array($model->tz_type, [18, 19, 20, 25, 27, 28, 29, 30, 31, 32, 33, 34])){
                                    $str = \backend\service\NumService::getDescByKuaixuan(json_decode($model->hz_Arr, true));
                                    if(in_array($model->tz_type, \Yii::$app->params['IMPORT_CODES_TYPES'])){
                                        $str .= \backend\models\ImportPlanCodes::findOne(['plan_id'=>$model->id])->codes;
                                    }
                                }else{
                                    $str = $model->hz_Arr;
                                }
                                $txt = BaseStringHelper::truncate($str,20);
                                $str = Html::a($txt, 'javascript:;', ['title' => $str,'alt'=>$str]);
                                if($model->singles && in_array($model->plan_type,[2, 3, 4, 5, 9, 10])){
                                    $str .= '翻倍梯度:'.$model->singles;
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
                        //'update_time',
                        ['attribute' => 'updated_at','label'=>'时间',
                            'value' => function($model) {
                                return date('m-d H:i', $model->updated_at);
                            }
                        ],

                        //['class' => 'yii\grid\ActionColumn'],
                        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
