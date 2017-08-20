<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Auth;
use Mail;

class UsersController extends Controller
{
    public function __construct(){
      //中间件
      //中间件except 方法来设定 指定动作 不使用 Auth 中间件进行过滤
      $this->middleware('auth',[
        'except'=>['show','create','store','index','confirmEmail']
      ]);
      //Auth 中间件提供的 guest 选项，用于指定一些只允许未登录用户访问的动作
      //只让未登录用户访问注册页面
      $this->middleware('guest', [
              'only' => ['create']
          ]);
    }

    //用户列表
    public function index(){
      //使用 paginate 方法来指定每页生成的数据数量为 10 条
      //在调用 paginate 方法获取用户列表之后，便可以通过以下代码在用户列表页上渲染分页链接。
      //{!! $users->render() !!}
      $users = User::paginate(10);
      return view('users.index',compact('users'));
    }


    public function create()
    {
        return view('users.create');
    }
    /**
     * 用户个人界面
     * @param  User   $user [description]
     * @return [type]       [description]
     */
    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    //用户注册
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required'
        ]);

        //数据入库
        $user = User::create([
          'name'=>$request->name,
          'email'=>$request->email,
          'password'=>bcrypt($request->password),
        ]);
        //注册成功后自动登录
        //Auth::login($user);  这里换成激活邮件发送
        $this->sendEmailConfirmationTo($user);

        //存入一条缓存的数据，让它只在下一次的请求内有效
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');

        return redirect('/');
    }

    //发送邮件
    /**
     * 发送激活邮件
     * @param  [type] $user [description]
     * @return [type]       [description]
     */
    protected function sendEmailConfirmationTo($user)
    {
        $view = 'emails.confirm';
        $data = compact('user');
        $from = 'aufree@yousails.com';
        $name = 'Aufree';
        $to = $user->email;
        $subject = "感谢注册 Sample 应用！请确认你的邮箱。";
        //Mail 的 send 方法接收三个参数。
        //第一参数是包含邮件消息的视图名称。
        //第二个参数是要传递给该视图的数据数组。
        //最后是一个用来接收邮件消息实例的闭包回调，我们可以在该回调中自定义邮件消息的发送者、接收者、邮件主题等信息。
        Mail::send($view, $data, function ($message) use ($from, $name, $to, $subject) {
            $message->from($from, $name)->to($to)->subject($subject);
        });
    }

    //编辑
    public function edit(User $user)
    {
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }

    //更新
    public function update(User $user,Request $request){
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'nullable|confirmed|min:6'
        ]);
        //通过在用户控制器中使用 authorize 方法来验证用户授权策略
//authorize 方法接收两个参数，第一个为授权策略的名称，第二个为进行授权验证的数据。
        $this->authorize('update', $user);

        $data = [];
        $data['name'] = $request->name;
        if($request->password){
          $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        session()->flash('success', '个人资料更新成功！');
         return redirect()->route('users.show', $user->id);
    }

    //用户删除
    public function destroy(User $user){
        //授权判断
      $this->authorize('destroy', $user);

      $user->delete();
      session()->flash('success', '成功删除用户'.$user->name.'！');
       return back();
    }
    /**
     * 邮件激活函数
     * @param  [type] $token [description]
     * @return [type]        [description]
     */
    public function confirmEmail($token)
    {
        //我们需要使用 firstOrFail 方法来取出第一个用户，在查询不到指定用户时将返回一个 404 响应
        $user = User::where('activation_token', $token)->firstOrFail();

        //在查询到用户信息后，我们会将该用户的激活状态改为 true，激活令牌设置为空。

        $user->activated = true;
        $user->activation_token = null;
        $user->save();
        //最后将激活成功的用户进行登录，并在页面上显示消息提示和重定向到个人页面。
        Auth::login($user);
        session()->flash('success', '恭喜你，激活成功！');
        return redirect()->route('users.show', [$user]);
    }
}
