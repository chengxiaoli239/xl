if (!window.Tnmc) {
    window.Tnmc = {};
}
/**
 * 获取地址栏参数
 */
window.Tnmc.getUrlArgs = function() {
    var params = [],
        hash;
    var url = window.location.href;
    if (url.indexOf("?") !== -1) {
        var hashes = window.location.href.slice(window.location.href.indexOf("?") + 1).split("&");
        for (var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split("=");
            if (!!hash[0] && !!hash[1]) {
                hash[0] = decodeURIComponent(hash[0].replace(/\+/g, "%20"));
                params[hash[0]] = decodeURIComponent(hash[1].replace(/\+/g, "%20"))
            }
        }
        return $.extend({}, params)
    } else {
        return {}
    }
}

window.Tnmc.getUrlFormArgs = function(aOpts) {
    var aOpts = aOpts || {};
    var formJson = window.Tnmc.queryToJson($('form[data-auto-get]').serialize());
    var urlArgs = window.Tnmc.getUrlArgs();
    var options = $.extend(aOpts, formJson || {}, urlArgs);
    delete options['r'];
    return options;
}

window.Tnmc.getUuidNum = function() {
    return (new Date()).getTime();
}
window.Tnmc.getUuid = function() {
    var d = new Date().getTime();
    var uuid = "xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx".replace(/[xy]/g, function(c) {
        var r = (d + Math.random() * 16) % 16 | 0;
        d = Math.floor(d / 16);
        return (c == "x" ? r : (r & 3 | 8)).toString(16)
    });
    return uuid
}
window.Tnmc.getApiUrl = function(path, params) {
    var reURL = /^(https?|ftp|file):\/\/.+$/;
    if (!reURL.test(path)) {
        path = window.Tnmc.api_host + path;
    }
    var params = params || {};
    return window.Tnmc.buildUrl(params, path)
}
/**
 * 生成当前相关地址
 * @param  {[type]} params
 * @param  {[type]} path  
 * @return {[type]}       
 */
window.Tnmc.getUrl = function(params, path) {
    var params = params || {};
    var urlArgs = window.Tnmc.getUrlArgs() || {};
    params = Object.assign(urlArgs, params);
    return window.Tnmc.buildUrl(params, path)
}
/**
 * 把a=1&b=2 格式转成对象{}
 * @param  {[type]} query
 * @return {[type]}      
 */
window.Tnmc.queryToJson = function(query, filterEmpty) {
    if (filterEmpty == undefined) {
        filterEmpty = true;
    }
    if (!query) {
        return {};
    }
    var json = {};
    var query = query.split('&');
    for (var i = 0; i < query.length; i++) {
        var hash = query[i].split("=");
        if (!!hash[0]) {
            hash[0] = decodeURIComponent(hash[0].replace(/\+/g, "%20"));
            json[hash[0]] = decodeURIComponent(hash[1].replace(/\+/g, "%20"))
        }
    }
    return json;
}
/**
 * 生成地址
 * @param  {[type]} params
 * @param  {[type]} path  
 * @return {[type]}       
 */
window.Tnmc.buildUrl = function(params, path) {
    var params = params || {};
    var path = path || window.location.pathname;
    var queryString = [];
    for (var key in params) {
        queryString.push(key + "=" + params[key])
    }
    queryString = queryString.join("&");
    if ($.trim(queryString)) {
        if (path.indexOf('?') > -1) {
            var url = path + "&" + queryString
        } else {
            var url = path + "?" + queryString
        }
    } else {
        var url = path
    }
    var reURL = /^(https?|ftp|file):\/\/.+$/;
    if (!reURL.test(url)) {
        url = window.location.protocol + "//" + window.location.host + url;
    }
    return url
}
/**
 * 生成地址
 * 提供地址，参数，把参数里的加到地址去
 */
window.Tnmc.autoMakeUrl = function(params, url) {
    var url = url || window.location.href;
    var query = url.split('?')[1];
    var path = url.split('?')[0];
    var json = window.Tnmc.queryToJson(query)
    var params = params || {};
    var queryString = [];
    for (var key in params) {
        json[key] = params[key]
    }
    for (var key in json) {
        queryString.push(key + "=" + json[key])
    }
    queryString = queryString.join("&");
    return path + '?' + queryString;
}
/**
 * 跳转地址，支持POST
 * @param  {[type]} href  
 * @param  {[type]} method
 * @param  {[type]} data  
 * @return {[type]}       
 */
window.Tnmc.gotoUrl = function(url, method, data) {
    var method = method || 'get'; //提交方式
    var data = data || {} //只支持对象形式
    if (method.toUpperCase() == 'POST') {
        var temp_form = document.createElement("form");
        temp_form.action = url;
        //如需打开新窗口，form的target属性要设置为'_blank' 
        temp_form.target = "_self";
        temp_form.method = "post";
        temp_form.style.display = "none"; //添加参数 
        for (var item in data) {
            var opt = document.createElement("textarea");
            opt.name = item;
            opt.value = data[item];
            temp_form.appendChild(opt);
        }
        document.body.appendChild(temp_form);
        //提交数据 
        temp_form.submit();
    } else {
        window.location.href = url;
    }
}
window.Tnmc.toDecimal = function(x) {
    var f = parseFloat(x);
    if (isNaN(f)) {
        return '0.00'
    }
    var f = Math.round(x * 100) / 100;
    var s = f.toString();
    var rs = s.indexOf(".");
    if (rs < 0) {
        rs = s.length;
        s += "."
    }
    while (s.length <= rs + 2) {
        s += "0"
    }
    return s
}

/**
 * POST请求快捷方式
 * @param  {[type]}   url     
 * @param  {[type]}   data    
 * @param  {Function} callback
 * @return {[type]}           
 */
window.Tnmc.post = function(url, data, callback) {
    this.ajax(url, 'post', data, callback)
}
window.Tnmc.quickPost = function(url, data, reload) {
    var index = layer.load(0, {
        shade: false
    }); //0代表加载的风格，支持0-2
    if (reload == undefined) {
        reload = true;
    }
    this.ajax(url, 'post', data, function(response) {
        layer.close(index)
        if (response.code === 0||response.code==200) {
            layer.msg(response.message || '操作成功', {
                offset: '40px',
                time: 1500,
                icon: 1
            })
            if (!!reload) {
                window.Tnmc.tableReload();
            }
        } else {
            layer.msg(response.message || '操作失败', {
                offset: '40px',
                time: 1500,
                icon: 2
            })
        }
    })
}
/**
 * get 获取资源
 * @param  {[type]}   url     
 * @param  {[type]}   data    
 * @param  {Function} callback
 * @return {[type]}           
 */
window.Tnmc.get = function(url, data, callback) {
    this.ajax(url, 'get', data, callback)
}
/**
 * 提交表单
 * @param  {[type]} formEle     
 * @param  {[type]} doneCallbcak
 * @return {[type]}             
 */
