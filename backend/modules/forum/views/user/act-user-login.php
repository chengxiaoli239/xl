<!--修改结果提示-->
<div class="modal fade in" id="rstTipModalLogin" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%); min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit; ">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title_login">提示信息</h4>
            </div>
            <div class="modal-body" id="modal-body-login">
                <div class="input-group" id="txt_id">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="ApplyLoginConfirm">确定</button>
            </div>
        </div>
    </div>
</div>
<!--修改结果提示-->
<div class="modal fade in" id="rstTipModalEndLogin" tabindex="-1" role="dialog" aria-labelledby="ModalLabel"
     style="display: none;left: 50%; top: 50%;transform: translate(-50%,-50%);
     min-width:90%;min-height:50%;overflow: visible;bottom: inherit; right: inherit;
">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_title_end_login">提示信息</h4>
            </div>
            <div class="modal-body" id="modal-body-login">
                <div class="form-group up-reason">
                    <label id="tip_msg_title_end_login" for="tip_msg_title_end_login"></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" data-dismiss="modal" id="ApplyCmConfirmEnd">确定</button>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="act_login_status" value="1">
<script src="/statics/datetimepicker/jquery.js"></script>
<script>
$(function () {
    // 修改过期时间
    $('.act-login').click(function () {
        op_id = $(this).attr('data-id');

        $('#ApplyLoginConfirm').attr('data-op-id', op_id);
        username = $(this).data('username');
        account = $(this).data('account');
        ssl_mode = $(this).data('ssl-mode');
        if(account == undefined || username == undefined){
            $('#txt_id').html('账号或密码不能为空');
            showTips('登录操作', '正在登录');
            $("#act_login_status").val(0);
            return false;
        }
        $("#act_login_status").val(1);
        txt = '系统账号:<strong><font color="green">' + username + '</font></strong> &nbsp;&nbsp;网盘账号:<strong><font color="green">'+ account + '</font></strong>'
        + '&nbsp;&nbsp;网盘地址:<strong><font color="green">'+ $(this).data('domain') + '</font></strong>'
        + '&nbsp;&nbsp;TLS:<strong><font color="green">'+ ssl_mode + '</font></strong>';
        $('#txt_id').html(txt);
        showTips('登录操作', '正在登录');
    });
    /**
     * @desc 显示修改结果提示框
     * @param tip_msg
     * @param title
     */
    function showTips(tip_msg = '信息变动', title = '提示信息') {
        $('#tip_msg_title_login').html(title);
        $('#tip_msg_rst_login').html(tip_msg);
        $('#rstTipModalLogin').modal('show');
    }

    $("#ApplyLoginConfirm").click(function () {
        status = $('#act_login_status').val();
        if(status == 0){
            $("#rstTipModalLogin").modal('hide');
            return false;
        }
        id = $(this).attr('data-op-id')
        apply(id);
    });

    /**
     * @desc 接口
     * @param uid
     */
    function apply(id) {
        data = {id:id,is_auto:2};
        console.log(data);
        $.post("/forum/tz-systems-users/login",data,function(rst) {
            console.log(rst);
            status = rst.status;
            msg = rst.msg;
            if(rst.status == 200){
                txt = '登录成功！';
                balance = rst.balance;
                $("#balance_"+id).html(balance);
                $("#ApplyLoginConfirmEnd").attr('refresh', 1);
            }else {
                balance = '';
                txt = '登录失败！';
                $("#ApplyLoginConfirmEnd").attr('refresh', 0);
            }
            msg = txt + '系统账号:' + rst.username + '   网盘账号:'+ rst.account + ' <font color="red">' + msg + '</font>， 余额：' + balance;

            $("#tip_msg_title_end_login").html(msg);
            $("#rstTipModalEndLogin").modal('show');
        },'JSON');
    }

    /**
     * 确定按钮
     */
    $("#ApplyLoginConfirmEnd").click(function () {
        refresh = $(this).attr('refresh');
        if(refresh == 1){
            url = location.href.replace(/#/g, '');
            window.location.href = url;
        }
    });
});
</script>
