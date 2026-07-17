<?php
$this->title = Yii::t('app', 'Chat Room');
?>
<title>聊天室</title>
<script src="/chat_statics/js/jquery-1.8.0.min.js"></script>
<script src="/chat_statics/js/fingerprint2.min.js"></script>
<style>
    .button-area li {
        height: .7rem;
        width: .7rem;
        line-height: .7rem;
        background-color: #ffffff;
        text-align: center;
        margin: .03rem;
        border-radius: .1rem;
        float: left;
        border: 1px solid #e8dddd;
        box-sizing: border-box;
    }

    .vkb{
        background:url(/chat_statics/images/wei-scene/vkb.png) no-repeat;
        background-size:100% 100%;
        width:.6rem;
        height:.6rem;
    }
    .btn-send{
        border-radius: 5px;
    }

    .wschat__input-panel .editor-text{
        width: 100%;
        line-height:.3rem;
        margin-left: .1rem;
        font-size:.3rem;
        padding: 0;
    }
</style>
<body class="bg--efeff4">
<input type="hidden" name="token" id="token" value="<?php echo $_GET['token']?>">

<!-- <>微现场 -->
<div class="wei__scene-panel flexbox flex-direction--column">
    <div class="wei__mask"></div>

    <!-- //消息上墙页面 -->
    <div class="ws__chatMsg-panel flex1" style="height:100%;">
        <div id="mescroll" class="chatMsg-ct mescroll mescroll-bar">
            <div class="mescroll-downwarp">
                <div class="downwarp-content">
                    <p class="downwarp-progress"></p>
                    <p class="downwarp-tip"></p>
                </div>
            </div>
            <!-- //消息列表-->
            <ul class="clearfix" id="J__chatMsgList">
                <li class="time"><span><?php echo date("Y-m-d H:i:s");?></span></li>
            </ul>
        </div>
    </div>

    <!-- //尾部 -->
    <div class="ws__footer">
        <!--<div class="bottomfixed">-->
        <!-- 输入框-->
        <div class="wschat__input-panel flexbox">
            <span class="vkb"></span>
            <div class="editor-container flex1">
                <textarea autoheight="true" class="editor-text J__editorText" style="border-radius: 5px;"></textarea>
            </div>
            <button class="btn-send J__submitCnt" disabled="">发送</button>
        </div>
        <!-- 操作区(表情-选择)-->
        <div class="wschat__choice-panel" style="overflow: hidden; display: block;">
            <div class="similar-area button-area" style="overflow: hidden; display: block;">
                <ul id="keyboard">
                    <li class="item">查</li><li class="item">上</li><li class="item">下</li>
                    <li class="item">二</li><li class="item">三</li><li class="item">四</li>
                    <li class="item">定</li><li class="item">现</li><li class="item">←</li>

                    <li class="item">奖</li><li class="item">大</li><li class="item">千</li>
                    <li class="item">1</li><li class="item">2</li><li class="item">3</li>
                    <li class="item">除</li><li class="item">双重</li><li class="item">兄弟</li>

                    <li class="item">走</li><li class="item">小</li><li class="item">百</li>
                    <li class="item">4</li><li class="item">5</li><li class="item">6</li>
                    <li class="item">取</li><li class="item">三重</li><li class="item">两</li>

                    <li class="item">倒</li><li class="item">单</li><li class="item">十</li>
                    <li class="item">7</li><li class="item">8</li><li class="item">9</li>
                    <li class="item">。</li><li class="item">四重</li><li class="item">清除</li>

                    <li class="item">全</li><li class="item">双</li><li class="item">个</li>
                    <li class="item">0</li><li class="item">.</li><li class="item">X</li>
                    <li class="item">各</li><li class="item">合</li><li class="item">换行</li>
                </ul>
            </div>
        </div>
        <!--</div>-->
    </div>
</div>
<input type="hidden" name="avatar" id="avatar" value="">
<input type="hidden" name="username" id="username" value="">