window.Tnmc.submitForm = function(formEle, submitBefore, doneCallbcak) {
    var url = $(formEle).attr('action');
    var formData = new FormData($(formEle)[0]);
    if (typeof submitBefore == 'function') {
        formData = submitBefore(formData);
    }
    window.Tnmc.formPost(url, formData, function(response) {
        if (response.code !== 0) {
            layer.msg(response.message, {
                offset: '40px',
                icon: 2
            })
        } else {
            layer.msg(response.message, {
                offset: '40px',
                icon: 1
            })
            if ($(formEle).data('reload')) {
                window.Tnmc.tableReload();
            }
        }
    })
}
/**
 * 表单数据POST提交
 * @param  {[type]} url         
 * @param  {[type]} data        
 * @param  {[type]} doneCallbcak
 * @return {[type]}             
 */
window.Tnmc.formPost = function(url, data, doneCallbcak) {
    this.formajax(url, 'post', data, doneCallbcak)
}
window.Tnmc.formajax = function(url, method, data, callback) {
    var url = window.Tnmc.buildUrl({}, url)
    $.ajax({
        url: url,
        type: method || 'POST',
        dataType: 'JSON',
        data: data,
        async: false,
        cache: false,
        contentType: false,
        processData: false,
        success: function(response) {
            if (!!window.Tnmc.loadIndex) {
                layer.close(window.Tnmc.loadIndex)
            }
            if (typeof callback == 'function') {
                callback(response)
            }
        },
        error: function(response) {
            window.Tnmc.debugError(response.responseText, response.code)
            if (!!window.Tnmc.loadIndex) {
                layer.close(window.Tnmc.loadIndex)
            }
        }
    })
}
window.Tnmc.ajax = function(url, method, data, callback) {
    var data = data || {};

    if (!!window.Tnmc.debug) {
        // console.log(window.Tnmc.buildUrl(data, url))
    }
    console.log('oo')
    $.ajax({
        url: url,
        type: method || 'POST',
        dataType: 'JSON',
        data: data,
        success: function(response) {
            // console.log(response)
            if (!!window.Tnmc.loadIndex) {
                layer.close(window.Tnmc.loadIndex)
            }
            if (typeof callback == 'function') {
                callback(response)
            }
        },
        error: function(response,res) {
            window.Tnmc.debugError(response.responseText, response.code)
            if (!!window.Tnmc.loadIndex) {
                layer.close(window.Tnmc.loadIndex)
            }
        }
    })
}
/**
 * 打印错误信息
 * @param  {[type]} content
 * @param {
     [type]
 }
 status
 * @return {[type]}        
 */
window.Tnmc.debugError = function(content, status) {
    if (!!window.Tnmc && !!window.Tnmc.debug) {
        layer.open({
            type: 1,
            title: '错误信息,状态码:' + status,
            content: content,
            shadeClose: true,
            shade: false,
            area: ['100%', '100%']
        })
    }else{
        layer.open({
            type: 1,
            title: '错误信息,状态码:' + status,
            content: '<div style="padding:20px;">'+content+'</div>',
            shadeClose: true,
            shade: false,
            area: ['800px', '600px']
        })
    }
}




window.Tnmc.formSubmitEventMonitor = function($eventTarget) {
    window.Tnmc.loadIndex = layer.load(0, {
        shade: false
    }); //0代表加载的风格，支持0-2
    var $submitBtn = $eventTarget.find('[type="submit"]');
    var url = $eventTarget.attr('action');
    var formData = new FormData($eventTarget[0]);
    $submitBtn.prop('disabled', true).addClass('loading')
    var timeout = 1000;
    if ($eventTarget.attr('timeout') != undefined) {
        timeout = parseInt($eventTarget.attr('timeout'));
    }

    window.Tnmc.formPost(url, formData, function(response) {
        setTimeout(function() {
            $submitBtn.prop('disabled', false).removeClass('loading')
        }, 1000)
        var index = 0;
        if (!!window.name) {
            index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
        }
        if (response.code !== 0) {
            if (!!index) {
                parent.layer.msg(response.message, {
                    offset: '40px',
                    icon: 2
                })
            } else {
                layer.msg(response.message, {
                    offset: '40px',
                    icon: 2
                })
            }
        } else {

            if (!index) {
                layer.msg(response.message, {
                    offset: '40px',
                    icon: 1
                })
                if ($eventTarget.data('reload')) {
                    window.Tnmc.tableReload();
                }
                if (!!$eventTarget.attr('data-next-url')) {
                    setTimeout(function() {
                        window.location.href = $eventTarget.data('next-url');
                    }, timeout)
                } else if (!!response.data && !!response.data.goto_url) {
                    setTimeout(function() {
                        window.location.href = response.data.goto_url;
                    }, timeout)
                }
            } else {
                if (!!response.data && !!response.data.goto_url) {
                    layer.msg(response.message, {
                        offset: '40px',
                        icon: 1
                    })
                    setTimeout(function() {
                        window.location.href = response.data.goto_url;
                    }, 0)
                } else {
                    parent.layer.msg(response.message, {
                        offset: '40px',
                        icon: 1
                    })
                    if ($eventTarget.data('reload')) {
                        parent.Tnmc.tableReload();
                        parent.layer.close(index); //再执行关闭
                    } else {
                        parent.layer.close(index); //再执行关闭
                    }
                }

            }
        }
    })
}
window.Tnmc.exportEventMonitor = function() {
    $('[data-export]').click(function() {

        $form = $('<form></form>');
        $form.css({
            'display':'none'
        })
        var url = $(this).data('url');
        var params = window.Tnmc.getUrlFormArgs();

        $('body').append($form);
        $form.attr('method','POST');
        $form.attr('action', url );
        $form.attr('target', '_blank' );
        for(var name in params){
            $input = $('<input name="'+name+'" value="'+params[name]+'" /> ')
            $form.append($input);
        }
        $form.submit()
        console.log(params)
    })
}
var autoRender = function($ele,options){
    this.laytpl = null;
    this.options = options;
    this.$ele = $ele;
    this.viewId = window.Tnmc.getUuid();
    this.eleId = $ele.attr('id');
    if( !this.eleId )
    {
       this.eleId =  window.Tnmc.getUuid();
       $ele.attr('id',this.eleId);
    }
    this.getTpl = document.getElementById(this.eleId).innerHTML;
    if( !!$ele.data('target') ){
      this.view =  document.getElementById($ele.data('target'));
    }else{
     if( $('#'+this.viewId).length==0 ){
       $('#'+this.eleId).parent().append( $('<div id="'+this.viewId+'"></div>') )
     }
      this.view =  document.getElementById(this.viewId);
    }
    var that = this;
    layui.use(['laytpl'],function(){
        that.laytpl = layui.laytpl;
        that.render();
    })
}
autoRender.prototype.setOpts = function(options){
    this.options = options;
    return this;
}
autoRender.prototype.render=function(){
   var that = this;
   var url = this.$ele.data('url');

   window.Tnmc.get(url,this.options,function(response){
    console.log(response)
        var html = template(that.eleId, {
            res:response
        });
        $('#'+that.viewId).html(html);
       // that.laytpl(that.getTpl).render(response, function(html){
       //   $('#'+that.viewId).html(html);
       // });
   })
   
}
$(function() {
    /**
     * 表单自动提交
     * @param  
     * @return 
     */
    $(document).on('submit', 'form[data-auto-submit]', function(event) {
        var $eventTarget = $(event.currentTarget);
        window.Tnmc.formSubmitEventMonitor($eventTarget);
        return false;
    })
    layui.use(['laytpl'],function(){
        $('[data-auto-render]').each(function() {
            new autoRender( layui.laytpl,$(this) )
        })
    })
   

    window.Tnmc.confirmEventMonitor();
    window.Tnmc.modalEventMonitor();
    window.Tnmc.modalToSelectItemEventMonitor();
    window.Tnmc.getMobileCodeEvent();
    window.Tnmc.autoPostEventMonitor();
    window.Tnmc.editor();
    window.Tnmc.tableCheckboxChangeMonitor();
    window.Tnmc.exportEventMonitor();
})

