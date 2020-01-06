<?php

namespace App\Http\Controllers\Test;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
class TestController extends Controller
{
   	public function hello(){
		echo "hello work aa 123 901123";
	}
    public function redis1(){
        $key="weixin";
        $val="hello world";
        Redis::set($key,$val);
        echo date("Y-m-d H:i:s");
    }
    public  function  xmlTest(){
        $xml_str = "<xml><ToUserName><![CDATA[gh_0d48a37849d5]]></ToUserName>
                    <FromUserName><![CDATA[oBLwLwwM1EWE1nQLvw5lfwPXDyjY]]></FromUserName>
                    <CreateTime>1575889906</CreateTime>
                    <MsgType><![CDATA[text]]></MsgType>
                    <Content><![CDATA[aa]]></Content>
                    <MsgId>22561320937880757</MsgId>
                    </xml>";
        $xml_obj=simplexml_load_string($xml_str);
        echo '<pre>';print_r($xml_obj);echo '</pre>';echo '</br>';
        echo 'ToUserName:'.$xml_obj->ToUserName;echo "</br>";
        echo 'FromUserName:'.$xml_obj->FromUserName;echo "</br>";
    }
}
