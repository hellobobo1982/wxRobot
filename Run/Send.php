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
sleep(2);

include_once "../../config/config.inc.php";
include_once 'wx.class.php';
include_once 'func.php';
include_once 'jd.class.php';
include_once 'my.class.php';


$username = isset($_POST["username"]) ? $_POST["username"] : -1;
$uin = isset($_POST["uin"]) ? $_POST["uin"] : -1;
$to_groups= isset($_POST["to_groups"]) ? $_POST["to_groups"] : array();
$to_groups = explode(",",$to_groups);//转换成数据

$pid = posix_getpid();
if(is_int($pid) && $pid > 0){
    file_put_contents(Dir_Run.$uin,$pid.PHP_EOL,FILE_APPEND);//唯一运行标志位
}else{
    mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|SendGetPid Err! " .$pid);
    Stop($uin);
}

$jd = new jd();
$wx = new wx();
$my = new my();

$pid = $my->getAgencyId($username);
$i = $wx->loadLastParam($uin);

//上次没有发送完毕的都置为2*/
$wx->updateReceiveByUin($uin);

$i = 0;
$j = 0;
do{
    $res = $wx->getReceive();
    if($res){
        while($row=mysqli_fetch_assoc($res)){
            $content = $row["Content"];
            $id = $row["id"];
            $msgtype = $row["MsgType"];
            if($msgtype == 1){$content = $jd->zl($content,$pid);}//转链
            foreach($to_groups as $togroup){
                $wx->Threshold();//检测阈值
                if($msgtype == 1){
                    $i = $wx->webwxSendmsg(addslashes($content),$togroup);
                    //mylog('Send-----发送文字：'.$i);
                }
                if($msgtype == 3){
                    $i = $wx->webwxsendmsgimg(null,addslashes($content),$togroup);
                    //mylog('Send---本次发送图片结果：'.$i.'========'.++$j);
                    //立即更新状态
                    $wx->insertSent($id,$msgtype);
                }
                if($i == 11 || !file_exists($uin)){
                    break 3;//退出
                }
                sleep(15);//休息5秒
            }
            $wx->updateReceive($id);
        }
    }
    sleep(60);
}while(true);

mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|Send Over " .$i);
if(file_exists(Dir_Run.$uin)) {
    $pids = file_get_contents(Dir_Run . $uin);
    $pidArr = explode(PHP_EOL, trim($pids));
    foreach ($pidArr as $pidItem) {
        if ($pidItem !=$pid && posix_kill(intval($pidItem), 0)) {//正在运行
            posix_kill(intval($pidItem), 9);//杀掉
            mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|Receive Killed Pid:".$pidItem);
        }
    }
    unlink(Dir_Run.$uin);//同时删除文件
    mylog("USER:".$username."|UIN:".$uin."|Delete File:".$uin);
}
mylog("USER:".$username."|UIN:".$uin."|PID:".$pid."|Send Kill Self Pid:".$pid);
posix_kill(intval($pid), 9);//自杀，一定是最后一步。
?>