window.Tnmc.confirmQueues = [];
window.Tnmc.confirmAfter = function(callback) {
    if (typeof callback == 'function') {
        window.Tnmc.confirmQueues.push(callback)
    }
}
window.Tnmc.tableCheckboxChangeMonitor = function() {
    $('[data-batch]').addClass('layui-btn-disabled').attr('disabled', true);
    $('[data-single]').addClass('layui-btn-disabled').attr('disabled', true);

    layui.use(['table'], function() {
        window.Tnmc.gg_table = layui.table; 
        var table = layui.table;
        table.on('checkbox(main)', function(obj) {
            window.Tnmc.tableCheckboxTableRowCount()
        });
    })
}
window.Tnmc.tableCheckboxTableRowCount=function(){
    var table=window.Tnmc.gg_table;
    var checkStatus = table.checkStatus('main-table');
    var count = checkStatus.data.length
    if (checkStatus.data.length > 0) {
        $('[data-batch]').removeClass('layui-btn-disabled').removeAttr('disabled')
    } else {
        $('[data-batch]').addClass('layui-btn-disabled').attr('disabled', true);
    }
    if (checkStatus.data.length == 1) {
        $('[data-single]').removeClass('layui-btn-disabled').removeAttr('disabled')
    } else {
        $('[data-single]').addClass('layui-btn-disabled').attr('disabled', true);
    }
    if ($('.layui-toolbox .checkbox-table-row-count').length == 0) {
        $('.layui-toolbox').append('<span class="checkbox-table-row-count"></span>');
    }
    if (count == 0) {
        $('.layui-toolbox .checkbox-table-row-count').hide();
    } else {
        $('.layui-toolbox .checkbox-table-row-count').text('已选中: ' + count + '条').show();
    }
}
window.Tnmc.confirmEventMonitor = function() {
    layui.use(['table'], function() {
        var table = layui.table
        $('body').delegate('[data-confirm]', 'click', function() {
            // 字段
            var field = $(this).data('field') || false;
            // 批量标识
            var batch = $(this).data('batch') || false;

            var msg = $(this).data('confirm') || '你确定执行该操作吗？';

            var url = $(this).data('url');

            var reload = $(this).data('unreload') != undefined ? false : true;

            var params = $(this).data('params');

            if (!!params) {
                params = window.Tnmc.queryToJson(params);
            } else {
                params = {};
            }
            //批量操作
            if (!!batch) {
                var checkStatus = table.checkStatus($(this).data('target') || 'main-table');
                if (field && checkStatus && checkStatus.data.length > 0) {
                    var ids = [];
                    for (var i in checkStatus.data) {
                        ids.push(checkStatus.data[i][field]);
                    }
                    params[field] = ids;
                }
            } else {
                if (!!field && $(this).data('value')) {
                    params[field] = $(this).data('value');
                }
            }
            var $this = $(this)
            layer.confirm(msg, function(index) {
                window.Tnmc.loadIndex = layer.load(0, {
                    shade: false
                }); //0代表加载的风格，支持0-2
                window.Tnmc.ajax(url, 'post', params, function(response) {
                    layer.close(window.Tnmc.loadIndex)
                    if (response.code === 0||response.code==200) {
                        layer.msg(response.message || '操作成功', {
                            offset: '40px',
                            time: 4000,
                            icon: 1
                        })


                        if (!!reload) {
                            window.Tnmc.tableReload();
                        }
                        if (window.Tnmc.confirmQueues.length > 0) {
                            for (var i in window.Tnmc.confirmQueues) {
                                window.Tnmc.confirmQueues[i]();
                            }
                        }
                    } else {
                        layer.msg(response.message || '操作失败', {
                            offset: '40px',
                            time: 4000,
                            // icon: 2
                        })
                    }

                    if( !!response.data.goto_url )
                    {
                        var opts = {
                            type:2,
                            content:response.data.goto_url,
                            area:['800px','80%']
                        }
                        if ($this.data('next-width') && $this.data('next-height')) {
                            opts.area = [$this.data('next-width'), $this.data('next-height')]
                        }

                        opts.title="操作结果";
                        layer.open(opts);
                    }
                })
            })
        })
    })
}

