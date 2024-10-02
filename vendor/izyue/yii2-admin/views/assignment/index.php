<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $searchModel izyue\admin\models\searchs\Assignment */
/* @var $usernameField string */
/* @var $extraColumns string[] */

$this->title = Yii::t('rbac-admin', 'Assignments');
$this->params['breadcrumbs'][] = $this->title;
$extraColumns = array_merge($extraColumns, [
    [
        'attribute' => 'updated_at',
        'label' => '更新',
        'format' => 'raw',
        'value' => function ($model) {
            return Yii::$app->formatter->asDatetime($model->updated_at);
        },
    ]
]);

$columns = array_merge(
    [
        ['class' => 'yii\grid\SerialColumn'],
        [
            'class' => 'yii\grid\DataColumn',
            'attribute' => $usernameField,
        ],
    ], 
    $extraColumns, 
    [
        [
            'label'=>'操作',
            'format'=>'raw',
            'value'=>function($model){
                return Html::a(Yii::t('app', 'edit'),  'javascript:void(0);', [
                        'class'=>'btn btn-xs btn-success edit-btn', 
                        'style'=>'margin-bottom:15px;',
                        'data-url' => Yii::$app->urlManager->createUrl(['forum/user/create-user', 'id' => $model->id]),
                    ]).' '
                    .Html::a(Yii::t('app', 'Delete'), ['delete', 'id'=>$model->id], [
                        'class'=>'btn btn-xs btn-warning',
                        'style'=>'margin-bottom:15px;',
                        'onclick'=>"return confirm('确定删除 “".$model->username."”吗?');"
                    ]).' '
                    .Html::a(Yii::t('app', 'Role assign'), ['view', 'id'=>$model->id], [
                        'class'=>'btn btn-xs btn-success',
                        'style'=>'margin-bottom:15px;',
                        //'onclick'=>"return confirm('确定删除 “".$model->username."”吗?');"
                    ]);
            }
        ]
    ]
);

$js=<<<'JS'
    $('.edit-btn').click(function(){
        var url = $(this).attr('data-url');
        console.log(url);
        $('#create-user-modal .modal-content').load(url, function() {
            $('#create-user-modal').modal('show');
        });
    });
JS;
$this->registerJs($js);

?>
<style>
    /* 默认的弹框大小 */
    .modal-lg {
        width: 65%;
        height: 30%;
        margin: 100px auto;
    }

    /* 在小屏幕上设置较大的弹框大小 */
    @media (max-width: 768px) {
        .modal-lg {
            width: 90%;
            height: 30%;
            margin: 50px auto;
        }
    }

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

<section class="wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?=$this->title?>
            <?= Html::a(Yii::t('rbac-admin', 'Create User').' <i class="fa fa-plus"></i>', 'javascript:void(0);', [
                'class' => 'edit-btn btn btn-success',
                'data-url' => Yii::$app->urlManager->createUrl(['forum/user/create-user']),
            ]) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <?php include(dirname(__FILE__).'/index_tab.php'); ?>
                <div class="space15"></div>
                <?php Pjax::begin(); ?>
                <?=
                GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'tableOptions' => [
                        'class' => 'table table-striped table-hover table-bordered',
                        'id' => 'editable-sample',
                    ],
                    'pager' => [
                        'prevPageLabel' => Yii::t('rbac-admin', 'Prev'),
                        'nextPageLabel' => Yii::t('rbac-admin', 'Next'),
                    ],
                    'layout'=> '{items}
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="dataTables_info" id="editable-sample_info">{summary}</div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="dataTables_paginate paging_bootstrap pagination">{pager}</div>
                                    </div>
                                </div>',
                    'columns' => $columns,
                ])
                ?>
                <?php Pjax::end(); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>


<!-- 模态框 -->
<?php Modal::begin([
    'id' => 'create-user-modal',
    'size' => 'modal-lg',
]); ?>
<?php Modal::end(); ?>

