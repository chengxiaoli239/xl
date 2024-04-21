<?php

use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\EyunAuth */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = '机器人';
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
<style>
.modal-content {
    box-shadow: none;
    border: none;
    height: 500px;
}
</style>

<section class="eyun-auth-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= \yii\widgets\Breadcrumbs::widget([
                'links' => $this->params['breadcrumbs'] ?? [],
            ]) ?>
            <?= Html::a('新建平台', 'javascript:void(0);', ['class'=>'btn btn-success', 'id'=>'create-btn',
                'data-url' => Yii::$app->urlManager->createUrl(['tools/platform-robot/create']),
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

                        ['attribute' => 'platform_id', 'label'=>'类型','headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return \common\helpers\Platform::TYPE_OPTIONS[$model->platform_id];
                            },
                        ],
                        //'platform_robot_id',
                        ['attribute' => 'platform_robot_id', 'label'=>'平台ID','headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return $model->platform_robot_id;
                            },
                        ],
                        //'name',
                        ['attribute' => 'name', 'label'=>'名称','headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return $model->name;
                            },
                        ],
                        ['attribute'=>'status', 'label'=>'状态',#'headerOptions'=>['width'=>'5%'],
                            'format'=>'raw',
                            'value'=>function($model){
                                $url0 = "/tools/platform-robot/switch-status?id=".$model->id.'&status=1'; # 点击激活
                                $url1 = "/tools/platform-robot/switch-status?id=".$model->id.'&status=0'; # 点击禁用
                                $txt = '<strong>'.\backend\models\open\PlatformRobot::STATUS_OPTIONS[$model->status].'</strong>';
                                if($model->status == 1){
                                    $txt = "<font color='green'>{$txt}</font>";
                                    return Html::a($txt, $url1, ['title' => '点击禁用']).'<i class="icon-refresh"></i>';
                                }
                                if(!$model->status){
                                    $txt = "<font color='red'>{$txt}</font>";
                                    return Html::a($txt, $url0, ['title' => '点击激活']).'<i class="icon-refresh"></i>';
                                }
                            }
                        ],
                        ['attribute' => 'token', 'label'=>'token',//'headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return $model->token;
                            },
                        ],
                        ['attribute' => 'group_id', 'label'=>'群',//'headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                $group_name = \common\models\open\PlatformGroup::find()->select(['name'])
                                    ->where(['group_id'=>$model->group_id])->scalar();
                                return $group_name;
                            },
                        ],
                        ['attribute' => 'name', 'label'=>'机器人连接',//'headerOptions'=>['width'=>'6%'], // 图片字段的属性
                            'format' => 'raw', // 使用 raw 格式，允许 HTML 标签
                            'value' => function ($model) {
                                return 'https://t.me/'.$model->name;
                            },
                        ],
                        //'callback_url:url',
                        //'base_url:url',
                        //'desc:ntext',
                        [
                            'class' => 'yii\grid\ActionColumn',
                            'template' => '{update} {delete} {login} {get_group}',
                            'buttons' => [
                                'update' => function ($url, $model, $key) {
                                    return Html::a('<span class="glyphicon glyphicon-pencil"></span>', 'javascript:void(0);', [
                                        'class' => 'edit-btn btn btn-xs edit-button',
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/platform-robot/update', 'id' => $model->id]),
                                    ]);
                                },
                                'delete' => function ($url, $model, $key) {
                                    return Html::a('<span class="glyphicon glyphicon-trash"></span>', ['delete', 'id' => $model->id], [
                                        'class' => 'btn btn-xs btn-danger delete-button',
                                        'data-confirm' => '确认删除', // 提示信息
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/platform-robot/delete', 'id' => $model->id]),
                                    ]);
                                },
                                'login' => function ($url, $model, $key) {
                                    return Html::a('<span class="glyphicon glyphicon-ok"></span>', ['login', 'id' => $model->id], [
                                        'class' => 'btn btn-xs btn-green login-button',
                                        'data-confirm' => '确认激活 ？', // 提示信息
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/platform-robot/login', 'id' => $model->id]),
                                    ]);
                                },
                                'get_group' => function ($url, $model, $key) {
                                    return Html::a(\yii\bootstrap\Html::icon('check'), ['get-group', 'id' => $model->id], [
                                        'class' => 'btn btn-xs btn-green login-button',
                                        'data-confirm' => '确认获取群 ？', // 提示信息
                                        'data-url' => Yii::$app->urlManager->createUrl(['tools/platform-robot/get-group', 'id' => $model->id]),
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