
//验证手机号

function vailPhone(){

    var phone = jQuery("#reg_username").val();

    var flag = false;

    var message = "";

    var myreg = /^(((13[0-9]{1})|(14[0-9]{1})|(17[0]{1})|(15[0-3]{1})|(15[5-9]{1})|(18[0-9]{1}))+\d{8})$/;

    if(phone == ''){

        message = "手机号码不能为空！";

    }else if(phone.length !=11){

        message = "请输入有效的手机号码！";

    }else if(!myreg.test(phone)){

        message = "请输入有效的手机号码！";

    }else{

        flag = true;

    }

    if(!flag){

        //提示错误效果
            alert(message);

    }else{

        //提示正确效果

        //jQuery("#phoneDiv").removeClass().addClass("ui-form-item has-success");

        //jQuery("#phoneP").html("");

        //jQuery("#phoneP").html("<i class=\"icon-success ui-margin-right10\"> <\/i>该手机号码可用");

    }

    return flag;

}
