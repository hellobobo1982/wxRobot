/*
  总路由文件
  前端任何请求都将经过该路由经行必要的检查和分配
  孙树杰 2019.7.14
*/
<?php
//error_reporting(0);
//ini_set("display_errors","off");
define("authority",true);

include_once "config/config.inc.php";
include_once 'Author.class.php';
include_once "func.php";

$c = isset($_POST["c"]) ? $_POST["c"] : null;//控制器,已开启magic_quotes_gpc
$a = isset($_POST["a"]) ? $_POST["a"] : null;//方法
$p = (!get_magic_quotes_gpc())?saddslashes($_POST):$_POST;//参数

if(empty($c) || empty($a)){
    $msg['code'] = -500;
    $msg['msg'] = '参数错误,无效请求';
    echo json_encode($msg,JSON_UNESCAPED_UNICODE);
    return;
}
$token = isset($_SERVER['HTTP_JFTOKEN']) ? $_SERVER['HTTP_JFTOKEN'] : null;

switch(Author::tokenPass($token)){
    case 1://通过
        break;
    case -1:
        $msg['code'] = -401;
        $msg['msg'] = '无效token';
        echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        exit();
    case -2:
        $msg['code'] = -402;
        $msg['msg'] = 'token有效期失效';
        echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        exit();
    case -3:
        $msg['code'] = -403;
        $msg['msg'] = '无效token';
        echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        exit();
    case -4:
        if($c == 'my' && $a =='register'){break;}//注册不需要token
        if($c == 'my' && $a =='login'){break;}//登陆不需要token
        $msg['code'] = -404;
        $msg['msg'] = '非法请求,令牌为空';
        echo json_encode($msg,JSON_UNESCAPED_UNICODE);
        exit();
}

switch ($c){

    case "benift":
        require_once "application/control/benift.php";
        break;
    case "wxmng":
        require_once "application/control/wxmng.php";
        break;
    case "my":
        require_once "application/control/my.php";
        break;
    default:
        header("Location: 404.html");
        break;
};

?>
