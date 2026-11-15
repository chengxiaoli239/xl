<?php

/* @var $this \yii\web\View */
/* @var $content string */

use backend\assets\AppAsset;
use yii\bootstrap\Modal;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use common\widgets\Alert;
use yii\helpers\Url;
use izyue\admin\components\MenuHelper;

AppAsset::register($this);

$menuRows = MenuHelper::getAssignedMenu(Yii::$app->user->id);

$accountMenu = null;
$otherMenuRows = [];
foreach ($menuRows as $menuRow) {
    if (in_array(($menuRow['label'] ?? ''), ['我的账号', '常用功能'], true)) {
        $accountMenu = $menuRow;
    } else {
        $otherMenuRows[] = $menuRow;
    }
}

$commonMenuItems = $accountMenu['items'] ?? [];
$hasMonitorMenu = false;
$hasDrawMenu = false;
foreach ($commonMenuItems as &$commonMenuItem) {
    $itemUrl = $commonMenuItem['url'] ?? '';
    $itemUrl = is_array($itemUrl) ? ($itemUrl[0] ?? '') : $itemUrl;
    $itemUrl = trim((string)$itemUrl, '/');
    if ($itemUrl === 'forum/number-monitor/index') {
        $hasMonitorMenu = true;
    }
    if ($itemUrl === 'forum/ssc-kj-data/index') {
        $hasDrawMenu = true;
        $commonMenuItem['label'] = '开奖记录';
    }
}
unset($commonMenuItem);
if (!$hasMonitorMenu) {
    $commonMenuItems[] = ['label' => '号码监控', 'url' => ['/forum/number-monitor/index']];
}
if (!$hasDrawMenu) {
    $commonMenuItems[] = ['label' => '开奖记录', 'url' => ['/forum/ssc-kj-data/index']];
}
$commonMenu = [
    'label' => '常用功能',
    'url' => '#',
    'defaultExpanded' => true,
    'items' => $commonMenuItems,
];
$menuRows = array_merge([$commonMenu], $otherMenuRows);

$route = Yii::$app->controller->getRoute();
$routeArray = explode('/', $route);
array_pop($routeArray);
$controllerName = implode('/', $routeArray);
use yii\widgets\PjaxAsset;
PjaxAsset::register($this);

$this->registerCssFile('@web/statics/css/slidebars.css', ['depends'=>'backend\assets\AppAsset']);
$identity = Yii::$app->user->identity;
$username = '';
if ($identity !== null) {
    if (is_array($identity) || $identity instanceof ArrayAccess) {
        $username = isset($identity['username']) ? $identity['username'] : '';
    } elseif (is_object($identity) && isset($identity->username)) {
        $username = $identity->username;
    }
}

function isSubUrl($menuArray, $route)
{

    if (isset($menuArray) && is_array($menuArray)) {

        if (isset($menuArray['items'])) {
            foreach ($menuArray['items'] as $item)
            {
                if (isSubUrl($item, $route)) {
                    return true;
                }
            }
        } else {
            $url = is_array($menuArray['url']) ? $menuArray['url'][0] : $menuArray['url'];
            if (strpos($url, $route)) {
                return true;
            }
        }
    } else {
        $url = is_array($menuArray['url']) ? $menuArray['url'][0] : $menuArray['url'];
        if (strpos($url, $route)) {
            return true;
        }
    }
    return false;

}

function isSubMenu($menuArray, $controllerName)
{

    if (isset($menuArray) && is_array($menuArray)) {

        if (isset($menuArray['items'])) {
            foreach ($menuArray['items'] as $item)
            {
                if (isSubMenu($item, $controllerName)) {
                    return true;
                }
            }
        } else {
            $url = is_array($menuArray['url']) ? $menuArray['url'][0] : $menuArray['url'];
            if (strpos($url, $controllerName.'/')) {
                return true;
            }
        }
    } else {
        $url = is_array($menuArray['url']) ? $menuArray['url'][0] : $menuArray['url'];
        if (strpos($url, $controllerName.'/')) {
            return true;
        }
    }
    return false;

}



