$(document).on("pagecreate ",function(){
    if(!window.localStorage){
        alert("暂不支持该手机,请更换手机尝试");
        return false;
    }


    /**********************************************************************************************************************/
//初始化localstorge数据
    //全局ajax设置
    $.ajaxSetup({
        url: "http://www.jingfenzhushou.com/lousi_tool/index.php",
        async:false,
        timeout: 300000,
        type: "post" ,
        cache:false,
        dataType: 'json',
        headers: {jfToken:localStorage.getItem('jfToken')==null ?'':localStorage.getItem('jfToken')},
    });

    /***********************************************************************************************************************/
    //初始页面展示区

    //微信页
    $("#wxmng").off("pageshow").on("pageshow",function () {
        $("#wxlist").empty();
        //提取微信列表
        if (localStorage.getItem('uins') !=null) {
            uins = localStorage.getItem('uins');
            uins = uins.split(";");
            if (uins.length !=0) {
                $.each(uins, function (i, n) {
                    n = JSON.parse(n);
                    $("#wxlist").append('<li id=' + n.uin + '><a href="#" onclick="wxClick(' + n.uin + ')"><img  height="80" width="80" src=' + n.headImg + '>' + n.nickname + '<span></span></a></li>').enhanceWithin();
                    if(alive(n.uin)){
                        i =isrun(n.uin);
                        if(i==1) {
                            $("#" + n.uin + " span").addClass("ui-li-count");
                            $("#" + n.uin + " span").text("运行中");
                        }else if(i==-1){
                            $("#" + n.uin + " span").addClass("ui-li-count");
                            $("#" + n.uin + " span").text("运行异常");
                        }else{
                            $("#" + n.uin +  " span" ).addClass("ui-li-count");
                            $("#" + n.uin +  " span" ).text("在线");
                        }
                    }else{
                        $("#" + n.uin +  " span" ).addClass("ui-li-count");
                        $("#" + n.uin +  " span" ).text("已下线");
                    }
                });
            }
        }
        $("#wxlist").trigger("create");//CSS样式丢失解决办法
        $("#wxlist").listview("refresh");//CSS样式丢失解决办法

    })

    //群列表页
    $("#grouppage").off("pageshow").on("pageshow",function () {
        $("#grouplist").empty();
        uin = $("#wxid").val();
        //提取群列表
        if (localStorage.getItem(uin + "_" + "Groups") != null) {
            Groups = localStorage.getItem(uin + "_" + "Groups").split(";");
        }else{
            Groups = new Array();
        }
        if (localStorage.getItem(uin + "_" + "fromGroup") != null) {
            from_group = localStorage.getItem(uin + "_" + "fromGroup").split(",");//split没有默认值，join默认是逗号
        }else{
            from_group = new Array();
        }
        if (localStorage.getItem(uin + "_" + "toGroup") != null) {
            to_group = localStorage.getItem(uin + "_" + "toGroup").split(",");
        }else{
            to_group = new Array();
        }
        /****************************/

        if (Groups.length != 0) {
            $.each(Groups, function (i, n)
            {
                n = JSON.parse(n);
                $("#grouplist").append('<li id='+n.GroupID +'><a href="#myPopup1" onclick="selectgroup(\''+n.GroupID+'\')" data-rel="popup" data-position-to="window"><img height="40" width="40" src=' + n.GroupImg + ' class="ui-li-icon">' + n.GroupName + '<span id="count"></span></a></li>');
                if(from_group.indexOf(n.GroupID) > -1){
                    $("#" + "\\@\\@" + n.GroupID.substring(2) +  " span" ).addClass("ui-li-count");
                    $("#" + "\\@\\@" + n.GroupID.substring(2) +  " span" ).text("监控群");
                }
                if(to_group.indexOf(n.GroupID) > -1){
                    $("#" + "\\@\\@" + n.GroupID.substring(2) +  " span" ).addClass("ui-li-count");
                    $("#" + "\\@\\@" + n.GroupID.substring(2) +  " span" ).text("目的群");
                }
            });
        }
        $("#grouplist").trigger("create");//CSS样式丢失解决办法
        $("#grouplist").listview("refresh");//CSS样式丢失解决办法

        //按钮状态
        i = isrun(uin);
        if(i == 1){
            $("#run").text('正在运行');
            $("#run").prop('disabled','disabled');
            $("#stop").prop('disabled',false);
            $("#stop").text('停止');
        }else if(i == 0){
            $("#run").prop('disabled',false);
            $("#run").text('开始运行');
            $("#stop").prop('disabled','disabled');
            $("#stop").text('停止');
        }else{
            $("#run").text('运行异常');
            $("#run").prop('disabled','disabled');
            $("#stop").prop('disabled',false);
            $("#stop").text('停止');
        }

    })

    /***********************************************************************************************************************/
///功能区


    //登陆-注册
    $("#loginform,#registerform").off("submit").on("submit",function() {
        $.ajax({
            data:$(this).serializeArray(),
            success:function(result){
                ErrHandle(result);
                if(result.code === 1){
                    localStorage.setItem('jfToken', result.token);
                    localStorage.setItem('jfUsername', result.username);
                    $.mobile.changePage("#wxmng", "slideup");
                }
            }
        });
        return false;
    });

    //监控/目标选择
    $("#btn").off("tap").on("tap",function () {
        uin = $("#wxid").val();
        if(isrun(uin) != 0){
            alert("请先停止运行再设置群");
            return false;
        }
        d = $('#groupid').val();
        var index_to = to_group.indexOf(d);
        var index_from = from_group.indexOf(d);
        if(index_from != -1){
            from_group.splice(index_from,1);
            $("#" + "\\@\\@" + d.substring(2) +  " span" ).removeClass("ui-li-count");
            $("#" + "\\@\\@" + d.substring(2) +  " span" ).text("");
        }
        if(index_to != -1){
            to_group.splice(index_to,1);
            $("#" + "\\@\\@" + d.substring(2) +  " span" ).removeClass("ui-li-count");
            $("#" + "\\@\\@" + d.substring(2) +  " span" ).text("");
        }

        switch ($('input:radio:checked').val()) {
            case "0":
                from_group.push(d);
                $("#" + "\\@\\@" + d.substring(2) +  " span" ).addClass("ui-li-count");
                $("#" + "\\@\\@" + d.substring(2) +  " span" ).html("监控群");
                break;
            case "1":
                to_group.push(d);
                $("#" + "\\@\\@" + d.substring(2) +  " span" ).addClass("ui-li-count");
                $("#" + "\\@\\@" + d.substring(2) +  " span" ).text("目的群");
                break;
        }

        from_group_str =from_group.join();
        to_group_str =to_group.join();

        if(from_group_str !=""){
            localStorage.setItem(uin + "_" + "fromGroup",from_group_str);
        }else{
            localStorage.removeItem(uin + "_" + "fromGroup",from_group_str);
        }
        if(to_group_str !=""){
            localStorage.setItem(uin + "_" + "toGroup",to_group_str);
        }else{
            localStorage.removeItem(uin + "_" + "toGroup",to_group_str);
        }

    });

    //开始运行
    $("#run").off("tap").on("tap",function() {//这里注意要先解绑OFF，否则出现提交多次接口的情况，日你个jquery
        uin = $("#wxid").val();
        username = localStorage.getItem("jfUsername");
        from_groups = localStorage.getItem(uin + "_" + "fromGroup");
        to_groups = localStorage.getItem(uin + "_" + "toGroup");
        $("#run").prop('disabled','disabled');
        $("#stop").prop('disabled',false);
        $.ajax({
            data:{'c': 'wxmng','a':'Run','username':username,'uin': uin,"from_groups":from_groups,"to_groups":to_groups},
            success:function(result){
                if(result.code===1){
                    $("#run").text('正在运行');
                }else{
                    alert(result.msg);
                    $("#run").prop('disabled',false);
                }
            }});
        return false;
    });

    //结束运行
    $("#stop").off("tap").on("tap",function() {
        uin = $("#wxid").val();
        //$("#stop").prop('disabled','disabled');//不设置停止按钮，永远可用。
        $.ajax({
            data:{'c': 'wxmng','a':'Stop','uin': uin},
            success:function(result){
                ErrHandle(result);
                if(result.code===1){
                    $("#run").prop('disabled',false);
                    $("#run").text('开始运行');
                    $("#stop").prop('disabled','disabled');
                    $("#stop").text('已停止');
                }else if(result.code===0){
                    alert("出现异常！未能停止，请再次尝试停止");
                }
            }});
        return false;
    });
    //刷新
    $("#refresh").off("tap").on("tap",function() {
        uin = $("#wxid").val();
        if(alive(uin)){
            getgroup(uin);
        }else{
            alert("微信已下线");
            $.mobile.changePage("#wxmng");
        }

    });


});

