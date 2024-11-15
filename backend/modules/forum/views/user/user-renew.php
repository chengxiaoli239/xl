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
                    <span for="dtp_input" class="input-group-addon">到期时间</span>
                    <input type="text" class="form-control" style="width: 160px;" id="default_datetimepicker">
                    <!--span class="input-group-addon">.00</span00-->
                    <div class="input-group-btn">
                        <button class="btn btn-default" id="setCurrent">当前</button>
                        <button class="btn btn-default" type="button" id="addOneHour">+1小时</button> &nbsp;
                        <button class="btn btn-warning" type="button" id="addOneDay">+1天</button>
                        <button class="btn btn-warning" type="button" id="addOneWeek">+1周</button>
                        <button class="btn btn-info" type="button" id="addOneMonth">+1月</button> &nbsp;
                    </div>
                </div>
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
<script src="/statics/assets/bootstrap-daterangepicker/moment.js"></script>
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

        op_id = $(this).attr('data-id');
        $('#ApplyCmConfirm').attr('data-op-id', op_id);
        console.log('expire-time', $(this).data('expire-time'))
        $("#default_datetimepicker").val($(this).data('expire-time'))
        txt = '正在修改 [<strong><font color="#a52a2a">'+$("#renew_"+op_id).attr('data-username')+'</font></strong>] 过期时间：';
        showTips('过期时间', txt);
    });

    // 添加按钮点击事件处理程序
    $('#addOneMonth').click(function () {
        var datetimepicker = $("#default_datetimepicker");
        var currentDate = datetimepicker.val();
        var newDate = moment(currentDate).add(1, 'month').format('YYYY-MM-DD HH:mm');
        datetimepicker.val(newDate);
    });

    $('#addOneWeek').click(function () {
        var datetimepicker = $("#default_datetimepicker");
        var currentDate = datetimepicker.val();
        var newDate = moment(currentDate).add(1, 'week').format('YYYY-MM-DD HH:mm');
        datetimepicker.val(newDate);
    });

    $('#addOneDay').click(function () {
        const datetimepicker = $("#default_datetimepicker");
        const currentDate = datetimepicker.val();
        const newDate = moment(currentDate).add(1, 'day').format('YYYY-MM-DD HH:mm');
        datetimepicker.val(newDate);
    });

    $('#addOneHour').click(function () {
        var datetimepicker = $("#default_datetimepicker");
        var currentDate = datetimepicker.val();
        var newDate = moment(currentDate).add(1, 'hour').format('YYYY-MM-DD HH:mm');
        datetimepicker.val(newDate);
    });
    $('#setCurrent').click(function() {
        $("#default_datetimepicker").val(getCurrentDateTime());
    });
    // 获取当前时间的函数
    function getCurrentDateTime() {
        var currentDate = new Date();
        var year = currentDate.getFullYear();
        var month = ('0' + (currentDate.getMonth() + 1)).slice(-2);
        var day = ('0' + currentDate.getDate()).slice(-2);
        var hours = ('0' + currentDate.getHours()).slice(-2);
        var minutes = ('0' + currentDate.getMinutes()).slice(-2);

        return year + '-' + month + '-' + day + ' ' + hours + ':' + minutes;
    }

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
        id = $(this).attr('data-op-id')
        val = $('#default_datetimepicker').val();
        apply(id, val);
    });

    /**
     * @desc 接口
     * @param uid
     * @param pwd
     */
    function apply(id, val) {
        data = {id:id, time_val:val};
        console.log(data);
        $.post("/forum/tz-systems-users/up-expire-time",data,function(rst) {
            Qrst = rst.data;
            status = rst.status;
            desc = '操作成功~';
            if(rst.status === 200){
                expire_time = Qrst.expire_time;
                if(expire_time == null || expire_time == '' || expire_time == NaN){
                    desc = '永久';
                }else{
                    desc = '过期时间：'+expire_time;
                }
                $("#renew_"+id).html(expire_time.substr(5));
                $("#ApplyCmConfirmEnd").attr('refresh', 1);
            }else {
                $("#ApplyCmConfirmEnd").attr('refresh', 0);
                desc = rst.msg;
            }

            $("#tip_msg_title_end_cm").html(desc);
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
