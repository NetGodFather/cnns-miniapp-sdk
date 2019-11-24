<?php
namespace cnns\miniapp;

use GuzzleHttp\Client;

class CNNSClient{
	protected $app_id, $privateKey, $publicKey;
	protected $authToken = '';
	protected $gateway = 'https://openapi.bishijie.com';
	
	public function __construct($app_id, $clientPrivateKey, $openPublicKey){
		$this->app_id = $app_id;
		$this->privateKey = openssl_pkey_get_private($clientPrivateKey);
		if ($this->privateKey === false) $this->privateKey = openssl_pkey_get_private(self::pkcs2pem($clientPrivateKey,false));

		if ($clientPrivateKey && $this->privateKey === false)
			throw new CNNSClientException('PrvateKey Format Error','10001');
		
		$this->publicKey = openssl_pkey_get_public($openPublicKey);
		if ($this->publicKey === false) $this->publicKey = openssl_pkey_get_public(self::pkcs2pem($openPublicKey));
		
		if ($openPublicKey && $this->publicKey === false)
			throw new CNNSClientException('PublicKey Format Error','10002');
	}
	public function setAuthToken($authToken){
		$this->authToken = $authToken; 
	}
	/**
	 * 通过 code 换区用户 auth_token 和 open_id
	 * 	返回值 open_id, auth_token, expires_in, refresh_token
	 * 
	 * @param string $code
	 * @return array
	 */
	public function authToken($code){
		return $this->call('open.auth.token', array('code'=>$code,'grant_type'=>'authorization_code'), 'POST');
	}
	/**
	 * 刷新授权token
	 * 
	 * @param strng $toke
	 * @return array
	 */
	public function refreshToken($token){
		return $this->call('open.auth.token', array('refresh_token'=>$token,'grant_type'=>'refresh_token'), 'POST');
	}
	/**
	 * 返回用户信息
	 * @param string $open_id
	 * @return array
	 */
	public function getUserInfo($open_id){
		return $this->call('open.user.info', array('open_id'=>$open_id), 'GET');
	}
	/**
	 * 获取用户账户余额
	 * 
	 * @param string $open_id
	 * @param array|string $coin_ids
	 * @return mixed
	 */
	public function getUserBalance($open_id,$coin_ids){
		if (is_array($coin_ids)) {
			$coin_ids = join(',',$coin_ids);
		}
		return $this->call('open.user.balance', array('open_id'=>$open_id, 'coin_ids'=>$coin_ids), 'GET');
	}
	/**
	 * 创建付款订单
	 * 
	 * @param string $open_id
	 * @param string $in_order_id
	 * @param string $title
	 * @param string $coin_id
	 * @param float $amount
	 * @param string $state
	 * 
	 * @return array
	 */
	public function createInOrder($open_id,$in_order_id,$title,$coin_id,$amount,$state=''){
		$parameters = array(
			'open_id'		=>	$open_id,
			'in_order_id'	=>	$in_order_id,
			'title'			=>	$title,
			'coin_id'		=>	$coin_id,
			'amount'		=>	$amount,
			'state'			=>	$state
		);
		return $this->call('open.pay.in_order.create', $parameters, 'POST');
	}
	/**
	 * 创建RMB付款订单
	 * 
	 * @param string $open_id
	 * @param string $in_order_id
	 * @param string $title
	 * @param string $coin_id
	 * @param float $rmb_amount
	 * @param string $state
	 */
	public function createInOrderByRMB($open_id,$in_order_id,$title,$coin_id,$rmb_amount,$state=''){
		$parameters = array(
			'open_id'		=>	$open_id,
			'in_order_id'	=>	$in_order_id,
			'title'			=>	$title,
			'coin_id'		=>	$coin_id,
			'rmb_amount'	=>	$rmb_amount,
			'state'			=>	$state
		);
		return $this->call('open.pay.in_order.create', $parameters, 'POST');
	}
	/**
	 * 获取收款订单信息
	 *  
	 * @param string $in_order_id
	 * @param number $in_pay_id
	 * @return mixed
	 */
	public function getInOrderInfo($in_order_id=0,$in_pay_id=0){
		if ($in_order_id) {
			$parameters = array( 'in_order_id'=>$in_order_id );
		}else{
			$parameters = array( 'in_pay_id'=>$in_pay_id );
		}
		return $this->call('open.pay.in_order.info', $parameters, 'GET');
	}
	/**
	 * 创建付款订单
	 * 
	 * @param string $open_id
	 * @param string $out_order_id
	 * @param string $title
	 * @param string $coin_id
	 * @param float $amount
	 * @param string $state
	 * @return mixed
	 */
	public function createOutOrder($open_id, $out_order_id, $title, $coin_id, $amount , $state=''){
		$parameters = array(
			'open_id'		=>	$open_id,
			'out_order_id'	=>	$out_order_id,
			'title'			=>	$title,
			'coin_id'		=>	$coin_id,
			'amount'		=>	$amount,
			'state'			=>	$state
		);
		return $this->call('open.pay.out_order.create', $parameters, 'POST');
	}
	/**
	 * 获取付款订单信息
	 *
	 * @param number $out_order_id
	 * @param number $out_pay_id
	 * @return mixed
	 */
	public function getOutOrderInfo($out_order_id=0,$out_pay_id=0){
		if ($out_order_id) {
			$parameters = array( 'out_order_id'=>$out_order_id );
		}else{
			$parameters = array( 'out_pay_id'=>$out_pay_id );
		}
		return $this->call('open.pay.out_order.info', $parameters, 'GET');
	}
	
