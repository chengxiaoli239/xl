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
                <?php //include(dirname(__FILE__).'/code_type_tab.php'); ?>
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
