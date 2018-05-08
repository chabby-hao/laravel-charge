<?php

namespace App\Http\Controllers\Api;

use App\Libs\ErrorCode;
use App\Libs\Helper;
use App\Models\Appointments;
use App\Models\Battery;
use App\Models\ChargeTasks;
use App\Models\DeviceInfo;
use App\Models\ReplaceTasks;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WelfareDevices;
use App\Models\WelfareUsers;
use App\Services\BoxService;
use App\Services\ChargeService;
use App\Services\DeviceService;
use App\Services\ReplaceService;
use App\Services\RequestService;
use App\Services\UserService;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ReplaceBatteryController extends Controller
{

    public function checkQrCode(Request $request)
    {
        if (!$userId = UserService::getUserId()) {
            return Helper::responeseError(ErrorCode::$tokenExpire);
        }
        $data = $this->checkRequireParams(['qr']);
        $qr = $data['qr'];
        //判断是换电还是绑定电池型号
        if (preg_match('/http:\/\/www.vipcare.com\/qr.html#(\d+)/i', $qr, $match)) {
            //绑定电池
            //电池二维码格式  http://www.vipcare.com/qr.html#928676166346
            $udid = $match[1];
            $battery = Battery::whereUdid($udid)->first();
            if ($battery) {
                UserDevice::whereUserId($userId)->delete();//1个用户只能绑一个，所以这里比较简单粗暴
                UserDevice::create([
                    'user_id' => $userId,
                    'battery_id' => $battery->id,
                ]);
                return $this->responseOk();//绑定成功
            }
            return Helper::responeseError(ErrorCode::$batteryNotRegister);
        } elseif ($arr = json_decode($qr, true) && isset($arr['cabinetId'])) {
            //换电,这里是柜子二维码,{'cabinetId':'02100434'}
            $cabinetId = $arr['cabinetId'];

            //检查用户余额
            if (UserService::getAvailabelBalance($userId) <= 0) {
                return Helper::responeseError(ErrorCode::$balanceNotEnough);
            }

            //柜子是否可用

            //是否有可换电的电池

            //开始一项新的换电任务
            ReplaceService::startReplaceBattery($cabinetId);

            return $this->responseOk();
        } else {
            return Helper::responeseError(ErrorCode::$qrCodeNotFind);//二维码有误
        }


    }

    public function appointment(Request $request)
    {
        if (!$userId = UserService::getUserId()) {
            return Helper::responeseError(ErrorCode::$tokenExpire);
        }
        $input = $this->checkRequireParams(['cabinetId']);
        $cabinetId = $input['cabinetId'];

        //检查是否可以预约

        //是否已经预约
        if(Appointments::whereUserId($userId)->where('expired_at', '<=', time())->first()){
            return Helper::responeseError(ErrorCode::$appointmentExists);
        }


        //预约
        ReplaceService::appointment($userId, $cabinetId);

        return $this->responseOk();

    }

}