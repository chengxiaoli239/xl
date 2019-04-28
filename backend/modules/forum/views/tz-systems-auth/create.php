<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\TzSystemsAuth */

$this->title = Yii::t('app', 'Create Tz Systems Auth');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Tz Systems Auths'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="tz-systems-auth-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
