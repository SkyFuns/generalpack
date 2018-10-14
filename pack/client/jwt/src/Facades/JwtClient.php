<?php
namespace Generalpack\Pack\Client\Jwt\Facades;
use Illuminate\Support\Facades\Facade;

class JwtClient extends Facade {
	protected static function getFacadeAccessor() {
		return 'jwtclient';
	}
}