<?php

namespace App\Http\Controllers\Goods;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\GoodsModel;
class IndexController extends Controller
{
    public  function  detail(Request $request){
        $goods_id=$request->input('id');
        $goods=GoodsModel::find($goods_id);
//        print_r ($goods->toArray());die;
        $data=['goods'=>$goods];
        return view("goods.detail",$data);
    }
}
