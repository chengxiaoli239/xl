<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscKjData3num */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Ssc Kj Data3nums');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-kj-data3num-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Ssc Kj Data3num'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

    <?php Pjax::begin(); ?>
                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'code_str',
                        'code_3n',
                        'qihao',
                        'date',
                        //'created_at',
                        //'update_time',
                        //'updated_at',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
    <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
