<?php

declare(strict_types=1);

namespace plugins\ucenter\validate;

use mon\util\Validate;
use plugins\ucenter\contract\CertificationEnum;

/**
 * 实名认证验证器
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class CertificationValidate extends Validate
{
    /**
     * 验证规则
     *
     * @var array
     */
    public $rule = [
        'uid'           => ['required', 'id'],
        'type'          => ['required', 'type'],
        'name'          => ['required', 'str', 'minLength:2', 'maxLength:100'],
        'identity'      => ['required', 'str', 'identity'],
        'person'        => ['required', 'str', 'minLength:2', 'maxLength:50'],
        'mobile'        => ['required', 'mobile'],
        'email'         => ['required', 'email'],
        'paper_front'   => ['required', 'str'],
        'paper_back'    => ['required', 'str'],
        'paper_hand'    => ['required', 'str'],
        'license'       => ['required', 'str'],
        'comment'       => ['isset', 'str', 'maxLength:250'],
        'status'        => ['required', 'int', 'min:0'],
    ];

    /**
     * 错误提示信息
     *
     * @var array
     */
    public $message = [
        'uid'           => '参数异常',
        'type'          => '请选择认证类型',
        'name'          => '请输入合法的名称',
        'identity'      => '请输入合法的证件号码',
        'person'        => '请输入联系人姓名',
        'mobile'        => '请输入合法的联系人手机号码',
        'email'         => '请输入合法的联系人邮箱地址',
        'paper_front'   => '请上传身份证正面照片',
        'paper_back'    => '请上传身份证背面照片',
        'paper_hand'    => '请上传手持身份证照片',
        'license'       => '请上传营业执照',
        'comment'       => '备注描述长度必须小于250',
        'status'        => '请选择审核状态',
    ];

    /**
     * 验证场景
     *
     * @var array
     */
    public $scope = [
        // 个人认证
        'person'    => ['type', 'name', 'identity', 'person', 'mobile', 'email', 'paper_front', 'paper_back', 'paper_hand'],
        // 企业认证
        'company'   => ['type', 'name', 'identity', 'person', 'mobile', 'email', 'license'],
        // 编辑
        'edit'      => ['uid', 'type', 'name', 'identity', 'person', 'mobile', 'email'],
        // 审核
        'status'    => ['uid', 'status', 'comment'],
    ];

    /**
     * 验证身份证号码/营业执照号码
     *
     * @param string $val
     * @return boolean
     */
    public function identity($val)
    {
        $type = $this->data['type'] ?? '0';
        if ($type != '1') {
            return $this->idCard($val);
        }

        return $this->license($val);
    }

    /**
     * 验证类型合法值
     *
     * @param string $value
     * @return boolean
     */
    public function type($value): bool
    {
        return isset(CertificationEnum::AUDIT_TYPE_SCOPE[$value]);
    }
}
