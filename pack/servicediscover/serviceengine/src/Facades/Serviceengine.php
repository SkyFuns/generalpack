<?php

namespace SkyFuns\Generalpack\Pack\Servicediscover\Serviceengine\Facades;

use Illuminate\Support\Facades\Facade;

class Serviceengine extends Facade {
	protected static function getFacadeAccessor() {
		return 'servicesengine';
	}
}