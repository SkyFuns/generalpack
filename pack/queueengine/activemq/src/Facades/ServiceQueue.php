<?php
namespace SkyFuns\Generalpack\Pack\Queueengine\Activemq\Facades;
use Illuminate\Support\Facades\Facade;

class ServiceQueue extends Facade {
	protected static function getFacadeAccessor() {
		return 'servicequeue';
	}
}