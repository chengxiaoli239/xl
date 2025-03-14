<style>
.tooltip {
    display: none;
    position: absolute;
    background-color: yellow;
    padding: 5px;
    border: 1px solid #ccc;
    z-index: 1000; /* 确保 tooltip 在最上面 */
}
input::placeholder {
    color: #999; /* 更改 placeholder 文字颜色 */
    opacity: 1;  /* 提高可见度 */
    font-weight: bold; /* 加粗文字 */
}

input {
    background-color: #f9f9f9; /* 改变输入框背景色 */
    border: 1px solid #ccc; /* 增加边框 */
}

input:focus {
    border-color: #66afe9; /* 获取焦点时的边框颜色 */
    outline: none; /* 去掉默认轮廓 */
}
.help-block{
    margin-bottom: -9px;
}
</style>
<?
$dynamics = array_column(\backend\service\numbers\DynamicFilterService::DYNAMIC_FILTER_TYPES, null, 'type');
?>
<!-- 动态过滤1 -->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #da4f49;">
    <div class="col-lg-10 col-xs-12">
        <button type="button" class="btn btn-default" id="toggleFilterDynamic1">动态过滤1 <span class="glyphicon glyphicon-chevron-down"></span></button>
        <div id="filterDynamic1Content" style="display: none;">
            <?= $form->field($model, 'filter_dynamic_types')->checkboxList($filter_dynamic_typesArr)->label('动态过滤1（1234分别代表：千百十个） <span id="tag_filter_dynamic_type" class="glyphicon glyphicon-comment"></span>') ?>
        </div>
    </div>
</div>

<!-- 动态过滤2 -->
<div class="row" style="border-width:2px;margin-top:3px;border-style:solid;border-color: #da4f49;">
    <div class="col-lg-10 col-xs-12">
        <button type="button" class="btn btn-default" id="toggleFilterDynamic2">动态过滤2 <span class="glyphicon glyphicon-chevron-down"></span></button>
        <div id="filterDynamic2Content" style="display: none;">
            <label>动态过滤2（1234分别代表：千百十个）：</label>
            <?php foreach ($filter_dynamic_types2 as $key=>$value): ?>
                <?php if(empty($value['playway']) OR in_array($playway, $value['playway'])): ?>
                    <div style="display: flex; align-items: center;">
                        <div style="margin-right: 2px;padding-top: 3px">
                            <?= $form->field($model, "filter_dynamic_types2[".$key."][type]")->checkbox([
                                'value'=>$value['type'],
                                'label' => $value['label'],
                                'labelOptions' => [
                                    'style' => 'display:inline;',
                                    'title' => $value['desc'],
                                ],
                                'title' => $value['desc'],
                                'alt' => $value['desc'],
                            ])->label(false) ?>
                        </div>
                        <div class="tooltip" style="display:none; position:absolute; background-color:yellow; padding:5px; border:1px solid #ccc;"><?=$value['desc']?></div>
                        <input type="hidden" name="UserSysPlans[filter_dynamic_types2][<?= $key ?>][label]" value="<?= htmlspecialchars($value['label'], ENT_QUOTES) ?>">
                        <?php foreach ($value['params'] as $k2 => $v2): ?>
                            <input type="text" id="input_<?= $key.'_'.$k2 ?>" name="UserSysPlans[filter_dynamic_types2][<?= $key ?>][params][<?= $k2 ?>]" style="width: 40px; margin: -2px 2px;" placeholder="<?= $k2 ?>" value="<?= $v2 ?>">
                        <?php endforeach; ?>
                        <?php if (true OR !empty($dynamics[$value['type']]['img']) OR !empty($dynamics[$value['type']]['is_show'])): ?>
                            <button
                                    type="button"
                                    class="btn btn-info btn-xs show-modal-btn" style="margin-left: 5px;"
                                    data-label="<?= htmlspecialchars($dynamics[$value['type']]['label'], ENT_QUOTES) ?>"
                                    data-desc="<?= htmlspecialchars(
                                        '描述：'
                                        . ($value['params']['x'] ? ' [x:' . $value['params']['x'] . ']' : '')
                                        . ($value['params']['y'] ? '、[y:' . $value['params']['y'] . ']' : '')
                                        . ($value['params']['z'] ? '、[z:' . $value['params']['z'] . ']' : '')
                                        . ($value['params']['h'] ? '、[h:' . $value['params']['h'] . ']' : '')
                                        . $dynamics[$value['type']]['desc'],
                                        ENT_QUOTES
                                    ) ?>"
                                    data-img="<?= htmlspecialchars($dynamics[$value['type']]['img'], ENT_QUOTES) ?>"
                            >查看</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- 模态框 -->
