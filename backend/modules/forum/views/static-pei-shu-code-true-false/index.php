<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\StaticPeiShuCodeTrueFalse */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Static Pei Shu Code True Falses';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="static-pei-shu-code-true-false-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Static Pei Shu Code True False', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'date',
                        'qihao',
                        'kj_code',
                        'code_147_369',
                        //'code_259_369',
                        //'code_019_368',
                        //'code_123_678',
                        //'code_147_258',
                        //'code_017_348',
                        //'code_456_789',
                        //'code_012_789',
                        //'code_345_678',
                        //'code_357_019',
                        //'code_3b',
                        //'lottery_type',
                        //'created_at',
                        //'updated_at',
                        //'update_time',

                        ['class' => 'yii\grid\ActionColumn'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
