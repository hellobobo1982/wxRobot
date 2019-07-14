<?php
if(!defined('authority')) {header("Location: ./404.html");}
include_once "base.class.php";
include_once "imgcompress.class.php";//压缩二维码和头像，加快客户端速度
class wx extends base
{

    public $uin;
    public $nickname;
    public $uid;
    public $sid;
    public $skey;
    public $pass_ticket;
    public $uri;
    public $synckey;
    public $SyncKey_orig;
    public $headImgUrl;


    public function __get($pri_name){
        if(isset($this->$pri_name)){
            return $this->$pri_name;
        }else{
            return null;
        }
    }
    public function __set($pri_name,$value){
        $this->$pri_name = $value;
    }


    public function getUuid(){
        $url = "https://login.wx.qq.com/jslogin?appid=wx782c26e4c19acffb&redirect_uri=https://wx.qq.com/cgi-bin/mmwebwx-bin/webwxnewloginpage&fun=new&lang=zh_CN&_=".getMillisecond();
        $data = httpGet($url);
        $pattern = "~window.QRLogin.code = (\\d+); window.QRLogin.uuid = \"(\\S+?)\";~";
        if(preg_match($pattern,$data,$match)){
            return $match[2];
        }else return -1;
    }

    public function getQcoderImg($uuid){
        $url = "https://login.weixin.qq.com/qrcode/".$uuid;
        $data = httpGet($url);
        $filename = md5(time().mt_rand(10, 99)).".jpg"; //新图片名称
        $filepath = Dir_Qcoder.$filename;
        file_put_contents($filepath,$data);
        //压缩
        $percent = 0.2;  //原图压缩，缩放
        $image = (new imgcompress($filepath,$percent))->compressImg(Dir_Qcoder."zip".$filename);
        unlink($filepath);
        return Net_Qcoder."zip".$filename;
    }

    public function waitScan($uuid){
        $dt = date("Y-m-d H:i:s");
        $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid=".$uuid."&tip=0&r=-".getMillisecond()."&_=".getMillisecond();
        do{
            $data = httpGet($url);
            $url = "https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login?loginicon=true&uuid=".$uuid."&tip=1&r=-".getMillisecond()."&_=".getMillisecond();
            usleep(500);
        }while(!strstr($data, '200') && diffdate($dt,date("Y-m-d H:i:s")) < 100);//三分钟超时
        if(diffdate($dt,date("Y-m-d H:i:s")) >= 100){
            return -1;//超时
        }else{
            $pattern = "/https:[\/]{2}[^\s]*/";
            preg_match($pattern,$data,$match);
            $this->uri = parse_url($match[0])['host'];
            return $match[0] ."&fun=new&version=v2";
        }

    }

    public function Login($url){
        //global $skey,$sid,$uin,$pass_ticket;
        $data = httpGet($url);//要及时改名得到的cookie;
        $wxinfoXML = simplexml_load_string($data);
        $ret = $wxinfoXML->ret;
        if($ret == 0){//要强制转换成string类型，否则 json_encode(class)时候，就会编程{"0":"xxxxxx"}的形式
            $this->skey = (string)$wxinfoXML->skey;
            $this->sid = (string)$wxinfoXML->wxsid;
            $this->uin = (string)$wxinfoXML->wxuin;
            $this->pass_ticket = (string)$wxinfoXML->pass_ticket;
            return 1;
        }else return $ret;
    }


    public function Ini(){//容易超时，数据太多
        $synckey ="";
        $url= "https://" .$this->uri."/cgi-bin/mmwebwx-bin/webwxinit?r=".getMillisecond()."&lang=zh_CN&pass_ticket=".$this->pass_ticket;
        $BaseRequest = '{"BaseRequest":{"DeviceID":"e'.time()*10000 .'","Sid":"'.$this->sid.'","Skey":"'.$this->skey.'","Uin":"'.$this->uin.'"}} ';
        $data = httpPost($url,$BaseRequest);
        $info_obj = json_decode($data);
        $i = $info_obj->BaseResponse->Ret;
        if($i == "0"){
            $this->uid = $info_obj->User->UserName;
            $this->nickname = $info_obj->User->NickName;
            $ImgUrl = $this->getIcon();
            if($ImgUrl != -1){
                $this->headImgUrl =$ImgUrl;
            }else{
                $this->headImgUrl = $info_obj->User->HeadImgUrl;
            }
            $this->SyncKey_orig =json_encode($info_obj->SyncKey);
            foreach ($info_obj->SyncKey->List as $ll)
            {
                $key = $ll->Key . "_" . $ll->Val;
                $synckey = $key . "|" . $synckey;
            }
            $this->synckey = substr($synckey,0,strlen($synckey)-1);
            return 1;
        }else return -1;
    }

