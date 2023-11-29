<?php

use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\EyunAuth */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '消息平台';
$this->params['breadcrumbs'][] = '系统管理';
$this->params['breadcrumbs'][] = ['label' => $this->title, 'url' => ['index']];

// 注册JS代码，用于处理确认对话框
$js = <<<'JS'
    // 点击具有 login-button 类的按钮时触发
    $('.login-button').click(function() {
        var confirmMessage = $(this).data('confirm'); // 获取确认提示信息
        // 弹出确认对话框
        if (confirm(confirmMessage)){
            // 用户点击确认，执行删除操作或者其他操作
            // 如果是删除操作，你可以在这里添加 AJAX 请求，向后端发送删除请求
            // 例如： $.post($(this).attr('href'));
            
            var url = $(this).attr('href');
            // 发送 AJAX 请求执行删除操作
            $.ajax({
                url: url,
                type: 'POST', // 或 'DELETE'，取决于你的后端配置
                dataType: 'json', // 如果你的后端返回 JSON，设置数据类型为 JSON
                success: function(data) {
                    if(data.status!=200){
                        confirm(data.msg)
                        return false;
                    }
                    if(confirm(data.msg)){
                    }
                }
            });
        }
        return false; // 阻止默认行为，确保不会跳转到按钮的链接
    });
    // 点击具有 delete-button 类的按钮时触发
    $('.delete-button').click(function() {
        var confirmMessage = $(this).data('confirm'); // 获取确认提示信息
        // 弹出确认对话框
        if (confirm(confirmMessage)){
            // 用户点击确认，执行删除操作或者其他操作
            // 如果是删除操作，你可以在这里添加 AJAX 请求，向后端发送删除请求
            // 例如： $.post($(this).attr('href'));
            
            var url = $(this).attr('href');
            // 发送 AJAX 请求执行删除操作
            $.ajax({
                url: url,
                type: 'POST', // 或 'DELETE'，取决于你的后端配置
                dataType: 'json', // 如果你的后端返回 JSON，设置数据类型为 JSON
                success: function(data) {
                    console.log('删除成功');
                }
            });
        }
        return false; // 阻止默认行为，确保不会跳转到按钮的链接
    });
    // 点击新建按钮弹出创建表单
    $('#create-btn').click(function(){
        var url = $(this).attr('data-url');
        $('#eyun-auth-modal .modal-content').load(url, function() {
            $('#eyun-auth-modal').modal('show');
        });
    });
    
    $('.edit-btn').click(function(){
        var url = $(this).attr('data-url');
        $('#eyun-auth-modal .modal-content').load(url, function() {
            $('#eyun-auth-modal').modal('show');
        });
    });
JS;

$this->registerJs($js);
?>

<section class="eyun-auth-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= \yii\widgets\Breadcrumbs::widget([
                'links' => $this->params['breadcrumbs'] ?? [],
            ]) ?>
            <?= Html::a('新建平台', 'javascript:void(0);', ['class'=>'btn btn-success', 'id'=>'create-btn',
                'data-url' => Yii::$app->urlManager->createUrl(['tools/eyun-auth/create']),
            ]) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <?php echo $this->render('_search', ['model' => $searchModel]); ?>

                <?= GridView::widget([
                    'dataProvider' => $dataProvider,
                    //'filterModel' => $searchModel,
                    'columns' => [
                        ['class' => 'yii\grid\SerialColumn'],

                        ['attribute' => 'type', 'label'=>'类型','headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return \backend\models\EyunAuthBackend::PLATFORM_ID_OPTIONS[$model->type];
                            },
                        ],
                        'account',
                        'password',
                        'status',
                        //'authorization:ntext',
                        //'callback_url:url',
                        //'base_url:url',
                        //'desc:ntext',
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete} {login}',
                            'buttons' => [
                                'update' => function ($url, $model, $key) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', 'javascript:void(0);', [
                                        'class' => 'edit-btn btn btn-xs edit-button',
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/eyun-auth/update', 'id' => $model->id]),
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $model->id], [
                                        'class' => 'btn btn-xs btn-danger delete-button',
                                        'data-confirm' => '确认删除', // 提示信息
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/eyun-auth/delete', 'id' => $model->id]),
                                    ]);
                                },
                                'login' => function ($url, $model, $key) {
                                    return Html::a('<span class="glyphicon glyphicon-ok"></span>', ['login', 'id' => $model->id], [
                                        'class' => 'btn btn-xs btn-green login-button',
                                        'data-confirm' => '确认登录', // 提示信息
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/eyun-auth/login', 'id' => $model->id]),
                                    ]);
                                },
                                // ...其他按钮...
                            ],
                        ],
                    ],
                ]); ?>
            </div>
        </div>
    </section>
    <!-- page end-->
</section>


<!-- 模态框 -->
<?php Modal::begin([
    'id' => 'eyun-auth-modal',
    'size' => 'modal-lg',
]); ?>
<?php Modal::end(); ?>
