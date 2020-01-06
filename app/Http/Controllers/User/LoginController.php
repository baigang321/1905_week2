<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\UserModel;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function addUser(){

        $pass = '123123';
        //使用hash密码加密
        $password = password_hash($pass,PASSWORD_BCRYPT);
        $email = 'zhangsan@qq.com';
        $user_name = Str::random(8);
        $data = [
            'user_name' => $user_name,
            'password' => $password,
            'email' => $email,
        ];
        $userInfo = UserModel::insert($data);
        if($userInfo){
            return redirect("test/adduserdo");
        }
    }
    public  function  adduserdo(){
        $data=UserModel::get();
      //  dd($data);
        return view("user.adduserdo",['data'=> $data]);
    }
    public function destroy($id){
        if(!$id){
            abort($id);
        }
        $res=UserModel::destroy($id);
        if($res){
            return redirect("test/adduserdo");
        }

    }

}