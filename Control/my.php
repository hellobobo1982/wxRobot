<?php
if(!defined('authority')) {header("Location: ./404.html");}
include_once "base.class.php";
include_once "my.class.php";
include_once "jd.class.php";

if(isset($a)){
    switch ($a) {
        case "register":
            if (array_key_exists('username', $p) && array_key_exists('password', $p)) {
                $username = $p["username"];
                $password = $p["password"];
                if(array_key_exists('reference', $p)){
                    $reference = $p["reference"];
                }else{$reference = 0;}//
                $my = new my();
                $i = $my->register($username,$password,$reference);
                switch ($i){
                    case 1:
                        $msg['code'] = 1;
                        $msg['token'] = Author::generateToken($username);//发放token
                        $msg['username'] = $username;
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -1:
                        $msg['code'] = -301;
                        $msg['msg'] = '账号已注册，请勿重复注册';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -2:
                        $msg['code'] = -302;
                        $msg['msg'] = '用户名/密码不能为空';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -3:
                        $msg['code'] = -303;
                        $msg['msg'] = '错误反馈，未能获取到PID';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -4:
                        $msg['code'] = -304;
                        $msg['msg'] = '参数错误，未能获取到PID';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -5:
                        $msg['code'] = -305;
                        $msg['msg'] = '名称重复';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -6:
                        $msg['code'] = -306;
                        $msg['msg'] = '代理关系更新失败';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -7:
                        $msg['code'] = -307;
                        $msg['msg'] = '合法性检查数据库出错';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                }
            }else{
                $msg['code'] = -308;
                $msg['token'] = '传入参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "login":
            if (array_key_exists('username', $p) && array_key_exists('password', $p)) {
                $username = $p["username"];
                $password = $p["password"];
                $my = new my();
                $i = $my->Login($username,$password);
                switch ($i) {
                    case 1:
                        $msg['code'] = 1;
                        $msg['token'] = Author::generateToken($username);//发放token
                        $msg['username'] = $username;
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -1:
                        $msg['code'] = -311;
                        $msg['msg'] = '查无此人';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -2:
                        $msg['code'] = -312;
                        $msg['msg'] = '账号异常冻结';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -3:
                        $msg['code'] = -313;
                        $msg['msg'] = '登陆失败';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                }
            }else{
                $msg['code'] = -314;
                $msg['token'] = '传入参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "getAgencyList":
            $username = Author::getUname($token);//解析得到用户信息
            $my = new my();
            $i = $my->getAgencyList($username);
            if ($i == 0) {
                $msg['code'] = 0;
                $msg['msg'] = '没有合伙人';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }elseif($i == -1){
                $msg['code'] = -321;
                $msg['msg'] = '数据库错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }else{
                $msg['code'] = 1;
                $msg['msg'] = $i;
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        default:
            $msg['code'] = -399;
            $msg['msg'] = '无效指令';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    unset($my);
}

