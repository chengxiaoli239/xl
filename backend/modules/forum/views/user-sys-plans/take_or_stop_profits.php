<!--?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:0.1-0.3-0.7-1.5-3.1-6.2-12.5-25.1') ?-->
<?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项') ?>
<div class="row">
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'take_profits')->textInput()->label('止盈点(例：3000)') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'stop_loss')->textInput()->label('止损点(例：4000)') ?>
    </div>
</div>
