<style>
#modalTable #table th{
    width: 16%;
}
#modalTable #table td{
    width: 16%;
}
#modalTable-head{
    height: 40px
}
.table thead > tr > th, .table tbody > tr > th, .table tfoot > tr > th, .table thead > tr > td, .table tbody > tr > td, .table tfoot > tr > td{
    padding: 5px 0px;
}
.table{
    margin-bottom: 1px;
}
.head-css{
    text-align: center;
    color: #FF7E00;
}
</style>

<!--提示框-start-->
<div class="modal fade " id="exampleModal_msg" tabindex="-1" role="dialog" aria-labelledby="ModalLabel" >
    <div class="modal-dialog modal-lg" role="document" style="width: 800px;margin: 100px auto;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span></button>
                <h4 class="modal-title" id="tip_msg_title">信息提示：</h4>
            </div>
            <div class="modal-body">
                <form id="tip_form_msg" style="display:block; width:100%;height: 560px;overflow-y: scroll">
                    <strong>推送结果：</strong>
                    <pre><code id="rst_code"></code></pre>
                    <strong>推送内容：</strong>
                    <pre><code id="push_content"></code></pre>
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
<!--提示框-end-->

<div id="modalTable" class="modal fade" tabindex="-1" role="dialog" style="display: none;padding-right: 0px;" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document" style="margin:100px auto;">
        <div class="modal-content">
            <div class="modal-header" id="modalTable-head" style="height: 45px;">
                <h5 class="modal-title">查询结果</h5>
                <button type="button" class="close" data-dismiss="modal" style="margin-top: -23px;" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body" style="padding: 0px;">

                </div><div class="clearfix"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>
<script src="/statics/datetimepicker/jquery.js"></script>
<script>
// 利润查询 - 月
$("#new-quick-bet").click(function () {
    url = '/forum/user-sys-plans/quick-bet'
    data = $('#w0').serialize()+'&static_type='+$(this).data('static-type');
    $.post(url, data, function(rst) {
        data = rst.data;
        bet_rst = data.push_rst
        content = data.push_data

        act_data = {"bet_url":$(this).data('url'), content};
        $('#rst_code').text(JSON.stringify(bet_rst,null,' '))
        $('#push_content').text(JSON.stringify(act_data,null,' '))

        $('#exampleModal_msg').modal('show');
    });
});
function isJSON(str) {
    if (typeof str == 'string') {
        try {
            var obj=JSON.parse(str);
            if(typeof obj == 'object' && obj ){
                return true;
            }else{
                return false;
            }

        } catch(e) {
            console.log('error：'+str+'!!!'+e);
            return false;
        }
    }
    console.log('It is not a string!')
}

</script>
