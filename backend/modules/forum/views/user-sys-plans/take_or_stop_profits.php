<?php

use yii\helpers\Html;
$isNewRecord = $model->isNewRecord;
// 使用更高的优先级，确保最后执行
$this->registerJs("
    (function() {
        var isNewRecord = " . ($isNewRecord ? 'true' : 'false') . ";
        
        function forceShowField() {
            if (isNewRecord) {
                var field = document.getElementById('bet-op-to-wp-singles');
                if (field) {
                    field.style.setProperty('display', 'block', 'important');
                }
            }
        }
        
        function initBetOpToWpSingles() {
            // 如果是新建计划，强制显示
            if (isNewRecord) {
                forceShowField();
                // 持续监控，防止被其他脚本隐藏
                var checkInterval = setInterval(function() {
                    var field = document.getElementById('bet-op-to-wp-singles');
                    if (field && field.style.display === 'none') {
                        field.style.setProperty('display', 'block', 'important');
                    }
                }, 100);
                
                // 5秒后停止监控（页面应该已经加载完成）
                setTimeout(function() {
                    clearInterval(checkInterval);
                }, 5000);
                return;
            }
            
            // 编辑计划时，根据选择来控制显示/隐藏
            function toggleBetOpToWpSingles() {
                if ($('input[name=\"UserSysPlans[bet_op_to_wp][]\"][value=\"2\"]').is(':checked')) {
                    $('#bet-op-to-wp-singles').show();
                } else {
                    $('#bet-op-to-wp-singles').hide();
                }
            }
            
            toggleBetOpToWpSingles();
            
            $('input[name=\"UserSysPlans[bet_op_to_wp][]\"]').off('change.betOpToWp').on('change.betOpToWp', function() {
                toggleBetOpToWpSingles();
            });
        }
        
        // 立即执行一次
        forceShowField();
        
        // 使用多个时机确保执行
        if (document.readyState === 'complete') {
            setTimeout(initBetOpToWpSingles, 100);
        } else {
            $(document).ready(function() {
                setTimeout(initBetOpToWpSingles, 100);
            });
        }
        
        // 额外延迟执行，确保在其他脚本之后
        setTimeout(function() {
            forceShowField();
        }, 300);
        setTimeout(function() {
            forceShowField();
        }, 600);
    })();
", \yii\web\View::POS_END);

?>
<!--?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:0.1-0.3-0.7-1.5-3.1-6.2-12.5-25.1') ?-->
<div class="row">
    <div class="col-lg-12 col-xs-12">
        <?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项 <span id="tag_plan_type" class="glyphicon glyphicon-comment">') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'take_profits')->textInput()->label('止盈点(例：3000)') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'stop_loss')->textInput()->label('止损点(例：4000)') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'bet_op_to_wp')->checkboxList(
            \backend\models\UserSysPlans::BET_DIRECT_OPTION,
            [
                //'value' => [1],
                'item' => function ($index, $label, $name, $checked, $value) {
                    $options = [
                        'class' => 'checkbox-item',
                        'label' => $label,
                        'value' => $value,
                        'checked' => $checked,
                    ];

                    return Html::checkbox($name, $checked, $options);
                }
            ]
        )->label('打盘口方向') ?>
    </div>
    <div class="col-lg-3 col-xs-6"?>
        <?= $form->field($model, 'bet_op_to_wp_singles')->textInput()->label('反向倍数：乘倍数为真实打的倍数,例：0.5') ?>
    </div>
</div>

<!--提示框-start-->
<div class="modal fade" id="exampleModal_msg_plan_type" tabindex="" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:99%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <!--
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">×</span></button>
                -->
                <h4 class="modal-title" id="tip_msg_title_plan_type">信息提示：</h4>
            </div>
            <div class="modal-body" style="max-height: 680px; overflow-y: auto;">
                <strong># 计划类型说明：</strong>
                <pre><code id="rst_code_plan_type">
