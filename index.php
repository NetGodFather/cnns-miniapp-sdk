<?php
require './vendor/autoload.php';
require './config.php';

if (!$cnns_app['app_id'] || !$cnns_app['clientPrivateKey'] || !$cnns_app['publicKey']) {
	exit('请设置 app_id , clientPrivateKey, publicKey');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <script src="./javascript/jquery.min.js"></script>
    <script src="./javascript/cnns_miniapp.js"></script>
    <title>CNNS SDK TEST</title>
</head>
<body>
<hr />
<input type="button" value="登陆" id="login"/>
<input type="button" value="支付" id="pay"/>
<input type="button" value="获取app信息" id="appbtn" />
<input type="button" value="创建订单" id="create-btn" />
<input type="button" value="切换到横屏" id="setlandscape" />
<input type="button" value="切换到竖屏" id="setportrait" />
<pre style="padding:10px;" id="log_out"></pre>
<script type="text/javascript">
var appId = "<?php echo $cnns_app['app_id'];?>";
var auth_token = "";
var open_id = "";
var in_pay_id = "";

var loginbtn = document.getElementById("login");
var paybtn = document.getElementById("pay");
var appinfobtn = document.getElementById("appbtn");
var createBtn = document.getElementById("create-btn");
var setlandscape = document.getElementById("setlandscape");
var setportrait = document.getElementById("setportrait");

function log(data){
	$('#log_out').text($('#log_out').text()+"\n"+data);
}
/**
 * 授权登录
 * 1. 如果用户已经登录，并且授权过，将直接回调，不会让用户确认；
 * 2. 如果用户尚未登录，将唤起登录界面，然用户先登录；
 *
 * 回调将会返回 app_id 和 code ，注意：
 * 1.如果未能返回 app_id ，code 是数字，则表示发生了错误；
 * 2.如果正常返回，可以通过 AJAX 通过服务器端的接口，获取用户信息； 
 */
loginbtn.addEventListener("click",function(){
	log('准备登录...');
	re = cw.cwLogin(
		{
			appId : appId,
			callback:function(sre) {
				if(typeof sre.app_id === 'undefined'){
					log('登录失败，错误码 ' + sre.code);
				}else{
					log("登录返回：\n"+JSON.stringify(sre));
					$.post('./ajax.php?a=auth_login',{code:sre.code},authLogin_callback);
				}
			}
    	});
	
	if(re)
		log('登录中...');
	else
		log('唤起授权登录失败，请确认是在支持的APP内打开');
		
});
function authLogin_callback(re){
	re = JSON.parse(re);
	if(re.code == '200'){
		log("登录成功\n--OPEN_ID:"+re.open_id+"\n--AUTH_TOKEN:"+re.auth_token+"\n--昵称:"+re.user.nickname+"\n--头像地址:"+re.user.headimgurl);
		auth_token = re.auth_token;
		open_id = re.open_id;
	}else{
		log("登录失败："+re.code+"("+ re.message +")");
	}
	
}

/**
 * 使用 AJAX 调用服务器端接口创建一个订单，然后获得 pay_id ,然后就可以使用 pay_id 调用支付
 * 创建订单，需要先登录
 */
createBtn.addEventListener('click', function(){
	if(!open_id || !auth_token){
		log("请先登录再创建订单");
		alert("请先登录再创建订单");
		return false;
	}
	$.post('./ajax.php?a=create_order',{open_id:open_id, auth_token:auth_token},createOrder_callback);
});
function createOrder_callback(re){
	re = JSON.parse(re);
	if(re.code == '200'){
		log("订单创建成功："+JSON.stringify(re.in_order));
		in_pay_id = re.in_order.in_pay_id;
	}else{
		log("订单创建失败："+re.code+"("+ re.message +")");
	}
	re = JSON.parse(re);
}

/*
 * 通过 in_pay_id 可以调起订单支付确认，让用户确认支付
 * 然后通过服务端再次获取订单信息，确认是否支付成功
  
 */
paybtn.addEventListener("click",function(){
	if(!in_pay_id){
		log("请先创建订单");
		alert("请先创建订单");
		return false;
	}
	log('请求用户确认支付');
	cw.cwPay({
		payId:in_pay_id,
		callback:function(sre){
			if(typeof sre.code === 'undefined'){
				log("订单确认成功:"+JSON.stringify(sre.data));
				log("服务端检测...");
				$.post('./ajax.php?a=check_pay',{auth_token:auth_token,in_pay_id:in_pay_id},checkPay_callback);
			}else{
				log('订单确认失败: '+sre.code+"("+sre.message+")");
			}
		}
	});
});
function checkPay_callback(re){
	re = JSON.parse(re);
	if(re.code == '200'){
		log("检测支付成功："+JSON.stringify(re.in_order));
		in_pay_id = re.in_order.in_pay_id;
	}else{
		log("检测支付成功："+re.code+"("+ re.message +")");
	}
}

/**
 * 将屏幕设置为竖屏，回调函数是在成功转换后，通知当前的屏幕方向
 * 
 */
setportrait.addEventListener("click",function(){
	log("设置为竖屏");
    cw.setScreenOrientation({ orientation:"portrait" });
});

/**
 * 将屏幕设置为竖屏，回调函数是在成功转换后，通知当前的屏幕方向
 * 
 */
setlandscape.addEventListener("click",function(){
	log("设置为横屏");
    cw.setScreenOrientation({ orientation:"landscape" });
});

/**
 * 查看 APP 信息，此接口是即时返回，不需要回调
 *
 */
appinfobtn.addEventListener("click",function(){
	var appinfo_str = "APP信息：\n";
    var _appinfo = cw.getAppInfo()

    for (var _k in _appinfo) {
    	appinfo_str += _k + " : " + appInfo[_k] + "\n";
    }
	log(appinfo_str);
 });
</script>
</body>
</html>