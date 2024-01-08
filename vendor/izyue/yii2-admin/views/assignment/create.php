<?php

use yii\helpers\Html;

/**
 * @var yii\web\View $this
 * @var izyue\admin\models\AuthItem $model
 */

if(\backend\service\UserService::is3dAdmin(\Yii::$app->user->identity)){
    $this->title = Yii::t('rbac-admin', 'Create Proxy');
}else{
    $this->title = Yii::t('rbac-admin', 'Create User');
}

$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', 'Roles'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

?>

<section class="wrapper site-min-height">
    <?=
    $this->render('_form', [
        'model' => $model,
    ]);
    ?>
</section>