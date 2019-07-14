<?php
class jd
{
    /////////////////////////////////////
    public function zl($content,$pid){
        $contentLines =  explode("<br/>",$content);//打散成数组<br/>
        $is_secondHand = false;
        $is_zhaoshang = false;
        $skus= array();
        $couponLinks = array();
        //全部找出
        $urlpattern = "~[a-zA-z]+://[./a-zA-z0-9]*~";
        $skupattern = "~[1-9][0-9]{4,}~";
        if(preg_match_all($urlpattern,$content,$match)){
            $urls = $match[0];
        }
        if(preg_match_all($skupattern,$content,$match)){
            $skus = $match[0];
        }

        //print_r($urls);
        //print_r($skus);
        //判断
        if(count($skus) == 0){
            //全是二次链
            $is_secondHand = true;
        }else{
            if(count($urls) ==count($skus)){
                //全是招商链
                $is_zhaoshang = true;
            }
        }

        //得到优惠券链接/SKU链接
        if($is_zhaoshang){
            //解析优惠券链条，得到原始优惠券链接
            foreach($urls as $shortUrl){
                $couponLink = $shortUrl;//取不到则为原链
                if(strpos($shortUrl,'t.cn') !== false){//支持t.cn链
                    $Link = httpGetLocation($shortUrl);
                    if(strpos($Link,'https://coupon.m.jd.com') !== false){
                        $couponLink = $Link;
                    }
                }
                array_push($couponLinks,$couponLink);
            }
        }
        if($is_secondHand){
            $hrl ="";
            foreach($urls as $shortUrl){
                if(strpos($shortUrl,'u.jd.com') !== false){//支持u.jd.com链
                    //Step 1
                    $result = httpGet($shortUrl);
                    $pattern = "~hrl=\'(\\S+?)\';~";
                    $pp = preg_match($pattern,$result,$match);
                    if($pp){
                        $hrl = $match[1];
                    }

                    //Step 2
                    if($hrl !=""){
                        $Link = httpGetLocation($hrl);
                        if(strpos($Link,'item.jd.com') !== false){//支持京东联盟
                            $pattern = "~item.jd.com/(\\S+?)\.html~";
                            preg_match($pattern,$Link,$match);
                            $sku = $match[1];
                        }
                        if(strpos($Link,'jingfen.jd.com') !== false){//支持京粉
                            $pattern = "~sku=(\\S+?)\&~";
                            preg_match($pattern,$Link,$match);
                            $sku = $match[1];
                        }
                        if(strpos($Link,'ifanli.m.jd.com') !== false){//支持ifanli
                            $pattern = "~skuId=(\\S+?)\&~";
                            preg_match($pattern,$Link,$match);
                            $sku = $match[1];
                        }
                    }

                }
                array_push($skus,$sku);
            }

            //获取sku对应的优惠券链
            $couponLinks = $this->getCouponLinkBySku($skus);
        }


        //优惠券/sku都准备好了，开始转链
        $replaceUrls = array();
        for($i = 0;$i<count($urls);$i++){
            $materialUrl = "https://item.jd.com/".$skus[$i].".html";

            $newShortUrl = $this->getShortUrl($materialUrl,empty($couponLinks[$i]) ? "" :$couponLinks[$i],empty($pid) ? "" :$pid);
            if(empty($newShortUrl)){//如果没有得到新链，无论如何，直接转换SKU的链给用户，比如有些有些优惠券的链接已经过期
                $newShortUrl = $this->getShortUrl($materialUrl);
            }
            $arr = array($urls[$i] => $newShortUrl);
            array_push($replaceUrls,$arr);//可能出现 oldUrl=>''的情况
        }

        foreach($contentLines as $i => $line){
            foreach($replaceUrls as $key => $value){
                if(strpos($line,key($value)) !== false){
                    $contentLines[$i] = $value[key($value)];//替换
                }
            }
            foreach($skus as $sku){
                if(strpos($line,$sku) !== false){
                    $contentLines[$i] = "";//替换
                }
            }
        }
        $newcontent = implode("\n",$contentLines);
        $newcontent = getEmoji($newcontent);
        return $newcontent;
    }


