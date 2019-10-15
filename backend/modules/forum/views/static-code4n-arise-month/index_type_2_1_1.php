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
