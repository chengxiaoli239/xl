<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model backend\models\User */
/* @var $form yii\widgets\ActiveForm */
use common\models\AdminModel;
$username = AdminModel::findOne($uid)->username;
?>

<div class="user-form row">
    <div class="col-lg-12">
        <section class="panel">
            <header class="panel-heading">
                <?= Html::encode($this->title).' ： '.$username; ?>
            </header>
            <div class="panel-body">
                <?php $form = ActiveForm::begin(); ?>

                    <?= $form->field($model, 'tz_systems_ids')
                        ->checkboxList($allSystems, ['itemOptions'=>['labelOptions'=>['class'=>'tz_system_id']]])
                        ->label('投注网点') ?>

                    <?= $form->field($model, 'tz_types')->checkboxList($allTzTypes)->label('投注方式tz_types') ?>

                    <?= $form->field($model, 'lottery_types')->checkboxList($allLotteryTypes)->label('彩种lottery_types') ?>

                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-danger']) ?>
                        </div>
                    </div>
                <?php ActiveForm::end(); ?>
            </div>
        </section>
    </div>
</div>
<?php foreach ($systemTzTypes as $k=>$systemTzType){?>
<input type="hidden" id="s_tz_types_<?=$k?>" value='<?php echo json_encode($systemTzType);?>'>
<?}?>
<script src="/statics/js/jquery-2.0.3.js"></script>
<script>
$(function () {
    $('.tz_system_id').click(function () {
        //$.each($("input[name='TzSystemsAuth[tz_types][]'"), function (n) {
        //    $(this).attr('checked', false);
        //});

        system_id = $(this).find('input').val();
        if($("#s_tz_types_"+system_id).length>0){

            d = $("#s_tz_types_"+system_id).val();
            var objArr = JSON.parse(d);
            $.each($("input[name='TzSystemsAuth[tz_types][]'"), function (n) {
                val = $(this).val().toString();
                index = $.inArray(val, objArr)
                console.log(index, val);
                if(index>=0){
                    $(this).attr('checked', true);
                }
            });
        }

        //$.each($('.tz_system_id'), function(ele){
        //    console.log($(this))
        //    $(this).find('input').prop("checked",false);
        //})
        $(this).find('input').prop("checked", !$(this).find('input').prop('checked'));
    });
})
</script>
