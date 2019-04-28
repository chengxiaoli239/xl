<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscDsStatic */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Ds Statics');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-ds-static-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Ds Static'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        ['attribute'=>'positions','label'=>'位置','value'=>function($model){
                            return $model->positions;
                        }],
                        //'qihao',
                        //'periods',
                        ['attribute'=>'periods','label'=>'区间(期)','value'=>function($model){
                            return $model->periods;
                        }],
                        'DS',
                        'SD',
                        'DD',
                        'SS',
                        //'updated_at',
                        'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
