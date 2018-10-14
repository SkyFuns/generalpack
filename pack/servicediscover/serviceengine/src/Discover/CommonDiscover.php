<?php

namespace Generalpack\Pack\Servicediscover\Serviceengine\Discover;

class CommonDiscover implements BaseDiscover {

	/**
	 * [register 实现注册服务]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-13
	 * @return   [type]     [description]
	 */
	public function register($service, $url) {}

	/**
	 * [urlGet 实现获取服务资源]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-13
	 * @return   [type]     [description]
	 */
	public function discover($service) {}
	/**
	 * [urlGet 实现注销服务]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-13
	 * @return   [type]     [description]
	 */
	public function unregister($service) {}

	public function ret_T($code, $description, $data = []) {

		return json_encode([
			'code' => $code,
			'description' => $description,
			'data' => $data,
		], JSON_UNESCAPED_UNICODE);
	}

}