window.Tnmc.downloadEventMonitor = function() {
    layui.use(['table'], function() {
        var table = layui.table
        $('body').delegate('[data-download]', 'click', function() {
            // 字段
            var field = $(this).data('field') || false;
            // 批量标识
            var batch = $(this).data('batch') || false;

            var msg = $(this).data('confirm') || '你确定执行该操作吗？';

            var url = $(this).data('url');


            var params = $(this).data('params');

            if (!!params) {
                params = window.Tnmc.queryToJson(params);
            } else {
                params = {};
            }
            //批量操作
            if (!!batch) {
                var checkStatus = table.checkStatus($(this).data('target') || 'main-table');
                if (field && checkStatus && checkStatus.data.length > 0) {
                    var ids = [];
                    for (var i in checkStatus.data) {
                        ids.push(checkStatus.data[i][field]);
                    }
                    params[field] = ids;
                }
            } else {
                if (!!field && $(this).data('value')) {
                    params[field] = $(this).data('value');
                }
            }
            layer.confirm(msg, function(index) {
                var index = layer.load(0, {
                    shade: false
                }); //0代表加载的风格，支持0-2
                window.Tnmc.ajax(url, 'post', params, function(response) {
                    layer.close(index)
                    if (response.code === 0||response.code==200) {
                        layer.msg(response.message || '操作成功', {
                            offset: '40px',
                            time: 8000,
                            // icon: 1
                        })
                        window.location.href = response.data.goto_url
                    } else {
                        layer.msg(response.message || '操作失败', {
                            offset: '40px',
                            time: 8000,
                            // icon: 2
                        })
                    }
                })
            })
        })
    })
}
window.Tnmc.newlinkEventMonitor = function() {
    layui.use(['table'], function() {
        var table = layui.table
        $('body').delegate('[data-new-link]', 'click', function() {
            // 字段
            var field = $(this).data('field') || false;
            // 批量标识
            var batch = $(this).data('batch') || false;

            var msg = $(this).data('confirm') || '你确定执行该操作吗？';

            var url = $(this).data('url');


            var params = $(this).data('params');

            if (!!params) {
                params = window.Tnmc.queryToJson(params);
            } else {
                params = {};
            }

            //批量操作
            if (!!batch) {
                var checkStatus = table.checkStatus($(this).data('target') || 'main-table');
                if (field && checkStatus && checkStatus.data.length > 0) {
                    var ids = [];
                    for (var i in checkStatus.data) {
                        ids.push(checkStatus.data[i][field]);
                    }
                    params[field] = ids;
                }
            } else {
                if (!!field && $(this).data('value')) {
                    params[field] = $(this).data('value');
                }
            }
            url = window.Tnmc.buildUrl(params, url);
            var a = $("<a href='" + url + "' target='_blank'></a>").get(0);
            var e = document.createEvent('MouseEvents');
            e.initEvent('click', true, true);
            a.dispatchEvent(e);
        })
    })
}
window.Tnmc.autoPostEventMonitor = function() {
    $('body').delegate('[data-auto-post]', 'click', function() {
        var url = $(this).data('url');
        var params = $(this).data('params');
        if (!!params) {
            params = window.Tnmc.queryToJson(params);
        } else {
            params = {};
        }

        var id = $(this).data('id');
        if (!!id) {
            params.id = id;
        }
        window.Tnmc.quickPost(url, params);
    })
}
window.Tnmc.modalEventConfirmQues = [];
window.Tnmc.modalEventConfirmPushQues = function(callback) {
    if (typeof callback == 'function') {
        window.Tnmc.modalEventConfirmQues.push(callback);
    }
}
window.Tnmc.modalEventMonitorInit_endFuncQueues = [];
window.Tnmc.modalEventMonitorInit = function(options) {
    window.Tnmc.$layerContent = null;
    var default_ = {
        type: 2,
        area: ['600px', '400px'],
        title: '',
        content: '',
        not_footer: false,
        submit_val: false
    }
    options = $.extend({}, default_, options);
    options.success = function(layerContent, index) {
        window.Tnmc.$layerContent = layer.getChildFrame('body', index);
    }
    if (!options.not_footer) {
        options.btn = ['确定', '取消']
        options.end = function() {
            var endFuncQueues = window.Tnmc.modalEventMonitorInit_endFuncQueues;
            if (endFuncQueues.length > 0) {
                for (var i in endFuncQueues) {
                    if (typeof endFuncQueues[i] == 'function') {
                        endFuncQueues[i]();
                    }
                }
            }
        }
        options.yes = function(index, layerContent) {
            $form = window.Tnmc.$layerContent.find('form[data-auto-submit]');
            $formCall = window.Tnmc.$layerContent.find('form[data-call]');
            if ($form.length > 0) {

                if (!!options.submit_val) {
                    $form.submit();
                    return;
                }
                window.Tnmc.loadIndex = layer.load(0, {
                    shade: false
                }); //0代表加载的风格，支持0-2
                if (window.Tnmc.valiForm($form)) {
                    $(layerContent).find('.layui-layer-btn0').prop('disabled', true).addClass('loading')
                    var url = $form.attr('action');
                    var formData = new FormData($form[0]);
                    window.Tnmc.formPost(url, formData, function(response) {
                        setTimeout(function() {
                            $(layerContent).find('.layui-layer-btn0').prop('disabled', false).removeClass('loading')
                        }, 1000)
                        if (response.code !== 0) {
                            layer.msg(response.message, {
                                offset: '40px',
                                time: 1500,
                                icon: 2
                            })
                        } else {
                            layer.close(index)
                            if ($form.data('reload')) {
                                window.Tnmc.tableReload();
                            }
                            layer.msg(response.message, {
                                offset: '40px',
                                icon: 1
                            })
                        }
                    })
                } else {
                    layer.close(window.Tnmc.loadIndex)
                }
            } else if ($formCall.length > 0) {
                layer.close(index)
                if (window.Tnmc.modalEventConfirmQues.length > 0) {
                    for (var i in window.Tnmc.modalEventConfirmQues) {
                        window.Tnmc.modalEventConfirmQues[i]();
                    }
                }
            } else {

                layer.close(index)
                window.Tnmc.tableReload();
            }


        }
    }
    layer.open(options)
}
window.Tnmc.modalEventMonitor = function() {
    window.Tnmc.$layerContent = null;
    layui.use(['table'], function() {
        $('body').delegate('[data-modal]', 'click', function() {
            var default_ = {
                type: 2,
                area: ['600px', '400px']
            }
            var title = $(this).text();
            var url = $(this).data('url');
            var config = $(this).data('config') || {};
            if (!config.area && $(this).data('width') && $(this).data('height')) {
                config.area = [$(this).data('width'), $(this).data('height')]
            }
            config = $.extend({}, default_, config);
            if (!config.title) {
                config.title = title || ($(this).data('title') || '&nbsp;');
            }
            // 使用再多操作的场景
            var batch = $(this).data('batch') || $(this).data('single') ? true : false;

            if (!!batch) {
                var checkStatus = layui.table.checkStatus($(this).data('target') || 'main-table');
                var field = $(this).data('field') || false;
                var fieldname = $(this).data('fieldname') || field;
                var params = {};
                if (field && checkStatus && checkStatus.data.length > 0) {
                    var ids = [];
                    for (var i in checkStatus.data) {
                        ids.push(checkStatus.data[i][field]);
                    }
                    params[fieldname] = ids.join('--')
                    url = window.Tnmc.autoMakeUrl(params, url)
                }
            }
            // 结束
            var options = {
                type: config.type,
                title: config.title,
                content: url,
                area: config.area,
                submit_val: $(this).data('submit') == undefined ? false : true,
                not_footer: $(this).data('not-footer') !== undefined ? true : false
            }
            window.Tnmc.modalEventMonitorInit(options)
        })
    })
}


