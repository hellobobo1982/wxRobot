<?php
if(!defined('authority')) {header("Location: ./404.html");}
include_once "wx.class.php";
include_once "func.php";


if(isset($a)){
    switch ($a){
        case "showQcoder":
            $wx = new wx();
            $uuid = $wx->getUuid();
            $imgFile = $wx->getQcoderImg($uuid);
            $msg['code'] = 1;
            $msg['uuid'] = $uuid;
            $msg['imgUrl'] = $imgFile;
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            break;
        case "waitScan":
            if (array_key_exists('uuid', $p)) {
                $uuid = $p["uuid"];
                $wx = new wx();
                pushlogin:  $redirectUrl = $wx->waitScan($uuid);
                if($redirectUrl == -1){
                    $msg['code'] = -207;
                    $msg['msg'] = '扫码超时';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    break;
                }
                $i = $wx->Login($redirectUrl);
                if ($i == 1) {
                    if(!rename(Dir_Cookie . "tmp.cookie", Dir_Cookie . $wx->uin)){
                        $msg['code'] = -205;
                        $msg['msg'] = 'cookie重命名出错';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    };//立即改名
                    $j = $wx->Ini();
                    if ($j == 1) {
                        $q = $wx->updateLastParam($wx->uin);//保存本次登陆信息为 最新的信息
                        if ($q == 1) {
                            $msg['code'] = 1;
                            $msg['uin'] = $wx->uin;
                            $msg['nickname'] = $wx->nickname;
                            $msg['headImg'] = $wx->getIcon();
                            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        } else if($q == -1){
                            $msg['code'] = -204;
                            $msg['msg'] = '写入信息失败';
                            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        }else{
                            $msg['code'] = -203;
                            $msg['msg'] = '还未产生必要的运行数据';
                            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        }
                    } else {
                        $msg['code'] = -202;
                        $msg['msg'] = '登陆初始化失败';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    }
                } else {
                    $msg['code'] = -201;
                    $msg['msg'] = '登陆失败';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            }else {
                $msg['code'] = -206;
                $msg['msg'] = '传入参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "getGroup":
            if (array_key_exists('uin', $p) && !empty($p["uin"])) {
                $uin=$p["uin"];
                $wx = new wx();
                $i = $wx->loadLastParam($uin);
                if($i == -1){
                    $msg['code'] = -211;
                    $msg['msg'] = '初始化失败，未能找到配置文件';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }else {
                    $j = $wx->webwxgetgroup();
                    if($j == 11){
                        $msg['code'] = -213;
                        $msg['msg'] = '微信已下线';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    }elseif($j ==-1){
                        $msg['code'] = -214;
                        $msg['msg'] = '拉取群失败';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    }else{
                        $msg['code'] = 1;
                        $msg['msg'] = $j;
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    }
                }
            }else{
                $msg['code'] = -212;
                $msg['msg'] = '参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "Run":
            if (array_key_exists('username', $p) && array_key_exists('uin', $p) && array_key_exists('from_groups', $p) && array_key_exists('to_groups', $p)) {
                $username = $p["username"];
                $from_groups = $p["from_groups"];
                $to_groups = $p["to_groups"];
                $uin = $p["uin"];
                if(empty($from_groups) || empty($to_groups)){
                    $msg['code'] = -223;
                    $msg['msg'] = '参数不能为空';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    break;
                }
                if(isRun($uin) !=0){
                    $msg['code'] = -222;
                    $msg['msg'] = '重复运行';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    break;
                }else{
                    $data = array("username"=>$username,"uin"=>$uin,"from_groups"=>$from_groups,"to_groups"=>$to_groups);
                    $i = Sync($_SERVER['HTTP_HOST'],Net_Run.'Receive.php',$data);
                    $j = Sync($_SERVER['HTTP_HOST'],Net_Run.'Send.php',$data);
                    if($i == -1 or $j == -1) {
                        $msg['code'] = -221;
                        $msg['msg'] = '运行失败';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    }else{
                        $msg['code'] = 1;
                        $msg['msg'] = '运行成功';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                    }
                }
            }else{
                $msg['code'] = -224;
                $msg['msg'] = '参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "Alive":
            if (array_key_exists('uin', $p) && !empty($p["uin"])) {
                    $uin=$p["uin"];
                    $wx = new wx();
                    $wx->loadLastParam($uin);
                //$i = $wx->getIcon();
                $i = $wx->syncCheck();
                if ($i != 11) {
                    $msg['code'] = 1;
                    $msg['msg'] = '在线';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                } else {
                    $msg['code'] = 0;
                    $msg['msg'] = '已下线';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            }else{
                    $msg['code'] = -232;
                    $msg['msg'] = '参数错误';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            break;
        case "Stop":
            if (array_key_exists('uin', $p) && !empty($p["uin"])) {
                $uin=$p["uin"];
                if(Stop($uin)){
                    $msg['code'] = 1;
                    $msg['msg'] = '已停止 ';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }else{
                    $msg['code'] = 0;
                    $msg['msg'] = '未能停止 ';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            }else{
                $msg['code'] = -262;
                $msg['msg'] = '参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "pushlogin":
            if (array_key_exists('uin', $p) && !empty($p["uin"])) {
                $uin=$p["uin"];
                $wx = new wx();
                $wx->loadLastParam($uin);
                $uuid = $wx->pushlogin($uin);
                if($uuid != -1){
                    goto pushlogin;
                }else{
                    $msg['code'] = -243;
                    $msg['msg'] = '已失效，请右上角重新登陆';
                    echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                }
            }else{
                $msg['code'] = -242;
                $msg['msg'] = '参数错误';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        case "IsRun":
            if (array_key_exists('uin', $p) && !empty($p["uin"])) {
                $uin=$p["uin"];
                switch(isRun($uin)){
                    case 1:
                        $msg['code'] = 1;
                        $msg['msg'] = '正在运行';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case 0:
                        $msg['code'] = 0;
                        $msg['msg'] = '没有运行';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                    case -1:
                        $msg['code'] = -1;
                        $msg['msg'] = '运行异常';
                        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
                        break;
                }
            }else{
                $msg['code'] = -259;
                $msg['msg'] = '无效指令';
                echo json_encode($msg, JSON_UNESCAPED_UNICODE);
            }
            break;
        default:
            $msg['code'] = -299;
            $msg['msg'] = '无效指令';
            echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    }
    unset($wx);
}
