<?php

declare(strict_types=1);

namespace plugins\ucenter\service;

use Throwable;
use mon\util\Date;
use mon\log\Logger;
use think\facade\Db;
use mon\util\Instance;
use mon\util\InviteCode;
use app\admin\dao\AdminLogDao;
use plugins\ucenter\dao\UserDao;
use app\admin\service\DictService;
use plugins\ucenter\dao\UserLogDao;
use plugins\ucenter\dao\UserSigninDao;
use plugins\ucenter\contract\UserEnum;
use plugins\ucenter\dao\UserLoginLogDao;
use plugins\ucenter\contract\AssetsEnum;
use plugins\ucenter\dao\UserAssetsLogDao;
use plugins\ucenter\contract\UserLogEnum;
use plugins\ucenter\validate\UserValidate;
use plugins\ucenter\contract\UserSigninEnum;
use plugins\ucenter\dao\UserCertificationDao;
use plugins\ucenter\contract\CertificationEnum;
use plugins\ucenter\validate\CertificationValidate;

/**
 * 用户相关服务
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class UserService
{
    use Instance;

    /**
     * 错误信息
     *
     * @var string
     */
    protected $error = '';

    /**
     * 用户注册
     *
     * @param array $data   请求参数
     * @return integer  注册用户ID
     */
    public function register(array $data): int
    {
        $validate = new UserValidate;
        $check = $validate->data($data)->scope('register')->check();
        if (!$check) {
            $this->error = $validate->getError();
            return 0;
        }
        // 注册配置
        $registerConfig = DictService::instance()->get(UserEnum::REGISTER_DICT, '', []);
        // 注册数据
        $registerData = [
            'nickname' => $data['nickname'] ?: ($registerConfig['add_nickname_prefix'] ?: '') . randString(),
            'avatar' => $data['avatar'] ?: ($registerConfig['add_avatar'] ?: ''),
            'level' => $data['level'] ?: ($registerConfig['add_level'] ?: 0),
            'sex' => $data['sex'] ?: 0,
            'status' => $data['status'] ?: ($registerConfig['add_status'] ?: 1),
            'password' => $data['password'],
            'comment' => $data['comment'] ?: '',
        ];

        // 邀请人
        $add_invite = $registerConfig['add_invite'] ?? 0;
        $invite_key = $registerConfig['invite_key'] ?? 'code';
        if ($add_invite == '1') {
            if (!isset($data[$invite_key]) || empty($data[$invite_key])) {
                $this->error = '请输入邀请码';
                return 0;
            }
            $registerData['pid'] = $this->decodeInvite($data[$invite_key]);
        } else {
            $registerData['pid'] = 0;
        }

        $registerType = '';
        // 验证器判断username是邮箱还是手机号，作为登录字段名
        if (check('email', $data['username'])) {
            // 邮箱注册
            $registerData['email'] = $data['username'];
            $registerType = '邮箱';
        } else if (check('mobile', $data['username'])) {
            // 手机号注册
            $registerData['mobile'] = $data['username'];
            $registerType = '手机号';
        }

        Db::startTrans();
        try {
            // 添加用户
            $uid = UserDao::instance()->add($registerData, 0);
            if (!$uid) {
                Db::rollback();
                $this->error = UserDao::instance()->getError();
                return 0;
            }

            // 记录日志
            $record = UserLogDao::instance()->record([
                'uid' => $uid,
                'action' => '用户注册',
                'content' => '新用户注册【' . $registerType . '】'
            ]);
            if (!$record) {
                Db::rollback();
                $this->error = '记录用户日志失败';
                return 0;
            }

            Db::commit();
            return $uid;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '用户注册异常';
            Logger::instance()->channel()->error('user register exception. msg: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 用户登录
     *
     * @param array $data   登录数据
     * @param string $ip    登录IP
     * @param integer $loginType    登录方式
     * @return array
     */
    public function login(array $data, string $ip = '0.0.0.0', int $loginType = 0): array
    {
        // 验证IP是否允许登录操作
        if (!$this->checkDisableIP($ip)) {
            $this->error = '异常登录操作，请稍后再登录';
            return [];
        }
        $validate = new UserValidate;
        $check = $validate->data($data)->scope('login')->check();
        if (!$check) {
            $this->error = $validate->getError();
            return [];
        }
        // 登录方式，设置为0，则自动判断
        if ($loginType == 0) {
            if (check('email', $data['username'])) {
                // 邮箱注册
                $loginType = UserEnum::LOGIN_TYPE['email'];
            } else if (check('mobile', $data['username'])) {
                // 手机号注册
                $loginType = UserEnum::LOGIN_TYPE['mobile'];
            }
        }

        // 获取用户信息
        $userInfo = [];
        switch ($loginType) {
            case 1:
                $userInfo = UserDao::instance()->where('mobile', $data['username'])->get();
                break;
            case 2:
                $userInfo = UserDao::instance()->where('email', $data['username'])->get();
                break;
            default:
                $this->error = '未知登录方式';
                return [];
        }
        if (!$userInfo) {
            $this->error = '用户不存在';
            return [];
        }
        // 判断用户状态
        if ($userInfo['status'] != UserEnum::USER_STATUS['enable']) {
            switch ($userInfo['status']) {
                case UserEnum::USER_STATUS['disable']:
                    $this->error = '用户已禁用';
                    break;
                case UserEnum::USER_STATUS['audit']:
                    $this->error = '用户审核中';
                    break;
                case UserEnum::USER_STATUS['audit_fail']:
                    $this->error = '用户审核未通过';
                    break;
                default:
                    $this->error = '用户状态未知错误';
                    break;
            }
            return [];
        }
        // 验证账号是否禁止登录
        if (!$this->checkDisableAccount($userInfo['id'])) {
            $this->error = '账号连续登录异常，请稍后再登录';
            return [];
        }
        // 验证密码
        if ($userInfo['password'] != UserDao::instance()->encodePassword($data['password'], $userInfo['salt'])) {
            $this->error = '用户名密码错误';
            // 记录登录错误日志
            UserLoginLogDao::instance()->record([
                'uid'       => $userInfo['id'],
                'type'      => UserLogEnum::LOGIN_LOG_TYPE['pwd_faild'],
                'action'    => $this->error
            ]);
            return [];
        }

        // 定义登陆信息
        $login_time = time();
        $login_token = $this->encodeLoginToken($userInfo['id'], $ip);
        Db::startTrans();
        try {
            // 更新用户信息
            $saveLoginUser = UserDao::instance()->where('id', $userInfo['id'])->save([
                'login_time' => $login_time,
                'login_ip' => $ip,
                'token' => $login_token
            ]);
            if (!$saveLoginUser) {
                Db::rollBack();
                $this->error = '登陆失败';
                return [];
            }

            // 记录登录日志
            $record = UserLoginLogDao::instance()->record([
                'uid' => $userInfo['id'],
                'type' => UserLogEnum::LOGIN_LOG_TYPE['success'],
                'action' => '登录成功',
            ]);
            if (!$record) {
                Db::rollback();
                $this->error = '记录登录日志失败：' . UserLoginLogDao::instance()->getError();
                return false;
            }

            Db::commit();
            $userInfo['login_time'] = $login_time;
            $userInfo['login_token'] = $login_token;
            return $userInfo;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '用户登录异常';
            Logger::instance()->channel()->error('user login exception. msg: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 系统登录用户账号
     *
     * @param integer $uid  用户ID
     * @param string $ip    登录ID
     * @param integer $adminID  管理员ID，0则不记录管理员日志
     * @param string  $log_model  管理员日志模块
     * @return array
     */
    public function sysLogin(int $uid, string $ip = '0.0.0.0', int $adminID = 0, string $log_model = 'sys'): array
    {
        // 用户信息
        $userInfo = UserDao::instance()->where('id', $uid)->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return [];
        }

        // 定义登陆信息
        $login_time = time();
        $login_token = $this->encodeLoginToken($userInfo['id'], $ip);
        Db::startTrans();
        try {
            // 更新用户信息
            $saveLogin = UserDao::instance()->where('id', $userInfo['id'])->save([
                'login_time' => $login_time,
                'login_ip' => $ip,
                'token' => $login_token
            ]);
            if (!$saveLogin) {
                Db::rollBack();
                $this->error = '登陆失败';
                return [];
            }

            // 记录管理员登录用户账号日志
            if ($adminID > 0) {
                $record = AdminLogDao::instance()->record([
                    'uid' => $adminID,
                    'module' => $log_model,
                    'action' => '登录用户账号',
                    'content' => '登录用户账号：' . $uid,
                    'sid' => $uid
                ]);
                if (!$record) {
                    Db::rollback();
                    $this->error = '记录操作日志失败, ' . AdminLogDao::instance()->getError();
                    return false;
                }
            }

            Db::commit();
            $userInfo['login_time'] = $login_time;
            $userInfo['login_token'] = $login_token;
            return $userInfo;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '登录用户账号异常';
            Logger::instance()->channel()->error('admin login user account exception. msg: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 用户实名认证登记
     *
     * @param array $data
     * @param integer $uid
     * @return boolean
     */
    public function certification(array $data, int $uid): bool
    {
        if (!isset($data['type']) || !isset(CertificationEnum::AUDIT_TYPE_SCOPE[$data['type']])) {
            $this->error = '请选择合法的认证类型';
            return false;
        }

        $scope = CertificationEnum::AUDIT_TYPE_SCOPE[$data['type']];
        $validate = new CertificationValidate();;
        $check = $validate->data($data)->scope($scope)->check();
        if (!$check) {
            $this->error = $validate->getError();
            return false;
        }

        // 认证信息
        $info = UserCertificationDao::instance()->where('uid', $uid)->get();
        if ($info && $info['status'] == CertificationEnum::AUDIT_STATUS['pass']) {
            $this->error = '已认证通过，不需修改';
            return false;
        }

        Db::startTrans();
        try {
            Logger::instance()->channel()->info('User certification');
            // 操作字段
            $data['uid'] = $uid;
            $data['status'] = 0;
            $field = ['uid', 'type', 'name', 'identity', 'person', 'mobile', 'email', 'paper_front', 'paper_back', 'paper_hand', 'license'];
            if (!$info) {
                // 未提交，新增
                $save = UserCertificationDao::instance()->allowField($field)->save($data, true);
            } else {
                // 已提交，更新
                $save = UserCertificationDao::instance()->allowField($field)->where('uid', $uid)->save($data);
            }
            if (!$save) {
                Db::rollback();
                $this->error = '保存实名认证信息失败';
                return false;
            }

            // 记录操作日志
            $record = UserLogDao::instance()->record([
                'uid' => $uid,
                'action' => '实名认证',
                'content' => '用户实名认证',
            ]);
            if (!$record) {
                Db::rollback();
                $this->error = '记录操作日志失败, ' . UserLogDao::instance()->getError();
                return false;
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '用户实名认证异常';
            Logger::instance()->channel()->error('User certification exception, msg => ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 用户签到
     * 
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function signin(int $uid): bool
    {
        $userInfo = UserDao::instance()->where('id', $uid)->get();
        if (!$userInfo) {
            $this->error = '用户不存在';
            return false;
        }
        if ($userInfo['status'] != UserEnum::USER_STATUS['enable']) {
            $this->error = '用户不可用';
            return false;
        }

        $date = new Date();
        $day = $date->format('Y-m-d');
        $exists = UserSigninDao::instance()->where('uid', $uid)->where('day', $day)->get();
        if ($exists) {
            $this->error = '用户当日已签到';
            return false;
        }
        // 配置信息
        $config = DictService::instance()->get(UserSigninEnum::CONFIG_KEY, '', []);
        // 签到积分
        $dayScore = $config[UserSigninEnum::CONFIG_DAY_GIFT_KEY];
        $weekScore = 0;
        // 存在周签到积分奖励且今天为周天，判断是否连续签到一周
        if ($config['week'] > 0 && $date->getWeek() == 0) {
            $week_start = $date->getWeekDay();
            $week_start_time = strtotime($week_start);
            $days = [$week_start];
            // 只要6天，因为今天是周天，还没写入数据
            for ($i = 1; $i < 6; $i++) {
                $t = $week_start_time + 86400 * $i;
                $days[] = date('Y-m-d', $t);
            }
            // 是否每天都有签到
            $count = UserSigninDao::instance()->where('uid', $uid)->whereIn('day', $days)->count();
            if ($count == 6) {
                // 全部有签到，增加获取的积分
                $weekScore = $config[UserSigninEnum::CONFIG_WEEK_GIFT_KEY];
            }
        }

        $score = $dayScore + $weekScore;
        Db::startTrans();
        try {
            Logger::instance()->channel()->info('user signin');
            // 签到
            $saveID = UserSigninDao::instance()->save(['uid' => $uid, 'day' => $day, 'score' => $score], true, true);
            if (!$saveID) {
                Db::rollback();
                $this->error = '签到失败';
                return false;
            }
            if ($score > 0) {
                // 添加积分，增加总的积分
                $userSave = UserDao::instance()->where('id', $uid)->inc('score', $score)->save();
                if (!$userSave) {
                    Db::rollback();
                    $this->error = '积分更新失败';
                    return false;
                }

                // 记录日志
                if ($dayScore > 0) {
                    // 记录日签到积分资产流水
                    $record = UserAssetsLogDao::instance()->record([
                        'uid'               => $uid,
                        'from'              => 0,
                        'sid'               => $saveID,
                        'cate'              => AssetsEnum::ASSETS_CATE['score'],
                        'type'              => AssetsEnum::ASSETS_LOG_TYPE['user_signin'],
                        'remark'            => '每日签到',
                        'available_before'  => $userInfo['score'],
                        'available_num'     => $dayScore,
                        'available_after'   => $userInfo['score'] + $dayScore,
                        'freeze_before'     => $userInfo['freeze_score'],
                        'freeze_num'        => 0,
                        'freeze_after'      => $userInfo['freeze_score'],
                    ]);
                    if (!$record) {
                        Db::rollback();
                        $this->error = '更新每日签到积分失败';
                        return false;
                    }
                }
                if ($weekScore) {
                    // 记录周签到积分资产流水
                    $record = UserAssetsLogDao::instance()->record([
                        'uid'               => $uid,
                        'from'              => 0,
                        'sid'               => $saveID,
                        'cate'              => AssetsEnum::ASSETS_CATE['score'],
                        'type'              => AssetsEnum::ASSETS_LOG_TYPE['user_signin'],
                        'remark'            => '一周签到',
                        'available_before'  => $userInfo['score'] + $dayScore,
                        'available_num'     => $weekScore,
                        'available_after'   => $userInfo['score'] + $score,
                        'freeze_before'     => $userInfo['freeze_score'],
                        'freeze_num'        => 0,
                        'freeze_after'      => $userInfo['freeze_score'],
                    ]);
                    if (!$record) {
                        Db::rollback();
                        $this->error = '更新周签到积分失败';
                        return false;
                    }
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $e) {
            Db::rollback();
            $this->error = '用户签到异常';
            Logger::instance()->channel()->error('user signin exception. file: ' . $e->getFile() . ' line: ' . $e->getLine() . ' msg: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 验证登录账号是否已超过登录错误次数限制
     *
     * @param integer $uid 用户ID
     * @return boolean
     */
    public function checkDisableAccount(int $uid): bool
    {
        $config = DictService::instance()->get(UserEnum::LOGIN_DICT, '', []);
        $start_time = time() - (60 * $config['login_gap']);
        $count = UserLoginLogDao::instance()->where('uid', $uid)->where('create_time', '>=', $start_time)
            ->where('type', '>', UserLogEnum::LOGIN_LOG_TYPE['success'])
            ->order('id', 'desc')->limit($config['account_error_limit'])->count();

        return !($count >= $config['account_error_limit']);
    }

    /**
     * 验证IP是否禁止登陆
     *
     * @param string $ip IP地址
     * @return boolean
     */
    public function checkDisableIP(string $ip): bool
    {
        $config = DictService::instance()->get(UserEnum::LOGIN_DICT, '', []);
        $start_time = time() - ($config['login_gap'] * 60);
        $count = UserLoginLogDao::instance()->where('create_time', '>=', $start_time)->where('ip', $ip)
            ->where('type', '>', UserLogEnum::LOGIN_LOG_TYPE['success'])
            ->order('id', 'desc')->limit($config['ip_error_limit'])->count();

        return !($count >= $config['ip_error_limit']);
    }

    /**
     * 用户ID转邀请码
     *
     * @param integer $uid
     * @return string
     */
    public function encodeInvite(int $uid): string
    {
        return InviteCode::instance()->encode($uid);
    }

    /**
     * 邀请转码用户ID
     *
     * @param string $code
     * @return integer
     */
    public function decodeInvite(string $code): int
    {
        return InviteCode::instance()->decode($code);
    }

    /**
     * 混淆加密生成登录的token
     *
     * @param string|integer $value 加密的用户唯一值
     * @param string $ip ip地址
     * @return string
     */
    public function encodeLoginToken($value, string $ip = ''): string
    {
        return md5(randString() . $ip . time() . $value);
    }

    /**
     * 获取错误信息
     *
     * @return mixed
     */
    public function getError()
    {
        $error = $this->error;
        $this->error = null;
        return $error;
    }
}
