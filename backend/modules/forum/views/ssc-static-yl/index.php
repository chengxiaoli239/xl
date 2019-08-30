<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscStaticYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

//p($codeTypeName);
$this->title = Yii::t('app', 'Ssc Static Yls').' [ '.$codeTypeName.' ]';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-static-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Ssc Static Yl', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                 <div class="btn-group">
                    <?= Html::a('号码类型', ['index', 'SscStaticYl[code_type]'=>1, 'SscStaticYl[type]'=>2], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                ||
                <div class="btn-group">
                    <?= Html::a('三现带双', ['index', 'SscStaticYl[code_type]'=>2, 'SscStaticYl[type]'=>3, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('三现带双热码', ['index', 'SscStaticYl[code_type]'=>3, 'SscStaticYl[type]'=>3, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('三现三重', ['index', 'SscStaticYl[code_type]'=>4, 'SscStaticYl[type]'=>3, 'SscStaticYl[type_3]'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                ||
                <div class="btn-group">
                    <?= Html::a('四现带双', ['index', 'SscStaticYl[code_type]'=>5, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('四现带双热码', ['index', 'SscStaticYl[code_type]'=>501, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                ||
                <div class="btn-group">
                    <?= Html::a('四现不带双', ['index', 'SscStaticYl[code_type]'=>6, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>0], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('四现不带双热码', ['index', 'SscStaticYl[code_type]'=>601, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>0, 'SscStaticYl[is_hots]'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                ||

                <div class="btn-group">
                    <?= Html::a('四兄弟', ['index', 'SscStaticYl[code_type]'=>7, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4b]'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('四单四双', ['index', 'SscStaticYl[code_type]'=>8, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_2]'=>0, 'SscStaticYl[type_4ds]'=>1], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('四单带双', ['index', 'SscStaticYl[code_type]'=>9, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4d]'=>1, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <div class="btn-group">
                    <?= Html::a('四双带双', ['index', 'SscStaticYl[code_type]'=>10, 'SscStaticYl[type]'=>4, 'SscStaticYl[type_4s]'=>1, 'SscStaticYl[type_2]'=>1, 'SscStaticYl[type_3]'=>0], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                </div>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'5%'],'label'=>'当前遗漏',
                            'value' => function($model) {
                                return \backend\service\SscDataService::getStaticNameByType($model->val);
                            }
                        ],
                        'current_miss',
                        'last_time_miss',
                        //'last_time_miss_range',
                        'max_miss',
                        //'max_range',
                        'yl_records:ntext',
                        'history_max_miss',
                        'count',
                        //'static_nums',
                        'theory_nums_perdate',
                        'today_nums',
                        'ytd_nums',
                        //'lottery_type',
                        //'status',
                        //'created_at',
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
