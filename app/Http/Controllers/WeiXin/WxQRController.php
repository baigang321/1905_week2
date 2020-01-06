<?php

namespace App\Http\Controllers\WeiXin;

use App\Http\Controllers\Controller;
use App\Model\WxUserModel;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
class WxQRController extends Controller
{
    public function qrcode(){
        $scene_id=$_GET['scene'];
        $access_token=WxUserModel::getAccessToken();
        $url='https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$access_token;
//      {"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}
        $data1=[
                'expire_seconds'=>604800,
                'action_name'=>'QR_SCENE',
                'action_info'=>[
                    'scene'=>[
                        'scene_id'=>$scene_id,
                    ]
                ]
        ];
        $client=new Client();
        $request=$client->request('POST',$url,['body'=>json_encode($data1)
        ]);
        $json1=$request->getBody();
        $tiket=json_decode($json1,true)['ticket'];
        $url='https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$tiket;
        return redirect($url);
    }
}