	protected function call($method, array $parameters,$PostOrGet='POST'){
		$request = $this->buildRequest($method, $parameters);
		$http = new Client();
		if ($PostOrGet == 'POST') {
			$response = $http->request('POST', $this->gateway, array('form_params'=>$request));
		}else{
			$response = $http->request('GET', $this->gateway, array('query'=>$request));
		}
		$response = json_decode($response->getBody(),true);
		if (array_key_exists('code', $response) && $response['code'] != '200') {
			throw new CNNSClientException(@$response['msg'], @$response['code'], NULL ,$method, $PostOrGet, $parameters , $request, $response);
		}
		$biz_response = $response['biz_response'];
		if (array_key_exists('error_code', $biz_response)) {
			throw new CNNSClientException(@$biz_response['error_message'], @$biz_response['error_code'], NULL ,$method, $PostOrGet, $parameters , $request, $response);
		}
		
		return @$biz_response;
	}
	protected function buildRequest($method,array $parameters){
		$_request = array(
			'app_id'	=>	$this->app_id,
			'method'	=>	$method,
			'format'	=>	'json',
			'charset'	=>	'utf-8',
			'sign_type'	=>	'RSA2',
			'timestamp'	=>	time().'000',
			'version'	=>	'1.0',
			'biz_content'	=>	json_encode($parameters)
		);
		if ($this->authToken) {
			$_request['app_auth_token'] = $this->authToken;
		}
		ksort($_request);
		
		$to_sign = array();
		foreach ($_request as $_k=>$_v){
			$to_sign[] = $_k.'='.$_v;
		}
		$to_signs = join('&', $to_sign);
		
		$sign = $this->createSign($to_signs);
		$_request['sign'] = $sign;
		return $_request;
	}
	
	/**
	 * 创建 RSA 签名
	 * @param string $data
	 * @return string
	 */
	protected function createSign($data=''){
		if (is_array($data))
			$data = json_encode($data);
		elseif (!is_string($data))
			return '';
		return openssl_sign($data, $sign, $this->privateKey, OPENSSL_ALGO_SHA256 ) ? base64_encode($sign) : '';
	}
	/**
	 * 验证 RSA 签名
	 * @param string $data
	 * @param string $sign
	 * @return boolean
	 */
	protected function verifySign($data = '', $sign = '')
	{
		if (!is_string($sign) || !is_string($sign)) {
			return false;
		}
		return (bool)openssl_verify(
			$data,
			base64_decode($sign),
			$this->publicKey,
			OPENSSL_ALGO_SHA256
		);
	}
	/**
	 * 将 PKCS格式转换为PEM 格式
	 * 
	 * @param string $pkcsKey
	 * @param boolean $isPub
	 * @return string
	 */
	public static function pkcs2pem($pkcsKey, $isPub=true){
		$pemKey = $isPub ? "-----BEGIN PUBLIC KEY-----\r\n" : "-----BEGIN PRIVATE KEY-----\r\n";
		
		$raw = strlen($pkcsKey)/64;
		$index = 0;
		while($index <= $raw ) {
			$line = substr($pkcsKey,$index*64,64)."\r\n";
			if(strlen(trim($line)) > 0) $pemKey .= $line;
			$index++;
		}
		$pemKey .= $isPub ? "-----END PUBLIC KEY-----\r\n" : "-----END PRIVATE KEY-----\r\n";
		return $pemKey;
	}
}

class CNNSClientException extends \Exception{
	public $method,$postOrGet,$parameters,$request ,$response;
	public function __construct ($message = null, $code = null, $previous = null, $method=null, $postOrGet=null, $parameters = array(), $request = array(), $response=null){
		$this->method = $method;
		$this->postOrGet = $postOrGet;
		$this->response = $response;
		$this->request = $request;
		$this->parameters = $parameters;
		return parent::__construct($message,$code,$previous);
	}
}