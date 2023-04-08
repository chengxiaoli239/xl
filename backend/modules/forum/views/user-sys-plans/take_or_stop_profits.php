<!--?= $form->field($model, 'singles')->textInput()->label('倍数梯度[元],如:0.1-0.3-0.7-1.5-3.1-6.2-12.5-25.1') ?-->
<div class="row">
    <div class="col-lg-6 col-xs-12">
        <?= $form->field($model, 'plan_type')->radioList($plan_types)->label('计划类型，为"止盈止损"计划时须填以下两项 <span id="tag_plan_type" class="glyphicon glyphicon-comment">') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'take_profits')->textInput()->label('止盈点(例：3000)') ?>
    </div>
    <div class="col-lg-3 col-xs-6">
        <?= $form->field($model, 'stop_loss')->textInput()->label('止损点(例：4000)') ?>
    </div>
</div>

<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg_plan_type" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 800px;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong># 动态过滤类型说明：</strong>
                    <pre>
                        <code id="rst_code">
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
<strong><font color="blue">11、遗漏倍投2：</font></strong>选择此类型计划是，"倍数梯度" 和 "遗漏x期投" 为必填项。比如遗漏填：5，翻倍梯度填：1-3-5，则遗漏5期之后开始下注，第一期1倍不中，第二期3倍下注，再不中则5倍下注，走完所有倍数不中则进入等待状态，等待满足遗漏5期，再次起投，期间中奖也一样进入等待状态
                        </code>
                    </pre>
                </form>
            </div>
            <!--div class="form-group down-reason">
                <p><label>备注信息:</label><input class="form-control" id="message" name="message" /></p>
            </div-->
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script>
    $(function () {
        $('#tag_plan_type').click(function () {
            $('#exampleModal_msg_plan_type').modal('show');
        });
    })
</script>
