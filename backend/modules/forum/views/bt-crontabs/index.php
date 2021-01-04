<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\BtCrontabs */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Bt Crontabs');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="bt-crontabs-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create Bt Crontabs'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
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
                        'p_id',
                        'name',
                        'sName',
                        //'sType',
                        //'status',
                        //'domain',
                        //'echo',
                        //'cycle',
                        //'backupTo',
                        //'save',
                        //'where_minute',
                        //'where_hour',
                        //'where1',
                        //'sBody',
                        //'type_desc',
                        //'urladdress',
                        //'addtime',
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
