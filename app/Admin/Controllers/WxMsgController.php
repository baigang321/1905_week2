<?php

namespace App\Admin\Controllers;

use App\Model\WxUserModel;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use GuzzleHttp\Client;
class WxMsgController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '微信用户管理';

    public  function  sendMsg(){
      //  echo  __METHOD__;
        $openid_arr=WxUserModel::select('openid',"nickname","sex")->get()->toArray();
//        print_r($openid_arr);
        $openid=array_column($openid_arr,'openid');
//        print_r($openid);
        $url='https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=28_bej1m6V34rNkZB1EBrcTjE08qsyLR02mc3By25SLtNXcI5zBs5_cVBJ1YrO1HQ9zSNF5kTBh-9vPbmMBkbHQztD4j9RrppPUxMpzGojZfbV5H2Sx4NuOaZ7Zafzv0sk_w0U3kwlYD2PQqi3cUQNfACAIEB';
        $msg=date("Y-m-d H:i:s")."快过年了";
        $data=[
            'touser'=>$openid,
            'msgtype'=>"text",
            'text'=>['content'=>$msg]
        ];
        $client=new Client;
        $request=$client->request('POST',$url,['body'=>json_encode($data,JSON_UNESCAPED_UNICODE)
        ]);
        echo $request->getBody();
    }
}
