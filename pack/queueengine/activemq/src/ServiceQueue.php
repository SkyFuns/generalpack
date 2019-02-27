<?php

namespace Generalpack\Pack\Queueengine\Activemq;

class ServiceQueue extends \FuseSource\Stomp\Stomp {
	/**
	 * 执行同步请求
	 *
	 * @var boolean
	 */
	public $sync = false;

	/**
	 * 用于持久订阅的客户端id
	 *
	 * @var string
	 */
	public $clientId = null;

	protected $_hosts = [];
	protected $_params = [];
	protected $_subscriptions = [];
	protected $_defaultPort = 61613;
	protected $_currentHost = -1;
	protected $_attempts = 10;
	protected $_username = '';
	protected $_password = '';
	protected $_sessionId;
	protected $_read_timeout_seconds = 60;
	protected $_read_timeout_milliseconds = 0;
	protected $_connect_timeout_seconds = 60;
	protected $_waitbuf = [];

	public function __construct() {

		$host = config('servicequeue.connections.activemq.host');
		$port = config('servicequeue.connections.activemq.port');
		$this->_brokerUri = "tcp://" . $host . ":" . $port;
		$this->_username = config('servicequeue.connections.activemq.user');
		$this->_password = config('servicequeue.connections.activemq.passsword');
		parent::_init();
		if (!$this->isConnected()) {
			$this->connect($this->_username, $this->_password);
		}
	}

	public function isConnected() {
		return parent::isConnected();
	}

	public function connect($username = '', $password = '') {
		return parent::connect($username, $password);
	}

	/**
	 * 向消息传递系统中的目的地发送消息
	 *
	 * @param string $destination 目标队列
	 * @param array $msg 数组参数
	 * @param array $properties
	 * @param boolean $sync 执行同步请求
	 * @return boolean
	 */
	public function send($destination, $msg, $properties = [], $sync = null) {

		parent::begin($destination);
		parent::send($destination, json_encode($msg), $properties, $sync);
		$commit = parent::commit($destination);
		if ($commit) {
			return "新增请求队列成功";
		}
		return "新增请求队列失败";
	}
	/**
	 * 订阅一个消息队列
	 *
	 * @param string $destination 目标队列
	 * @param array $properties
	 * @param boolean $sync 执行同步请求
	 * @return boolean
	 * @throws StompException
	 */
	public function subscribe($destination, $properties = null, $sync = null) {

		parent::begin($destination);
		$sub = parent::subscribe($destination, $properties, $sync);

		if ($sub) {
			do {
				$next = true;
				if (parent::hasFrameToRead()) {
					$frame = parent::readFrame();
					sleep(1);
					parent::ack($frame);
					$next = false;
					return $frame->body;
				}
			} while ($next);
		}
		return "无订阅队列";
	}
	/**
	 * 删除现有的订阅
	 *
	 * @param string $destination
	 * @param array $properties
	 * @param boolean $sync 执行同步请求
	 * @return boolean
	 * @throws StompException
	 */
	public function unsubscribe($destination, $properties = null, $sync = null) {
		return parent::unsubscribe($destination, $properties, $sync);
	}
	/**
	 * 回滚正在进行的事务
	 *
	 * @param string $transactionId
	 * @param boolean $sync 执行同步请求
	 */
	public function abort($transactionId = null, $sync = null) {
		return parent::abort($transactionId, $sync);
	}
	/**
	 * 优雅地与服务器断开连接
	 *
	 */
	public function destruct() {
		return parent::__destruct();
	}
}
