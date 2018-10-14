<?php

namespace App\Console\Commands\Service;

use SensioLabs\Consul\Exception\ClientException;
use SensioLabs\Consul\Exception\ServerException;

class DiscoverServices extends Services {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'service:discover {service_name}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '发现服务';

	public function handle() {
		$service_name = $this->argument('service_name');

		if (!empty($service_engine)) {
			$this->serviceEngine = $service_engine;
		}

		try {
			echo $this->ServiceEngine()->discover($service_name);
		} catch (ServerException $e) {
			echo $e->getMessage();
		} catch (ClientException $s) {
			echo $s->getMessage();
		}
	}
}
