<?php

namespace Generalpack\Pack\Client\Jwt;

use \Curl\Curl;

class AuthJwt {

	private $curl;

	public $obtain;

	public $refresh;

	public $verify;

	public $root_url;

	public $error;

	public function __construct($root_url, $obtain = null, $refresh = null, $verify = null, $timeout = 2) {

		$this->root_url = $root_url;
		$this->obtain = empty($obtain) ? "auth_jwt" : $obtain;
		$this->refresh = empty($refresh) ? "auth_jwt_refresh" : $refresh;
		$this->verify = empty($verify) ? "auth_jwt_verify" : $verify;
		$this->curl = new Curl();
		$headers = [
			'Content-Type' => 'application/json',
		];
		$this->curl->setHeaders($headers);
	}
	public function obtain($username, $password) {

		$data = [
			'username' => $username,
			'password' => $password,
		];

		$body = $this->__request($this->obtain, $data);
		if (!$body) {
			return false;
		}
		return $body->token;

	}
	public function refresh($token) {
		$data = [
			'token' => $token,
		];

		$body = $this->__request($this->refresh, $data);

		if (!$body) {
			return false;
		}
		return $body->token;

	}
	public function verify($token) {
		$data = [
			'token' => $token,
		];

		$body = $this->__request($this->verify, $data);

		if (!$body) {
			return false;
		}
		return $body->token;

	}
	public function _error() {
		return $this->error;
	}
	public function __request($resource_name, $data) {

		$url = $this->root_url . $resource_name . '/';
		$response = $this->curl->post($url, $data);
		$get_headers = $this->curl->getResponseHeaders();
		header($get_headers['status-line']);
		if (isset($response->non_field_errors[0])) {
			$this->error = $response->non_field_errors[0];
			return false;
		}
		if ($this->curl->error) {
			$this->error = $this->curl->errorCode . ': ' . $this->curl->errorMessage . "\n";
			return false;
		}
		return $response;
	}
}