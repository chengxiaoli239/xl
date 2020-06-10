<link rel="stylesheet" type="text/css" href="/statics/datetimepicker/jquery.datetimepicker.css"/>
<!--修改结果提示-->
<div class="modal fade in" id="rstTipModalCm" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title_cm">提示信息</h4>
            </div>
            <div class="modal-body" id="modal-body-cm">
                <div class="input-group">
                    <span for="dtp_input3" class="input-group-addon">到期时间：</span>
                    <input type="text" class="form-control" style="width: 200px;" id="default_datetimepicker">
                    <!--span class="input-group-addon">.00</span00-->
                </div>
                <!--
                <div id="edit_cm" style="display: none;">
                    <label for="dtp_input3">到期时间：</label>
                    <input type="text" id="default_datetimepicker"/>
                </div>
                -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="ApplyCmConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<!--修改结果提示-->
<div class="modal fade in" id="rstTipModalEndCm" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_title_end_cm">提示信息</h4>
            </div>
            <div class="modal-body" id="modal-body-cm">
                <div class="form-group up-reason">
                    <label id="tip_msg_title_end_cm" for="tip_msg_title_end_cm"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="ApplyCmConfirmEnd">确定</button>
            </div>
        </div>
    </div>
</div>
<script src="/statics/datetimepicker/jquery.js"></script>
<script src="/statics/datetimepicker/build/jquery.datetimepicker.full.js"></script>
<script>
$.datetimepicker.setLocale('en');
$('#default_datetimepicker').datetimepicker({
    formatTime:'H:i',
    formatDate:'d.m.Y',
    //defaultDate:'8.12.1986', // it's my birthday
    defaultDate:'+03.01.1970', // it's my birthday
    defaultTime:'10:00',
    timepickerScrollbar:false
});
$(function () {
    // 修改过期时间
    $('.renew-account').click(function () {
        var up_name = $(this).attr('data-name');

        op_id = $(this).attr('id');
        $("#edit_cm").show();
        //$("#modal-body-cm").html($('#edit_cm'))
        $('#ApplyCmConfirm').attr('data-op-id', op_id);
        showTips('过期时间', '输入到期日期：');
    });
    /**
     * @desc 显示修改结果提示框
     * @param tip_msg
     * @param title
     */
    function showTips(tip_msg = '信息变动', title = '提示信息') {
        $('#tip_msg_title_cm').html(title);
        $('#tip_msg_rst_cm').html(tip_msg);
        $('#rstTipModalCm').modal('show');
    }

    $("#ApplyCmConfirm").click(function () {
        val = $('#default_datetimepicker').val();
        id = $(this).attr('data-id')
        apply(id, val);
    });

    /**
     * @desc 接口
     * @param uid
     * @param pwd
     */
    function apply(id, val) {
        data = {id:id, time_val:val};
        $.post("/forum/tz-systems-users/up-expire-time",data,function(rst) {
            Qrst = rst.data;
            status = rst.status;
            msg = '';
            if(rst.status == 200){
                expire_time = Qrst.expire_time;
                txt = '';
                if(expire_time == null || expire_time == '' || expire_time == NaN){
                   txt = '永久';
                }else{
                    txt = expire_time;
                }
                $('#renew_'+id).html(txt)
                $("#ApplyCmConfirmEnd").attr('refresh', 1);
            }else {
                $("#ApplyCmConfirmEnd").attr('refresh', 0);
                msg = rst.msg;
            }

            $("#tip_msg_title_end_cm").html(msg);
            $("#rstTipModalEndCm").modal('show');
        },'JSON');
    }

    /**
     * 确定按钮
     */
    $("#ApplyCmConfirmEnd").click(function () {
        refresh = $(this).attr('refresh');
        if(refresh == 1){
            url = location.href.replace(/#/g, '');
            window.location.href = url;
        }
    });
});
</script>
