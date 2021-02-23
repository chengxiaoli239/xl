<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\Static4dProfitsMonth */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Static4d Profits Months');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static4d-profits-month-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Static4d Profits Month', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        'month',
                        'codes_1112',
                        'codes_1121',
                        'codes_1211',
                        'codes_2111',
                        'codes_1222',
                        'codes_2122',
                        'codes_2212',
                        'codes_2221',
                        'codes_1122',
                        'codes_1212',
                        'codes_1221',
                        'codes_2112',
                        'codes_2121',
                        'codes_2211',
                        'codes_1111',
                        'codes_2222',
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
                <tbody>
                <tr data-key="1"><td>13</td><td>2018-07</td><td>-7630.00</td><td>3315.00</td><td>12270.00</td><td>13265.00</td><td>26200.00</td><td>-16585.00</td><td>-42455.00</td><td>-7630.00</td><td>-8625.00</td><td>26200.00</td><td>9285.00</td><td>-3650.00</td><td>-10615.00</td><td>-665.00</td><td>-12605.00</td><td>1325.00</td></tr>
                </tbody>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
