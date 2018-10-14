<?php

namespace App\Console\Commands\Service;

use App\Console\Commands\Command;

class Services extends Command {
	private $serve;

	protected $tags;

	protected $heartbeat;

	public function __construct() {
		$discover = config('servicesengine');
		$this->serve = new $discover['Discover_Discover']['Engine'];
		$this->heartbeat = $discover['Discover_Discover']['Heartbeat'];
		parent::__construct();
	}
	public function ServiceEngine() {
		return $this->serve;
	}
}
