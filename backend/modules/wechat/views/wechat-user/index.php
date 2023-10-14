<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\wechat\WechatUser */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Wechat Users';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="wechat-user-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Wechat User', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'user_id',
                        'userName',
                        'nickName',
                        'aliasName',
                        //'status',
                        //'balance',
                        //'is_credit',
                        //'bigHead',
                        //'smallHead',
                        //'labelList',
                        //'remark',
                        //'expire_time:datetime',
                        //'created_at',
                        //'updated_at',
                        //'update_at',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
