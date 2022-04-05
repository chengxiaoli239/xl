<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model backend\models\sports\EventsLiveDatas */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Events Live Datas'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="events-live-datas-view wrapper site-min-height">
    <div class="row">
        <div class="col-lg-12">
            <section class="panel">
                <header class="panel-heading">
                    <?= Html::encode($this->title) ?>
                </header>
                <div class="panel-body">
                    <p>
                        <?= Html::a(Yii::t('app', 'Update'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
                        <?= Html::a(Yii::t('app', 'Delete'), ['delete', 'id' => $model->id], [
                            'class' => 'btn btn-danger',
                            'data' => [
                                'confirm' => Yii::t('app', 'Are you sure you want to delete this item?'),
                                'method' => 'post',
                            ],
                        ]) ?>
                    </p>
                    <div class="row">
                        <div class="col-lg-11">
                            <?= DetailView::widget([
                                'model' => $model,
                                'attributes' => [

                                    'id',
                                    'uid',
                                    'event_id',
                                    'clock_minute',
                                    'clock_second',
                                    'clock_minutesLeftInPeriod',
                                    'clock_secondsLeftInMinute',
                                    'clock_period',
                                    'clock_running',
                                    'score_home',
                                    'score_away',
                                    'score_info',
                                    'score_who',
                                    'statics_football_home_yellowCards',
                                    'statics_football_way_yellowCards',
                                    'statics_football_home_redCards',
                                    'statics_football_way_redCards',
                                    'statics_football_home_corners',
                                    'statics_football_way_corners',
                                    'liveStatistics:ntext',
                                    'created_at',
                                    'updated_at',
                                    'update_time',
                                ],
                            ]) ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</section>
