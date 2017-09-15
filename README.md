关于
-----

简单的RPC类库。

需求
----
PHP 5.3 +

安装
----
composer require luojixinhao/rpc:*

联系
--------
Email: lx_2010@qq.com<br>


## 示例

```php
/*服务端*/
class API {

	public function api1($arg1 = '', $arg2 = '') {
		return "API1返回数据: 参数1={$arg1}，参数2={$arg2}";
	}

	public function api2($arg1 = '', $arg2 = '') {
		return array('API2返回数据', $arg1, $arg2);
	}

	protected function api3() {
		return '这个不可调用，返回“ERROR”';
	}

}

$service = new rpcServer(new API());
$service->handle();
```

```php
/* 客户端 */
$client = new rpcClient("http://localhost/mygithub/rpc/rpc/src/rpc.php?m=1");

$result = $client->api0();
//不存在的方法，返回：ERROR

$result = $client->api1('一', '二');
//返回：API1返回数据: 参数1=一，参数2=二

$result = $client->api2('一', array('二'));
//返回：
//Array
//(
//    [0] => API2返回数据
//    [1] => 一
//    [2] => Array
//        (
//            [0] => 二
//        )
//
//)

$result = $client->api3();
//受保护的方法，返回：ERROR


$client->reset(); //调用其他RPC服务时，需要执行复位，以免后面再次调用前面的RPC


rpcClient::call("http://localhost/rpcserver/?1", "api1", "parameters1", function() {
	echo '回调参数：';
	print_r(func_get_args());
});
rpcClient::call("http://localhost/rpcserver/?2", "api2", array("parameters1", "parameters2"), function() {
	echo '回调参数：';
	print_r(func_get_args());
});
$re = rpcClient::loop();
//返回：
//Array
//(
//    [1] => Array
//        (
//            [content] => Array
//                (
//                    [0] => API2返回数据
//                    [1] => parameters1
//                    [2] => parameters2
//                )
//
//            [url] => http://localhost/rpcserver/?2
//        )
//
//    [2] => Array
//        (
//            [content] => API1返回数据: 参数1=parameters1，参数2=
//            [url] => http://localhost/rpcserver/?1
//        )
//
//)
```