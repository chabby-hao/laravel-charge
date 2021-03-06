<?php
/**
 * Created by PhpStorm.
 * User: chabby
 * Date: 2017/11/13
 * Time: 上午10:55
 */

namespace App\Http\Controllers\Admin;


use App\Libs\MyPage;
use App\Models\ChargeTasks;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class ChargeController extends BaseController
{
    public function list(Request $request)
    {

        $where = [];
        if($deviceNo = Input::get('device_no')){
            $where['device_no'] = $deviceNo;
        }
        if($portNo = Input::get('port_no')){
            $where['port_no'] = $portNo;
        }
        if($phone = Input::get('phone')){
            $where['phone'] = $phone;
        }
        if(is_numeric($request->input('task_state'))){
            $where['task_state'] = Input::get('task_state');
        }
        $model = ChargeTasks::join('users',function($join){
            $join->on('users.id','=','user_id');
        })->where($where);

        if($deviceNos = AdminService::getCurrentDeviceNos(true)){
            $model->whereIn('device_no', $deviceNos);
        }

        $paginate = $model->select(['charge_tasks.*','users.phone'])->orderByDesc('id')->paginate();


        return view('admin.charge.list',[
            'charges'=>$paginate->items(),
            'page_nav'=>MyPage::showPageNav($paginate),
        ]);
    }


}