<strong><font color="blue">1、正常：</font></strong>按照所填倍数，平刷
<strong><font color="blue">2、倍投：</font></strong>选择此计划类型时，"倍数梯度" 为必填，翻倍梯度则不中时按照梯度逐步翻倍，比如翻倍梯度填：1-3-5-7，第一期不中，第二期倍数为3投注，再不中则倍数为5投注，如果翻完所有倍数还是不中，则会回到第1个倍数接着循环，期间只要中也会回到第1个倍数
<strong><font color="blue">3、中则投否则反买：</font></strong>第一期中则按照现有的号码接着投，如果不中则反方向下注，次类型主要用于四定
<strong><font color="blue">4、遗漏投：</font></strong>选择此计划类型时，"遗漏x期投" 为必填项，填：10，则会等待当前计划下注记录遗漏到达10期数才开始投注，一直投注到中为止，如果中则会等待下一次遗漏满足10期的遗漏再开始下注
<strong><font color="blue">5、遗漏倍投：</font></strong>选择此类型计划是，"倍数梯度" 和 "遗漏x期投" 为必填项。比如遗漏填：5，翻倍梯度填：1-3-5-7，则遗漏5期之后开始下注，第一期1倍不中，第二期3倍下注，再不中则5倍下注，依次类推，期间如果中，则停止下注进入等待状态，等遗漏满足5期再接着下注
<strong><font color="blue">6、中则波推倍投：</font></strong>"倍数梯度"为必填项，计划开启时计划进入等待状态，如果中则开始第一个倍数下注，如果中则第二个倍数下注，再中则第三个倍数，期间不中则再次进入等待状态，等再次模拟中时重启发起下注
<strong><font color="blue">7、A出x次B出y次投B：</font></strong>此类型主要用于导入类型的计划：二、三、四定导入，号码组A和号码组B总合为所有号码，对应的号码组有填次数，比如组A连续5次，组B 1次，则开始投B，直到中，如果填了"倍数梯度"，则投B不中时，按照倍数梯度翻倍打，期间如果中，则进入等待状态，再次满足组A连续5次，组B 1次，则开始投B，注：号码组A和组B 是互补的号码，要不A中要不B中
<strong><font color="blue">8、A出x次B出y次投B_2：</font></strong>此类型主要用于导入类型的计划：二、三、四定导入，号码组A和号码组B总合为所有号码，对应的号码组有填次数，比如组A连续5次，组B 1次，则开始投B第1个倍数梯度打，不中时则会再次进入等待状态，再次满足5次A,1次B,接着投倍数梯度中的第二个倍数，注：号码组A和组B 是互补的号码，要不A中要不B中
<strong><font color="blue">9、区间遗漏投：</font></strong>区间统计期数，比如20期、区间遗漏期数，比如15、区间止盈(例：3000)、区间止损(例：4000) 为必填，为20期中有15期不中，满足条件开始投，如果倍数梯度填写，则按照倍速翻倍打，如果中则回到第一个倍数继续翻倍投，期间只要触碰止盈或止损都会重新归零，进入等待状态，再次满足20期漏15期会进入下一个周期的下注
<strong><font color="blue">10、中则倍投：</font></strong>选择此计划类型时，"倍数梯度" 为必填，第一期模拟中，则开始下注，如果不中则进入等待状态，等下一次模拟中再接着打下一个倍数，期间只要真实中都会回到第1个倍数接着打，如果翻完所有倍数还是不中，则回到第一个倍数
<strong><font color="blue">11、遗漏倍投2：</font></strong>选择此类型计划是，"倍数梯度" 和 "遗漏x期投" 为必填项。比如遗漏填：5，翻倍梯度填：1-3-5，则遗漏5期之后开始下注，第一期1倍不中则再等遗漏5期3倍下注，再不中则再等遗漏5期5倍下注，走完所有倍数不中则进入等待状态，等待满足遗漏5期，再次起投，期间只要中则一样进入等待状态
<strong><font color="blue">12、遗漏中则倍投：</font></strong>选择此类型计划是，"倍数梯度" 和 "遗漏x期投" 为必填项。比如遗漏填：2，翻倍梯度填：1-3-5，则遗漏2期之后开始下注，第3期1倍不中，第4期3倍下注，再不中则5倍下注，走完所有倍数不中则进入等待状态，等待满足遗漏5期，再次起投，如果第3期中，第四期不中则进入等待状态，如果：第4、5、6期全中，则返回第一个倍数接着打
<strong><font color="blue">13、遗漏x期投y期：</font></strong>选择此类型计划，"遗漏x期"为必填项，"倍数梯度"根据是否翻倍来决定要不要填，如果不填则平打一期 。填了“翻倍梯度”，比如翻倍梯度填：1-3-5，"遗漏x期投"填：2，则遗漏2期之后开始下注，第1期1倍，第2期投3倍，第三期投5倍，直到下注完所有的翻倍梯度则停止，等待下一次满足遗漏2期的条件再继续投
<strong><font color="blue">14、遗漏x期起投：</font></strong>选择此类型计划，"遗漏x期"为必填项，"倍数梯度"根据是否翻倍来决定要不要填，如果不填则一直平打 。填了“翻倍梯度”，比如翻倍梯度填：1-3-5-7，"遗漏x期投"填：2，则遗漏2期之后开始下注，第1期1倍，不中则第2期投3倍，再不中第三期投5倍，期间只要中就回第一个倍数接着投，投完所有倍数都不中那也回第一个倍数续投
<strong><font color="blue">15、区间亏损起投：</font></strong>选择此类型计划，"亏损x元起投"为必填项，"倍数梯度"根据是否翻倍来决定要不要填，如果不填则一直平打 。填了“翻倍梯度”，比如翻倍梯度填：1-3-5-7，"亏损x元起投"填：500，则计划输到500之后开始下注，第1期1倍，不中则第2期投3倍，再不中第三期投5倍，期间只要中就回第一个倍数接着投，投完所有倍数都不中那也回第一个倍数续投，期间盈利只要触碰止盈或止损都会归零重新计算，再次进入等待状态，再次满足亏损500期会进入下一个周期的下注，注：该类型一定要设置“区间止盈”和“区间止损”，否则会无限下注
                </code></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="">确定</button>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $('#tag_plan_type').click(function () {
        $('#exampleModal_msg_plan_type').modal('show');
    });
})
</script>
