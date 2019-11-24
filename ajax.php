<?php
use cnns\miniapp\CNNSClient;
use function GuzzleHttp\json_encode;
use cnns\miniapp\CNNSClientException;

require './vendor/autoload.php';
require './config.php';

$action = $_GET['a'];
$client = new CNNSClient($cnns_app['app_id'], $cnns_app['clientPrivateKey'], $cnns_app['publicKey']);

switch ($action) {
	/**
	 * 登录，使用 code 换 auth_token
	 * 
	 */
	case 'auth_login': 
		$code = trim($_POST['code']);
		try {
			$re = $client->authToken($code);
		} catch (CNNSClientException $e) {
			echo json_encode(array('code'=>'505','message'=>'接口返回错误','data'=>array('method'=>$e->method,'error_code'=>$e->getCode(),'message'=>$e->getMessage())),JSON_UNESCAPED_UNICODE);
			exit();
		}
		$auth_token = $re['auth_token'];
		$open_id = $re['open_id'];
		$client->setAuthToken($auth_token);
		try {
			$user_info = $client->getUserInfo($open_id);
		} catch (CNNSClientException $e) {
			echo json_encode(array('code'=>'505','message'=>'接口返回错误','data'=>array('method'=>$e->method,'error_code'=>$e->getCode(),'message'=>$e->getMessage())),JSON_UNESCAPED_UNICODE);
			exit();
		}
		echo json_encode(array('code'=>'200','user'=>$user_info,'auth_token'=>$auth_token,'open_id'=>$open_id),JSON_UNESCAPED_UNICODE);
		exit();
		break;
	/**
	 * 创建一个 0.1 ~ 2 CNNS 之间的随机订单，用于测试
	 */
	case 'create_order':
		$open_id = trim($_POST['open_id']);
		$auth_token = trim($_POST['auth_token']);
		$client->setAuthToken($auth_token);
		try {
			$re = $client->createInOrder($open_id, 'in_'.rand(100000000,999999999), 'Test in_order('.date('Y-m-d H:i:s').')', 'cnns', rand(1,10)*0.1);
		} catch (CNNSClientException $e) {
			echo json_encode(array('code'=>'505','message'=>'接口返回错误','data'=>array('method'=>$e->method,'error_code'=>$e->getCode(),'message'=>$e->getMessage())),JSON_UNESCAPED_UNICODE);
			exit();
		}
		echo json_encode(array('code'=>'200','in_order'=>$re),JSON_UNESCAPED_UNICODE);
		exit();
		break;
		/**
		 * 创建一个 0.004 ~ 0.08 RMB 之间的随机订单，用于测试
		 */
	case 'create_rmb_order':
		$open_id = trim($_POST['open_id']);
		$auth_token = trim($_POST['auth_token']);
		$client->setAuthToken($auth_token);
		try {
			$re = $client->createInOrderByRMB($open_id, 'in_'.rand(100000000,999999999), 'Test in_order('.date('Y-m-d H:i:s').')', 'cnns', rand(4,80)*0.001);
		} catch (CNNSClientException $e) {
			echo json_encode(array('code'=>'505','message'=>'接口返回错误','data'=>array('method'=>$e->method,'error_code'=>$e->getCode(),'message'=>$e->getMessage())),JSON_UNESCAPED_UNICODE);
			exit();
		}
		echo json_encode(array('code'=>'200','in_order'=>$re),JSON_UNESCAPED_UNICODE);
		exit();
		break;
	case 'check_pay':
		$auth_token = trim($_POST['auth_token']);
		$in_pay_id = trim($_POST['in_pay_id']);
		$client->setAuthToken($auth_token);
		try {
			$in_order = $client->getInOrderInfo(0,$in_pay_id);
		} catch (CNNSClientException $e) {
			echo json_encode(array('code'=>'505','message'=>'接口返回错误','data'=>array('method'=>$e->method,'error_code'=>$e->getCode(),'message'=>$e->getMessage())),JSON_UNESCAPED_UNICODE);
			exit();
		}
		$payStatus = $in_order['status'];
		if ($payStatus != '3'){
			echo json_encode(array('code'=>'301','message'=>'支付失败','in_order'=>$in_order));
		}else{
			echo json_encode(array('code'=>'200','message'=>'支付成功','in_order'=>$in_order));
		}
		exit();
		break;
	default:
		exit( json_encode(array('code'=>'502','message'=>'未知的请求方法'),JSON_UNESCAPED_UNICODE) );
		break;
}