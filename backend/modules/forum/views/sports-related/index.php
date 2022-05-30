<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SportsRelated */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Sports Relateds');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="sports-related-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Sports Related'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        'relate_A_game_id',
                        'relate_B_game_id',
                        'relate_type',
                        //'relate_sport_type',
                        //'plate_A_id',
                        //'plate_A_name',
                        //'plate_B_id',
                        //'plate_B_name',
                        //'base_url_A:url',
                        //'base_url_B:url',
                        //'plate_bet_url_A:url',
                        //'plate_bet_url_B:url',
                        //'plate_bet_conditions',
                        //'desc:ntext',
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