    public function syncCheck(){
        $url = "https://webpush.".$this->uri."/cgi-bin/mmwebwx-bin/synccheck?r=".time()."&skey=".$this->skey."&sid=".urlencode($this->sid)."&uin=".$this->uin."&deviceid=e".mt_rand(10000, 99999).time()."&synckey=".$this->synckey."&_=".time();
        $data = httpGet($url,$this->uin);
        if(strstr($data, 'retcode:"0",selector:"0"') ) return 0;
        if(strstr($data, 'retcode:"0"') ) return 2;
        if(strstr($data, 'retcode:"11') ) {
            mylog($data . "---".$url ."-------------".$this->uin );
            return 11;
        }

        else return $data;
    }

    public function webwxSync(){
        //global $uri,$skey,$sid,$uin,$pass_ticket,$uid,$nickname,$headImgUrl,$synckey,$SyncKey_orig;
        $synckey ="";
        $msgArr = array();
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxsync?sid=".urlencode($this->sid)."&skey=".$this->skey."&lang=zh_CN&pass_ticket=".$this->pass_ticket;

        $BaseRequest = '{"BaseRequest":{"DeviceID":"e'. mt_rand(10000, 99999).time().'","Sid":"'.$this->sid.'","Skey":"'.$this->skey.'","Uin":'.$this->uin.'},"SyncKey":'.$this->SyncKey_orig.',"rr":'.time().'} ';
        $data = httpPost($url,$BaseRequest);
        $info_obj = json_decode($data);

        //更新synckey
        $this->SyncKey_orig =json_encode($info_obj->SyncKey);
        foreach ($info_obj->SyncKey->List as $ll)
        {
            $key = $ll->Key . "_" . $ll->Val;
            $synckey = $key . "|" . $synckey;
        }
        $this->synckey = substr($synckey,0,strlen($synckey)-1);
        return $info_obj;
    }

    public function webwxgetgroup(){

        $grouplist = array();
        $seq = 0;
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxgetcontact?pass_ticket=".$this->pass_ticket."&r=".time()."&seq=".$seq."&skey=".$this->skey;

        do{
            $data = httpGet($url,$this->uin);
            $info_obj = json_decode($data);
            //return $data;
            $i = $info_obj->BaseResponse->Ret;
            if($i == "0"){
                foreach ($info_obj->MemberList as $mb)
                {
                    if (substr($mb->UserName,0,2) == "@@")
                    {//群组信息
                        $groupImg = $this->getGroupImg($mb->UserName);
                        //return $groupImg;
                        $arr = array('GroupID' => $mb->UserName,
                            'GroupImg'=>$groupImg,
                            'GroupName' => $mb->NickName);
                        array_push($grouplist,json_encode($arr,JSON_UNESCAPED_UNICODE));
                    }
                }
                $seq = $info_obj->Seq;
            }elseif(substr($i,0,2) == "11"){//已下线
                return 11;
            }else{
                mylog("webwxgetgroup:".$data);
                return -1;
            }
        }while($seq != "0");

        return $grouplist;
    }

    public function webwxSendmsg($content,$to){
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxsendmsg?lang=zh_CN&pass_ticket=".$this->pass_ticket;
        $BaseRequest = '{"BaseRequest":{"DeviceID":"e'. mt_rand(10000, 99999).time().'","Sid":"'.$this->sid.'","Skey":"'.$this->skey.'","Uin":'.$this->uin.'},"Msg":{"Type":1,"Content":"'.$content.'","FromUserName":"'.$this->uid.'","ToUserName":"'.$to.'","LocalID":"'.time().'","ClientMsgId":"'.time().'"},"Scene":0}';
        //echo($url.'</BR>'.$BaseRequest.'</br>');
        $data = httpPost($url,$BaseRequest);
        //var_dump($data);
        $info_obj = json_decode($data);
        $i = $info_obj->BaseResponse->Ret;
        if($i == "0"){
            return 0;
        }
        if(strstr($i, '11') ) {
            mylog("webwxSendmsg内部：".$data.">>>>>>>".$to.">>>>>".$url.">>>>>".$BaseRequest);
            return 11;
        }else return -1;
    }

