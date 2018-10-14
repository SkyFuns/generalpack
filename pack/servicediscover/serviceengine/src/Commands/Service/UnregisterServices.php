<?php

namespace App\Console\Commands\Service;

use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ServerException;

class UnregisterServices extends Services {

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'service:unregister {service_name}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '注销服务';

	public function handle() {
		$service_name = $this->argument('service_name');

		try {
			echo $this->ServiceEngine()->unregister($service_name);
		} catch (ServerException $e) {
			echo $e->getMessage();
		} catch (ClientException $s) {
			echo $s->getMessage();
		}
	}
}