<div class="modal fade" id="dynamicModal" tabindex="-1" role="dialog" aria-labelledby="dynamicModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="dynamicModalLabel">详情信息</h4>
            </div>
            <div class="modal-body">
                <!-- 动态填充内容 -->
                <p id="modalDesc"></p>
                <img id="modalImage" src="" alt="详情图片" style="max-width: 100%; height: auto; display: none;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
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
<strong><font color="blue">24、过滤期号尾号一致的1000组历史开奖号码：</font></strong>比如上期期号：288期，则历史开奖期号，288期、128期、108期、008期等最近的1000组直码都剔除
<strong><font color="blue">25、剔除前期号码至少1个上奖：</font></strong>上期开奖；3456，则剔除之后012789上一个，比如3458，5669这些都中奖，3455，3446不中奖
<strong><font color="blue">26、剔除前期号码至少2个上奖：</font></strong>上期开奖；3456，则剔除之后012789上两个，比如3478，5699这些都中奖，3458，3449不中奖
<strong><font color="blue">27、剔除前100期123位一致的直码：</font></strong>上期123位开奖；345X，则剔除之后，比如3458，3459这些都不中奖，3448，3445中奖
<strong><font color="blue">28、剔除前100期124位一致的直码：</font></strong>上期124开奖；34X5，则剔除之后，比如3485，3495这些都不中奖，3845，3285中奖
<strong><font color="blue">29、剔除前100期134位一致的直码：</font></strong>上期134开奖；3X45，则剔除之后，比如3845，3945这些都不中奖，3375，3935中奖
<strong><font color="blue">30、剔除前100期234位一致的直码：</font></strong>上期234开奖；X345，则剔除之后，比如8345，9345这些都不中奖，6245，9145中奖
<strong><font color="blue">31、剔除历史期号一致的号码全倒：</font></strong>历史所有期号一致的开奖号码对应的全倒号码剔除掉
<strong><font color="blue">32、过滤1235期号尾号一致1000组历史直码：</font></strong>过滤1235位历史所有期号尾号一致的1000组历史直码
<strong><font color="blue">33、过滤1245期号尾号一致1000组历史直码：</font></strong>过滤1245位历史所有期号尾号一致的1000组历史直码
<strong><font color="blue">34、过滤1345期号尾号一致1000组历史直码：</font></strong>过滤1345位历史所有期号尾号一致的1000组历史直码
<strong><font color="blue">35、过滤2345期号尾号一致1000组历史直码：</font></strong>过滤2345位历史所有期号尾号一致的1000组历史直码
<strong><font color="blue">36、过滤1234大小类型一致近2500组直码：</font></strong>过滤1234历史所有大小类型一致历史直码，开：1279=>两大两小， 只要是两大两小,不区分位置最近2500期内都会被过滤
<strong><font color="blue">37、过滤1234前期大小或单双类型分别都不一致号码：</font></strong>比如上期开：3637，则如果开：双单双双、大小大小  这种类型就不中奖，其它的就中奖
<strong><font color="blue">38、过滤1234最近两大两小1000组直码：</font></strong>比如上期开：固定每期过滤，最近开的两大两小1000组直码
<strong><font color="blue">39、过滤1234最近两单两双1000组直码：</font></strong>比如上期开：固定每期过滤，最近开的两单两双1000组直码
<strong><font color="blue">40、过滤1234最近两大两小200组直码：</font></strong>比如上期开：固定每期过滤，最近开的两大两小200组直码
<strong><font color="blue">41、过滤1234最近两单两双200组直码：</font></strong>比如上期开：固定每期过滤，最近开的两单两双200组直码
<strong><font color="blue">42、过滤1234最近两大两小500组直码：</font></strong>比如上期开：固定每期过滤，最近开的两大两小500组直码
<strong><font color="blue">43、过滤1234最近两单两双500组直码：</font></strong>比如上期开：固定每期过滤，最近开的两单两双500组直码
<strong><font color="blue">44、过滤1234最近500组直码：</font></strong>比如上期开：固定每期过滤，最近开的500组直码
<strong><font color="blue">45、过滤1234最近300组直码：</font></strong>比如上期开：固定每期过滤，最近开的300组直码
<strong><font color="blue">46、过滤1234最近50期全倒：</font></strong>最近50期开过的号码全倒，重复情况不够50期的往前继续搜集够50期
<strong><font color="blue">47、配数单双互排除及该位置号码：</font></strong>比如开：1234，则配数除：千024681、百135792、十024683、个135794
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
$(document).ready(function() {
    // Toggle dynamic filter 1
    $('#toggleFilterDynamic1').click(function() {
        $('#filterDynamic1Content').toggle();
        $(this).find('span').toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
    });

    // Toggle dynamic filter 2
    $('#toggleFilterDynamic2').click(function() {
        $('#filterDynamic2Content').toggle();
        $(this).find('span').toggleClass('glyphicon-chevron-down glyphicon-chevron-up');
    });

    // Show/hide dynamic filter 1 content based on checkbox selection
    $('input[name="UserSysPlans[filter_dynamic_types][]"]').change(function() {
        if ($('input[name="UserSysPlans[filter_dynamic_types][]"]:checked').length > 0) {
            $('#filterDynamic1Content').show();
        } else {
            $('#filterDynamic1Content').hide();
        }
    }).trigger('change');

    // Show/hide dynamic filter 2 content based on checkbox selection
    $('input[name^="UserSysPlans[filter_dynamic_types2]"]').change(function() {
        if ($('input[name^="UserSysPlans[filter_dynamic_types2]"]:checked').length > 0) {
            $('#filterDynamic2Content').show();
        } else {
            $('#filterDynamic2Content').hide();
        }
    }).trigger('change');
});
// 监听动态过滤2中的复选框点击事件
$('input[type="checkbox"][name^="UserSysPlans[filter_dynamic_types2]"]').click(function() {
    // 获取提示框
    var tooltip = $('.tooltip');

    // 显示提示
    tooltip.css({top: $(this).offset().top + 20, left: $(this).offset().left}).fadeIn();

    // 设置定时器，3秒后隐藏提示
    setTimeout(function() {
        tooltip.fadeOut();
    }, 3000); // 3000毫秒 = 3秒
});
$(function () {
    $('#tag_filter_dynamic_type').click(function () {
        $('#exampleModal_msg_filter_dynamic_type').modal('show');
    });
})

// 监听所有 "查看详情" 按钮的点击事件
document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("dynamicModal");
    const modalTile = document.getElementById("dynamicModalLabel");
    const modalDesc = document.getElementById("modalDesc");
    const modalImage = document.getElementById("modalImage");

    // 获取所有的按钮
    document.querySelectorAll(".show-modal-btn").forEach(function (button) {
        button.addEventListener("click", function () {
            // 从按钮中获取数据
            const title = this.getAttribute("data-label");
            const desc = this.getAttribute("data-desc");
            const imgUrl = this.getAttribute("data-img");


            // 设置模态框内容
            modalDesc.innerHTML = desc;
            modalTile.textContent = title
            if (imgUrl) {
                modalImage.src = imgUrl;
                modalImage.style.display = "block";
            } else {
                modalImage.style.display = "none";
            }

            // 显示模态框
            $(modal).modal("show");
        });
    });
});
</script>