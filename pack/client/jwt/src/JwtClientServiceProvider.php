<?php

namespace Generalpack\Pack\Client\Jwt;

use Illuminate\Support\ServiceProvider;

class JwtClientServiceProvider extends ServiceProvider {
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
			__DIR__ . '/config/jwtclient.php' => config_path('jwtclient.php'),

		]);
	}
	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register() {

		// 单例绑定服务
		$this->app->singleton('jwtclient', function ($app) {
			return new JwtClient();
		});
	}
	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides() {
		return ['jwtclient'];
	}
}
