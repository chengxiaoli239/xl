<!--动态过滤-->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #da4f49;">
    <!--
    <div class="col-lg-2 col-xs-3">
        <?= $form->field($model, 'is_filter_dynamic')->checkboxList(['1'=>'是'])->label('动态过滤') ?>
    </div>
    -->
    <div class="col-lg-10 col-xs-12">
        <?= $form->field($model, 'filter_dynamic_types')->checkboxList($filter_dynamic_typesArr)->label('动态过滤类型 <span id="tag_filter_dynamic_type" class="glyphicon glyphicon-comment"></span>') ?>
    </div>
</div>

<!--提示框-start-->
<div class="modal fade" id="exampleModal_msg_filter_dynamic_type" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 800px;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <strong># 动态过滤类型说明：</strong>
                <pre><code id="rst_code">
<strong><font color="blue">1、1小1大，剔除前期号码至少2个上奖: </font></strong>01234上一个，56789上一个，上期开奖；3456，则剔除之后012789上两个，比如3478，5699这些都中奖，3458，3449不中奖
<strong><font color="blue">2、头尾去除期号最后两位相加：</font></strong>期号123，期号后两位相加：2+3=5，则：千位、个位排除5
<strong><font color="blue">3、头去除期号最后两位相加：</font></strong>期号123，期号后两位相加：2+3=5，则：千位排除5
<strong><font color="blue">4、尾去除期号最后两位相加：</font></strong>期号123，期号后两位相加：2+3=5，则：个位排除5
<strong><font color="blue">5、头尾相加不等于期号后两位相加：</font></strong>期号123，期号后两位相加：2+3=5，则：千位 + 个位不等于5、15
<strong><font color="blue">6、过滤前200期开过号码的全转(四定)：</font></strong>前两百期的号码，每一期全倒剔除掉
<strong><font color="blue">7、千十相加不等于期号后两位相加(四定)：</font></strong>期号123，期号后两位相加：2+3=5，则：千位 + 十位不等于5、15
<strong><font color="blue">8、随机9000组(四定)：</font></strong>随机生成9000组
<strong><font color="blue">9、过滤最近2880组(四定)：</font></strong>剔除刚开2880期号码，不够2880组往前搜集
<strong><font color="blue">10、过滤后4最近2880组(四定)：</font></strong>后四：百十个五，四个位置，剔除刚开2880期号码，不够2880组往前搜集
<strong><font color="blue">11、</font></strong>过滤最近1w期重复2次以上直码(四定)
<strong><font color="blue">12、过滤1235最近2880组(四定)：</font></strong>剔除1235位置刚开的2880期号码，不够2880组往前搜集，与第9个点类似，1235代表位置：千百十五
<strong><font color="blue">13、过滤1245最近2880组(四定)：</font></strong>剔除1245位置刚开的2880期号码，不够2880组往前搜集，与第9个点类似，1245代表位置：千百个五
<strong><font color="blue">14、过滤1345最近2880组(四定)：</font></strong>剔除1345位置刚开的2880期号码，不够2880组往前搜集，与第9个点类似，1345代表位置：千十个五
<strong><font color="blue">15、取前四最近8000组(四定)：</font></strong>取前四最近8000期开过的号码，不够往后搜集够8000组
<strong><font color="blue">16、取后四最近8000组(四定)：</font></strong>取前四（百十个五位置）最近8000期开过的号码，不够往后搜集够8000组
<strong><font color="blue">17、过滤两个位置一样的所有号码：</font></strong>比如开：3456，则：3499，3418，9056，5687两个位置与上期一致，不中奖
<strong><font color="blue">18、过滤1234最近2000组(四定)：</font></strong>前四位不够往后搜集，与第10点类似，1234代表：千百是个
<strong><font color="blue">19、过滤1235最近2000组(四定)：</font></strong>过滤掉最近2000期的号码，不够往后搜集，1235代表：千百十五
<strong><font color="blue">20、过滤1245最近2000组(四定)：</font></strong>过滤掉最近2000期的号码，不够往后搜集，1245代表：千百个五
<strong><font color="blue">21、过滤1345最近2000组(四定)：</font></strong>过滤掉最近2000期的号码，不够往后搜集，1345代表：千十个五
<strong><font color="blue">22、过滤前100期开过号码的全转(四定)：</font></strong>过滤掉前100期开过号码全转的直码
<strong><font color="blue">23、过滤前期同位置号码(四定6561组)：</font></strong>比如上期开：3456，则：千位不等于3、百位不等于4、十位不等于5、个不等于6
<strong><font color="blue">24、过滤期号尾号一致的历史开奖号码：</font></strong>比如上期期号：288期，则历史开奖期号，288期、128期、108期、008期等对应的所有直码都剔除
<strong><font color="blue">25、剔除前期号码至少1个上奖：</font></strong>上期开奖；3456，则剔除之后012789上两个，比如3458，5669这些都中奖，3455，3446不中奖
<strong><font color="blue">26、剔除前期号码至少2个上奖：</font></strong>上期开奖；3456，则剔除之后012789上两个，比如3478，5699这些都中奖，3458，3449不中奖
                </code></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" data-type="" id="confirm_ms">确定</button>
            </div>
        </div>
    </div>
</div>
<script>
$(function () {
    $('#tag_filter_dynamic_type').click(function () {
        $('#exampleModal_msg_filter_dynamic_type').modal('show');
    });
})
</script>