    /////////////////////////////////////
    public function getShortUrl($materialId,$couponUrl="",$positionId=""){
        $shorturl="";
        $dt = date("Y-m-d H:i:s");
        $param_json = '{"promotionCodeReq":{"materialId":"' . $materialId .'","unionId":"'. Jd_unionId .'","couponUrl":"'. $couponUrl .'","positionId":"'. $positionId .'"}}';
        $tmp3 = Jd_appSecret . "app_key" . Jd_appKey . "formatjsonmethodjd.union.open.promotion.byunionid.getparam_json" . $param_json . "sign_methodmd5timestamp" . $dt . "v1.0" . Jd_appSecret;
        $sign = strtoupper(md5($tmp3));
        $dt= strtr($dt, ':', 'zz'); // zesz
        $dt=rawurlencode($dt);
        $dt= strtr($dt, 'zz', ':'); // zesz

        $url = "https://router.jd.com/api?v=1.0&method=jd.union.open.promotion.byunionid.get&access_token=&app_key=" .Jd_appKey . "&sign_method=md5&format=json&sign=" . $sign . "&timestamp=" . $dt . "&param_json=" . urlencode($param_json);
        $data = httpGet($url);
        $info_obj = json_decode($data,true);

        if(isset($info_obj["errorResponse"])){
            mylog($data);
        }else{
            $result = json_decode($info_obj["jd_union_open_promotion_byunionid_get_response"]["result"],true);
            if($result["code"] == 200){
                if(isset($result["data"])){
                    $shorturl = $result["data"]["shortURL"];
                }
            }else{
                mylog($data.$result["message"] .'{materialId:'.$materialId.'couponUrl:'.$couponUrl.'}');
            }
        }
        return $shorturl;
    }

    //根据skuid获取优惠券链接
    public function getCouponLinkBySku($skuArr){
        $couponArr= array();
        $dt = date("Y-m-d H:i:s");
        //string param_json = "{\"goodsReqDTO\":{\"skuIds\":[\"" + SkuId + "\"]}}";
        $param_json = '{"goodsReqDTO":{"skuIds":["'.implode("\",\"", $skuArr).'"]}}';
        //echo $param_json.'</br>';
        $tmp3 = Jd_appSecret . "app_key" . Jd_appKey . "formatjsonmethodjd.union.open.goods.queryparam_json" . $param_json . "sign_methodmd5timestamp" . $dt . "v1.0" . Jd_appSecret;
        $sign = strtoupper(md5($tmp3));
        $dt= strtr($dt, ':', 'zz'); // zesz
        $dt=rawurlencode($dt);
        $dt= strtr($dt, 'zz', ':'); // zesz

        $url = "https://router.jd.com/api?v=1.0&method=jd.union.open.goods.query&access_token=&app_key=" .Jd_appKey . "&sign_method=md5&format=json&sign=" . $sign . "&timestamp=" . $dt . "&param_json=" . $param_json;
        $data = httpGet($url);
        $info_obj = json_decode($data,true);

        if(isset($info_obj["errorResponse"])){
            mylog('getCouponLinkBySku>>'.$data);
        }else{
            //var_dump($info_obj);
            $result = json_decode($info_obj["jd_union_open_goods_query_response"]["result"],true);
            if($result["code"] == 200){
                if(isset($result["data"])){
                    foreach($result["data"] as $sku){
                        if(count($sku["couponInfo"]["couponList"]) > 0){
                            array_push($couponArr,$sku["couponInfo"]["couponList"][0]["link"]);
                        }else{
                            array_push($couponArr,"");
                            mylog('没有对应的优惠券链接' .'{sku:'.$sku["skuId"].'}');
                        }
                    }
                }
            }else{
                //echo $result["message"];
                mylog($result["message"] .'{$skuArr:'.implode(',',$skuArr).'}');
            }
        }
        return $couponArr;
    }


    //根据关键词查找商品
    public function getProductByKeyWord($keywords){
        $products= array();
        $dt = date("Y-m-d H:i:s");
        $param_json = '{"goodsReqDTO":{"keyword":'.$keywords.'}}';
        //echo $param_json.'</br>';
        $tmp3 = Jd_appSecret . "app_key" . Jd_appKey . "formatjsonmethodjd.union.open.goods.queryparam_json" . $param_json . "sign_methodmd5timestamp" . $dt . "v1.0" . Jd_appSecret;
        $sign = strtoupper(md5($tmp3));
        $dt= strtr($dt, ':', 'zz'); // zesz
        $dt=rawurlencode($dt);
        $dt= strtr($dt, 'zz', ':'); // zesz

        $url = "https://router.jd.com/api?v=1.0&method=jd.union.open.goods.query&access_token=&app_key=" .Jd_appKey . "&sign_method=md5&format=json&sign=" . $sign . "&timestamp=" . $dt . "&param_json=" . $param_json;
        $data = httpGet($url);
        $info_obj = json_decode($data,true);

        if(isset($info_obj["errorResponse"])){
            mylog('getCouponLinkBySku>>'.$data);
        }else{
            //var_dump($info_obj);
            $result = json_decode($info_obj["jd_union_open_goods_query_response"]["result"],true);
            if($result["code"] == 200){
                if(isset($result["data"])){
                    foreach($result["data"] as $sku){
                        if(count($sku["couponInfo"]["couponList"]) > 0){
                            $couponLink = $sku["couponInfo"]["couponList"][0]["link"];
                        }else{
                            $couponLink="";
                        }
                        $skuNew["skuname"] = $sku["skuName"];
                        $skuNew["materialUrl"] = $sku["materialUrl"];
                        $skuNew["imageUrl"] = $sku["imageInfo"]["imageList"][0]["url"];
                        $skuNew["couponLink"]=$couponLink;
                        array_push($products,json_encode($skuNew));
                    }
                }
            }else{
                //echo $result["message"];
                mylog("getProductByKeyWord:".$data);
            }
        }
        return $products;
    }


