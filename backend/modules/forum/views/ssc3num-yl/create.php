<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model backend\models\Ssc3numYl */

$this->title = Yii::t('app', 'Create Ssc3num Yl');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Ssc3num Yls'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="ssc3num-yl-create wrapper site-min-height">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
