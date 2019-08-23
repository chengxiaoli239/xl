<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\TzSystemsUsers */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Tz Systems Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="tz-systems-users-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Tz Systems Users'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'uid',
                        //'tz_system_id',
                        'username',
                        'sys_name',
                        'account',
                        //'password',
                        ['attribute' => 'password', 'label'=>'密码', //'headerOptions' => ['width' => '170'],
                            'value'=> function($model){
                                return $model->password ? $model->password : '';
                            },
                        ],
                        //'balance',
                        ['attribute' => 'balance', 'label'=>'余额', //'headerOptions' => ['width' => '170'],
                            'value'=> function($model){
                                return $model->balance ? $model->balance : '';
                            },
                        ],
                        //'status',
                        ['attribute' => 'status', 'label'=>'状态', //'headerOptions' => ['width' => '170'],
                            'format' => 'raw',
                            'value'=> function($model){
                                return $model->status ? '<font color="green">已启用</font>' : '<font color="red">已禁用</font>';
                            },
                        ],
                        'ssc_domain',
                        //'cookie',
                        //'created_at:datetime',
                        /*
                        ['attribute' => 'updated_at', 'label'=>'更新时间', 'headerOptions' => ['width' => '170'],
                            'value'=> function($model){
                                return  date('Y-m-d H:i:s',$model->updated_at);   //主要通过此种方式实现
                            },
                        ],
                        */
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
