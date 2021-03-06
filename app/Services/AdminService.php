<?php
//第三方请求签名服务
namespace App\Services;

use App\Libs\Helper;
use App\Models\Admins;

class AdminService extends BaseService
{

    public static function addAdmin($name, $pwd, $userType, $deviceNos)
    {
        try {

            if($userType == Admins::USER_TYPE_CHANNEL && !$deviceNos){
                return false;
            }

            $admin = new Admins();
            $admin->name = $name;
            $admin->pwd = self::_encrypt($pwd);
            $admin->user_type = $userType;
            if($userType == Admins::USER_TYPE_CHANNEL && deviceNos){
                $admin->user_config = json_encode(['device_nos'=>$deviceNos]);
            }

            $res = $admin->save();
        } catch (\Exception $e) {
            \Log::error('add admin db error : ' . $e->getMessage());
            return false;
        }
        return $res;
    }

    public static function editAdmin($id, $deviceNos)
    {
        try {
            $admin = Admins::find($id);

            if(!$admin){
                return false;
            }
            $userType = $admin->user_type;

            if($userType != Admins::USER_TYPE_CHANNEL){
                return false;
            }

            if($deviceNos){
                $admin->user_config = json_encode(['device_nos'=>$deviceNos]);
            }

            $res = $admin->save();
        } catch (\Exception $e) {
            \Log::error('edit admin deviceNos db error : ' . $e->getMessage());
            return false;
        }
        return $res;
    }

    public static function login($name, $pwd)
    {
        $admin = Admins::whereName($name)->first();
        if($admin){
            $pwd2= $admin->pwd;
            $pwd1 = self::_encrypt($pwd);
            if($pwd1 === $pwd2){
                session()->put('is_login', 1);
                session()->put('admin_name', $name);
                session()->put('admin_id', $admin->id);
                session()->put('user_config',$admin->user_config);
                session()->put('user_type',$admin->user_type);
                session()->save();
                return true;
            }
        }

        return false;
    }

    public static function logout()
    {
        session()->flush();
        return session()->save();
    }

    private static function _encrypt($pwd)
    {
        return md5($pwd);
    }

    public static function getCurrentUserType()
    {
        $type = session()->get('user_type');
        return $type;
    }

    public static function isChannelAdmin()
    {
        if(self::getCurrentUserType() == Admins::USER_TYPE_CHANNEL){
            return true;
        }
        return false;
    }

    public static function getCurrentDeviceNos($isInt = false)
    {

        $config = session()->get('user_config');
        if($config) {
            $config = json_decode($config, true);
            $data = $config['device_nos'] ?: [];
            if($isInt && $data){
                array_walk($data, function(&$v){
                    $v = intval($v);
                });
            }
            return $data;
        }
        return [];
    }

    public static function getDeviceNosByAdminId($adminId, $isArr = false)
    {
        $model = Admins::find($adminId);
        $config = $model->user_config;
        if($config){
            $config = json_decode($config, true);
            if($isArr){
                return $config['device_nos'] ? : [];
            }
            return implode(',', $config['device_nos']);
        }
        if($isArr){
            return [];
        }
        return '';
    }

}