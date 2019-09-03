<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticPerHzPerdateProfits */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static Per Hz Perdate Profits');
$this->params['breadcrumbs'][] = $this->title;
$update_time = \backend\models\searchs\StaticPerHzPerdateProfits::find()->select(['max(update_time) AS update_time'])->one()['update_time'];
?>
<section class="static-per-hz-perdate-profits-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title).'[更新时间：'.$update_time.']' ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Static Per Hz Perdate Profits'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'date',
                        //'codes_1',
                        //'codes_2',
                        //'codes_3',
                        //'codes_4',
                        //'codes_5',
                        'codes_6',
                        'codes_7',
                        'codes_8',
                        'codes_9',
                        'codes_10',
                        'codes_11',
                        'codes_12',
                        //'codes_13',
                        'codes_14',
                        //'codes_15',
                        //'codes_16',
                        'codes_17',
                        //'codes_18',
                        'codes_19',
                        'codes_20',
                        'codes_21',
                        'codes_22',
                        //'codes_23',
                        'codes_24',
                        'codes_25',
                        'codes_26',
                        'codes_27',
                        'codes_28',
                        'codes_29',
                        //'codes_30',
                        //'codes_31',
                        //'codes_32',
                        //'codes_33',
                        //'codes_34',
                        //'codes_35',
                        //'codes_36',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
