##Generalpack扩展包##


###安装
	一： 在您的项目composer中增加如下信息，如没有composer文件，则在根目录中通过命令行 composer init生成composer文件。 

###Gitlab扩展包库
	
	1.在composer.json中增加如下扩展包库
	
	"repositories": [
        {
            "type": "git",
            "url": "https://code.cdqidian.cn/liangyl/generalpack.git",
            "reference":"master"
        }
    ]


	2.增加扩展包依赖：

	"require": {
        "generalpack": "master",
		"sensiolabs/consul-php-sdk": "^3.0",
		"fusesource/stomp-php": "2.0.*"
    }

	3.使用 composer 更新扩展包
	
	composer update
	  
	4.增加psr-4自动加载

    "psr-4": {
			/**ActiveMQ队列加载***/
            "Generalpack\\Pack\\Queueengine\\Activemq\\": "vendor/generalpack/pack/queueengine/activemq/src",
	        /**服务管理加载***/    
			"Generalpack\\Pack\\Servicediscover\\Serviceengine\\": "vendor/generalpack/pack/servicediscover/serviceengine/src"
    }


	5.使用composer加载

	在项目根目录执行： composer dump-autoload

	
	二：在config/app.php中增加如下信息
		/**服务提供商***/
		'providers' => [
			Generalpack\Pack\Queueengine\Activemq\ActivemqServiceProvider::class,
			Generalpack\Pack\Servicediscover\Serviceengine\ServiceengineServiceProvider::class
		]

		/**加载类别名***/
	    'aliases' => [
			'Activemq' => Generalpack\Pack\Queueengine\Activemq\Facades\ServiceQueue::class,
			'Serviceengine' => Generalpack\Pack\Servicediscover\Serviceengine\Facades\ServiceEngine::class
		]

	
	三：在命令行中发布sengine配置

	/**发布队列配置***/
	php artisan vendor:publish --provider="Generalpack\Pack\Queueengine\Activemq\ActivemqServiceProvider"


	/**发布服务管理配置***/
	php artisan vendor:publish --provider="Generalpack\Pack\Servicediscover\Serviceengine\ServiceengineServiceProvider"

	
###代码调用实例

		namespace App\Http\Controllers;
		use Illuminate\Http\Request;
		use Serviceengine;
		
		class TestController extends Controller {
			public function test(Request $request) {
		
				$url = Serviceengine::discover('onlyuser');
				var_dump($url);
				die();
		
			}
		}

###命令调用实例

	

	服务发现 --- php artisan service:discover onlyuser
	需传递服务名发现服务
	
	服务注册 --- php artisan service:register onlyuser http:192.168.1.107:8000
	需传递服务名和服务地址注册服务

	服务注销 --- php artisan service:unregister onlyuser
	需传递服务名来注销服务