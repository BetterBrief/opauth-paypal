<?php
/**
 * PayPal strategy for Opauth
 * 
 * More information on Opauth: http://opauth.org
 * 
 * @copyright    Original © 2012 U-Zyn Chua (http://uzyn.com)
 * @copyright    Modified © 2013 Will Morgan (https://github.com/willmorgan)
 * @link         http://opauth.org
 * @package      Opauth.PayPalStrategy
 * @license      MIT License
 *
 * @author       U-Zyn Chua <@uzyn>
 * @author       Will Morgan <@willmorgan>
 */
class PayPalStrategy extends OpauthStrategy{
	
	/**
	 * Compulsory config keys, listed as unassociative arrays
	 * eg. array('app_id', 'app_secret');
	 */
	public $expects = array('app_id', 'app_secret');
	
	/**
	 * Optional config keys with respective default values, listed as associative arrays
	 * eg. array('scope' => 'email');
	 */
	public $defaults = array(
		'redirect_uri' => '{complete_url_to_strategy}int_callback',
		/**
		 * @config boolean If true, use the PayPal sandbox
		 */
		'sandbox' => false,
	);

	/**
	 * Auth request
	 */
	public function request(){
		$url = 'https://www.sandbox.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize';
		$params = array(
			'client_id' => $this->strategy['app_id'],
			'response_type' => 'code',
			'scope' => 'openid',
			'nonce' => time() + base64_encode(rand(0x00, 0xff)),
			'redirect_uri' => $this->strategy['redirect_uri'],
		);
		if (!empty($this->strategy['scope'])) $params['scope'] = $this->strategy['scope'];
		if (!empty($this->strategy['state'])) $params['state'] = $this->strategy['state'];
		if (!empty($this->strategy['response_type'])) $params['response_type'] = $this->strategy['response_type'];
		if (!empty($this->strategy['display'])) $params['display'] = $this->strategy['display'];
		$this->clientGet($url, $params);
	}
	
	/**
	 * Internal callback, after PayPal's OAuth
	 */
	public function int_callback(){
		if (array_key_exists('code', $_GET) && !empty($_GET['code'])){
			$url = 'https://api.sandbox.paypal.com/v1/identity/openidconnect/tokenservice';
			$params = array(
				'client_id' =>$this->strategy['app_id'],
				'client_secret' => $this->strategy['app_secret'],
				'redirect_uri'=> $this->strategy['redirect_uri'],
				'code' => trim($_GET['code']),
				'grant_type' => 'authorization_code'
			);
			$response = $this->serverPost($url, $params, null, $headers);
			$results = json_decode($response, true);
			
			if (!empty($results) && !empty($results['access_token'])){

				$me = $this->me($results['access_token']);
				$this->auth = array(
					'provider' => 'PayPal',
					'uid' => $me['user_id'],
					'info' => array(),
					'credentials' => array(
						'token' => $results['access_token'],
						'expires' => date('c', time() + $results['expires'])
					),
					'raw' => $me
				);
				$this->callback();
			}
			else{
				$error = array(
					'provider' => 'PayPal',
					'code' => 'access_token_error',
					'message' => 'Failed when attempting to obtain access token',
					'raw' => $headers
				);

				$this->errorCallback($error);
			}
		}
		else{
			$error = array(
				'provider' => 'PayPal',
				'code' => $_GET['error'],
				'message' => $_GET['error_description'],
				'raw' => $_GET
			);
			
			$this->errorCallback($error);
		}
	}
	
	/**
	 * Queries PayPal Identity.x for user info
	 *
	 * @param string $access_token 
	 * @return array Parsed JSON results
	 */
	private function me($access_token){
		$url = 'https://api.sandbox.paypal.com/v1/identity/openidconnect/userinfo';
		$data = array(
			'schema' => 'openid',
		);
		$headers = array(
			'Authorization: Bearer ' . $access_token,
			'Accept: application/json',
			'Content-Type: application/json',
		);
		$options = array(
			'http' => array(
				'header' => implode("\r\n", $headers),
			),
		);
		$me = $this->serverGet($url, $data, $options, $headers);
		if (!empty($me)){
			return json_decode($me, true);
		}
		else{
			$error = array(
				'provider' => 'PayPal',
				'code' => 'me_error',
				'message' => 'Failed when attempting to query for user information',
				'raw' => array(
					'response' => $me,
					'headers' => $headers
				)
			);

			$this->errorCallback($error);
		}
	}
}
