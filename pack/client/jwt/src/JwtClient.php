<?php
namespace Generalpack\Pack\Client\Jwt;

use Serviceengine;
use \Curl\Curl;

class JwtClient {

	public $service_url;

	private $login_url;

	public $error;

	public $autologin = true;

	public $curl;

	private $user;

	private $password;

	private $_options;

	public function __construct($service_name = null) {
		$this->curl = new Curl();
		$this->user = config('jwtclient.CLIENT.USER');
		$this->password = config('jwtclient.CLIENT.PASSWORD');
		$this->_options = config('jwtclient.CLIENT');
	}
	public function __discover($service_name) {

		if (empty($service_name)) {
			$this->error = '服务名不能为空';
			return false;
		}
		$discover = json_decode(Serviceengine::discover($service_name), true);
		if (empty($discover['data']) && $discover['code'] != 200) {
			$this->error = $discover['description'];
			return false;
		}
		$this->service_url = $discover['data'][$service_name];
		return true;
	}
	public function act($resource, $action, $id, $body = null, $headers = null, $parms = null) {

		$this->curl->setHeaders($headers);

		$this->service_url = $this->service_url . $resource . '/';
		if (!empty($id)) {
			$this->service_url = $this->service_url . $id . '/';
		}

		switch ($action) {
		case 'list':
			$response = $this->curl->get($this->service_url, $parms);
			break;

		case 'add':
			$response = $this->curl->post($this->service_url, $body);
			break;

		case 'put':
			$response = $this->curl->put($this->service_url, $body);
			break;

		case 'patch':
			$response = $this->curl->patch($this->service_url, $body);
			break;

		case 'delete':
			$response = $this->curl->delete($this->service_url);
			break;
		}

		if ($this->curl->error) {
			$get_headers = $this->curl->getResponseHeaders();
			header($get_headers['status-line']);
			$this->error = $this->curl->errorCode . ': ' . $this->curl->errorMessage . "\n";
			return false;
		}
		return $response;
	}
	public function action($resource, $action = 'list', $id = null, $body = null, $parms = null, $headers = null) {

		$status = null;
		$data = null;

		if (empty($resource)) {

			$this->error = '请求服务资源不能为空';
			return false;
		}
		if (!empty($this->_options['AUTO_LOGIN']) && $this->_options['AUTO_LOGIN'] == 'False') {
			$this->autologin = false;
		}
		while (true) {
			if (empty($headers) && $this->autologin) {
				$token = $this->login($this->user, $this->password);
				if (!$token) {
					break;
				}
				$headers = $this->_create_jwt_header($token);
			}
			$response = $this->act($resource, $action, $id, $body, $headers, $parms);
			return $response;
		}
	}
	public function _create_jwt_header($token) {

		$headers = [];
		$headers['Authorization'] = config('jwtclient.CLIENT.JWT_HEADER_PFX') . "  " . $token;
		return $headers;
	}
	public function get_onlyuser() {

		$onlyuser = json_decode(Serviceengine::discover('onlyuser'), true);
		if ($onlyuser['code'] == 200 && !empty($onlyuser['data'])) {
			return $onlyuser['data']['onlyuser'];
		} else {
			$this->error = $onlyuser['description'];
			return false;
		}
	}
	public function login($username = null, $password = null) {

		$username = empty($username) ? $this->user : $username;
		$password = empty($password) ? $this->password : $password;
		$login_url = $this->get_onlyuser();
		if (!$login_url) {
			return false;
		}
		$auth_jwt = new AuthJwt($login_url);
		$token = $auth_jwt->obtain($username, $password);
		if (!$token) {
			$this->error = $auth_jwt->_error();
			return false;
		}
		return $token;

	}
	public function refresh($token = null) {

		$login_url = $this->get_onlyuser();
		if (!$login_url) {
			return false;
		}
		$auth_jwt = new AuthJwt($login_url);
		$token = $auth_jwt->refresh($token);
		if (!$token) {
			$this->error = $auth_jwt->_error();
			return false;
		}
		return $token;

	}
	public function verify($token = null) {

		$login_url = $this->get_onlyuser();
		if (!$login_url) {
			return false;
		}
		$auth_jwt = new AuthJwt($login_url);
		$token = $auth_jwt->verify($token);
		if (!$token) {
			$this->error = $auth_jwt->_error();
			return false;
		}
		return $token;

	}
	public function __error() {
		return $this->error;
	}
}