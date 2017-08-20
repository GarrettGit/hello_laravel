<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;

class SessionsController extends Controller
{

    public function __construct()
      {
          $this->middleware('guest', [
              'only' => ['create']
          ]);
      }

    public function create(){
      return view('sessions.create');
    }

    //login验证
    public function store(Request $request){
      $this->validate($request,[
        'email'=>'required|email|max:255',
        'password' => 'required'
      ]);

      $credentials = [
           'email'    => $request->email,
           'password' => $request->password,
       ];

      if(Auth::attempt($credentials, $request->has('remember'))){
        //判断用户是否激活
          if(Auth::user()->activated){
              session()->flash('success', '欢迎回来！');
              //redirect() 实例提供了一个 intended 方法，该方法可将页面重定向到上一次请求尝试访问的页面上，
              //并接收一个默认跳转地址参数，当上一次请求记录为空时，跳转到默认地址上。
              return redirect()->intended(route('users.show', [Auth::user()]));
          }else{
               Auth::logout();
               session()->flash('warning', '你的账号未激活，请检查邮箱中的注册邮件进行激活。');
               return redirect('/');
          }

      }else{
        session()->flash('danger', '很抱歉，您的邮箱和密码不匹配');
        return redirect()->back();
      }
      return ;
    }
    //退出
    public function destroy()
    {
        Auth::logout();
        session()->flash('success', '您已成功退出！');
        return redirect('login');
    }
}
