<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="user-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create User'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        //'admin_id',
                        //'username',
                        ['attribute' => 'username','label'=>'账号', # 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model){
                                return $model->username;
                            }
                        ],
                        //'updated_at:datetime',
                        ['attribute' => 'id','label'=>'更新时间', # 'headerOptions'=>['width'=>'5%'],
                            'value' => function($model){
                                return date('Y-m-d H:i:s', $model->updated_at);
                            }
                        ],
                        ['attribute' => 'id','label'=>'投注系统权限', # 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url = "/forum/user/open-systems?uid=".$model->id; #
                                return Html::a('添加/编辑', $url, ['title' => '开通系统权限','alt'=>$model->id]);
                            }
                        ],
                        /*
                        ['attribute' => 'id','label'=>'投注方式', # 'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value' => function($model) {
                                $url = "/forum/user/open-tz-type?uid=".$model->id; # 立即下注
                                return Html::a('添加', $url, ['title' => '开通系统权限','alt'=>$model->id]);
                            }
                        ],
                        */
                        //'balance',
                        //'simulate_balance',
                        //'email:email',
                        //'tz_password',
                        //'cookie',
                        //'cookie2',
                        //'status',
                        //'created_at',
                        //'updated_at',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
