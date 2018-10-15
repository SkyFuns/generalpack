<?php

namespace SkyFuns\Generalpack\Pack\Servicediscover\Serviceengine\Discover;

interface BaseDiscover {
	/**
	 * [register 注册服务]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-13
	 * @return   [type]     [description]
	 */
	public function register($service, $url);

	/**
	 * [urlGet 获取服务资源]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-13
	 * @return   [type]     [description]
	 */
	public function discover($service);

	/**
	 * [urlGet   注销服务]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-13
	 * @return   [type]     [description]
	 */
	public function unregister($service);

}
