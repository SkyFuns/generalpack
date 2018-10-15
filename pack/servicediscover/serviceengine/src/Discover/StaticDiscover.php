<?php

namespace SkyFuns\Generalpack\Pack\Servicediscover\Serviceengine\Discover;

/**
 * 静态服务引擎
 */
class StaticDiscover extends CommonDiscover {
	public $discover;

	public $services = [];

	public function __construct() {
		$this->discover = config('servicesengine');

		if (!empty($this->discover['Discover_Discover']['Options']['Services'])) {
			$this->services = $this->discover['Discover_Discover']['Options']['Services'];
		}
	}

	public function register($service, $url) {

		return $this->ret_T(200, '静态服务注册暂不支持');
	}

	public function discover($service) {

		if (empty($this->services)) {
			return $this->ret_T(400, '服务配置为空');
		}

		foreach ($this->services as $key => $url) {

			if ($service == $key) {
				$data[$key] = $url;
				return $this->ret_T(200, '服务已发现', $data);
			}
		}
		return $this->ret_T(404, '没有找到服务');
	}

	/**
	 * [deregisterService 注销一个本地agent的服务项]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $serviceId [description]
	 * @return   [type]                [description]
	 */
	public function unregister($service) {

		return $this->ret_T(200, '静态服务注销暂不支持');
	}

}