function initMenu($menuArray, $controllerName, $isSubUrl, $isShowIcon=false)
{
    if (isset($menuArray) && is_array($menuArray)) {

        $url = is_array($menuArray['url']) ? $menuArray['url'][0] : $menuArray['url'];

        if (empty($isSubUrl)) {
            $isSubMenu = isSubMenu($menuArray, $controllerName);
        } else {
            $route = Yii::$app->controller->getRoute();
            $isSubMenu = isSubUrl($menuArray, $route);
        }
        if ($isSubMenu) {
            $class = ' active ';
        } else {
            $class = '';
        }

        if (isset($menuArray['items'])) {
            $expandedClass = !empty($menuArray['defaultExpanded']) ? ' dcjq-current-parent' : '';
            echo '<li class="sub-menu'.$expandedClass.'">';
        } else {
            echo '<li class="'.$class.'">';
        }
        $url = $url == '#' ? 'javascript:;' : Url::toRoute($url);
        $menuIcons = [
            '游戏记录' => 'fa-list-alt', '计划任务' => 'fa-tasks',
            '个人信息' => 'fa-user-o', '异常监控' => 'fa-exclamation-circle',
            '号码监控' => 'fa-eye', '开奖记录' => 'fa-calendar',
        ];
        $icon = $menuIcons[$menuArray['label']] ?? 'fa-file-text-o';
        echo '<a href="'.$url.'"  class="'.$class.'">'.($isShowIcon ? '<i class="fa '.$icon.'" aria-hidden="true"></i>' : '').'<span>'.$menuArray['label'].'</span></a>';

        if (isset($menuArray['items'])) {
            echo '<ul class="sub">';
            foreach ($menuArray['items'] as $item)
            {
                echo initMenu($item, $controllerName, $isSubUrl, true);
            }
            echo '</ul>';
        }

        echo '</li>';
    }

}

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <script>
    (function () {
        try {
            if (localStorage.getItem('xl-admin-theme') === 'dark') {
                document.documentElement.setAttribute('data-xl-theme', 'dark');
            }
        } catch (error) {}
    })();
    </script>
    <?php $this->head() ?>
</head>
<body class="xl-admin-shell">
<?php $this->beginBody() ?>