    public static function getNewPid($NewName){//获取新的代理号  //标记位静态方法，方便在注册时候调用

        $param_json = "{\"positionReq\":{\"unionId\":" . Jd_unionId .",\"key\":\"" . Jd_key ."\",\"unionType\":1,\"type\":4,\"spaceNameList\":[\"" . $NewName . "\"]}}";
        $dt = date("Y-m-d H:i:s");
        $tmp3 = Jd_appSecret . "app_key" . Jd_appKey . "formatjsonmethodjd.union.open.position.createparam_json" . $param_json . "sign_methodmd5timestamp" . $dt . "v1.0" . Jd_appSecret;
        $sign = strtoupper(md5($tmp3));
        $dt= strtr($dt, ':', 'zz'); // zesz
        $dt=rawurlencode($dt);
        $dt= strtr($dt, 'zz', ':'); // zesz
        $url = "https://router.jd.com/api?v=1.0&method=jd.union.open.position.create&access_token=&app_key=" .Jd_appKey . "&sign_method=md5&format=json&sign=" . $sign . "&timestamp=" . $dt . "&param_json=" . $param_json;
        $data = httpGet($url);
        $respObject = json_decode($data,true);
        if(isset($respObject['errorResponse']))
        {
            mylog("getNewPid error>>>>".$data.">>>>>url:".$url);
            return -1;
        }
        else
        {
            $tmp4 = $respObject["jd_union_open_position_create_response"]["result"];
            $result = json_decode($tmp4,true);
            $code = $result["code"];
            if($code == 200)
            {
                $newPid =$result['data']['resultList'][$NewName];
                return $newPid;
            }else{
                mylog("getNewPid error".$data);
                return -2;
            }
        }
    }


    public static function getCouponInfo($CouponLink){//根据优惠券链接获取优惠券信息

        $param_json = "{\"positionReq\":{\"unionId\":" . Jd_unionId .",\"key\":\"" . Jd_key ."\",\"unionType\":1,\"type\":4,\"spaceNameList\":[\"" . $NewName . "\"]}}";
        $dt = date("Y-m-d H:i:s");
        $tmp3 = Jd_appSecret . "app_key" . Jd_appKey . "formatjsonmethodjd.union.open.coupon.queryparam_json" . $param_json . "sign_methodmd5timestamp" . $dt . "v1.0" . Jd_appSecret;
        $sign = strtoupper(md5($tmp3));
        $dt= strtr($dt, ':', 'zz'); // zesz
        $dt=rawurlencode($dt);
        $dt= strtr($dt, 'zz', ':'); // zesz
        $url = "https://router.jd.com/api?v=1.0&method=jd.union.open.coupon.query&access_token=&app_key=" .Jd_appKey . "&sign_method=md5&format=json&sign=" . $sign . "&timestamp=" . $dt . "&param_json=" . $param_json;
        $data = httpGet($url);
        $respObject = json_decode($data,true);
        if(isset($respObject['errorResponse']))
        {
            mylog("getNewPid error>>>>".$data.">>>>>url:".$url);
            return -1;
        }
        else
        {
            $tmp4 = $respObject["jd_union_open_position_create_response"]["result"];
            $result = json_decode($tmp4,true);
            $code = $result["code"];
            if($code == 200)
            {
                $newPid =$result['data']['resultList'][$NewName];
                return $newPid;
            }else{
                mylog("getNewPid error".$data);
                return -2;
            }
        }
    }

    function generateSign($params,$appSecret){
        ksort($params);
        $stringToBeSigned = $appSecret;
        foreach ($params as $k => $v)
        {
            if("@" != substr($v, 0, 1))
            {
                $stringToBeSigned .= "$k$v";
            }
        }
        unset($k, $v);
        $stringToBeSigned .= $appSecret;
        return strtoupper(md5($stringToBeSigned));
    }

    function get_microtime_format($time){
        if(strstr($time,'.')){
            sprintf("%01.3f",$time); //小数点。不足三位补0
            list($usec, $sec) = explode(".",$time);
            $sec = str_pad($sec,3,"0",STR_PAD_RIGHT); //不足3位。右边补0
        }else{
            $usec = $time;
            $sec = "000";
        }
        $date = date("Y-m-d H:i:s.x",$usec);
        return str_replace('x', $sec, $date);
    }
}
