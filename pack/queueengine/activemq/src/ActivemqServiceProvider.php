<?php

namespace SkyFuns\Generalpack\Pack\Queueengine\Activemq;

use Illuminate\Support\ServiceProvider;

class ActivemqServiceProvider extends ServiceProvider {
	/**
	 * 服务提供者加是否延迟加载.
	 *
	 * @var bool
	 */
	protected $defer = true; // 延迟加载服务
	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot() {

		$this->publishes([
			__DIR__ . '/config/servicequeue.php' => config_path('servicequeue.php'),
			__DIR__ . '/Commands/Command.php' => app_path() . "\Console\Commands\Command.php",
			__DIR__ . '/Commands/Mq/GetMessage.php' => app_path() . "\Console\Commands\Mq\GetMessage.php",
			__DIR__ . '/Commands/Mq/SetMessage.php' => app_path() . "\Console\Commands\Mq\SetMessage.php",
		]);
	}
	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {

		// 单例绑定服务
		$this->app->singleton('servicequeue', function ($app) {
			return new ServiceQueue();
		});
	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {

		return ['servicequeue'];
	}
}
