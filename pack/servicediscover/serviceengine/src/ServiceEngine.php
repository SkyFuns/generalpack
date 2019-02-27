<?php

namespace Generalpack\Pack\Servicediscover\Serviceengine;

class ServiceEngine {

	public $config;

	public $error;

	private $engine;

	private $isregister;

	/**
	 * @param string $msg
	 * @return string
	 */
	public function __construct() {

		$this->config = config('servicesengine');
		$engine = $this->config['Discover_Discover']['Engine'];

		$this->isregister = $this->config['Discover_Discover']['Register'];

		if (empty($engine)) {
			$engine = "Generalpack\Pack\Servicediscover\Serviceengine\Discover\StaticDiscover";
		}
		$this->engine = new $engine;
	}

	public function getError() {
		return $this->error;
	}

	public function register($service = null, $url = null) {

		if (!$this->isregister) {
			$this->error = '注册服务已关闭';
			return false;
		}

		if (empty($service)) {
			$this->error = '服务名不能为空';
			return false;
		}
		if (empty($url)) {
			$this->error = '服务Url不能为空';
			return false;
		}

		return $this->engine->register($service, $url);
	}

	public function discover($service = null) {

		if (empty($service)) {
			$this->error = '服务名不能为空';
			return false;
		}

		return $this->engine->discover($service);
	}

	public function unregister($service = null) {
		if (empty($service)) {
			$this->error = '服务名不能为空';
			return false;
		}

		return $this->engine->unregister($service);

	}

}