<?php
    include_once "emoji.php";

    function generateSign($params, $appSecret)
    {
        ksort($params);
        $stringToBeSigned = $appSecret;
        foreach ($params as $k => $v) {
            if ("@" != substr($v, 0, 1)) {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $appSecret;
        return strtoupper(md5($stringToBeSigned));
    }

    function get_microtime_format($time)
    {
        if (strstr($time, '.')) {
            sprintf("%01.3f", $time); //小数点。不足三位补0
            list($usec, $sec) = explode(".", $time);
            $sec = str_pad($sec, 3, "0", STR_PAD_RIGHT); //不足3位。右边补0
        } else {
            $usec = $time;
            $sec = "000";
        }
        $date = date("Y-m-d H:i:s.x", $usec);
        return str_replace('x', $sec, $date);
    }



//CURLOPT_COOKIEJAR 用于保存 cookie 到文件
//CURLOPT_COOKIEFILE 用于将保存的 cookie 文件发送出去
//CURLOPT_COOKIE 用于发送 cookie 变量
//uin 不为空则表示需要发送cookie
function httpGet($url,$uin="")
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_COOKIEJAR,Dir_Cookie."tmp.cookie");
    if($uin){
        curl_setopt($curl, CURLOPT_COOKIEFILE,Dir_Cookie.$uin);
    }
    $data = curl_exec($curl);
    curl_close($curl);
    return $data;
}
//uin 不为空则表示需要发送cookie
function httpPost($url,$data,$uin="")
{
/*
 * 1.检查头信息content-type是不是为“content-type:application/x-www-form-urlencoded" 这种传输是以表单的方式提交数据php使用$_POST方式接受。
 * 2.如果头信息content-type是不是为“content-type:application/json"这种传输是以json方式提交数据，php需要使用file_get_contents("php://input")获取输入流的方式接受
*/
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    if ($uin) {
        curl_setopt($curl, CURLOPT_COOKIEFILE,Dir_Cookie.$uin);
    }
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json;charset='utf-8'", "Content-Length:" . strlen($data)));
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    $result = curl_exec($curl);
    curl_close($curl);
    return $result;
}

//得到Location,主要用在获取优惠券链接或二次转链获取SKU链接
function httpGetLocation($url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    // 返回最后的Location
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    $data = curl_exec($curl);
    $Location = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
    curl_close($curl);
    return $Location;
}


function getMillisecond()
{
    list($usec, $sec) = explode(" ", microtime());
    $msec = round($usec * 1000);
    return time();
}

function diffdate($start_date, $end_date)
{//返回秒数
    $time1 = strtotime($start_date);

    $time2 = strtotime($end_date);


    $time3 = $time2 - $time1;

    return $time3;
}

function getEmoji($content)
{
    $preg = "/\<span[\s]*class\=\"emoji[\s]*emoji(.*?)\"\>.*?\<\/span\>/sim";
    preg_match_all($preg, $content, $strResult);
    for ($i = 0; $i < count($strResult[0]); $i++) {
        //$content = str_replace($strResult[0][$i],$strResult[1][$i],$content);
        $content = str_replace($strResult[0][$i], utf8_bytes(hexdec($strResult[1][$i])), $content);
    }
    return $content;
}

function utf8_bytes($cp)
{
    if ($cp > 0x10000) {
        # 4 bytes
        return chr(0xF0 | (($cp & 0x1C0000) >> 18)) .
            chr(0x80 | (($cp & 0x3F000) >> 12)) .
            chr(0x80 | (($cp & 0xFC0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else if ($cp > 0x800) {
        # 3 bytes
        return chr(0xE0 | (($cp & 0xF000) >> 12)) .
            chr(0x80 | (($cp & 0xFC0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else if ($cp > 0x80) {
        # 2 bytes
        return chr(0xC0 | (($cp & 0x7C0) >> 6)) .
            chr(0x80 | ($cp & 0x3F));
    } else {
        # 1 byte
        return chr($cp);
    }
}


//php执行命令
function Sync($host,$path,$param=array())
{
    $query = isset($param) ? http_build_query($param) : '';
    $port = 80;
    $errno = 0;
    $errstr = '';
    $timeout = 10000;
    $fp = fsockopen($host, $port,$errno, $errstr, $timeout);

    if(!$fp){
        return -1;
    }else{
        stream_set_blocking($fp,true);//开启了手册上说的非阻塞模式
        //stream_set_timeout($fp,-1);
        $out = "POST ".$path." HTTP/1.1\r\n";
        $out .= "host:".$host."\r\n";
        $out .= "content-length:".strlen($query)."\r\n";
        $out .= "content-type:application/x-www-form-urlencoded\r\n";
        $out .= "connection:close\r\n\r\n";
        $out .= $query;
        fputs($fp, $out);
        usleep(1000); // 这一句也是关键，如果没有这延时，可能在nginx服务器上就无法执行成功
        fclose($fp);
    }
    return 0;
}

//接收/发送,返回数组标志，前端来判断
function isRun($uin){

    $i=array();//存放运行标志 1为运行0为停止
    if(file_exists(Dir_Run.$uin)) {
        $pids = file_get_contents(Dir_Run . $uin);
        $pidArr = explode(PHP_EOL, trim($pids));

        foreach ($pidArr as $pid) {
            if (posix_kill(intval($pid), 0)) {//正在运行
                array_push($i,1);
            }else{
                array_push($i,0);
            }
        }
        echo $ss = implode('|',$i);
        if(array_sum($i) == count($i)){
            return 1;//都运行
        }elseif(array_sum($i) == 0){
            return 0;//都停止
        }else{
            return -1;//异常，其中某一个进程没有停止
        }
    }else{
        return 0;
    }




}

function Stop($uin){
    if(file_exists(Dir_Run.$uin)) {
        $pids = file_get_contents(Dir_Run.$uin);
        $pidArr = explode(PHP_EOL, trim($pids));
        foreach ($pidArr as $pid) {
            if(posix_kill(intval($pid), 0)){//正在运行
                posix_kill(intval($pid), 9);//杀掉
            }
        }
        sleep(1);//等待1秒后再检查;需要时间停止
        //检查是否杀掉
        $i =true;
        foreach ($pidArr as $pid) {
            if(posix_kill(intval($pid), 0)){//正在运行
                $i = false;
                mylog("KILL SIGLE Err! |UIN:".$uin."|PIDs:".$pids."|PID未能被杀掉:".$pid."|DT:".date("Y-m-d H:i:s"));
            }
        }

        if($i){//成功停止
            if(file_exists(Dir_Run.$uin)){
                unlink(Dir_Run.$uin);//同时删除文件
            }
            mylog("KILL SIGLE OK! |UIN:".$uin."|PIDs:".$pids."|DT:".date("Y-m-d H:i:s"));
            return true;
        }else{
            return false;
        }
    }else{
        mylog("KILL SIGLE Err！|UIN:".$uin."|File not Exsit!|DT:".date("Y-m-d H:i:s"));
        return true;
    }
}

function mylog($log)
{
    file_put_contents(Dir_Log.DIRECTORY_SEPARATOR."log.log",date("Y-m-d H:i:s").'==>'.$log.PHP_EOL,FILE_APPEND);
}

//SQL ADDSLASHES处理数组
function saddslashes($string) {
    if(is_array($string)) {
        foreach($string as $key => $val) {
            $string[$key] = saddslashes($val);
        }
    } else {
        $string = addslashes($string);
    }
    return $string;
}
