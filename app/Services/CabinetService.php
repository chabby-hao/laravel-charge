<?php

namespace App\Services;

use App\Libs\WxApi;
use App\Models\Appointments;
use App\Models\CabinetDoors;
use App\Models\Cabinets;
use App\Models\ChargeTasks;
use App\Models\DeviceInfo;
use App\Models\User;
use App\Models\WelfareUsers;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Psy\Command\WhereamiCommand;

class CabinetService extends BaseService
{

    const KEY_CABINET_PRE = 'cab:';
    const KEY_DOOR_PRE = 'door:';

    public static function getCabinetKey($cabinetNo)
    {
        $no = intval($cabinetNo);
        $key = self::KEY_CABINET_PRE . $no;
        return $key;
    }

    public static function getDoorKey($cabinetNo, $doorNo)
    {
        $cabinetNo = intval($cabinetNo);
        $doorNo = intval($doorNo);
        $key = self::KEY_DOOR_PRE . $cabinetNo . '_' . $doorNo;
        return $key;
    }

    public static function getCabinetInfo($cabinetNo)
    {
        Redis::select(5);
        $key = CabinetService::getCabinetKey($cabinetNo);
        $data = Redis::hGetAll($key);
        //::info("cabinet redis hgetall $key info:", $data);
        return $data;
    }

    public static function getDoorInfo($cabinetNo, $doorNo)
    {
        Redis::select(5);
        $key = self::getDoorKey($cabinetNo, $doorNo);
        $data = Redis::hGetAll($key);
        Log::info("door redis hgetall $key info:", $data);
        return $data;
    }

    public static function isDoorHasUsefulBattery($cabinetNo, $doorNo, $batteryLevel)
    {
        $data = self::getDoorInfo($cabinetNo, $doorNo);
        if ($data && $data['hasBattery']) {
            //有电池
            $batteryId = $data['batteryId'];
            $batteryInfo = BatteryService::getBatteryInfo($batteryId);
            if ($batteryInfo && intval($batteryInfo['batteryState']) === BatteryService::BATTERY_STATE_USEFUL && $batteryInfo['voltage'] == $batteryLevel) {
                return true;
            }
        }
        return false;
    }

    public static function getCabinetNoById($cabinetId)
    {
        $model = Cabinets::find($cabinetId);
        return $model ? $model->cabinet_no : 0;
    }

    public static function getCabinetAddressById($cabinetId)
    {
        $model = Cabinets::find($cabinetId);
        return $model ? $model->address : '';
    }

    /**
     * 柜子是否可用
     * @param $cabinetId
     */
    public static function isCabinetUseful($cabinetId)
    {
        //判断在线状态
        $cabinetNo = self::getCabinetNoById($cabinetId);
        $data = self::getCabinetInfo($cabinetNo);
        $val = $data['attach'];
        Log::debug('cabinet attach : ' . $val);
        return $val;
    }

    public static function getdoors($cabinetId)
    {
        $doors = CabinetDoors::whereCabinetId($cabinetId)->get();
        return $doors;
    }

    /**
     * 获取可用的电池数量
     * @param $cabinetId
     * @param $batteryLevel
     * @return int
     */
    public static function getAvalibleBaterrysCount($cabinetId, $batteryLevel)
    {
        $total = 0;
        $doors = self::getdoors($cabinetId);
        /** @var CabinetDoors $door */
        foreach ($doors as $door) {
            $cabinetNo = self::getCabinetNoById($cabinetId);
            $doorNo = $door->door_no;
            if (self::isDoorHasUsefulBattery($cabinetNo, $doorNo, $batteryLevel)) {
                ++$total;
            }
        }
        return $total;
    }

    /**
     * 判断柜子是否有可用的电池
     * @param $cabinetId
     * @param $batteryType
     */
    public static function hasAvailableBattery($cabinetId, $batteryLevel)
    {
        if (self::getAvalibleBaterrysCount($cabinetId, $batteryLevel) > 0) {
            return true;
        }
        return false;
    }

    /**
     * 获取柜子的预约数量
     * @param $cabinetId
     * @return int
     */
    public static function getAppointmentCount($cabinetId)
    {
        $now = Carbon::now()->toDateTimeString();
        return Appointments::whereCabinetId($cabinetId)->where('expired_at', '>', $now)->count();
    }

    /**
     * 获取可以预约的某型号电池
     * @param $cabinetId
     * @param $batteryType
     */
    public static function getAvailableAppointmentBatteryCount($cabinetId, $batteryLevel)
    {
        $total = self::getAvalibleBaterrysCount($cabinetId, $batteryLevel);
        $appoint = self::getAppointmentCount($cabinetId);
        $count = $total - $appoint;
        return $count > 0 ? $count : 0;
    }

    /**
     * 下发换电指令
     * @param $cabinetNo
     */
    public static function sendReplaceCommand($cabinetNo, $taskId, $batteryId)
    {
        if (self::replaceRedisSet($cabinetNo, $taskId, $batteryId)) {
            return CommandService::sendStartReplaceCmd($cabinetNo);
        }
        return false;
    }

    private static function replaceRedisSet($cabinetNo, $taskId, $batteryId)
    {
        $cabinetNo = intval($cabinetNo);
        Redis::select(5);
        $key = 'repalce_' . $cabinetNo;
        $val1 = Redis::hSet($key, 'taskId', $taskId);
        $val2 = Redis::hSet($key, 'batteryId', $batteryId);
        Log::debug("replaceRedisSet, taskId: $taskId, batteryId:$batteryId");
        return $val1 !== false && $val2 !== false ? true : false;
    }

    public static function getCabinetIdByQr($qr)
    {
        $arr = json_decode($qr, true);
        if ($arr && isset($arr['cabinetId'])) {
            return $arr['cabinetId'];
        } elseif($model = Cabinets::whereQr($qr)->first()){
            return $model->id;
        }else{
            return false;
        }
    }

    public static function isReplacing($cabinetId)
    {
        $cabinetNo = self::getCabinetNoById($cabinetId);
        $data = self::getCabinetInfo($cabinetNo);
        return $data['charging'] ? true : false;
    }

    public static function isOps($cabinetId)
    {
        $cabinetNo = self::getCabinetNoById($cabinetId);
        $data = self::getCabinetInfo($cabinetNo);
        Log::debug('ops data info:', $data);
        return $data['ops'] ? true : false;
    }


}