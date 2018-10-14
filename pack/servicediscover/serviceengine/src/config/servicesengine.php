<?php

return [

	//微服务名称
	'Discover_Name' => env('Discover_NAME', ''),

	//微服务Url
	'Discover_Url' => env('Discover_URL', ''),

	//是否是主节点
	'Discover_Master' => env('Discover_MASTER', true),

	//微服务资源
	'Discover_Resources' => [],

	//服务发现配置

	'Discover_Discover' => [

		//是否注册
		'Register' => env('Discover_ISREGISTER', false),

		//心跳
		'Heartbeat' => env('Discover_HEARTBEAT', 5),

		//发现者引擎
		'Engine' => env('Discover_ENGINE', 'Generalpack\Pack\Servicediscover\Serviceengine\Discover\StaticDiscover'),

		//引擎配置
		'Options' => [
			'Services' => [
				'onlyuser' => 'http://192.168.1.107:8000/',
				'queue' => 'http://127.0.0.1:8161/',
			],
		],
	],

	//客户端配置

	'Client' => [

		//认证管理微服务名
		'Auth' => env('CLIENT_AUTH', 'onlyuser'),
		'Obtain' => env('CLIENT_OBTAIN', ''),
		'Refresh' => env('CLIENT_REFRESH', ''),
		'Verify' => env('CLIENT_VERIFY', ''),
		'User' => env('CLIENT_USER', 'tests'),
		'Password' => env('CLIENT_PASSWORD', '47801bb525fd5eb4e6071e4b69ab91ce'),
		'Jwt_header_pfx' => env('CLIENT_JWT_HEADER_PFX', 'Bearer'),
		'Timeout' => env('CLIENT_TIMEOUT', ''),
		'Auto_login' => env('CLIENT_AUTO_LOGIN', true),

	],

	'Service_manager' => [

		//服务管理地址
		'SERVICE_BIND' => env('SERVICE_BIND', '127.0.0.1:8500'),
		'Auth_key' => env('AUTH_KEY', ''),
	],

];
