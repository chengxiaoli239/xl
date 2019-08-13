<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel backend\models\searchs\TzSystemsUsers */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('app', 'Tools Manage');
$this->params['breadcrumbs'][] = $this->title;
?>
<section class="tz-systems-users-index wrapper site-min-height">
    <!-- page start-->
    <section class="panel">
        <header class="panel-heading">
            <?= Html::encode($this->title) ?>
        </header>
        <div class="panel-body">
            <div class="adv-table editable-table ">
                <!--div class="clearfix">
                    <div class="btn-group">
                        <a class="btn btn-success" href="/forum/betting-records/create" style="margin-bottom:15px;">Create Betting Records</a>                    </div>
                </div-->

                <div id="p0" data-pjax-container="" data-pjax-push-state="" data-pjax-timeout="1000">
                    <div id="w0" class="grid-view"><div class="summary">第<b>1-20</b>条，共<b>1</b>条数据.</div>
                        <table class="table table-striped table-bordered"><thead>
                            <tr>
                                <th width="5%"width="5%">序号</th>
                                <th><a href="#" data-sort="qihao">xxx</a></th>
                                <th><a href="#" data-sort="qihao">操作</a></th>
                                <th><a href="#" data-sort="qihao">状态</a></th>
                                <th><a href="/forum/betting-records/index?page=1&amp;_pjax=%23p0&amp;sort=create_time" data-sort="create_time">操作时间</a></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr data-key="49082">
                                <td>1</td>
                                <td>#</td>
                                <td><a href="#" alt="描述" title="">动作</a></td>
                                <td>清除数据表[<a href="/forum/tools/clear-tables-data" alt="描述" title="">执行</a>]</td>
                                <td>2019-08-13 11:12:09</td>
                            </tr>
                            </tbody></table>
                        <ul class="pagination"><li class="prev disabled"><span>«</span></li>
                            <li class="active"><a href="/forum/betting-records/index?page=1&amp;_pjax=%23p0" data-page="0">1</a></li>
                            <li><a href="/forum/betting-records/index?page=2&amp;_pjax=%23p0" data-page="1">2</a></li>
                            <li class="next"><a href="/forum/betting-records/index?page=2&amp;_pjax=%23p0" data-page="1">»</a></li>
                        </ul>
                    </div>


                    <script>jQuery('#w0').yiiGridView({"filterUrl":"\/forum\/betting-records\/index?page=1\u0026_pjax=%23p0\u0026_pjax=%23p0","filterSelector":"#w0-filters input, #w0-filters select"});</script></div>            </div>

        </div>
    </section>
    <!-- page end-->
</section>