window.Tnmc.openModal = function(opts) {
    var $layerContent_dom_ = null;
    var opts = $.extend({
        'title': '',
        'area': ['600px', '600px'],
        'url': '',
        'is_form': true,
        'responseCall': false
    }, opts);
    var options = {
        type: 2,
        title: opts.title,
        content: opts.url,
        area: opts.area,
    }
    options.btn = ['确定', '取消'];
    options.success = function(layerContent, index_) {
        $layerContent_dom_ = layer.getChildFrame('body', index_);
    }
    options.yes = function(index, layerContent) {
        $form = $layerContent_dom_.find('form');

        window.Tnmc.loadIndex = layer.load(0, {
            shade: false
        }); //0代表加载的风格，支持0-2
        $(layerContent).find('.layui-layer-btn0').prop('disabled', true).addClass('loading')
        var url = $form.attr('action');
        var formData = new FormData($form[0]);
        window.Tnmc.formPost(url, formData, function(response) {
            setTimeout(function() {
                $(layerContent).find('.layui-layer-btn0').prop('disabled', false).removeClass('loading')
            }, 1000)
            if (response.code !== 0) {
                layer.msg(response.message, {
                    offset: '40px',
                    time: 1500,
                    icon: 2
                })
            } else {
                layer.close(index)
                layer.msg(response.message, {
                    offset: '40px',
                    icon: 1
                })
                if (!!opts.responseCall && typeof opts.responseCall == 'function') {
                    opts.responseCall(response);
                }
            }
        })
    }
    layer.open(options)
}
window.Tnmc.modalToSelectItemEventMonitor = function() {
    window.Tnmc.$layerContent = null;
    layui.use(['table'], function() {
        $('body').delegate('[data-modal-select]', 'click', function() {
            var default_ = {
                type: 2,
                area: ['90%', '90%']
            }
            var title = $(this).text();
            var url = $(this).data('url');
            var config = {}
            if ($(this).data('width') && $(this).data('height')) {
                config.area = [$(this).data('width'), $(this).data('height')]
            }
            config = $.extend({}, default_, config);
            if (!config.title) {
                config.title = title || ($(this).data('title') || '&nbsp;');
            }

            $eventTarget = $(this)

            var eventConfig = $(this).data('config') || '';
            eventConfig = window.Tnmc.queryToJson(eventConfig)



            // 结束

            var options = {
                type: config.type,
                title: config.title,
                content: url,
                area: config.area,
                success: function(layerContent, index) {
                    $childBody = layer.getChildFrame('body', index);
                    $childBody.delegate('[data-select-target]', 'click', function() {
                        var dconfig = $(this).data('config');
                        dconfig = window.Tnmc.queryToJson(dconfig)
                        for (var name in dconfig) {
                            input_name = name;
                            if (!!eventConfig[name]) {
                                input_name = eventConfig[name];
                            }
                            // 优先选择
                            var $ele = $(input_name);
                            // 可能有图片
                            if ($ele.length > 0) {
                                var tagName = $ele[0].tagName.toLowerCase();
                                if (tagName == 'img') {
                                    $ele.attr('src', dconfig[name]);
                                } else {
                                    $ele.val(dconfig[name])
                                }
                            } else {
                                if ($('input[name="' + input_name + '"]').length == 0) {
                                    var $value_input = $('<input type="hidden" name="' + input_name + '" value="" />');
                                    $eventTarget.after($value_input)
                                }
                                $('input[name="' + input_name + '"]').val(dconfig[name])
                            }
                        }
                        layer.close(index)
                    })

                }
            }

            layer.open(options)
        })
    })
}


window.Tnmc.valiForm = function($form) {
    return true;
}
window.Tnmc.getMobileCodeEvent = function(url) {
    $('body').delegate('[data-get-mobile-code]', 'click', function() {
        if ($(this).prop("disabled")) {
            return false
        }
        var $this = $(this);
        var url = $(this).data('url') || '/api/sms/post_sendmobile_code';
        var params = $(this).data('params');


        if (!!params) {
            params = window.Tnmc.queryToJson(params);
        } else {
            params = {};
        }

        var defaultText = $this.text();
        $form = $(this).parents("form");
        params.mobile = $form.find('input[name="mobile"]').val();

        $(this).prop("disabled", true).addClass("loading");
        window.Tnmc.post(url, params, function(response) {
            $this.removeClass("loading");
            if (response.code === 0||response.code==200) {
                var html = '<span class="wait" >60</span>秒后重发';
                $this.html(html);
                var interval = setInterval(function() {
                    var time = parseInt($this.find(".wait").text());
                    $this.find(".wait").text(--time);
                    if (time <= 0) {
                        $this.prop("disabled", false).text(defaultText);
                        clearInterval(interval)
                    }
                }, 1000);
            } else {
                $this.prop("disabled", false).removeClass('loading').text(defaultText);
                layer.msg(response.message)
            }
        })
    })
}


window.Tnmc.tableIns = null;
window.Tnmc.tableReload = function($form) {
    if (!$form) {
        $form = $('form');
    }
    var url = $form.attr('action');
    var formJson = window.Tnmc.queryToJson($form.serialize());
    var urlArgs = window.Tnmc.getUrlArgs();
    var options = $.extend({}, formJson, urlArgs)
    delete options['r'];
    if (!!window.Tnmc.tableIns) {
        window.Tnmc.tableIns.reload({
            where: options
        })
    }else if (typeof window.Tnmc.customReload == 'function') {
        window.Tnmc.customReload()
    } else {
        setTimeout(function() {
            window.location.reload();
        }, 1000)
    }
    window.Tnmc.tableCheckboxChangeMonitor();
}
window.Tnmc.afterFormSubmit=function(){

}

$(function() {
    $(document).on('submit', '[data-auto-get]', function(event) {
        var $form = $(event.currentTarget);
        var url = $form.attr('action');
        var formJson = window.Tnmc.queryToJson($form.serialize());
        var urlArgs = window.Tnmc.getUrlArgs();
        var options = $.extend({}, formJson, urlArgs)
        delete options['r'];
        if (!!window.Tnmc.tableIns) {
            window.Tnmc.tableIns.reload({
                where: options,
                page: {
                    curr: 1 //重新从第 1 页开始
                }
            })
        }
        window.Tnmc.afterFormSubmit();
        return false;
    })
})
window.Tnmc.uploadHandler = function(opts) {
    window.Tnmc.uploadHandlerOpts = {};
    var opts = $.extend({
        url: '',
        ele: '',
        data: {},
        reload: false,
        success: null
    }, opts || {})
    var _this = this;
    window.Tnmc.uploadHandlerOpts.$progress = null;
    var content = '<div class="layui-progress" style="margin-top:50px;border-radius:0px;" lay-showpercent="true"><div class="layui-progress-bar"  style="width: 0%;border-radius:0px;"><span class="layui-progress-text">0%</span></div></div>'
    window.Tnmc.uploadHandlerOpts.upload_layer_index = 0
    window.Tnmc.uploadHandlerOpts.$upload_target = null;
    window.Tnmc.uploadHandlerOpts.upload_count = 0;
    $(opts.ele).fileupload({
        url: opts.url,
        dataType: 'json',
        formData: opts.data,
        progressall: function(e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            window.Tnmc.uploadHandlerOpts.$progress.find('.layui-progress-bar').css({
                width: progress + '%'
            })
            window.Tnmc.uploadHandlerOpts.$progress.find('.layui-progress-text').html(progress + '%');

        },
        start: function(e, data) {
            window.Tnmc.uploadHandlerOpts.$upload_target = $(e.target);
            layer.open({
                type: 1,
                title: '上传进度',
                content: content,
                shadeClose: false,
                closeBtn: false,
                shade: true,
                id: "upload_loading_modal",
                area: ['500px', '200px'],
                btn: [],
                move: false,
                success: function(ele, index) {
                    window.Tnmc.uploadHandlerOpts.upload_layer_index = index
                    window.Tnmc.uploadHandlerOpts.$progress = $(ele).find('.layui-progress')
                }
            })
        },
        done: function(e, data) {
            window.Tnmc.uploadHandlerOpts.upload_count++;
            if (data.originalFiles.length == window.Tnmc.uploadHandlerOpts.upload_count) {
                if (!!opts.reload) {
                    window.Tnmc.tableReload();
                }
                if (typeof opts.success == 'function') {
                    opts.success()
                }
            }
            layer.close(window.Tnmc.uploadHandlerOpts.upload_layer_index)
        },
        success: function(response) {
            if (response.code == 0) {
                layer.msg('上传成功');
            } else {
                layer.msg(response.message)
            }



        }
    }).prop('disabled', !$.support.fileInput).parent().addClass($.support.fileInput ? undefined : 'disabled');
}


