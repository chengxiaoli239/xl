<?php

use yii\helpers\Html;
?>
<div class="form-group">
    <div class="col-lg-offset-2 col-lg-10">
        <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
        <?= Html::button(Yii::t('app', 'query-yl'), ['class' => 'btn btn-success id-query', 'data-type'=>1]) ?>
        <?= Html::button(Yii::t('app', 'query-profits-months'), ['class' => 'btn btn-success id-query-profits', 'data-type'=>2, 'data-static-type'=>1]) ?>
        <?= Html::button(Yii::t('app', 'query-profits-years'), ['class' => 'btn btn-success id-query-profits', 'data-type'=>2, 'data-static-type'=>2]) ?>
    </div>
</div>