/*********************************************************************************************************************/
var der = $.Deferred();

function loadQcoder(){
    $.mobile.loading("show");
    $.ajax({//获取二维码
        async:true,
        data:{'c': 'wxmng','a':'showQcoder'},
        success:function(result){
            $.mobile.loading("hide");
            //使用html会先清空再插入，而append则只管插入，会重复。
            $("#myPopup").html('<a href="#" data-rel="back" class="ui-btn ui-corner-all ui-shadow ui-btn ui-icon-delete ui-btn-icon-notext ui-btn-right">关闭</a><img src=' + result.imgUrl + ' height="200" width="200" /><p align="center">请使用微信扫一扫登陆</p>');
            //$("#myPopup").trigger("create");
            //$("#myPopup").popup("open");

            uuid = result.uuid;

            ///////////////////////////

            $.ajax({//等待扫码
                async:true,
                data:{'c': 'wxmng','a':'waitScan','uuid':uuid},
                success:function(result){
                    ErrHandle(result);
                    if(localStorage.getItem('uins') !=null){
                        uins =localStorage.getItem('uins');
                        uins = uins.split(";");
                    }else
                        uins = new Array();

                    var profile = {};
                    if(result.code===1) {
                        $("#myPopup").popup("close");
                        profile.uin = result.uin;
                        profile.nickname = result.nickname;
                        profile.headImg = result.headImg;
                        //先删除
                        for ( var i = 0; i <uins.length; i++){
                            n = JSON.parse(uins[i]);
                            if(n.uin == result.uin){
                                uins.splice(i,1);
                            }
                        }
                        //再添加
                        uins.push(JSON.stringify(profile));
                        localStorage.setItem('uins',uins.join(";"));//这里要注意使用封号切割，因为里面json是用逗号
                        //localStorage.setItem(result.uin, JSON.stringify(profile));
                        $("#" + result.uin).remove();
                        $("#wxlist").append('<li id='+result.uin +'><a href="#" onclick="wxClick(' + result.uin+ ')"><img height="80" width="80" src=' + result.headImg + '>' + result.nickname + '</a></li>').trigger("create");
                        $("#wxlist").listview("refresh");
                    }else{
                        alert(result.msg);
                    }
                },
                complete:function () {
                    //$.mobile.loading("hide");
                }});

            //////////////////////////////////



        },
        complete:function () {
            $.mobile.loading("hide");
        }});


}


