<?php

declare(strict_types=1);

namespace plugins\ucenter\validate;

use mon\util\Validate;

/**
 * 用户验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'           => ['required', 'id'],
        'pid'           => ['required', 'int', 'min:0'],
        'email'         => ['email'],
        'mobile'        => ['mobile'],
        'password'      => ['required', 'account', 'maxLength:16', 'minLength:6'],
        'pay_password'  => ['required', 'account', 'maxLength:16', 'minLength:6'],
        'old_password'  => ['required', 'account', 'maxLength:16', 'minLength:6'],
        'nickname'      => ['required', 'str', 'maxLength:24'],
        'level'         => ['required', 'int', 'min:0'],
        'avatar'        => ['isset', 'str', 'maxLength:250'],
        'sex'           => ['required', 'in:0,1,2'],
        'comment'       => ['isset', 'str', 'maxLength:250'],
        'status'        => ['required', 'int', 'min:0'],

        // 前端注册
        'username'      => ['required', 'str'],
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    public $message = [
        'idx'           => '参数异常',
        'pid'           => '参数异常.',
        'email'         => '请输入合法的邮箱地址',
        'mobile'        => '请输入合法的手机号码',
        'password'      => [
            'required'  => '密码必须',
            'maxLength' => '密码长度不能超过16',
            'minLength' => '密码长度不能小于6',
            'account'   => '密码格式错误'
        ],
        'pay_password'  => [
            'required'  => '交易密码必须',
            'maxLength' => '交易密码长度不能超过16',
            'minLength' => '交易密码长度不能小于6',
            'account'   => '交易密码格式错误'
        ],
        'old_password'  => [
            'required'  => '旧密码必须',
            'maxLength' => '旧密码长度不能超过16',
            'minLength' => '旧密码长度不能小于6',
            'account'   => '旧密码格式错误'
        ],
        'nickname'      => '请输入合法的昵称',
        'level'         => '请指定合法的用户等级',
        'avatar'        => '请上传用户头像',
        'sex'           => '请选择性别信息',
        'comment'       => '签名描述长度必须小于250',
        'status'        => '请选择和合法的用户状态',

        // 前端注册
        'username'      => '请输入合法的用户名'
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 后台
        'add'       => ['pid', 'email', 'mobile', 'password', 'nickname', 'level', 'avatar', 'sex', 'comment', 'status'],
        'edit'      => ['idx', 'pid', 'email', 'mobile', 'nickname', 'level', 'avatar', 'sex', 'comment', 'status'],
        'password'  => ['idx', 'password'],
        'status'    => ['idx', 'status'],

        // 验证器判断username是邮箱还是手机号，作为登录字段名
        'login'     => ['username', 'password'],
        'register'  => ['username', 'password'],
    ];
}