<script type="text/javascript">
    var token = $("#token").val();
    var uid = '1238120025638916098'
    var url_domain = 'http://18.163.69.56:8090'
    $(function(){
        var buttonArray = [
            '查','上','下','二','三','四','定','现','←',
            '奖','大','千','1','2','3','除','双重','兄弟',
            '走','小','百','4','5','6','取','三重','两',
            '倒','单','十','7','8','9','。','四重','清除',
            '全','双','个','0','.','X','各','合','换行'
        ];
        var keyboard = '';
        for (var i = 0; i < buttonArray.length; i++) {
            keyboard += "<li class='item'>" + buttonArray[i] + "</li>"
        }
        $('#keyboard').html(keyboard);
    });

    function isEmpty(obj){
        if(typeof obj == "undefined" || obj == null || obj == ""){
            return true;
        }else{
            return false;
        }
    }

    function autoHeight(elem){
        console.log('autoHeight');
        elem.style.height = 'auto';
        elem.scrollTop = elem.scrollHeight; //防抖动
        elem.style.height = elem.scrollHeight + 'px';
    }

    $(function(){
        $.fn.autoHeight = function(){
            this.each(function(){
                /*autoHeight(this);
                $(this).on('keyup', function(){
                    autoHeight(this);
                });*/
                $(this).bind('input propertychange', function(){
                    autoHeight(this);
                });
            });
        }
        $('textarea[autoHeight]').autoHeight();
    })

    /** __自定函数 */
    $(function(){
        /* 获取用户信息 */
        $.post();

        /* ——聊天区函数(底部) */
        //...点击内容区域(关闭表情/选择区)
        $(document).on("click", ".ws__chatMsg-panel", function (){
            //console.log("点击内容区");

            $(".wschat__choice-panel").slideUp(200, function(){
                $(this).find(".similar-area").hide();
            });
        });

        //...控制表情框/选择区显示-隐藏
        $(document).on("click", ".wschat__input-panel", function (e){
            if($(e.target).hasClass("vkb")){
                //console.log("点击选择区");

                if($('.wschat__choice-panel').is(":hidden")){
                    $(".wschat__choice-panel").slideDown(200);
                    $(".wschat__choice-panel .button-area").slideDown(200);
                }else{
                    $(".wschat__choice-panel").slideUp(200);
                    $(".wschat__choice-panel .button-area").slideUp(200);
                }
                scrollToBottom();
            }
        });

        /* ——聊天编辑器区域 */
        var $editor = $(".J__editorText"), editor = $editor[0];
        $('#keyboard li').on('click', function(e){
            var val = $(this).text();
            if(val == '查'){
                ws.send(val);
            }else if(val == '←'){
                $editor.val(($editor.val() || "").replace(/[\S\s]$/, ""));
            }else if(val == '清除'){
                $editor.val('');
            }else if(val == '换行'){
                $editor.val($editor.val() + "\r\n");
            }else{
                if(val == '全'){
                    val = '0123456789';
                }else if(val == '大'){
                    val = '56789';
                }else if(val == '小'){
                    val = '01234';
                }else if(val == '单'){
                    val = '13579';
                }else if(val == '双'){
                    val = '02468';
                }
                $editor.val($editor.val() + val);
            }
            autoHeight(editor);
            //$editor.scrollTop($editor[0].scrollHeight);

        })

        //...滚动到聊天内容底部
        function scrollToBottom(){
            $('.ws__chatMsg-panel').animate({scrollTop: $("#J__chatMsgList").height()}, 300);
        }
        //...发送内容
        var $chatMsgList = $("#J__chatMsgList");
        $(".J__submitCnt").on("click", function(){
            //判断内容是否为空
            //var avatar = "http://154.83.17.96:6060/static/images/avatar/f1/f_3.jpg";
            var avatar = $("#avatar").val();
            var name = $("#username").val();
            var text = $editor.val();
            var json = {
                "class":"Index", "action":"index",
                "param":{
                    "type": 4, 'token':token,
                    "name":name, "avatar": avatar,
                    "message": text, "c":'text', "roomid":1
                }
            };
            console.log(json)
            if(isEmpty($editor.val())){
                return;
            }
            // JSON.stringify(json)
            //ws.send($editor.val());
            ws.send(JSON.stringify(json));

            //清空输入框
            $editor.val('');
            autoHeight(editor);
            scrollToBottom();
        });
    });
