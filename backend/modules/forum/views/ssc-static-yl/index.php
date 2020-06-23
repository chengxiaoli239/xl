<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\SscStaticYl */
/* @var $dataProvider yii\data\ActiveDataProvider */

//p($codeTypeName);
$this->title = Yii::t('app', 'Ssc Static Yls'); # .' [ '.$codeTypeName.' ]';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="ssc-static-yl-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
            <?php include(dirname(__FILE__).'/index_tab.php'); ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <?= Html::a('Create Ssc Static Yl', ['create'], ['class' => 'btn btn-success', 'style' => 'margin-bottom:15px;']) ?>
                    </div>
                </div-->

                <?php include(dirname(__FILE__).'/code_type_tab.php'); ?>


                <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        //'id',
                        //'val',
                        ['attribute' => 'val','headerOptions'=>['width'=>'5%'],'label'=>'号码',
                            'format'=>'raw',
                            'value' => function($model) {
                                $txt = \backend\service\SscDataService::getStaticNameByType($model->val);
                                $options = [
                                    'class' => 'code_val',
                                    'data-val' => $model->val,
                                    'data-lottery_type' => Yii::$app->request->queryParams['SscStaticYl']['lottery_type'],
                                ];
                                return Html::a($txt, '#', $options);
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
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
    $(function () {
        $('.code_val').click(function () {
            val = $(this).data('val');
            lottery_type = $(this).data('lottery_type');
            $.post('/forum/ssc-static-yl/get-code-type-static', {val:val,lottery_type:lottery_type}, function(rst) {
                $('#tip_msg_rst').html('<strong>号码：</strong>'+val + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
                $('#rstTipModal').modal('show');
            });
        });
    })
</script>
