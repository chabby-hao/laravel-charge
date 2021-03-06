<?php
/**
 * Created by PhpStorm.
 * User: chabby
 * Date: 2017/11/13
 * Time: 上午10:55
 */

namespace App\Http\Controllers\Admin;


use App\Libs\MyPage;
use App\Models\Admins;
use App\Services\AdminService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;

class AdminController extends BaseController
{
    public function list()
    {

        $paginate = Admins::orderByDesc('id')->paginate();


        return view('admin.admin.list',[
            'admins'=>$paginate->items(),
            'page_nav'=>MyPage::showPageNav($paginate),
        ]);
    }

    public function add(Request $request)
    {

        if($request->isXmlHttpRequest()){
            //添加管理员
            $check = ['name','pwd','user_type','device_nos'];
            $data = $this->_checkParams($check, $request->input(),['device_nos']);

            if(AdminService::addAdmin($data['name'], $data['pwd'], $data['user_type'], $data['device_nos'])){
                $this->_outPutRedirect(URL::action('Admin\AdminController@list'));
            }
            $this->_outPutError('添加失败,请确认信息再次输入');
        }

        return view('admin.admin.add');
    }

    public function edit(Request $request)
    {

        $id = $request->input('id');
        $user = Admins::find($id);

        if($request->isXmlHttpRequest()){
            //添加管理员
            $check = ['device_nos'];
            $data = $this->_checkParams($check, $request->input());

            if(AdminService::editAdmin($id, $data['device_nos'])){
                $this->_outPutRedirect(URL::action('Admin\AdminController@list'));
            }
            $this->_outPutError('操作失败');
        }

        $user->device_nos = AdminService::getDeviceNosByAdminId($id, true);
        return view('admin.admin.edit',[
            'user'=>$user,
        ]);
    }

    public function login(Request $request)
    {

        if($request->isXmlHttpRequest()){
            $check = ['name','pwd'];
            $data = $this->_checkParams($check, $request->input());
            if(AdminService::login($data['name'], $data['pwd'])){
                if(AdminService::getCurrentUserType() ==  Admins::USER_TYPE_CHANNEL){
                    return $this->_outPutRedirect(URL::action('Admin\HomeController@index'), 0);
                }
                return $this->_outPutRedirect(URL::action('Admin\DeviceController@deviceList'), 0);
            }
            return $this->_outPutError('用户名或者密码错误');
        }

        return view('admin.admin.login');
    }

    public function logout()
    {
        AdminService::logout();
        return Redirect::action('Admin\AdminController@login');
    }


}
