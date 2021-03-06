<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\ResetPassword;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
     /**
      * [gravatar description]
      * 使用 Gravatar 来为用户提供个人头像
      * @param  string $size [为 gravatar 方法传递的参数 size 指定了默认值 100；]
      * @return [type]       [description]
      */
    public function gravatar($size = '100'){
      #通过 $this->attributes['email'] 获取到用户的邮箱；
      #通过 使用 trim 方法剔除邮箱的前后空白内容；
      #通过 用 strtolower 方法将邮箱转换为小写；
      #通过 将小写的邮箱使用 md5 方法进行转码；
      $hash = md5(strtolower(trim($this->attributes['email'])));
      #将转码后的邮箱与链接、尺寸拼接成完整的 URL 并返回；
        return "http://www.gravatar.com/avatar/$hash?s=$size";
    }
    /**
     * boot 方法会在用户模型类完成初始化之后进行加载，因此我们对事件的监听需要放在该方法中。
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
          //creating 用于监听模型被创建之前的事件
        static::creating(function ($user) {
            $user->activation_token = str_random(30);
        });
    }
      //密码重置
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPassword($token));
    }
}
