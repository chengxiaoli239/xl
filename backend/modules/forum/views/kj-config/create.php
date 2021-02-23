<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\KjConfig */

$this->title = Yii::t('app', 'Create Kj Config');
$this->params['breadcrumbs'][] = ['label' => 'Kj Configs', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="kj-config-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
        'lottery_type_arr' => $lottery_type_arr,
    ]) ?>

</div>
