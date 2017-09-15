<?php

namespace luojixinhao\rpc;

/**
 * @author Jason
 * @date 2017-09-15
 * @version 1.0
 */
class rpcServer {

	protected $api;
	public $packaging = 'json'; //可取json,php

	public function __construct($api) {
		$this->api = $api;
	}

	public function handle() {
		$reStr = '"ERROR"';
		$method = filter_input(INPUT_POST, 'method');
		if (is_object($this->api) && is_callable(array($this->api, $method))) {
			$arguments = filter_input(INPUT_POST, 'args');
			$args = array();
			if ('json' == $this->packaging) {
				$args = json_decode($arguments, true);
			} elseif ('php' == $this->packaging) {
				$args = unserialize($arguments);
			}
			if ($args) {
				$args = (array) $args;
			} else {
				$args = array();
			}
			$re = call_user_func_array(array($this->api, $method), $args);
			if ('json' == $this->packaging) {
				$reStr = json_encode($re);
			} elseif ('php' == $this->packaging) {
				$reStr = serialize($re);
			} else {
				$re = (string) $reStr;
			}
		}
		print $reStr;
	}

}

class rpcClient {

	protected static $rpcServer = array();
	protected static $rpcMethod = array();
	protected static $rpcArguments = array();
	protected static $rpcCallback = array();
	protected static $rpcMethodCommon = '';
	protected static $rpcArgumentsCommon = array();
	public static $packaging = 'json'; //可取json,php
	public static $maxTry = 1;
	public static $maxConcur = 10;
	public static $reType = 2; //返回类型：可设置为0~5

	public function __construct($rpcUrl = '') {
		if (!is_array($rpcUrl)) {
			$rpcUrl = array($rpcUrl);
		}
		foreach ($rpcUrl as $url) {
			self::call($url, null, null, null);
		}
	}

	/**
	 * 检测依赖库是否存在
	 */
	protected static function checkMCurl() {
		return class_exists('\luojixinhao\mCurl\multiCurl', false);
	}

	/**
	 * 创建RPC
	 * @param type $rpcUrl
	 * @param type $method
	 * @param type $args
	 * @param type $callback
	 */
	public static function call($rpcUrl, $method, $args = array(), $callback = null) {
		self::$rpcServer[] = $rpcUrl;
		self::$rpcMethod[] = $method;
		self::$rpcArguments[] = $args;
		self::$rpcCallback[] = $callback;
	}

	/**
	 * 执行RPC
	 */
	public static function loop() {
		if (!self::checkMCurl()) {
			die('ERROR: need multiCurl libraries!');
		}
		$mc = new \luojixinhao\mCurl\multiCurl(array('maxTry' => self::$maxTry, 'maxConcur' => self::$maxTry, 'handle' => self::$packaging));
		foreach (self::$rpcServer as $key => $rpcUrl) {
			$method = isset(self::$rpcMethod[$key]) && !is_null(self::$rpcMethod[$key]) ? self::$rpcMethod[$key] : self::$rpcMethodCommon;
			$args = isset(self::$rpcArguments[$key]) && !is_null(self::$rpcArguments[$key]) ? self::$rpcArguments[$key] : self::$rpcArgumentsCommon;
			if ('json' == self::$packaging) {
				$args = json_encode($args);
			} elseif ('php' == self::$packaging) {
				$args = serialize($args);
			} else {
				$args = (string) $args;
			}
			$callback = isset(self::$rpcCallback[$key]) ? self::$rpcCallback[$key] : null;
			$mc->add($rpcUrl, array(
				CURLOPT_USERAGENT => '',
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => array(
					'method' => $method,
					'args' => $args,
				),
				CURLOPT_HTTPHEADER => array(
					'Connection: keep-alive',
				),
				), $args, $callback, $callback);
			unset(self::$rpcMethod[$key], self::$rpcArguments[$key], self::$rpcCallback[$key]);
		}
		return $mc->run(self::$reType);
	}

	/**
	 * 清除添加的RPC
	 */
	public static function reset() {
		self::$rpcServer = array();
		self::$rpcMethod = array();
		self::$rpcArguments = array();
		self::$rpcCallback = array();
		self::$rpcMethodCommon = '';
		self::$rpcArgumentsCommon = array();
	}

	public function __call($method, $arguments) {
		self::$rpcMethodCommon = $method;
		self::$rpcArgumentsCommon = $arguments;
		$result = self::loop();
		if (!isset($result[2]['content']) && isset($result[1]['content'])) {
			$result = $result[1]['content'];
		}
		return $result;
	}

}
