<?php

class Author
{
//token产生
    public static function generateToken($username){
        $userinfo = array(
            "username" =>$username,
            "expireTime"=>date("Y-m-d",strtotime("+1 month")),
            "timestamp"=>time()//时间戳
        );
        $info = base64_encode(json_encode($userinfo));//序列化
        $sign = hash_hmac('md5',$info,'youneverkonwyourluck');//签名
        $token = $info .'.'.$sign;
        return $token;
    }
//token合法性验证
    public static function tokenPass($token){
        if(empty($token)){
            return -4;//非法请求,令牌为空
        }else{
            $info = explode('.',$token);
            $sign = hash_hmac('md5',$info[0],'youneverkonwyourluck');
            if(count($info) == 2){
                if($sign == $info[1]){
                    $infoObj = json_decode(base64_decode($info[0]));
                    $dt = date("Y-m-d H:i:s");
                    if(strtotime($dt)<strtotime($infoObj->expireTime)){
                        return 1;//通过
                    }else{
                        return -2;//token有效期失效
                    }
                }else{
                    return -1;//无效token
                }
            }else{
                return -3;//无效token
            }
        }
    }
//解析token得到username
    public static function getUname($token){
        if(empty($token)){
            return -4;//非法请求
        }else{
            $info = explode('.',$token);
            $sign = hash_hmac('md5',$info[0],'youneverkonwyourluck');
            if(count($info) == 2){
                if($sign == $info[1]){
                    $infoObj = json_decode(base64_decode($info[0]));
                    return $infoObj->username;
                }else{
                    return -1;//无效token
                }
            }else{
                return -3;//无效token
            }
        }
    }

}
