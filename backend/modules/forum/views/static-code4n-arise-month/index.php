<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticCode4nAriseMonth */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static Code4n Arise Months');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-code4n-arise-month-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Static Code4n Arise Month'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

            <?//php Pjax::begin(); ?>
                <?php include(dirname(__FILE__).'/code_type_tab.php'); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'month',
                        'code_0145',
                        'code_0137',
                        'code_1256',
                        'code_2348',
                        'code_3567',
                        'code_3478',
                        'code_0678',
                        'code_0347',
                        'code_5689',
                        'code_0138',
                        'code_0189',
                        'code_0139',
                        'code_0125',
                        'code_1367',
                        'code_1348',
                        'code_0129',
                        'code_1378',
                        'code_1359',
                        'code_3589',
                        'code_0149',
                        'code_0478',
                        'code_5789',
                        'code_1238',
                        'code_1267',
                        'code_1234',
                        'code_2367',
                        'code_2569',
                        'code_1469',
                        'code_1269',
                        'code_4679',
                        'code_0258',
                        'code_0267',
                        'code_0369',
                        'code_0567',
                        'code_1568',
                        'code_2567',
                        'code_2457',
                        'code_0259',
                        'code_2356',
                        'code_4789',
                        'code_0148',
                        'code_0136',
                        'code_1678',
                        'code_2358',
                        'code_0569',
                        'code_0278',
                        'code_2478',
                        'code_0247',
                        'code_1379',
                        'code_0239',
                        'code_1136',
                        'code_2899',
                        'code_0448',
                        'code_4668',
                        'code_5889',
                        'code_1179',
                        'code_1159',
                        'code_1227',
                        'code_2247',
                        'code_0014',
                        'code_1168',
                        'code_0013',
                        'code_3559',
                        'code_4457',
                        'code_1366',
                        'code_0037',
                        'code_3346',
                        'code_7899',
                        'code_1889',
                        'code_2477',
                        'code_0466',
                        'code_1899',
                        'code_6889',
                        'code_4489',
                        'code_0499',
                        'code_0899',
                        'code_0477',
                        'code_3347',
                        'code_2344',
                        'code_0488',
                        'code_0229',
                        'code_7789',
                        'code_1124',
                        'code_0114',
                        'code_4456',
                        'code_0016',
                        'code_1149',
                        'code_3799',
                        'code_1499',
                        'code_3367',
                        'code_3499',
                        'code_0025',
                        'code_2447',
                        'code_0017',
                        'code_3348',
                        'code_0115',
                        'code_1228',
                        'code_1778',
                        'code_2388',
                        'code_3577',
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            <?php //Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