    public function webwxsendmsgimg($mediaid,$content,$to){
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxsendmsgimg?fun=async&f=json&pass_ticket=".$this->pass_ticket;
        $BaseRequest = '{"BaseRequest":{"DeviceID":"e'. mt_rand(10000, 99999).time().'","Sid":"'.$this->sid.'","Skey":"'.$this->skey.'","Uin":'.$this->uin.'},"Msg":{"Type":3,"MediaId":"'.$mediaid.'","Content":"'.$content.'","FromUserName":"'.$this->uid.'","ToUserName":"'.$to.'","LocalID":"'.time().'","ClientMsgId":"'.time().'"},"Scene":0}';
        $data = httpPost($url,$BaseRequest,$this->uin);
        $info_obj = json_decode($data);
        $i = $info_obj->BaseResponse->Ret;
        if($i == "0"){
            return 0;
        }
        if($i == "1205"){
            mylog("webwxsendmsgimg 1205：".$data.">>>>>>>".$to.">>>>>".$url.">>>>>".$BaseRequest);
            return 1205;
        }
        if(strstr($i, '11') ) {
            mylog("webwxsendmsgimg：".$data.">>>>>>>".$to.">>>>>".$url.">>>>>".$BaseRequest);
            return 11;
        }else{
            mylog("webwxsendmsgimg：".$url.PHP_EOL.$BaseRequest.PHP_EOL.$data);
            return -1;
        }
    }

    public function logout(){
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxlogout?redirect=1&type=1&skey=".$this->skey;
        $BaseRequest ="sid=".$this->sid."&uin=".$this->uin;
        $data = httpPost($url,$BaseRequest,$this->uin);
        echo $data;
    }

    public function pushlogin($uin){
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxpushloginurl?uin=".$uin;
        $data = httpGet($url,$uin);
        $info_obj = json_decode($data);
        if($info_obj->ret == "0"){
            return $info_obj->uuid;
        }else{
            mylog("pushlogin:".$data);
            return -1;
        }

    }

    public function getIcon(){
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxgeticon?seq=1755106174&username=".$this->uid."&skey=".$this->skey;
        $data = httpGet($url,$this->uin);
        if(strlen($data)){
            file_put_contents(Dir_HeadImg.$this->uin.'.jpg',$data);
            return Net_HeadImg.$this->uin.'.jpg';
        }
        return -1;
    }

    public function getGroupImg($groupid){
        $url = "https://".$this->uri."/cgi-bin/mmwebwx-bin/webwxgetheadimg?seq=1755106174&username=".$groupid."&skey=".$this->skey;
        $data = httpGet($url,$this->uin);
        if(strlen($data)){
            file_put_contents(Dir_HeadImg.$groupid.'.jpg',$data);
            //压缩
            $percent = 0.2;  #原图压缩，缩放
            $image = (new imgcompress(Dir_HeadImg.$groupid.'.jpg',$percent))->compressImg(Dir_HeadImg."zip".$groupid.'.jpg');
            unlink(Dir_HeadImg.$groupid.'.jpg');
            return Net_HeadImg."zip".$groupid.'.jpg';
        }
        return -1;
    }

//运行时状态信息和方法******************************************************************************************///


    /********************************************************************///微信类
    //提取微信最后一次运行状态信息
    public function loadLastParam($uin){
        if(file_exists(Dir_Config.$uin)){
            $configs = file_get_contents(Dir_Config.$uin);
            $wxCfg = json_decode($configs);
        }else{
            mylog("LoadLastParam未能找到配置文件:".Dir_Config.$uin);
            return -1;//初始化失败，未能找到配置文件
        }
        $this->uin = $wxCfg->uin;
        $this->nickname = $wxCfg->nickname;
        $this->uid=$wxCfg->uid;
        $this->sid=$wxCfg->sid;
        $this->skey=$wxCfg->skey;
        $this->pass_ticket=$wxCfg->pass_ticket;
        $this->uri=$wxCfg->uri;
        $this->synckey=$wxCfg->synckey;
        $this->SyncKey_orig=$wxCfg->SyncKey_orig;
        $this->headImgUrl=$wxCfg->headImgUrl;
        return 1;
    }
    //记录/更新微信运行状态
    public function updateLastParam($uin){
        if(isset($this->uin)
            && isset($this->nickname)
            && isset($this->uid)
            && isset($this->sid)
            && isset($this->skey)
            && isset($this->pass_ticket)
            && isset($this->uri)
            && isset($this->synckey)
            && isset($this->SyncKey_orig)
            && isset($this->headImgUrl)){
            $i = file_put_contents(Dir_Config.$uin,json_encode($this),LOCK_EX);
            if($i > 0){
                return 1;
            }else{
                mylog("updateLastParam 写入失败:".$uin);
                return -1;//写入失败
            }
        }else{
            mylog("updateLastParam 还未产生必要的运行数据:".$uin);
            return -2;
        }
    }




