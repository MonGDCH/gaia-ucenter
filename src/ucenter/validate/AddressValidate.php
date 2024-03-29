<?php

declare(strict_types=1);

namespace plugins\ucenter\validate;

use mon\util\Validate;
use plugins\ucenter\contract\UserEnum;

/**
 * 用户地址验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class AddressValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'idx'       => ['required', 'id'],
        'uid'       => ['required', 'id'],
        'name'      => ['required', 'str', 'minLength:2', 'maxLength:100'],
        'mobile'    => ['required', 'mobile'],
        'pca'       => ['required', 'str', 'maxLength:250'],
        'address'   => ['required', 'str', 'maxLength:250'],
        'pcode'     => ['isset', 'str', 'maxLength:20'],
        'status'    => ['required', 'status'],
        'default'   => ['required', 'inDefault']
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    public $message = [
        'idx'       => '参数异常',
        'uid'       => '用户参数异常',
        'name'      => '请输入收件人姓名',
        'mobile'    => '请输入合法的收件人手机号码',
        'pca'       => '请选择所在省市区地址',
        'address'   => '请输入详细地址',
        'pcode'     => '请输入邮政编码',
        'status'    => '请选择合法的状态',
        'default'   => '请选择是否作为默认地址'
    ];
    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        'add'       => ['uid', 'name', 'mobile', 'pca', 'address', 'pcode', 'default'],
        'edit'      => ['idx', 'uid', 'name', 'mobile', 'pca', 'address', 'pcode', 'default'],
        'status'    => ['idx', 'status'],
    ];

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function status($value): bool
    {
        return isset(UserEnum::USER_ADDRESS_STATUS_TITLE[$value]);
    }

    /**
     * 状态合法值
     *
     * @param string $value
     * @return boolean
     */
    public function inDefault($value): bool
    {
        return isset(UserEnum::USER_ADDRESS_DEFAULT_TITLE[$value]);
    }
}
