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
		/**
		 * @config string The scopes to ask for separated by spaces
		 */
		'scope' => 'openid email profile',
	);

	/**
	 * CLIENT BROWSER facing URL
	 * @return string the URL to redirect the client to to start OAuth
	 */
	public function getClientAuthURL() {
		$domain = 'www.paypal.com';
		if($this->isSandboxMode()) {
			$domain = 'www.sandbox.paypal.com';
		}
		return sprintf(
			'https://%s/webapps/auth/protocol/openidconnect/v1/authorize',
			$domain
		);
	}

	/**
	 * SERVER facing URL
	 * @return string the URL to obtain an OAuth access_token from
	 */
	public function getTokenServiceURL() {
		$domain = 'api.paypal.com';
		if($this->isSandboxMode()) {
			$domain = 'api.sandbox.paypal.com';
		}
		return sprintf(
			'https://%s/v1/identity/openidconnect/tokenservice',
			$domain
		);
	}

	/**
	 * SERVER facing URL
	 * @return string the URL to get the user's OpenID info from
	 */
	public function getUserInfoURL() {
		$domain = 'api.paypal.com';
		if($this->isSandboxMode()) {
			$domain = 'api.sandbox.paypal.com';
		}
		return sprintf(
			'https://%s/v1/identity/openidconnect/userinfo',
			$domain
		);
	}

	/**
	 * @return boolean If we're in sandbox mode
	 */
	public function isSandboxMode() {
		return !empty($this->strategy['sandbox']);
	}

	/**
	 * Auth request
	 * Redirects the client to the authorise page where they are asked to enter
	 * their credentials and accept the information that this application asks
	 * for.
	 * @return void
	 */
	public function request(){
		$url = $this->getClientAuthURL();
		$params = array(
			'client_id' => $this->strategy['app_id'],
			'response_type' => 'code',
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
	 * After the user accepts the terms on the client authentication page
	 * (see ->request), we are returned a code that is used to exchange for an
	 * access_token. This code can be used only once.
	 */
	public function int_callback(){
		if (array_key_exists('code', $_GET) && !empty($_GET['code'])){
			$url = $this->getTokenServiceURL();
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
	 * Queries PayPal's Identity resource for OpenID user info.
	 * At this point, the server must hold a valid access_token.
	 *
	 * @param string $access_token 
	 * @return array Parsed JSON results in OpenID format
	 */
	protected function me($access_token){
		$url = $this->getUserInfoURL();
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
