<?php

use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\TzSystems */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Tz Systems');
$this->params['breadcrumbs'][] = $this->title;
$user = \Yii::$app->user;
$js=<<<'JS'
    $('.edit-btn').click(function(){
        var url = $(this).attr('data-url');
        console.log(url);
        $('#create-tz-system-modal .modal-content').load(url, function() {
            $('#create-tz-system-modal').modal('show');
        });
    });
JS;
$this->registerJs($js);
$columns = array_merge([
    ['class' => 'yii\grid\SerialColumn'],
    ['attribute'=>'name', 'label'=>'名称',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            return $model->name;
        }
    ],
    ['attribute'=>'ssc_domain', 'label'=>'站点',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            return $model->ssc_domain;
        }
    ],
    ['attribute'=>'status', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            $statusOptions = [
                0 => '已关闭',
                1 => '已开启',
            ];
            return $statusOptions[$model->status];
        }
    ],
    ['attribute'=>'kj_num', 'label'=>'位数',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            return $model->kj_num;
        }
    ],
],$user->id!=1?[]:[
    ['attribute'=>'tz_types', 'label'=>'对接玩法',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            return $model->tz_types;
        }
    ],
    //'status',
    ['attribute'=>'status', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            $url0 = "/forum/tz-systems/switch-status?id=".$model->id.'&status=1'; # 点击开启
            $url1 = "/forum/tz-systems/switch-status?id=".$model->id.'&status=0'; # 点击关闭
            if($model->status == 1){
                $txt = "<font color='green'>已开启</font>" ;
                return Html::a($txt, $url1, ['title' => '点击关闭']);
            }
            if(!$model->status){
                $txt = "<font color='red'>已关闭</font>";
                return Html::a($txt, $url0, ['title' => '点击开启']);
            }
        }
    ],
    ['attribute'=>'is_auto_login', 'label'=>'自动登陆',#'headerOptions'=>['width'=>'5%'],
        'format'=>'raw',
        'value'=>function($model){
            $url0 = "/forum/tz-systems/switch-is-auto-login?id=".$model->id.'&status=1'; # 点击开启
            $url1 = "/forum/tz-systems/switch-is-auto-login?id=".$model->id.'&status=0'; # 点击关闭
            if($model->is_auto_login == 1){
                $txt = "<font color='green'>已开启</font>" ;
                return Html::a($txt, $url1, ['title' => '点击关闭']);
            }
            if(!$model->is_auto_login){
                $txt = "<font color='red'>已关闭</font>";
                return Html::a($txt, $url0, ['title' => '点击开启']);
            }
        }
    ],
    //'type',
    //'created_at',
    //'updated_at',

], [
    [
        'label'=>'操作',
        'format'=>'raw',
        'value'=>function($model){
            return Html::a(Yii::t('app', 'edit'),  'javascript:void(0);', [
                    'class'=>'btn btn-xs btn-success edit-btn',
                    'style'=>'margin-bottom:15px;',
                    'data-url' => Yii::$app->urlManager->createUrl(['forum/tz-systems/create', 'id' => $model->id]),
                ]).' '
                .Html::a(Yii::t('app', 'Delete'), ['delete', 'id'=>$model->id], [
                    'class'=>'btn btn-xs btn-warning',
                    'style'=>'margin-bottom:15px;',
                    'onclick'=>"return confirm('确定删除 “".$model->name."”吗?');"
                ]);
        }
    ]
]);
?>
<style>
    .adv-table {
        overflow-x: auto; /* 当内容溢出时显示水平滚动条 */
    }

    .adv-table thead th:last-child,
    .adv-table tbody td:last-child {
        position: sticky;
        right: 0;
        z-index: 1; /* 确保“操作”列位于最上层 */
        background-color: #fff; /* 防止内容滚动时遮挡其他列 */
    }
    /* 媒体查询：手机端宽度小于某个阈值时应用滚动条样式 */
    @media (max-width: 768px) {
        .adv-table-wrapper {
            overflow-x: scroll; /* 当内容溢出时显示水平滚动条 */
            -webkit-overflow-scrolling: touch; /* iOS上的滚动效果 */
        }
    }
</style>
<section class="tz-systems-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::a('新建系统<i class="fa fa-plus"></i>', 'javascript:void(0);', [
            'class' => 'edit-btn btn btn-success',
            'data-url' => Yii::$app->urlManager->createUrl(['forum/tz-systems/create']),
        ]) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <?php echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => $columns
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>


<!-- 模态框 -->
<?php Modal::begin([
    'id' => 'create-tz-system-modal',
    'size' => 'modal-lg',
]); ?>
<?php Modal::end(); ?>

