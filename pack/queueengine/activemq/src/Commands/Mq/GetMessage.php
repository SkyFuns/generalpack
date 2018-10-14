<?php

namespace App\Console\Commands\Mq;

use App\Console\Commands\Command;
use Generalpack\Pack\Queueengine\Activemq\ServiceQueue;

class GetMessage extends Command {
	/**
	 * 定义命令
	 *
	 * @var string
	 */
	protected $signature = 'Message:get {queue_name} {--seconds= : 设置超时秒数}'; // 这里是生成命令的名称

	/**
	 * 命令介绍
	 *
	 * @var string
	 */
	protected $description = '通过队列名获取队列服务器中数据';
	/**
	 * [$seconds 监听默认秒数]
	 * @var integer
	 */
	protected $seconds = 5;

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
		$queuename = $this->argument('queue_name');
		$seconds = $this->option('seconds');

		if (!empty($seconds)) {
			$this->seconds = $seconds;
			$mq->clientId = uniqid();
		}

		$mq->setReadTimeout($this->seconds);
		$mq->begin('/queue/' . $queuename);
		$sub = $mq->subscribe('/queue/' . $queuename, null, true);

		if ($sub) {
			do {
				$next = true;
				if ($mq->hasFrameToRead()) {
					$frame = $mq->readFrame();
					sleep(1);
					$mq->ack($frame);
					echo $frame->body;
				} else {
					$next = false;
				}
			} while ($next);
		}
		echo "无订阅队列";
	}
}
