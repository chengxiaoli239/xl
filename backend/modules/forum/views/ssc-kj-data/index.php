<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscKjData */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Kj Datas');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-kj-data-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Kj Data'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        'qihao',
                        //'kj_code',
                        'code_str',
                        //'codes_hz',
                        'codes_4nums_hz',
                        'code_3n',
                        'code_4n',
                        //'code1',
                        //'code2',
                        //'code3',
                        //'code4',
                        //'code5',
                        //'type_2',
                        'type_22',
                        'type_3',
                        //'type_4',
                        'type_2b',
                        'type_3b',
                        'type_4b',
                        'type_4ds',
                        //'code_1_2',
                        //'code_1_3',
                        //'code_1_4',
                        //'code_2_3',
                        //'code_2_4',
                        //'code_3_4',
                        //'date',
                        'update_time',

                        //['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