window.Tnmc.editor = function() {
    // 注册监听 contenteditable 可编辑属性的内容变化
    $('body').on('focus', '[contenteditable]', function() {
        var $this = $(this);
        $this.data('before', $this.html());
    }).on('blur keyup paste input', '[contenteditable]', function() {
        var $this = $(this);
        if ($this.data('before') !== $this.html()) {
            $this.data('before', $this.html());
            $this.trigger('change');
        }
    });
    // 根据该属性，替换成可编辑的div
    $('[data-tnmc-editor]').each(function() {
        var name = $(this).attr('name');
        var val = $(this).val();
        $editor = $('<div class="tnmc-editor" style="position:relative;margin-top:40px;">')
        $content = $('<div contenteditable="true" style="min-height:200px;border:1px solid #e6e6e6;" name="' + name + '">');
        $toolbox = $('<div class="tnmc-toobox" style="position: absolute;top: -31px;width: 100%;height: 26px;">');

        $linkGroup = $('<div class="layui-btn-group"><a class="layui-btn layui-btn-sm" href="http://www.135editor.com/beautify_editor.html" target="_blank">135编辑器</a> <a class="layui-btn layui-btn-sm" href="http://www.365editor.com/?f=b" target="_blank">365编辑器</a> <a class="layui-btn layui-btn-sm" href="http://www.wxeditor.com" target="_blank">易点编辑器</a> </div>');
        $copyBtn = $('<a class="layui-btn layui-btn-sm" href="javascript:;" style="float:right;">清空</a>')
        $toolbox.append($linkGroup);
        $toolbox.append($copyBtn);
        $editor.append($content);
        $editor.append($toolbox);
        $content.html(val);
        $(this).after($editor);
        $(this).hide();
        $copyBtn.click(function(e) {
            $content.html('');
        })
    })
    // 内容变化，回写给textarea
    $('[contenteditable="true"]').change(function() {
        if (!$(this).attr('name')) {
            return;
        }
        $('textarea[name="' + $(this).attr('name') + '"]').val($(this).html());
    })
}

window.Tnmc.getTableHeight = function(eles) {
    if (eles == undefined) {
        var eles = [
            '.layui-form',
            '.layui-toolbox',
            '.layadmin-header',
        ];
    }
    if ($('.duty-container').length > 0) {
        var h = $('.duty-container').outerHeight();
    } else {
        var h = $(window).outerHeight();
    }
    for (var i in eles) {
        if ($(eles[i]).length > 0) {
            h = h - parseInt($(eles[i]).outerHeight())
        }
    }

    return h-15;
}
window.Tnmc.autoBuildFromUrl = function() {
    $('a[data-auto-fromurl]').each(function() {
        if ($(this).attr('data-href') == undefined) {
            $(this).attr('data-href', $(this).attr('href'));
        }
        var href = $(this).attr('data-href');
        var options = window.Tnmc.getUrlFormArgs();
        var from_url = encodeURIComponent(window.Tnmc.buildUrl(options, window.location.pathname))
        if (href.indexOf('?') > -1) {
            href = href + '&from_url=' + from_url
        } else {
            href = href + '?from_url=' + from_url
        }
        $(this).attr('href', href);
    })
    $('a[data-from_url]').each(function() {
        var from_url = window.Tnmc.getUrlArgs()['from_url']
        if (!!from_url) {
            $(this).attr('href', from_url)
        }
    })
}
window.Tnmc.viewSingleImage = function() {
    $('body').delegate('[data-image-view]', 'click', function() {
        var imageUrl = $(this).attr('data-url');
        layer.photos({
            anim: 5,
            photos: {
                title: '',
                id: '',
                data: [{
                    src: imageUrl
                }]
            }
        });
    })
}
window.Tnmc.textareaLimitMax = function() {
    var setTextLimitMaxStyle = function($textEle) {
        if ($textEle.attr('limit-tip') == undefined) {
            $textEle.attr('limit-tip', '1');
            $textEle.after($('<div limit-tip-ele style="position:absolute;right:0;bottom:-16px;"></div>'))
        }
        var $tipEle = $textEle.parent().find('[limit-tip-ele]')
        if ($tipEle.length > 0) {
            var content = $textEle.val();
            $tipEle.html(content.length + '/' + $textEle.attr('limit-max'))
        }
    }
    $('[limit-max]').each(function() {

        setTextLimitMaxStyle($(this))
    })
    $('[limit-max]').keyup(function() {
        setTextLimitMaxStyle($(this))
    })
}
$(function() {
    window.Tnmc.downloadEventMonitor();
    window.Tnmc.newlinkEventMonitor();
    window.Tnmc.textareaLimitMax();
    window.Tnmc.autoBuildFromUrl();
    window.Tnmc.viewSingleImage();
    window.Tnmc.autoJsonFormat();
})

window.Tnmc.autoJsonFormat=function(){
    $('[data-json-html]').each(function(){
        $(this).html( window.Tnmc.jsonFormat( $(this).text() ) )
    })
}

/**
 * json格式化便于美观在html页面输出
 * @param json
 * @param options
 * @returns {string}
 */
