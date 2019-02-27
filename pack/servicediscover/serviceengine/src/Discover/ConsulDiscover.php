<?php

namespace Generalpack\Pack\Servicediscover\Serviceengine\Discover;

use GuzzleHttp\Exception\TransferException;
use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ServerException;
use SensioLabs\Consul\ServiceFactory;

/**
 * Cosul服务引擎
 */
class ConsulDiscover extends CommonDiscover {
	public $service;

	public $host;

	public $tags = 'dev';

	public $heartbeat = 5;

	public function __construct() {
		$options = [];
		$options['base_uri'] = env('SERVICE_BIND', '127.0.0.1:8500');
		$this->client = new ServiceFactory($options);
		$this->service = $this->client->get('agent');
	}
	public function getHeaders($service) {
		return $service->headers;
	}
	public function getBody($service) {
		return json_decode($service->getBody(), true);
	}
	public function getStatusCode($service) {
		return $service->getStatusCode();
	}
	/**
	 * [getJson  返回PHP数组]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $service [description]
	 * @return   [type]              [description]
	 */
	public function getJson($service) {
		return $service->json();
	}
	/**
	 * [agentServiceRegister  注册服务]
	 * @Author   Liangyulin
	 * @DateTime 2018-08-31
	 * @return   [type]     [description]
	 */
	public function register($service, $url) {

		if (filter_var($url, FILTER_VALIDATE_URL)) {
			$address = $url;
		} else {
			return $this->ret_T(408, 'Url错误');
		}

		$server = [
			'ID' => $service,
			'Name' => $service,
			'Tags' => [0 => $this->tags],
			'EnableTagOverride' => true,
			'Check' => [
				'HTTP' => $address,
				'Interval' => $this->heartbeat . 's',
				'DeregisterCriticalServiceAfter' => '90m',
			],
		];

		$uri = explode(':', $url);
		if (is_numeric($uri[1])) {
			$server['Address'] = $uri[0] . ":" . $uri[1];
			$server['Port'] = $uri[1];
		} elseif (isset($uri[2]) && is_numeric($uri[2])) {
			$server['Address'] = $uri[0] . ":" . $uri[1];
			$server['Port'] = intval($uri[2]);
		} else {
			$server['Address'] = $uri[0] . ":" . $uri[1];
		}

		try {
			$app = $this->service->registerService($server);
			if (200 == $this->getStatusCode($app)) {
				$data[$service] = $url;
				return $this->ret_T(200, '服务已注册', $data);
			}
		} catch (ServerException $e) {
			return $this->ret_T(408, $e->getMessage());
		} catch (ClientException $a) {
			return $this->ret_T(400, $a->getMessage());
		} catch (TransferException $i) {
			return $this->ret_T(400, $i->getMessage());
		}
	}
	/**
	 * [agentServices 查看已注册的服务]
	 * @Author   Liangyulin
	 * @DateTime 2018-08-31
	 * @return   [type]     [description]
	 */
	public function discover($service) {

		$result = $this->getBody($this->service->services());
		$address = "";
		if (!empty($result)) {
			foreach ($result as $key => $val) {
				if ($key == $service) {
					$address = $val['Address'] . ":" . $val['Port'];
					if (!filter_var($address, FILTER_VALIDATE_IP)) {
						$port = explode(":", $address);
						if ($port[2] == 0) {
							$address = $port[0] . ":" . $port[1];
						}
						return $this->ret_T(200, '服务已发现', $address);
					}
				}
			}
		}
		return $this->ret_T(404, '没有找到服务');
	}
	/**
	 * [checks 检查服务是否正常]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @return   [type]     [description]
	 */
	public function checks($service) {
		$check = json_decode($this->getBody($this->service->checks()), true);
		$status = null;
		foreach ($check as $key => $val) {

			if ($key == 'service:' . $service) {
				$status = $val['Status'];
			}
		}
		return $status;
	}
	/**
	 * [checks 集群中管理成员]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @return   [type]     [description]
	 */
	public function members(array $options = []) {
		return $this->getBody($this->service->members($options));
	}
	/**
	 * [self Agent配置和成员信息]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @return   [type]     [description]
	 */
	public function self() {
		return $this->getBody($this->service->self());
	}
	/**
	 * [join 触发本地agent加入node节点]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $address [description]
	 * @param    array      $options [description]
	 * @return   [type]              [description]
	 */
	public function join($address, array $options = array()) {
		return $this->getBody($this->service->join($address, $options));
	}
	/**
	 * [join 强制删除node节点]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $address [description]
	 * @param    array      $options [description]
	 * @return   [type]              [description]
	 */
	public function forceLeave($node) {
		return $this->getBody($this->service->forceLeave($node));
	}
	/**
	 * [registerCheck 在本地agent增加一个检查项，使用PUT方法传输一个json格式的数据]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $check [description]
	 * @return   [type]            [description]
	 */
	public function registerCheck($check) {
		return $this->getBody($this->service->registerCheck($check));
	}
	/**
	 * [deregisterCheck 注销一个本地agent的检查项]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $checkId [description]
	 * @return   [type]              [description]
	 */
	public function deregisterCheck($checkId) {
		return $this->getBody($this->service->deregisterCheck($checkId));
	}
	/**
	 * [passCheck 设置一个本地检查项的状态为passing]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $checkId [description]
	 * @param    array      $options [description]
	 * @return   [type]              [description]
	 */
	public function passCheck($checkId, array $options = array()) {
		return $this->getBody($this->service->passCheck($checkId, $options));
	}
	/**
	 * [warnCheck 设置一个本地检查项的状态为warning]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $checkId [description]
	 * @param    array      $options [description]
	 * @return   [type]              [description]
	 */
	public function warnCheck($checkId, array $options = array()) {
		return $this->getBody($this->service->warnCheck($checkId, $options));
	}
	/**
	 * [failCheck 设置一个本地检查项的状态为critical]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $checkId [description]
	 * @param    array      $options [description]
	 * @return   [type]              [description]
	 */
	public function failCheck($checkId, array $options = array()) {
		return $this->getBody($this->service->failCheck($checkId, $options));
	}
	/**
	 * [registerService 在本地agent增加一个新的服务项，使用PUT方法传输一个json格式的数据]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $service [description]
	 * @return   [type]              [description]
	 */
	public function registerService($service) {
		return $this->getBody($this->service->registerService($service));
	}
	/**
	 * [deregisterService 注销一个本地agent的服务项]
	 * @Author   Liangyulin
	 * @DateTime 2018-09-03
	 * @param    [type]     $serviceId [description]
	 * @return   [type]                [description]
	 */
	public function unregister($service) {
		$this->service->deregisterService($service);
		return $this->ret_T(200, '服务已注销');

	}

}
