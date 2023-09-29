<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\wechat\Odds */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '赔率表';
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="odds-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <?php echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    #'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        #'id',
                        #'user_id',
                        #'play_method_id',
                        'name',
                        'money',
                        'bouns',
                        'odds',
                        //'status',
                        //'created_at',
                        //'updated_at',
                        'update_at',

                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update}  {view}', // This will only show the "Update" button
                            'buttons' => [
                                'update' => function ($url, $model, $key) {
                                    return Html::a(
                                        '<span class="glyphicon glyphicon-pencil"></span> 更新',
                                        $url,
                                        ['class' => 'btn btn-success btn-xs']
                                    );
                                },
                                'view' => function ($url, $model, $key) {
                                    return Html::a(
                                        '<span class="glyphicon glyphicon-eye-open"></span> 查看',
                                        $url,
                                        ['class' => 'btn btn-primary btn-xs']
                                    );
                                },
                                'view-modal' => function ($url, $model, $key) {
                                    return Html::a(
                                        '<span class="glyphicon glyphicon-eye-open"></span> View',
                                        ['/kkkkk/dadsf'], // Use '#' as the href
                                        [
                                            'class' => 'btn btn-primary',
                                            'data-toggle' => 'modal',
                                            'data-target' => '#viewModal',
                                            'data-url' => Yii::$app->urlManager->createUrl(['view', 'id' => $key]),
                                        ]
                                    );
                                },
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>

<!-- Include the modal HTML in your view/layout -->
<div class="modal fade" id="viewModal" tabindex="-1" role="dialog" aria-labelledby="viewModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="viewModalLabel">View Details</h4>
            </div>
            <div class="modal-body">
                <!-- Content loaded via AJAX will be displayed here -->
                <div id="viewModalContent"></div>
            </div>
        </div>
    </div>
</div>

<script src="/statics/datetimepicker/jquery.js"></script>
<!-- Add JavaScript to load content into the modal when the button is clicked -->
<script>
    $(document).on('click', '[data-toggle="modal"][data-target="#viewModal"]', function(e) {
        e.preventDefault();

        // Load content via AJAX and inject it into the modal body
        var url = $(this).data('url');
        $('#viewModalContent').load(url);
    });
</script>
