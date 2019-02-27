<?php

namespace Generalpack\Pack\Queueengine\Activemq;

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
			__DIR__ . '/config/servicesengine.php' => app()->path() . "\..\config\servicesengine.php",

			__DIR__ . '/Commands/Service/Services.php' => app()->path() . "\Console\Commands\Service\Services.php",

			__DIR__ . '/Commands/Service/DiscoverServices.php' => app()->path() . "\Console\Commands\Service\DiscoverServices.php",

			__DIR__ . '/Commands/Service/RegisterServices.php' => app()->path() . "\Console\Commands\Service\RegisterServices.php",

			__DIR__ . '/Commands/Service/UnregisterServices.php' => app()->path() . "\Console\Commands\Service\UnregisterServices.php",

			__DIR__ . '/Commands/Command.php' => app()->path() . "\Console\Commands\Command.php",
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
