<!DOCTYPE html>
<html lang="zh">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta http-equiv="X-UA-Compatible" content="ie=edge" />
	<title>我的数据</title>
	<link rel="stylesheet" type="text/css" href="{{ asset('css/legendCss/bootstrapV4.min.css') }}"/>
	<link rel="stylesheet" type="text/css" href="{{ asset('css/legendCss/index.css') }}"/>
</head>
<body>
	<div class="container-fluid" style="position: fixed;top: 0;left: 0;z-index: 2;background: #fff;">
		<nav class="navbar justify-content-between myNav">
			<a class="navbar-brand" href="#">
				<img src="{{ asset('images/legendImages/axc_logo.png') }}"/>
			</a>		  
		    <div class="form-inline">
		    		<a href="http://anxinchong.vipcare.com/admin/home/show" class="user">{{session('admin_name')}}</a>
		      	<a href="/admin/admins/logout" class="login_out"><img src="{{ asset('images/legendImages/login_out.png') }}"/></a>
		    </div>
		</nav>
	</div>
	<!--我的数据-->
	<div class="container-fluid comm_box" style="margin:100px 15px;">
		<div class="clearfix" style="padding: 40px 0 20px;">
			<a class="today_data go_back" href="javascript:history.go(-1);">
				<img src="{{ asset('images/legendImages/goback.png') }}"/>我的数据
			</a>
		</div>
		<div class="row comm_row">
			<div class="col comm_col">
				<div class="comm_col_top clearfix">
					<p class="fl">充电棚数</p>
					<p class="fr">个</p>
				</div>
				<p class="mine_comm_col_mid color_grey" data-mine-info = "cdpCount"></p>
				<p class="mine_comm_col_foot"></p>
			</div>
		</div>
		<div class="row comm_row">
			<div class="col comm_col">
				<div class="comm_col_top clearfix">
					<p class="fl">充电口数</p>
					<p class="fr">个</p>
				</div>
				<p class="mine_comm_col_mid color_grey" data-mine-info = "cdkCount"></p>
				<p class="mine_comm_col_foot"></p>
			</div>
		</div>
		<div class="row comm_row">
			<div class="col comm_col">
				<div class="comm_col_top clearfix">
					<p class="fl">分成金额</p>
					<p class="fr">元</p>
				</div>
				<p class="mine_comm_col_mid color_pink" data-mine-info = "shareAmount"></p>
				<p class="mine_comm_col_foot"></p>
			</div>
		</div>
	</div>
	
	
	<!--loading-->
	<div class="big_bg">
		<img class="load" src="{{ asset('images/legendImages/loading.gif') }}"/>
	</div>

	
	
</body>
<script src="{{ asset('js/legendJs/jquery-3.3.1.min.js') }}" type="text/javascript" charset="utf-8"></script>
<script src="{{ asset('js/legendJs/bootstrapV4.min.js') }}" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
	$(function(){	
		$('.big_bg').on('touchstart',e=>{
        		e.preventDefault()
        },false);
        $('.big_bg').css({'display':'block'});
        $.ajax({
        		url:'/admin/home/show',
        		type:'post',
        		async:true,
			dataType:'json',
			cache: false,
			data:{},
        		success:function(res){
        			console.log(res);
        			if(res.code==200){
        				$.each(res,function(i,result){
						$('p[data-mine-info='+i+']').html(result);
					});
					$('.big_bg').css({'display':'none'});
        			}
        		},
        		error:function(res){
        			console.log(res)
        		}
        })
		
		
	})

	
	
</script>
</html>




      