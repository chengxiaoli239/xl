<script>
    $(function () {
    // 四定单双：两单两双，四单，四双等
    $('.reverse_type_4ds').click(function () {
        $("[name='UserSysPlans[type_4ds][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });
    // 单双类型:1122,2121,2222 等16种组合
    $('.reverse_type_ds_detail').click(function () {
        $("[name='UserSysPlans[type_ds_details][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });

    // 和值反选
    $('.reverse_type_hz').click(function () {
        $("[name='UserSysPlans[hz][]']").each(function () {
            if($(this).prop('checked') == false){
                $(this).prop("checked",true);
            }else {
                $(this).prop("checked",false);
            }
        });
    });
    // 0-6
    $('.reverse_type_hz_0_6').click(function () {
        console.log('aslkdjf')
        arr = ['0','1','2','3','4','5','6']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 5-10
    $('.reverse_type_hz_5_10').click(function () {
        arr = ['5','6','7','8','9','10']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 11-15
    $('.reverse_type_hz_11_15').click(function () {
        arr = ['11','12','13','14','15']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 16-19
    $('.reverse_type_hz_16_19').click(function () {
        arr = ['16','17','18','19']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 20-24
    $('.reverse_type_hz_20_24').click(function () {
        arr = ['20','21','22','23','24']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 25-29
    $('.reverse_type_hz_25_29').click(function () {
        arr = ['25','26','27','28','29']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 30-36
    $('.reverse_type_hz_30_36').click(function () {
        arr = ['30','31','32','33','34','35','36']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 单
    $('.reverse_type_hz_dan').click(function () {
        arr = ['1','3','5','7','9','11','13','15','17','19','21','23','25','27','29','31','33','35']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    // 双
    $('.reverse_type_hz_shuang').click(function () {
        arr = ['0','2','4','6','8','10','12','14','16','18','20','22','24','26','28','30','32','34','36']
        $("[name='UserSysPlans[hz][]']").each(function () {
            v = $.inArray($(this).val(), arr);
            if(v != -1){
                if($(this).prop('checked') == false){
                    $(this).prop("checked",true);
                }else {
                    $(this).prop("checked",false);
                }
            }
        });
    });
    $('.reverse_type_hz_Null').click(function () {
        $("[name='UserSysPlans[hz][]']").each(function () {
            $(this).prop("checked",false);
        });
    });

    $(".id-query").click(function () {
        url = '/forum/ssc-static-yl/query'
        data = $('#w0').serialize()+'&type='+$(this).data('type');
        $.post(url, data, function(rst) {
            $('#tip_msg_rst').html('<strong>号码：</strong>'+rst.code_desc + "<br>" +'<strong>组数：</strong>'+ rst.counts + "<br>" +'<strong>当前：</strong>'+ rst.current_times + "<br>" + '<strong>历史最大：</strong>'+ rst.max_miss + "<br>" + "<strong>遗漏记录：</strong>" +rst.current_times + '-' +rst.yl_str)
            $('#rstTipModal').modal('show');
        });
    });

    // 大
    $('.code_type_dx_1').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('56789') : obj.val('');
    });
    // 双
    $('.code_type_dx_2').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('01234') : obj.val('');
    });
    // 单
    $('.code_type_ds_1').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('13579') : obj.val('');
    });
    // 双
    $('.code_type_ds_2').click(function () {
        obj = $(this).parent().next();
        obj.val() == '' ? obj.val('02468') : obj.val('');
    });

});