function wxClick(uin) {
    //判断是否存活
    if(!alive(uin)){
        localStorage.removeItem(uin + "_" + "Groups");
        localStorage.removeItem(uin + "_" + "fromGroup");
        localStorage.removeItem(uin + "_" + "toGroup");
        alert("请移步至微信-点击微信登陆");//只能放这里提示了。
        $.ajax({//获取二维码
            data:{'c': 'wxmng','a':'pushlogin','uin': uin},
            success:function(result){
                ErrHandle(result);
                //已经处理完失效情况-243,且返回
                if(result.code == 1){//返回的是新登陆后的1，其实应该更新下profile信息的，这里就省略吧
                    getgroup(uin);
                }
            }
        });
    }else if(localStorage.getItem(uin + "_" + "Groups") != null){
        $("#wxid").val(uin);//TMD怎么也传不过去参数，只能预埋点实现了。
        $.mobile.changePage("#grouppage");
    }else{
        $("#wxid").val(uin);
        getgroup(uin);
    }
}


function alive(uin) {
    //判断是否存活
    $.mobile.loading("show");
    var code = 0;
    $.ajax({//判断是否存活
        data: {'c': 'wxmng', 'a': 'Alive', 'uin': uin},
        success: function (result) {
            $.mobile.loading("hide");
            ErrHandle(result);
            code = result.code;//1：存活；0：已下线
        }
    });
    return code;

}

function isrun(uin) {
    //判断是否运行
    var code = 0;
    $.ajax({//判断是否运行
        data:{'c': 'wxmng','a':'IsRun','uin': uin},
        success:function(result){
            ErrHandle(result);
            code = result.code;//1:运行；0：不运行；-1：运行异常
        }
    });
    return code;
}

function getgroup(uin){

    $.mobile.loading("show");
    $.ajax({//拉取群列表
        async:true,//否则loading不出来
        data:{'c': 'wxmng','a':'getGroup','uin':uin},
        beforeSend:function(){
            //$.mobile.loading("show");
        },
        success:function(result){
            if(result.code ===1) {
                localStorage.removeItem(uin + "_" + "Groups");
                localStorage.removeItem(uin + "_" + "fromGroup");
                localStorage.removeItem(uin + "_" + "toGroup");
                localStorage.setItem(uin + "_" + "Groups",result.msg.join(";"));
            }
        },
        complete:function () {
            $.mobile.loading("hide");
            $("#wxid").val(uin);//TMD怎么也传不过去参数，只能预埋点实现了。
            $.mobile.changePage("#grouppage");//转向列表页放这里处理，因为为了显示loading，使用的是同步方式。
        }
    });
}

//公用函数
function selectgroup(id)
{
    $("#groupid").val(id);
}


window.alert = function(name){

    var iframe = document.createElement("IFRAME");
    iframe.style.display="none";
    iframe.setAttribute("src", 'data:text/plain,');
    document.documentElement.appendChild(iframe);
    window.frames[0].window.alert(name);
    iframe.parentNode.removeChild(iframe);
};


function transformTimeStamp(ev) {
    return new Date(parseInt(ev) ).toLocaleString('zh', { hour12: false ,  month: '2-digit',  day: '2-digit',  hour: '2-digit',  minute: '2-digit'});
}


function withdraw_onlick(){
    $("#withdrawNumber").val($("#CommissionAvilable").text());
    var myDate = new Date();
    $("#requestTime").val(myDate);
}

function newGuide(){

    //$("#guideview").trigger("create")

    $("#step1View").attr("data-arrow","b").trigger("create");
    $("#step1View").trigger("create")
    $("#step1View").popup("open",{ positionTo:"#wxmenu"}).trigger("create");
}


