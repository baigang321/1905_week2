<?php

namespace App\Http\Controllers\WeiXin;

use App\Http\Controllers\Controller;
use App\Model\MsgModel;
use Illuminate\Http\Request;
use App\Model\WxUserModel;
use Illuminate\Support\Facades\Redis;
//use Facade\FlareClient\Http\Client;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
class WxController extends Controller
{
    protected $access_token;
    public function  __construct()
    {
       //获取access_token
        $this->access_token=$this->getAccessToken();
    }
    public  function  test(){
        echo $this->access_token;
    }
    protected   function  getAccessToken(){
        $key="wx_access_token";
        $access_token=Redis::get($key);
        if($access_token){
            return $access_token;
        }
        $url='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env("WX_APPID").'&secret='.env("WX_APPSECRET");
        $data_json=file_get_contents($url);
        $arr=json_decode($data_json,true);
        Redis::set($key,$arr['access_token']);
        Redis::expire($key,3600);
        return $arr['access_token'];

    }
    //处理接入
    public function wechat()
    {
        $token = '12259b56f5898cd6192c50';       //开发提前设置好的 token
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $echostr = $_GET["echostr"];
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);
        if ($tmpStr == $signature) {        //验证通过
            print_r($echostr);
        } else {
            die("not ok");
        }
    }
    //接收微信推送事件
    public  function  receiv(){
        $log_file="wx.log";
        $xml_str=file_get_contents("php://input");

        //将接收的"数据记录到日志文件
        $data=date("Y-m-d H:i:s").$xml_str;
        file_put_contents($log_file,$data,FILE_APPEND);
        //处理xml数据
        $xml_obj=simplexml_load_string($xml_str);
     //   var_dump($xml_str);die;
        $event=$xml_obj->Event; //类型
        $openid=$xml_obj->FromUserName;    //获取用户的openid
        if($event=='subscribe'){
            $u =WxUserModel::where(["openid"=>$openid])->first();
            if($u){
                $msg = '欢迎进入选课程';
                $xml = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                      <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
                      <CreateTime>'.time().'</CreateTime>
                      <MsgType><![CDATA[text]]></MsgType>
                      <Content><![CDATA['.$msg.']]></Content>
                </xml>';
                echo $xml;
            }else{
                $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->access_token.'&openid='.$openid.'&lang=zh_CN';
                $user_info = file_get_contents($url);
                $u = json_decode($user_info,true);
                $user_data =[
                    'openid' => $openid,
                    'nickname' => $u['nickname'],
                    'sex' => $u['sex'],
                    'headimgurl' => $u['headimgurl'],
                    'subscribe_time' => $u['subscribe_time']
                ];
                $uid = WxUserModel::insertGetId($user_data);
                $msg = "谢谢关注";
                //回复用户关注
                $xml = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                          <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
                          <CreateTime>'.time().'</CreateTime>
                          <MsgType><![CDATA[text]]></MsgType>
                          <Content><![CDATA['.$msg .']]></Content></xml>';
                echo $xml;
            }
        }elseif($event=='CLICK'){
            // 菜单点击事件
            if($xml_obj->EventKey=='weather'){
                //如果是 获取天气
                //请求第三方接口 获取天气
                $weather_api='https://free-api.heweather.net/s6/weather/now?location=beijing&key=6a12ef5492144cbd8795e92cfb507212';
                $weather_info = file_get_contents($weather_api);
                $weather_info_arr = json_decode($weather_info,true);
                $cond_txt = $weather_info_arr['HeWeather6'][0]['now']['cond_txt'];
                $tmp = $weather_info_arr['HeWeather6'][0]['now']['tmp'];
                $wind_dir = $weather_info_arr['HeWeather6'][0]['now']['wind_dir'];
                $msg = $cond_txt . ' 温度： '.$tmp . ' 风向： '. $wind_dir;
                $response_xml = '<xml>
                      <ToUserName><![CDATA['.$openid.']]></ToUserName>
                      <FromUserName><![CDATA['.$xml_obj->ToUserName.']]></FromUserName>
                      <CreateTime>'.time().'</CreateTime>
                      <MsgType><![CDATA[text]]></MsgType>
                      <Content><![CDATA['. date('Y-m-d H:i:s').$msg .']]></Content>
                    </xml>';
                echo $response_xml;
            }
        }
        //判断消息类型
        $msg_type = $xml_obj->MsgType;
        $touser = $xml_obj->FromUserName;           //接收消息得到用户openid
        $formuser = $xml_obj->ToUserName;           //自己开发的公众号的id
        $time = time();
        $media_id = $xml_obj->MediaId;
        if($msg_type=='text'){
            $content = date('Y-m-d H:i:s').$xml_obj->Content;
            $contents =$xml_obj->Content;
            $response_text = '<xml>
                <ToUserName><![CDATA['.$touser.']]></ToUserName>
                <FromUserName><![CDATA['.$formuser.']]></FromUserName>
                <CreateTime>'.$time.'</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA['.$content.']]></Content>
                </xml>';
                  echo $response_text;        //回复用户消息
                    //消息入库
            $xiaoxi =[
                'openid' => $touser,
                'message' => $contents,
            ];
            $res = MsgModel::insertGetId($xiaoxi);
            }elseif($msg_type=='image'){ //图片消息
                //下载文件
                $this->getMedia2($media_id,$msg_type);
                //回复图片
            $response = '<xml>
                      <ToUserName><![CDATA['.$touser.']]></ToUserName>
                      <FromUserName><![CDATA['.$formuser.']]></FromUserName>
                      <CreateTime>'.time().'</CreateTime>
                      <MsgType><![CDATA[image]]></MsgType>
                      <Image>
                        <MediaId><![CDATA['.$media_id.']]></MediaId>
                      </Image>
                    </xml>';
              echo $response;
//            $xiaoxi =[
//                'openid' => $touser,
//                'message' => $image,
//            ];
//            $res = MsgModel::insertGetId($xiaoxi);
            }elseif($msg_type=='voice'){ //语言消息

                //下载语音
                $this->getMedia2($media_id,$msg_type);

                //回复语音
            $response = '<xml>
                      <ToUserName><![CDATA['.$touser.']]></ToUserName>
                      <FromUserName><![CDATA['.$formuser.']]></FromUserName>
                      <CreateTime>'.time().'</CreateTime>
                      <MsgType><![CDATA[voice]]></MsgType>
                      <Voice>
                        <MediaId><![CDATA['.$media_id.']]></MediaId>
                      </Voice>
                    </xml>';
            echo $response;
        }elseif($msg_type=='video'){
            // 下载小视频
            $this->getMedia2($media_id,$msg_type);
            // 回复
            $response = '<xml><ToUserName><![CDATA['.$touser.']]></ToUserName>
                <FromUserName><![CDATA['.$formuser.']]></FromUserName>
                <CreateTime>'.time().'</CreateTime>
                <MsgType><![CDATA[video]]></MsgType>
                <MediaId><![CDATA['.$media_id.']]></MediaId>
                <ThumbMediaId><![CDATA[_Zv6fn1sysyO8C8L7kmHz29e0dmkElgk1KSAFeu5HxKPlW5hP6dSKMlqRbmfe4lR]]></ThumbMediaId>
                <MsgId>22567372236573458</MsgId>
                </xml>';
            echo $response;
        }


    }
    //获取用户基本信息
    public  function  getUserInfo($access_token,$openid){
     $url='https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token.'&openid='.$openid.'&lang=zh_CN';
     //发送网络请求
        $json_str=file_get_contents($url);
        $log_file='wx_user.log';
        file_put_contents($log_file,$json_str,FILE_APPEND);
    }
        public function getMedia(){
        $media_id='9X_DpNTKScInJJ2szHwGgKBaUsrv2WAO_mTIvjlWMOqIonk79gTJKH_tgvIXI6Gk';
        $url='https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->access_token.'&media_id='.$media_id;
        $data=file_get_contents($url);
            $file_name = date('YmdHis').mt_rand(11111,99999).'.amr';
            file_put_contents($file_name,$data);
            echo "下载素材成功";echo '</br>';
            echo "文件名： ". $file_name;
        }
    protected function getMedia2($media_id,$media_type)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->access_token.'&media_id='.$media_id;

        //获取素材内容
        $client = new Client();
