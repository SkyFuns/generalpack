<?php

namespace App\Console\Commands\Service;

use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ServerException;

class RegisterServices extends Services {
	/**
	 * 控制台命令的名称和签名
	 *
	 * @var string
	 */
	protected $signature = 'service:register {service_name} {service_url} {--service_tags} {--service_heartbeat}';

	/**
	 * 控制台命令描述。
	 *
	 * @var string
	 */
	protected $description = '注册服务';

	public function handle() {
		$service_name = $this->argument('service_name');
		$service_url = $this->argument('service_url');
		$service_tags = $this->option('service_tags');
		if (!empty($service_tags)) {
			$this->tags = $service_tags;
		}
		$service_heartbeat = $this->option('service_heartbeat');
		if (!empty($service_heartbeat)) {
			$this->heartbeat = $service_heartbeat;
		}
		try {
			echo $this->ServiceEngine()->register($service_name, $service_url);
		} catch (ServerException $e) {
			echo $e->getMessage();
		} catch (ClientException $s) {
			echo $s->getMessage();
		}
	}
}