window.Tnmc.jsonFormat=function(json, options) {

    var reg = null,
        formatted = '',
        pad = 0,
        PADDING = '    '; // one can also use '\t' or a different number of spaces

    // optional settings
    options = options || {};
    // remove newline where '{' or '[' follows ':'
    options.newlineAfterColonIfBeforeBraceOrBracket = (options.newlineAfterColonIfBeforeBraceOrBracket === true) ? true : false;
    // use a space after a colon
    options.spaceAfterColon = (options.spaceAfterColon === false) ? false : true;

    // begin formatting...
    if (typeof json !== 'string') {
        // make sure we start with the JSON as a string
        json = JSON.stringify(json);
    } else {
        // is already a string, so parse and re-stringify in order to remove extra whitespace
        json = JSON.parse(json);
        json = JSON.stringify(json);
    }

    // add newline before and after curly braces
    reg = /([\{\}])/g;
    json = json.replace(reg, '\r\n$1\r\n');

    // add newline before and after square brackets
    reg = /([\[\]])/g;
    json = json.replace(reg, '\r\n$1\r\n');

    // add newline after comma
    reg = /(\,)/g;
    json = json.replace(reg, '$1\r\n');

    // remove multiple newlines
    reg = /(\r\n\r\n)/g;
    json = json.replace(reg, '\r\n');

    // remove newlines before commas
    reg = /\r\n\,/g;
    json = json.replace(reg, ',');

    // optional formatting...
    if (!options.newlineAfterColonIfBeforeBraceOrBracket) {
        reg = /\:\r\n\{/g;
        json = json.replace(reg, ':{');
        reg = /\:\r\n\[/g;
        json = json.replace(reg, ':[');
    }
    if (options.spaceAfterColon) {
        reg = /\:/g;
        json = json.replace(reg, ': ');
    }

    $.each(json.split('\r\n'), function(index, node) {
        var i = 0,
            indent = 0,
            padding = '';

        if (node.match(/\{$/) || node.match(/\[$/)) {
            indent = 1;
        } else if (node.match(/\}/) || node.match(/\]/)) {
            if (pad !== 0) {
                pad -= 1;
            }
        } else {
            indent = 0;
        }

        for (i = 0; i < pad; i++) {
            padding += PADDING;
        }

        formatted += padding + node + '\r\n';
        pad += indent;
    });

    return formatted;
}

$(function(){
    $dutyContainer = $('.duty-container');
    $dutyContainer.css({
        marginTop:$('.nav_menu').outerHeight(),
        height:$(window).outerHeight() - $('.nav_menu').outerHeight(),
        overflowY: 'auto'
    });
})



var execRowspan = function(fieldName,index,flag){
        // 1为不冻结的情况，左侧列为冻结的情况
        let fixedNode = index=="1"?$(".layui-table-body")[index - 1]:(index=="3"?$(".layui-table-fixed-r"):$(".layui-table-fixed-l"));
        // 左侧导航栏不冻结的情况
        let child = $(fixedNode).find("td");
        let childFilterArr = [];
        // 获取data-field属性为fieldName的td
        for(let i = 0; i < child.length; i++){
            if(child[i].getAttribute("data-field") == fieldName){
                childFilterArr.push(child[i]);
            }
        }
        // 获取td的个数和种类
        let childFilterTextObj = {};
        for(let i = 0; i < childFilterArr.length; i++){
            let childText = flag?childFilterArr[i].innerHTML:childFilterArr[i].textContent;
            if(childFilterTextObj[childText] == undefined){
                childFilterTextObj[childText] = 1;
            }else{
                let num = childFilterTextObj[childText];
                childFilterTextObj[childText] = num*1 + 1;
            }
        }
        let canRowspan = true;
        let maxNum;//以前列单元格为基础获取的最大合并数
        let finalNextIndex;//获取其下第一个不合并单元格的index
        let finalNextKey;//获取其下第一个不合并单元格的值
        for(let i = 0; i < childFilterArr.length; i++){
            (maxNum>9000||!maxNum)&&(maxNum = $(childFilterArr[i]).prev().attr("rowspan")&&fieldName!="8"?$(childFilterArr[i]).prev().attr("rowspan"):9999);
            let key = flag?childFilterArr[i].innerHTML:childFilterArr[i].textContent;//获取下一个单元格的值
            let nextIndex = i+1;
            let tdNum = childFilterTextObj[key];
            let curNum = maxNum<tdNum?maxNum:tdNum;
            if(canRowspan){
                for(let j =1;j<=curNum&&(i+j<childFilterArr.length);){//循环获取最终合并数及finalNext的index和key
                    finalNextKey = flag?childFilterArr[i+j].innerHTML:childFilterArr[i+j].textContent;
                    finalNextIndex = i+j;
                    if((key!=finalNextKey&&curNum>1)||maxNum == j){
                        canRowspan = true;
                        curNum = j;
                        break;
                    }
                    j++;
                    if((i+j)==childFilterArr.length){
                        finalNextKey=undefined;
                        finalNextIndex=i+j;
                        break;
                    }
                }
                childFilterArr[i].setAttribute("rowspan",curNum);
                if($(childFilterArr[i]).find("div.rowspan").length>0){//设置td内的div.rowspan高度适应合并后的高度
                    $(childFilterArr[i]).find("div.rowspan").parent("div.layui-table-cell").addClass("rowspanParent");
                    $(childFilterArr[i]).find("div.layui-table-cell")[0].style.height= curNum*38-10 +"px";
                }
                canRowspan = false;
            }else{
                childFilterArr[i].style.display = "none";
            }
            if(--childFilterTextObj[key]==0|--maxNum==0|--curNum==0|(finalNextKey!=undefined&&nextIndex==finalNextIndex)){//||(finalNextKey!=undefined&&key!=finalNextKey)
                canRowspan = true;
            }
        }
    }
//合并数据表格行
window.Tnmc.layuiRowspan = function(fieldNameTmp,index,flag){
        let fieldName = [];
        if(typeof fieldNameTmp == "string"){
            fieldName.push(fieldNameTmp);
        }else{
            fieldName = fieldName.concat(fieldNameTmp);
        }
        for(let i = 0;i<fieldName.length;i++){
            execRowspan(fieldName[i],index,flag);
        }
    }


    var gimport =function($ele,opts){
        this.opts = $.extend({},{
            'upload_url':'/index.php?r=import/upload',
            'preview_url':'/index.php?r=import/get-list',
            'remove_url':'/index.php?r=import/post-remove',
            'clear_url':'/index.php?r=import/post-clear',
            'upload':null,
        },opts||{})

       this.$ele = $ele;

       this.tableIns = null;

       this.data = {};

       this.render();

    }
    gimport.prototype.render=function(){
        this.openmodal();
        var that = this;
        $('body').delegate('.import-return-btn','click',function(){
             that.data={};
             that.$uploadcontainer.show();
             that.$tablecontainer.hide();
             that.clear();
        })
    }
    gimport.prototype.openmodal=function(){
        var defaultopts = {
            type: 1,
            area: ['600px', '400px']
        }
        var title = this.$ele.text();
        var url = this.$ele.data('url');
        var config = this.$ele.data('config') || {};
        if (!config.area && this.$ele.data('width') && this.$ele.data('height')) {
            config.area = [this.$ele.data('width'), this.$ele.data('height')]
        }
        config = $.extend({}, defaultopts, config);
        if (!config.title) {
            config.title = title || (this.$ele.data('title') || '&nbsp;');
        }
        // 使用再多操作的场景

        var content = '<div class="layui-upload-drag import-upload-drag" id="import-upload-gg-btn"><i class="layui-icon"></i><p>点击上传，或将文件拖拽到此处</p><div class="layui-hide" id=""><hr></div></div><div class="upload-data-table" id="import-table-gg-container"> <div class="alert alert-warning" style="margin-bottom:0px;">数据预览 <a href="javascript:;" class="import-return-btn" style="margin-left:20px;">返回上传</a></div> <table class="layui-hide table-grid" lay-filter="import-main" id="import-main-table"></table></div>';

        // 结束
        var options = {
            type: config.type,
            title: config.title,
            content: content,
            area: config.area,
        }
        var that = this;
        that.$layerContent = null;

        options.success = function(layerContent, index) {
            that.$layerContent = $(layerContent).find('.layui-layer-content');
            that.$uploadcontainer = $('#import-upload-gg-btn')
            that.$tablecontainer = $('#import-table-gg-container')
            that.$tablecontainer.hide()
            that.opts.upload.render({
                   elem: '#import-upload-gg-btn',
                   url: that.opts.upload_url,
                   accept: 'file',
                   done: function(res){
                       if( res.code==0 ){
                          that.data = res.data
                          layer.msg('文件上传成功');
                           that.renderTable();
                       }else{
                          layer.msg(res.msg);
                       }
                      console.log(res)
                   }
             });
        }
        options.end=function(){
            that.clear();
        }
        options.btn = ['确定导入', '取消']
        options.yes = function(index, layerContent) {
            if(!that.data||!that.data.uuid){
                layer.msg('请上传文件');
                return ;
            }
            window.Tnmc.post(url, {
                uuid:that.data.uuid
            }, function(response) {
                console.log(response);
                setTimeout(function() {
                    $(layerContent).find('.layui-layer-btn0').prop('disabled', false).removeClass('loading')
                }, 1000)
                if (response.code !== 0) {
                    layer.msg(response.msg, {
                        offset: '40px',
                        time: 4000,
                        icon: 2
                    })
                } else {
                    layer.close(index)
                    window.Tnmc.tableReload();
                    layer.msg(response.msg, {
                        offset: '40px',
                        icon: 1
                    })
                }
            })

        }
        layer.open(options)
    }
    /**
     * 渲染表格
     * @return {[type]} [description]
     */
    gimport.prototype.renderTable=function(){
           this.$uploadcontainer.hide();
           this.$tablecontainer.show();
           var that =this;
           var cols = [];
           for(var i in this.data.fields){
              cols.push({
                  field:this.data.fields[i],
                  title:this.data.fields[i]
              })
           }
           this.tableIns=this.opts.table.render({
                elem: '#import-main-table',
                cellMinWidth:100,
                url:this.opts.preview_url,
                cols: [cols],
                page: true,
                limit:20,
                where: {
                    uuid:this.data.uuid
                }
          });

    }
    gimport.prototype.clear=function(){
        if( !this.data.uuid ){
            return ;
        }
        window.Tnmc.post(that.opts.clear_url,{
            uuid:that.data.uuid
        },function(response){
            console.log(response)
        })
    }

    $(function(){
        layui.use(['upload','table'],function(){
            $('body').delegate('[data-import]','click',function(){
                new gimport( $(this), {
                    table:layui.table,
                    upload:layui.upload,
                } );
            })
        })
    })




    var singleUpload=function($ele){
            this.upload_api = '/index.php?r=/purchase/order-monitor-examine/post-upload'
            this.$ele = $ele;
            this.$target =$('input[name="'+$ele.data('name')+'"]')
            if( this.$target.length==0 ){
                this.$target=$('<input type="hidden" name="'+$ele.data('name')+'" value="'+$ele.data('value')+'" >')
                $ele.after(this.$target);
            }
            this.src = $ele.data('src');
            this.value = $ele.data('value');
            this.id = window.Tnmc.getUuid()
            this.$html=$('<div class="layui-upload-drag" id="'+this.id+'" style="padding: 10px 30px;"><p>点击上传，或拖拽到此处</p>'+
                          '<div class="layui-hide layui-img-container" id="view_'+this.id+'"><img src="" alt="" style="max-width: 196px">'+
                          '</div>'+
                        '</div>')   
            $ele.append(this.$html);
            var that = this;
            layui.use(['upload'],function(){
                that.upload= layui.upload;
                that.render();
            })

    }
    singleUpload.prototype.render=function(){
        //拖拽上传
         var that = this;
         this.upload.render({
               elem: '#'+this.id,
               url: this.upload_api,
               done: function(res){
                     if( res.code>0 )
                     {
                         layer.msg(res.message);
                     }
                     that.src=res.data.url[0];
                     that.value=res.data.url[0];
                     that.toggle();
               }
         });
         this.toggle()
    }
    singleUpload.prototype.toggle=function(){
        if( !!this.src ){
            this.$target.val(this.value)
            this.$html.find('img').attr('src',this.src)
            this.$html.find('.layui-img-container').removeClass('layui-hide');
        }else{
            this.$html.find('.layui-img-container').addClass('layui-hide');
            this.$target.val('')
        }
    }

    // 批量
    var  multipleUpload=function($ele){
            this.$ele = $ele;
            this.name = this.$ele.data('name');
            this.eleId = window.Tnmc.getUuid();
            this.$dom = $('<div> <div class="img-preview fl">'+
                           '</div>'+
                           '<div  class="img-upload-btn fl img-upload-item" id="'+this.eleId+'">+'+
                           '</div></div>')
            this.$ele.append(this.$dom);
            var that = this;
            this.value=this.$ele.data('value')
            this.$dom.delegate('.delete-btn','click',function(){
                $(this).parent().remove();
            })
            layui.use(['upload'],function(){
                  that.upload = layui.upload;
                  that.render();
            })
            for(var i in this.value){
                that.$dom.find('.img-preview').append(this.createPreviewItem( this.value[i] ))
            }
        }
        multipleUpload.prototype.createPreviewItem=function( src ){
            return  '<div  data-url="'+src+'" data-image-view=""  class="img-preview-item img-upload-item" style="background:url('+src+') no-repeat center center"><input type="hidden" name="'+this.name+'[]" value="'+src+'" ><a class="delete-btn" href="javascript:;">删除</a></div>';
        }
        multipleUpload.prototype.render=function(){
            var that = this;
            this.upload.render({
                elem: '#'+this.eleId,
                url: '/index.php?r=/purchase/order-monitor-examine/post-upload',
                multiple: true,
                auto: true,
                field:'files',
                accept: 'file', //普通文件
                done: function(res){
                    if(res.code!=0&&res.code!=200){
                        layer.msg(res.msg)
                    }else
                    {
                        for(var i in res.data.url)
                        {
                           if(  that.$dom.find('.img-preview .img-preview-item').size()>=6 ){
                                layer.msg('最多只能上传6张图片~~~~', {
                                  offset: '40px',
                                  time: 1500,
                                  icon: 2
                              })
                           }else{
                             that.$dom.find('.img-preview').append(that.createPreviewItem( res.data.url[i] ))
                           }
                        }
                    }
                  //上传完毕
                }
              });
        }

    $(function(){
        $('[data-upload-single]').each(function(){
             new singleUpload( $(this) )
        })
        $('[data-upload-multiple]').each(function(){
             new multipleUpload( $(this) )
        })
    })



    