<?php

namespace App\Console\Commands;

use Illuminate\Console\Command as Comds;

class Command extends Comds {

	protected $signature = "service";
	/**
	 * 创建一个新的命令实例。
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}
}