    public function insertReceive($Content,$MsgType){
        $Content = addslashes($Content);
        $dt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO wxmsg_receive(uin,Content,MsgType,ReceiveTime,Flag)
				VALUES('$this->uin','$Content','$MsgType','$dt','0')";
        $res = mysqli_query(parent::$conn,$sql);
        mylog($sql);
        if($res){
            return mysqli_affected_rows(parent::$conn);
        }else{
            mylog("insertReceive Err>>".mysqli_error(parent::$conn)."插入失败|UIN:".$this->uin);
            return -1;
        }
    }
    public function updateReceive($id){
        $dt = date('Y-m-d H:i:s');
        $sql = "UPDATE wxmsg_receive SET Flag = 1 where id = ".$id;
        $res = mysqli_query(parent::$conn,$sql);
        if($res){
            return mysqli_affected_rows(parent::$conn);
        }else{
            return -1;
        }
    }
    //发送之前需要将之前未发送的置为2，否则容易导致图片 -1
    public function updateReceiveByUin($uin){
        $dt = date('Y-m-d H:i:s');
        $sql = "UPDATE wxmsg_receive SET Flag = 2 where Flag = 0 and uin = '".$uin."'";
        $res = mysqli_query(parent::$conn,$sql);
        if($res){
            return mysqli_affected_rows(parent::$conn);
        }else{
            return -1;
        }
    }



    public function getReceive(){
        $sql = "select * from wxmsg_receive where Flag = 0 and uin = '".$this->uin."'";
        $res = mysqli_query(parent::$conn,$sql);
        if($res){
            return $res;
        }else {
            mylog("error".mysqli_error(parent::$conn));
            return -1;
        }
    }

    public function insertSent($id,$MsgType){
        $dt = date('Y-m-d H:i:s');
        $sql = "INSERT INTO wxmsg_sent(id,uin,MsgType,SentTime,Flag) 
				VALUES('$id','$this->uin','$MsgType','$dt','1') ";
        $res =mysqli_query(parent::$conn,$sql);
        if($res){
            return mysqli_affected_rows(parent::$conn);
        }else {
            return -1;
        }
    }

    public function Threshold(){
        ///取前第100条数据，得到当时的发送时间
        $dt = date("Y-m-d H:i:s");
        $dt100 = null;
        $dt20 = null;
        $sql1 = "select id,SentTime from wxmsg_sent where Flag = 1 and MsgType = 3 and uin = '".$this->uin ."'order by SentTime desc limit 99,1";
        $sql2 = "select id,SentTime from wxmsg_sent where Flag = 1 and MsgType = 3 and uin = '".$this->uin ."'order by SentTime desc limit 19,1";
        $res1 = mysqli_query(parent::$conn,$sql1);
        $res2 = mysqli_query(parent::$conn,$sql2);

        if($res1){
            if(mysqli_num_rows($res1) > 0){
                $row1 = mysqli_fetch_assoc($res1);
                if($row1["SentTime"] != null){
                    $dt100 = $row1["SentTime"];
                    mylog( "100当前时间>>>>>>".$dt."  比对时间>>>>>".$dt100.">>>>ID:".$row1["id"]);

                }
            }
        }

        if($res2){
            if(mysqli_num_rows($res2) > 0){
                $row2 = mysqli_fetch_assoc($res2);
                if($row2["SentTime"] != null){
                    $dt20 = $row2["SentTime"];
                    mylog( "20当前时间>>>>>>".$dt."  比对时间>>>>>".$dt20.">>>>ID:".$row2["id"]);
                }
            }
        }

        if($dt100 != null){
            if(diffdate($dt100,$dt) < 3600){
                mylog("休眠60分钟>>".$dt);
                //usleep(1000000*3600);//有些服务器会报不安全，可以改为usleep(1000000*3600);
                sleep(3600);
            }
        }

        if($dt20 != null){
            if(diffdate($dt20,$dt) < 180){
                mylog( "休眠3分钟>>当前时间:".$dt);
                //usleep(1000000*180);
                sleep(180);
            }
        }
        //echo '现在时间:'.date().'>>>>>>最近时间差ts: '.$row["ts"].'>>>>>>最近一个小时累计total60: '.$row["total60"].'>>>>>>>>>最近3分钟累计total3: '.$row["total3"].'</br>';
    }


}