</script>

<script type="text/javascript">
    var ws;
    // 重连
    function reconnect(url) {
        if (reconnect.lockReconnect) return;
        $('.J__submitCnt').attr('disabled',true);
        reconnect.lockReconnect = true;
        setTimeout(function () {     //没连接上会一直重连，设置延迟避免请求过多
            createWebSocket(url);
            reconnect.lockReconnect = false;
        }, 2000);
    }

    // 实例websocket
    function createWebSocket(url) {
        try {
            if ('WebSocket' in window) {
                ws = new WebSocket(url);
            } else if ('MozWebSocket' in window) {
                ws = new MozWebSocket(url);
            } else {
                _alert("当前浏览器不支持websocket协议,建议使用现代浏览器",3000)
            }
            initEventHandle(url);
        } catch (e) {
            console.log(e);
            reconnect(url);
        }
    }

    // 初始化事件函数
    function initEventHandle(wsUrl) {
        ws.onopen = function(){
            notice('连接成功')
            $('.J__submitCnt').removeAttr('disabled');
        };
        ws.onclose = function () {
            //notice('掉线了，正在重连...')
            reconnect(wsUrl);
        };
        ws.onerror = function (err) {
            notice('连接出错，正在重连...')
            reconnect(wsUrl);
        };
        ws.onmessage = onmessage;
    }

    var clipboard;

    //  监听消息
    function onmessage(event){
        var ret = JSON.parse(event.data);
        var $chatMsgList = $("#J__chatMsgList");
        console.log(ret.code);
        if(ret.code == 0){
            for(var i = 0; i < ret.data.length; i++){
                var message = ret.data[i];
                var avatar = str.indexOf("http://") != -1? message.avatar : url_domain + '/' + message.avatar;
                var tpl_msg = '';
                if(message.msgType == 0){
                    var className = (uid == message.fromId)?'me':'others';
                    tpl_msg = [
                        '<li class="msg-item '+className+'" data-id="'+message.id+'">\
							<div class="avatar">\
								<img src="'+avatar+'" />\
							</div>\
							<div class="content">\
								<p class="author">'+message.name+'</p>\
								<div class="msg">\
									<div class="plain">' + message.content.replace(/\n/g,'<br>') + '</div>\
								</div>\
							</div>\
						</li>'
                    ].join("");
                }else if(message.msgType == 1){
                    tpl_msg = [
                        '<li class="msg-item others image" data-id="'+message.id+'">\
							<div class="avatar">\
								<img src="'+avatar+'" />\
							</div>\
							<div class="content">\
								<p class="author">'+message.name+'</p>\
								<div class="msg">\
									<div class="picture">\
										<img class="J__img" src="' + message.content + '" />\
									</div>\
								</div>\
						</div>\
					</li>'
                    ].join("");
                }
                $chatMsgList.prepend(tpl_msg);
            }
            if(ret.data.length > 0){
                var lastMessage = ret.data[ret.data.length-1];
                var tpl_msg = ['<li class="time"><span>'+lastMessage.createdAt.replace('T',' ')+'</span></li>'].join("");
                $chatMsgList.prepend(tpl_msg);
            }
            mescroll.scrollTo(0, 300);
            //$('.ws__chatMsg-panel').animate({scrollTop: 0}, 300);
        }else if(ret.code == 1){  // 系统提示
            notice(ret.msg)
        }else if(ret.code == 2){  // 聊天
            // 1、微信群聊： "wxid1":"群房间号", "wxid2":"发送的用户id"  2、私聊 "wxid1":"wxid_875i1kgd38x122", "wxid2":"_"
            var message = ret.data;
            var avatar = message.avatar.indexOf("http://") != -1 ? message.avatar :  message.avatar;
            var tpl_msg = '';
            console.log(ret);
            if(message.roomid > 0){ // 群聊
                //var className = (uid == message.from_id) ? 'me':'others';
                var className = (message.mine == 1) ? 'me':'others';
                console.log(className)
                tpl_msg = [
                    '<li class="msg-item '+className+'" data-id="'+message.id+'">\
							<div class="avatar">\
								<img src="'+avatar+'" />\
							</div>\
							<div class="content">\
								<p class="author">'+message.name+'</p>\
								<div class="msg">\
									<div class="plain">' + message.newmessage.replace(/\n/g,'<br>') + '</div>\
								</div>\
							</div>\
						</li>'
                ].join("");
            }else { // 私聊
                tpl_msg = [
                    '<li class="msg-item others image" data-id="'+message.id+'">\
							<div class="avatar">\
								<img src="'+avatar+'" />\
							</div>\
							<div class="content">\
								<p class="author">'+message.name+'</p>\
								<div class="msg">\
									<div class="picture">\
										<img class="J__img" src="' + message.newmessage + '" />\
									</div>\
								</div>\
						</div>\
					</li>'
                ].join("");
            }
            $chatMsgList.append(tpl_msg);
            mescroll.scrollTo($("#J__chatMsgList").height(), 0);
            //$('.ws__chatMsg-panel').animate({scrollTop: $("#J__chatMsgList").height()}, 300);
        }

        if(clipboard){
            clipboard.destroy();
        }
        clipboard = new ClipboardJS('.msg-item', {
            text: function(trigger){
                console.log($(trigger).find('.plain').html());
                return $(trigger).find('.plain').html().replace(/<br>/g, "\n");
            }
        });
        clipboard.on('success',function(){
            alert('复制成功');
        })
        clipboard.on('error',function(){
            alert('复制成功');
        })
    };

    function notice(msg){
        var $chatMsgList = $("#J__chatMsgList");
        $chatMsgList.append('<li class="time"><span>'+msg+'</span></li>');
        $('.ws__chatMsg-panel').animate({scrollTop: $("#J__chatMsgList").height()}, 300);
    }

    function getHistory(){
        setTimeout(function(){
            var items = $('.msg-item');
            ws.send(JSON.stringify({
                action: 'pull',
                lastId: items.length > 0?$(items[0]).data('id'):''
            }));
            mescroll.endSuccess();
        }, 1000);
    }


    function getQueryVariable(variable)
    {
        var query = window.location.search.substring(1);
        var vars = query.split("&");
        for (var i=0;i<vars.length;i++) {
            var pair = vars[i].split("=");
            if(pair[0] == variable){return pair[1];}
        }
        return(false);
    }

    var fp = new Fingerprint2();
    fp.get(function(result) {
        var uid = '1238120025638916098'
        var domain = '18.163.69.56';
        var url = "http://"+domain+":8090/api/chat/get-user-info";
        console.log(result);
        $.post(url,{token:token}, function(ret){
            if(ret.status == 200){
                $("#avatar").val(ret.data.avatar)
                $("#username").val(ret.data.name)
                createWebSocket("ws://"+domain+":9501/ws?uid="+uid+"&fp="+result);
            }
        }, 'json');
        setInterval('heartbeat()', 50000); // add 04.01
    });

    var mescroll;

    $(function(){
        mescroll = new MeScroll("mescroll", {
            down:{
                auto: false,
                callback:getHistory,
                autoShowLoading: true
            }
        });
    })
    //发送心跳包 add 04.01
    function heartbeat() {
        var json = {
            "class": "Index",
            "action": "index",
            "param": {
                "type": 'heartbeat',
                "txt": 'heartbeat',
                "token": token,
            }
        };
        //chat.wsSend(JSON.stringify(json));
        ws.send(JSON.stringify(json));
    }
</script>

</body>
