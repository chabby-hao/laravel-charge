<?php echo view('admin.header')->render() ?>
    <div id="content">
        <div id="content-header">
            <div id="breadcrumb"><a href="#" title="Go to Home" class="tip-bottom"><i class="icon-home"></i> Home</a>
                <a href="#" class="current">添加账户</a></div>
            <h1>添加账户</h1>
        </div>
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span8">
                    <div class="widget-box">
                        <div class="widget-title"><span class="icon"> <i class="icon-align-justify"></i> </span>
                            <h5>详情</h5>
                        </div>
                        <div class="widget-content">
                            <form id="myform" method="post" class="form-horizontal">
                                <input type="hidden" name="id" value="<?php echo $user->id ?>"/>
                                <div class="control-group">
                                    <label class="control-label"><span class="text-error">*</span>账号 :</label>
                                    <div class="controls">
                                        <input value="<?php echo $user->name ?>" readonly type="text" class="span11"/>
                                        <span class="help-block">例：chabby</span>
                                    </div>
                                </div>

                                <div id="device_nos" class="control-group">
                                    <label class="control-label"><span class="text-error">*</span>设备号（棚号） :</label>
                                    <div class="controls">
                                        <?php $deviceNos = \App\Models\DeviceInfo::getAllDeviceNo();?>
                                        <?php foreach (\App\Models\DeviceInfo::getAllDeviceStr() as $k=> $deviceStr) {$deviceNo = $deviceNos[$k]; ?>
                                            <label>
                                                <input type="checkbox" name="device_nos[]"  <?php if(in_array($deviceNo, $user->device_nos)) echo 'checked' ?> value="<?php echo $deviceNo; ?>"/>
                                                <?php echo $deviceStr; ?></label>
                                        <?php } ?>
                                    </div>
                                </div>



                                <div class="form-actions">
                                    <button type="button" id="mysubmit" class="btn btn-success">提交</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<script>

    $(function(){
        var myform = $("#myform");

        $("#mysubmit").click(function(){
            myform.submit();
        });

        myform.ajaxForm({
            dataType: 'json',
            //beforeSubmit : test,//ajax动画加载
            success: function(data)
            {
                if(ajax_check_res(data)){
                    //myalert('保存成功');
                }
            }
        });

    });

</script>
<?php echo view('admin.footer')->render() ?>

