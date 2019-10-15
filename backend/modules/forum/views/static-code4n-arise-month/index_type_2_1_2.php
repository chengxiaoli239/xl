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

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            <?php //Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
