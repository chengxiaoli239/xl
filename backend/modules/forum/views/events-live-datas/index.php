<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\EventsLiveDatas */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Events Live Datas');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="events-live-datas-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Events Live Datas'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        'id',
                        'uid',
                        'event_id',
                        'clock_minute',
                        'clock_second',
                        //'clock_minutesLeftInPeriod',
                        //'clock_secondsLeftInMinute',
                        //'clock_period',
                        //'clock_running',
                        //'score_home',
                        //'score_away',
                        //'score_info',
                        //'score_who',
                        //'statics_football_home_yellowCards',
                        //'statics_football_way_yellowCards',
                        //'statics_football_home_redCards',
                        //'statics_football_way_redCards',
                        //'statics_football_home_corners',
                        //'statics_football_way_corners',
                        //'liveStatistics:ntext',
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
