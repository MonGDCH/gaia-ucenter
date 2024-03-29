<?php

declare(strict_types=1);

namespace plugins\ucenter\validate;

use mon\util\Validate;
use plugins\ucenter\contract\AssetsEnum;

/**
 * 用户资产验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class AssetsValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'uid'       => ['required', 'id'],
        'from'      => ['required', 'int', 'min:0'],
        'sid'       => ['int', 'min:0'],
        'cate'      => ['required', 'cate'],
        'type'      => ['required', 'type'],
        'remark'    => ['isset', 'str', 'maxLength:250'],
        'available_before'  => ['required', 'int', 'min:0'],
        'available_num'     => ['required', 'int', 'min:0'],
        'available_after'   => ['required', 'int', 'min:0'],
        'freeze_before'     => ['required', 'int', 'min:0'],
        'freeze_num'        => ['required', 'int', 'min:0'],
        'freeze_after'      => ['required', 'int', 'min:0'],

        'amount'    => ['required', 'int', 'min:1'],
        'usable'    => ['required', 'in:0,1'],
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    public $message = [
        'uid'       => '参数异常',
        'from'      => '参数异常.',
        'sid'       => '请输入关联ID',
        'cate'      => '请选择资产类型',
        'type'      => '请选择操作类型',
        'remark'    => '请输入合法的备注',
        'available_before'  => '可用资产变更前数量参数错误',
        'available_num'     => '可用资产变更数量参数错误',
        'available_after'   => '可用资产变更后数量参数错误',
        'freeze_before'     => '冻结资产变更前数量参数错误',
        'freeze_num'        => '冻结资产变更数量参数错误',
        'freeze_after'      => '冻结资产变更后数量参数错误',

        'amount'    => '操作数量必须为大于0的整数',
        'usable'    => '请选择操作类型',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 记录日志
        'record'    => [
            'uid', 'from', 'sid', 'cate', 'type', 'remark', 'available_before', 'available_num', 'available_after',
            'freeze_before', 'freeze_num', 'freeze_after'
        ],
        // 充值
        'charge'    => ['uid', 'from', 'cate', 'usable', 'amount', 'type', 'remark'],
        // 扣减
        'deduction' => ['uid', 'from', 'cate', 'usable', 'amount', 'type', 'remark'],
        // 可用、冻结转换
        'shift'     => ['uid', 'from', 'cate', 'usable', 'amount', 'type', 'remark'],
        // 转账
        'transfer'  => ['uid', 'from', 'cate', 'usable', 'amount', 'remark']
    ];

    /**
     * 验证资产类型
     *
     * @param mixed $value
     * @return boolean
     */
    public function cate($value): bool
    {
        return isset(AssetsEnum::ASSETS_CATE_TITLE[$value]);
    }

    /**
     * 验证操作类型
     *
     * @param mixed $value
     * @return boolean
     */
    public function type($value): bool
    {
        return isset(AssetsEnum::ASSETS_LOG_TYPE_TITLE[$value]);
    }
}
