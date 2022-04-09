<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\LotteryDataDealStatus */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Lottery Data Deal Statuses');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="lottery-data-deal-status-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Lottery Data Deal Status'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'lottery_type',
                        'status',
                        'status_desc',
                        'static4dPerDateProfits_status',
                        //'static4dPerDateProfits_status_desc',
                        //'updateDs_status',
                        //'updateDs_status_desc',
                        //'updateDsYL_status',
                        //'updateDsYL_status_desc',
                        //'update3NumYL_status',
                        //'update3NumYL_status_desc',
                        //'updateSdHzYL_status',
                        //'updateSdHzYL_status_desc',
                        //'opProfitsPlans_status',
                        //'opProfitsPlans_status_desc',
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
