<?php

namespace App\Console\Commands\Mq;

use App\Console\Commands\Command;
use Generalpack\Pack\Queueengine\Activemq\ServiceQueue;

class SetMessage extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'Message:set {queue_name} {queue_data}'; // 这里是生成命令的名称

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '写入到队列服务器';

	/**
	 * 执行控制台命令。
	 *
	 * @return mixed
	 */
	public function handle(ServiceQueue $mq) {
		if (!$mq->isConnected()) {
			echo "客户端会话无连接";
			die();
		}
		$queue_name = $this->argument('queue_name');
		$queue_data = $this->argument('queue_data');
		$mq->clientId = $queue_name;
		$mq->begin($queue_name);
		$mq->send('/topic/' . $queue_name, $queue_data, ['persistent' => 'true']);
		$mq->commit($queue_name);
		echo "数据已写入到队列";
		die();
	}
}
