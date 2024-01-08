<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\widgets\Pjax;

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
    ],
    [
        'attribute' => 'created_at',
        'label' => '创建',
        'format' => 'raw',
        'value' => function ($model) {
            return Yii::$app->formatter->asDatetime($model->created_at);
        },
    ],
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
            'class' => 'yii\grid\ActionColumn',
        ],
    ]
);
?>

<section class="wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?=$this->title?>
            <?= Html::a(Yii::t('rbac-admin', 'Create User').' <i class="fa fa-plus"></i>', ['create'], ['class' => 'btn btn-success btn-xs']) ?>
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