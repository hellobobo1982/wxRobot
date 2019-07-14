<?php
ignore_user_abort(true);
set_time_limit(0);

ob_start();
echo 'ok'."\n";
header('Connection: close');
header("HTTP/1.1 200 OK");
header('Content-Length: 0');
ob_end_flush();
ob_flush();
flush();
sleep(1);

include_once "../../config/config.inc.php";
include_once 'jd.class.php';
include_once 'wx.class.php';
include_once 'func.php';


$username = isset($_POST["username"]) ? $_POST["username"] : -1;
$uin = isset($_POST["uin"]) ? $_POST["uin"] : -1;
$from_groups = isset($_POST["from_groups"]) ? $_POST["from_groups"] : -1;
$from_groups = explode(",",$from_groups);//转换成数据

$pid = posix_getpid();
if(is_int($pid) && $pid > 0){
    file_put_contents(Dir_Run.$uin,$pid.PHP_EOL,FILE_APPEND);//唯一运行标志位
}else{
    mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|ReceiveGetPid Err! " .$pid);
    Stop($uin);
}

$wx = new wx();
$i = $wx->loadLastParam($uin);

$i = 0;
while($i !== 11) {
    $i = $wx->syncCheck();
    mylog("syncCheck|UIN:".$uin);
    $wx->ping();//防止mysql长时间搁置，导致gone away
    if($i == "2"){
        $data = $wx->webwxSync();
        if($data->BaseResponse->Ret== "0"){

            foreach($data->AddMsgList as $msg){

                if(in_array($msg->MsgType, array(1,3,43))){

                    if(substr($msg->FromUserName,0,2) == "@@" ||substr($msg->ToUserName,0,2) == "@@"){
                        $GroupID ="";
                        if(substr($msg->FromUserName,0,2) == "@@"){
                            $GroupID = $msg->FromUserName;
                            $end = strpos($msg->Content,"<br/>");
                            $UserID = substr($msg->Content,0,$end - 1);
                            $Content = substr($msg->Content,$end + 5);
                        }
                        if(substr($msg->ToUserName,0,2) == "@@"){
                            $GroupID = $msg->ToUserName;
                            $UserID = $msg->FromUserName;
                            $Content = $msg->Content;
                        }
                        $MsgType = $msg->MsgType;
                        if(in_array($GroupID,$from_groups)){//是否是监控群的消息
                            ////////放入数据库
                            if($MsgType == 3 || $MsgType == 43 || (strpos($Content,'http') !== false)){//排除非推广文字
                                $wx->insertReceive($Content,$MsgType);
                            }
                        }
                    }
                }
            }
        }
    }
}

mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|Receive Over " .$i);
if(file_exists(Dir_Run.$uin)) {
    $pids = file_get_contents(Dir_Run . $uin);
    $pidArr = explode(PHP_EOL, trim($pids));
    foreach ($pidArr as $pidItem) {
        if ($pidItem !=$pid && posix_kill(intval($pidItem), 0)) {//正在运行
            posix_kill(intval($pidItem), 9);//杀掉
            mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|Send Killed Pid:".$pidItem);
        }
    }
    unlink(Dir_Run.$uin);//同时删除文件
    mylog("USER:".$username."|UIN:".$uin."|Delete File:".$uin);
}
mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|Receive Kill Self Pid:".$pid);
posix_kill(intval($pid), 9);//自杀，一定是最后一步。
?>