<section id="container" >
    <button type="button" class="fa fa-bars xl-nav-toggle" aria-label="切换菜单" aria-controls="nav-accordion"></button>
    <!--sidebar start-->
    <aside>
        <div id="sidebar" class="nav-collapse" style="width: 185px">
            <div class="xl-sidebar-brand">
                <a href="<?= Url::home() ?>">数字经济</a>
                <span>运营管理中心</span>
            </div>
            <!-- sidebar menu start-->
            <ul class="sidebar-menu" id="nav-accordion">
                <li>
                    <a class="<?=($controllerName == 'site' ? 'active' : '')?>" href="<?=Url::home()?>">
                        <span><?=Yii::t('admin', 'dashboard')?></span>
                    </a>
                </li>
                <?php
                    if(isset($menuRows)){

                        $isSubUrl = false;
                        foreach($menuRows as $menuRow){
                            $isSubUrl = isSubUrl($menuRow, $route);
                            if ($isSubUrl) {
                                break;
                            }
                        }
                        foreach($menuRows as $menuRow){
                            initMenu($menuRow, $controllerName, $isSubUrl);
                        }
                    }
                ?>

            </ul>
            <div class="xl-sidebar-tools">
                <button type="button" id="xl-theme-toggle" class="xl-theme-toggle" aria-pressed="false" aria-label="切换到暗色主题" title="切换到暗色主题">
                    <i class="fa fa-moon-o" aria-hidden="true"></i><span>暗色</span>
                </button>
                <details class="xl-account-menu">
                    <summary><i class="fa fa-user-o" aria-hidden="true"></i><span><?= Html::encode($username) ?></span><i class="fa fa-angle-down" aria-hidden="true"></i></summary>
                    <div class="xl-account-actions">
                        <a href="<?= Url::toRoute('/forum/user/view') ?>">个人信息</a>
                        <a href="javascript:;" id="updatePassword">修改密码</a>
                        <a href="<?= Url::toRoute('/site/logout') ?>" data-method="post">退出登录</a>
                    </div>
                </details>
            </div>
            <!-- sidebar menu end-->
        </div>
    </aside>
    <!--sidebar end-->
    <!--main content start-->
    <section id="main-content">
        <script src="/statics/js/jquery-2.0.3.js"></script>
        <?=$content?>
    </section>
    <!--main content end-->

    <!-- Right Slidebar start -->
    <div class="sb-slidebar sb-right sb-style-overlay">
        <h5 class="side-title">Online Customers</h5>
        <ul class="quick-chat-list">
            <li class="online">
                <div class="media">
                    <a href="#" class="pull-left media-thumb">
                        <img alt="" src="<?=Yii::getAlias('@web')?>/statics/img/chat-avatar2.jpg" class="media-object">
                    </a>
                    <div class="media-body">
                        <strong>John Doe</strong>
                        <small>Dream Land, AU</small>
                    </div>
                </div><!-- media -->
            </li>
            <li class="online">
                <div class="media">
                    <a href="#" class="pull-left media-thumb">
                        <img alt="" src="<?=Yii::getAlias('@web')?>/statics/img/chat-avatar.jpg" class="media-object">
                    </a>
                    <div class="media-body">
                        <div class="media-status">
                            <span class=" badge bg-important">3</span>
                        </div>
                        <strong>Jonathan Smith</strong>
                        <small>United States</small>
                    </div>
                </div><!-- media -->
            </li>

            <li class="online">
                <div class="media">
                    <a href="#" class="pull-left media-thumb">
                        <img alt="" src="<?=Yii::getAlias('@web')?>/statics/img/pro-ac-1.png" class="media-object">
                    </a>
                    <div class="media-body">
                        <div class="media-status">
                            <span class=" badge bg-success">5</span>
                        </div>
                        <strong>Jane Doe</strong>
                        <small>ABC, USA</small>
                    </div>
                </div><!-- media -->
            </li>
            <li class="online">
                <div class="media">
                    <a href="#" class="pull-left media-thumb">
                        <img alt="" src="<?=Yii::getAlias('@web')?>/statics/img/avatar1.jpg" class="media-object">
                    </a>
                    <div class="media-body">
                        <strong>Anjelina Joli</strong>
                        <small>Fockland, UK</small>
                    </div>
                </div><!-- media -->
            </li>
            <li class="online">
                <div class="media">
                    <a href="#" class="pull-left media-thumb">
                        <img alt="" src="<?=Yii::getAlias('@web')?>/statics/img/mail-avatar.jpg" class="media-object">
                    </a>
                    <div class="media-body">
                        <div class="media-status">
                            <span class=" badge bg-warning">7</span>
                        </div>
                        <strong>Mr Tasi</strong>
                        <small>Dream Land, USA</small>
                    </div>
                </div><!-- media -->
            </li>
        </ul>
        <h5 class="side-title"> pending Task</h5>
        <ul class="p-task tasks-bar">
            <li>
                <a href="#">
                    <div class="task-info">
                        <div class="desc">Dashboard v1.3</div>
                        <div class="percent">40%</div>
                    </div>
                    <div class="progress progress-striped">
                        <div style="width: 40%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="40" role="progressbar" class="progress-bar progress-bar-success">
                            <span class="sr-only">40% Complete (success)</span>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="#">
                    <div class="task-info">
                        <div class="desc">Database Update</div>
                        <div class="percent">60%</div>
                    </div>
                    <div class="progress progress-striped">
                        <div style="width: 60%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="60" role="progressbar" class="progress-bar progress-bar-warning">
                            <span class="sr-only">60% Complete (warning)</span>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="#">
                    <div class="task-info">
                        <div class="desc">Iphone Development</div>
                        <div class="percent">87%</div>
                    </div>
                    <div class="progress progress-striped">
                        <div style="width: 87%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="20" role="progressbar" class="progress-bar progress-bar-info">
                            <span class="sr-only">87% Complete</span>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="#">
                    <div class="task-info">
                        <div class="desc">Mobile App</div>
                        <div class="percent">33%</div>
                    </div>
                    <div class="progress progress-striped">
                        <div style="width: 33%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="80" role="progressbar" class="progress-bar progress-bar-danger">
                            <span class="sr-only">33% Complete (danger)</span>
                        </div>
                    </div>
                </a>
            </li>
            <li>
                <a href="#">
                    <div class="task-info">
                        <div class="desc">Dashboard v1.3</div>
                        <div class="percent">45%</div>
                    </div>
                    <div class="progress progress-striped active">
                        <div style="width: 45%" aria-valuemax="100" aria-valuemin="0" aria-valuenow="45" role="progressbar" class="progress-bar">
                            <span class="sr-only">45% Complete</span>
                        </div>
                    </div>

                </a>
            </li>
            <li class="external">
                <a href="#">See All Tasks</a>
            </li>
        </ul>
    </div>
    <!-- Right Slidebar end -->

    <!--footer start-->
    <footer class="site-footer">
        <div class="text-center">
            <?= date('Y') ?> &copy; 数字经济
            <a href="#" class="go-top">
                <i class="fa fa-angle-up"></i>
            </a>
        </div>
    </footer>
    <!--footer end-->
</section>

<?php Modal::begin([
    'id' => 'update-password-modal',
    'size' => 'modal-md',
    'header' => '<h4 class="modal-title">修改密码</h4>',
]); ?>
<div id="update-password-content">
<?php Modal::end(); ?>

<script>
$('#updatePassword').click(function () {
    $('#update-password-modal').modal('show');
    $('#update-password-content').load("/forum/user/act-update-password-page");
});
</script>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
