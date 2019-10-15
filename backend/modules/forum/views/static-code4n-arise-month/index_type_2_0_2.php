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

                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            <?php //Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