//        echo 1;die;
        $response = $client->request('GET',$url);
        //获取文件扩展名
//        var_dump($response);die;
        $f = $response->getHeader('Content-disposition')[0];
//        dd($f);
        $extension = substr(trim($f,'"'),strpos($f,'.'));
        //获取文件内容
        $file_content = $response->getBody();
        // 保存文件
        $save_path = 'wx_media/';
        if($media_type=='image'){       //保存图片文件
            $file_name = date('YmdHis').mt_rand(11111,99999).$extension;
            $save_path = $save_path.'imgs/'.$file_name;
        }elseif($media_type=='voice'){  //保存语音文件
            $file_name = date('YmdHis').mt_rand(11111,99999).$extension;
            $save_path = $save_path.'voice/'.$file_name;
        }elseif($media_type=='video')
        {
            $file_name = date('YmdHis').mt_rand(11111,99999).$extension;
            $save_path = $save_path.'video/'.$file_name;
        }
        file_put_contents($save_path,$file_content);
    }
    public function flushAccessToken()
    {
        $key ='wx_access_token';
        Redis::del($key);
        echo $this->getAccessToken();
    }
    public function createMenu()
    {
        $url = 'http://1905baigang.comcto.com/vote';
        $url2 = 'http://1905baigang.comcto.com/';
        $redirect_uri = urlencode($url);        //授权后跳转页面
        $redirect_uri2 = urlencode($url2);
        //创建自定义菜单的接口地址
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->access_token;
        $menu = [
            'button'    => [
                [
                    'type'  => 'click',
                    'name'  => '获取天气',
                    'key'   => 'weather'
                ],
                [
                    'type'  => 'view',
                    'name'  => '查看课程',
                    'url'   => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxcdf7b27e5b700e3e&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_userinfo#wechat_redirect',
                ],
                [
                    'type'  => 'view',
                    'name'  => '课程管理',
                    'url'   => 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wxcdf7b27e5b700e3e&redirect_uri='.$redirect_uri2.'&response_type=code&scope=snsapi_userinfo#wechat_redirect',
                ],
            ]
        ];
        $menu_json = json_encode($menu,JSON_UNESCAPED_UNICODE);
        $client = new Client();
        $response = $client->request('POST',$url,[
            'body'  => $menu_json
        ]);
        echo '<pre>';print_r($menu);echo '</pre>';
        echo $response->getBody();      //接收 微信接口的响应数据
    }
    public  function  sendMsg(){
        //获取天气
        $weather_api='https://free-api.heweather.net/s6/weather/now?location=beijing&key=6a12ef5492144cbd8795e92cfb507212';
        $weather_info = file_get_contents($weather_api);
        $weather_info_arr = json_decode($weather_info,true);
        $cond_txt = $weather_info_arr['HeWeather6'][0]['now']['cond_txt'];
        $tmp = $weather_info_arr['HeWeather6'][0]['now']['tmp'];
        $wind_dir = $weather_info_arr['HeWeather6'][0]['now']['wind_dir'];
        $msg = $cond_txt . ' 温度： '.$tmp . ' 风向： '. $wind_dir;
        echo $msg;echo "\n";

        $openid_arr=WxUserModel::select('openid',"nickname","sex")->get()->toArray();
//        print_r($openid_arr);
        $openid=array_column($openid_arr,'openid');
//        print_r($openid);
        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=28_F2LDWMj-sBInIpOdtNFp_BInyB-ORUKNf37McAg-V-t0aHM1yiyBdTE2nq19U3jT70vUYzVON68NTMlay8z4nYhE5aKpgmAw1KNSh5xfUnPd13tfUk6PQUPWXLb0Fd0Xwe9zvgYBciqe3L8JXJCgAHADPI';
        $msg=date("Y-m-d H:i:s")." 最后的轻语".$msg;
        $data=[
            'touser'=>$openid,
            'msgtype'=>"text",
            'text'=>['content'=>$msg]
        ];
        $client=new Client;
        $request=$client->request('POST',$url,['body'=>json_encode($data,JSON_UNESCAPED_UNICODE)
        ]);
        echo $request->getBody();echo "\n";
    }
    //元旦活动
    public function newYear()
    {
        $wx_appid = env('WX_APPID');
        $noncestr = Str::random(8);
        $timestamp = time();
        $url = env('APP_URL') . $_SERVER['REQUEST_URI'];    //当前页面的URL
        $signature = $this->signature($noncestr,$timestamp,$url);

        $data = [
            'appid'         => $wx_appid,
            'timestamp'     => $timestamp,
            'noncestr'      => $noncestr,
            'signature'     => $signature
        ];

        return view('weixin.newyear',$data);
    }
    // 计算jsapi签名
    public function signature($noncestr,$timestamp,$url)
    {
        $noncestr = $noncestr;
        // 1 获取 jsapi ticket
        $ticket = WxUserModel::getJsapiTicket();
        // 拼接带签名字符串
        $string1 = "jsapi_ticket={$ticket}&noncestr={$noncestr}&timestamp={$timestamp}&url={$url}";
        // sha1
        return  sha1($string1);
    }
}
