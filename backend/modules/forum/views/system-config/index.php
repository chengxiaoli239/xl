<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SystemConfig */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'System Configs');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="system-config-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <!--
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        -->
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a(Yii::t('app', 'Create System Config'), ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div>

                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        'name',
                        'key',
                        //'value',
                        ['attribute'=>'value','label'=>'值','headerOptions'=>['width'=>'4%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                if($model->value === '0' OR $model->value === 0 OR $model->value === '1'  OR $model->value == 1){
                                    $txt = $model->value==1 ? '已开启' : '已关闭';
                                    $color = $model->value==1 ? 'green' : 'red';
                                    $opions = [
                                        'class' => 'act-switch-status',
                                        'data-act-model' => 'SystemConfig',
                                        'data-act-field' => 'value',
                                        'data-act-id' => $model->id,
                                        'title' => $txt.'，点击'.($model->value==1 ? '关闭' : '开启'),
                                    ];
                                    $txt = Html::a('<strong><font color="'.$color.'">'.$txt.'</font></strong>', 'javascript:;', $opions);
                                }else{
                                    $txt = $model->value;
                                }
                                return $txt;
                            }
                        ],
                        'desc:ntext',
                        //'extend',
                        //'created_at',
                        //'updated_at',

                        ['class' => 'yii\grid\ActionColumn','headerOptions'=>['width'=>'5%'],'template'=>'{update}&nbsp;&nbsp;&nbsp;&nbsp;{delete}'],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>
<?php include(dirname(__FILE__).'/../switch_status_tpl.php'); ?>
