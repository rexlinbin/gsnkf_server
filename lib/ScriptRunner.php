<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ScriptRunner.php 97104 2014-04-02 06:44:10Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/ScriptRunner.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2014-04-02 06:44:10 +0000 (Wed, 02 Apr 2014) $
 * @version $Revision: 97104 $
 * @brief
 *
 **/
if (! defined ( 'ROOT' ))
{
	define ( 'ROOT', dirname ( dirname ( __FILE__ ) ) );
	define ( 'LIB_ROOT', ROOT . '/lib' );
	define ( 'EXLIB_ROOT', ROOT . '/exlib' );
	define ( 'DEF_ROOT', ROOT . '/def' );
	define ( 'CONF_ROOT', ROOT . '/conf' );
	define ( 'LOG_ROOT', ROOT . '/log' );
	define ( 'MOD_ROOT', ROOT . '/module' );
	define ( 'HOOK_ROOT', ROOT . '/hook' );
	define ( 'COV_ROOT', ROOT . '/cov' );
}

require_once (DEF_ROOT . '/Define.def.php');

if (! function_exists ( 'btstore_get' ))
{
	require_once (LIB_ROOT . '/SimpleBtstore.php');
}

if (file_exists ( DEF_ROOT . '/Classes.def.php' ))
{
	require_once (DEF_ROOT . '/Classes.def.php');

	function __autoload($className)
	{

		$className = strtolower ( $className );
		if (isset ( ClassDef::$ARR_CLASS [$className] ))
		{
			require (ROOT . '/' . ClassDef::$ARR_CLASS [$className]);
		}
		else
		{
			trigger_error ( "class $className not found", E_USER_ERROR );
		}
	}
}

class OptionType
{

	const NOARG = 1;

	const REQUIRED = 2;

	const OPTIONAL = 3;
}

/**
 * 脚本基类
 * @author hoping
 *
 */
abstract class BaseScript
{

	const USAGE = 'usage: php /home/pirate/rpcfw/lib/ScriptRunner.php -f -c -s -g -d -h
其中各参数含义如下：
-f 指定需要运行的脚本文件名
-s 指定当前脚本所使用的服务器
-c 指定需要运行的脚本文件中的执行类名
-g 指定当前的group
-d 指定当前的database
-h 打印本段内容';

	/**
	 * 服务器ip
	 * @var string
	 */
	protected $serverIp;

	/**
	 * 组标识
	 * @var string
	 */
	protected $group;

	/**
	 * 需要使用的数据库
	 * @var string
	 */
	protected $db;

	/**
	 * 日志id
	 * @var string
	 */
	protected $logid;

	/**
	 * 当前时间
	 * @var int
	 */
	protected $time;

	/**
	 * 服务器id
	 * @var int
	 */
	protected $serverId;

	/**
	 * 脚本的执行函数
	 */
	public function execute($arrOption)
	{

		$err = "ok";
		try
		{
			$this->init ( $arrOption );
			$this->initLogger ();
			if (! empty ( $this->group ))
			{
				$group = $this->group;
				require_once (CONF_ROOT . "/gsc/$group/index.php");
			}
			
			$this->executeScript ( $arrOption );
			$this->sendCallback ();
		}
		catch ( Exception $e )
		{
			$err = $e->getMessage ();
			if ($err != 'fake' || FrameworkConfig::DEBUG)
			{
				Logger::fatal ( "uncaught exception:%s", $e->getMessage () );
				Logger::info ( "%s", $e->getTraceAsString () );
			}
		}
		return $err;
	}

	/**
	 * 错误处理函数
	 * @param int $errcode
	 * @param string $errstr
	 * @param string $errfile
	 * @param int $errline
	 * @param array $errcontext
	 */
	public function errorHandler($errcode, $errstr, $errfile, $errline, $errcontext)
	{

		if (! $errcode & error_reporting ())
		{
			return true;
		}
		
		Logger::fatal ( 'errcode:%d, errstr:%s, errfile:%s, errline:%s', $errcode, $errstr, 
				$errfile, $errline );
		Logger::trace ( 'error context:%s', $errcontext );
		throw new Exception ( 'php' );
	}

	/**
	 * 增加一个配置
	 * @param array $arrConfig
	 * @param string $config
	 * @param string $value
	 */
	static private function addConfig(&$arrConfig, $config, $value)
	{

		if (isset ( $arrConfig [$config] ))
		{
			if (is_array ( $arrConfig [$config] ))
			{
				$arrConfig [$config] [] = $value;
			}
			else
			{
				$arrConfig [$config] = array ($arrConfig [$config], $value );
			}
		}
		else
		{
			$arrConfig [$config] = $value;
		}
		unset ( $arrConfig );
	}

	/**
	 * 根据参数解析输入配置
	 * @param array $arrArg
	 * @return array
	 */
	static protected function getOption($arrArg, $option, $offset = 0)
	{

		$arrOption = array ();
		for($counter = 0; $counter < strlen ( $option ); $counter ++)
		{
			$config = $option [$counter];
			if ($config == ':')
			{
				Logger::fatal ( 'invalid option:%s at %d', $option, $counter );
				throw new Exception ( 'config' );
			}
			
			$arrOption [$config] = OptionType::NOARG;
			if (isset ( $option [$counter + 1] ) && $option [$counter + 1] == ':')
			{
				$counter ++;
				$arrOption [$config] = OptionType::REQUIRED;
				if (isset ( $option [$counter + 1] ) && $option [$counter + 1] == ':')
				{
					$counter ++;
					$arrOption [$config] = OptionType::OPTIONAL;
				}
			}
		}
		
		$arrArg = array_merge ( $arrArg );
		$arrRet = array ('args' => array () );
		for($counter = $offset; $counter < count ( $arrArg ); $counter ++)
		{
			$config = trim ( $arrArg [$counter] );
			if ($arrArg [$counter] [0] == '-')
			{
				$config = trim ( $config, '-' );
				if (! isset ( $arrOption [$config] ))
				{
					$arrRet ['args'] [] = $arrArg [$counter];
					continue;
				}
				
				switch ($arrOption [$config])
				{
					case OptionType::NOARG :
						self::addConfig ( $arrRet, $config, true );
						break;
					case OptionType::REQUIRED :
						$counter ++;
						if (! isset ( $arrArg [$counter] ) || $arrArg [$counter] [0] == '-')
						{
							Logger::fatal ( "option %s requires arg", $config );
							throw new Exception ( 'config' );
						}
						
						self::addConfig ( $arrRet, $config, $arrArg [$counter] );
						break;
					case OptionType::OPTIONAL :
						if (isset ( $arrArg [$counter + 1] ) && $arrArg [$counter + 1] [0] != '-')
						{
							$counter ++;
							self::addConfig ( $arrRet, $config, $arrArg [$counter] );
						}
						else
						{
							self::addConfig ( $arrRet, $config, true );
						}
						break;
					default :
						Logger::fatal ( "undefined option type:%d", $arrOption [$config] );
						throw new Exception ( 'config' );
				}
			}
			else
			{
				$arrRet ['args'] [] = $config;
			}
		}
		return $arrRet;
	}

	private function sendCallback()
	{

		Logger::debug ( "send callback to server now" );
		$arrCallbackList = RPCContext::getInstance ()->getCallback ();
		if ( isset(ScriptConf::$CALLBACK_INTERVAL ) )
		{
			Util::sendCallback ( $arrCallbackList, ScriptConf::$CALLBACK_INTERVAL );
		}
		else if ( defined("ScriptConf::CALLBACK_INTERVAL") )
		{
			Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
		}
		else
		{
			Logger::FATAL("ScriptConf::\$CALLBACK_INTERVAL and ScriptConf::CALLBACK_INTERVAL not defined!");
		}
	}

	/* (non-PHPdoc)
	 * @see BaseScript::initLogger()
	 */
	protected function initLogger()
	{

		RPCContext::getInstance ()->getFramework ()->initExtern ( $this->group, $this->serverIp, 
				$this->logid, $this->db, $this->time, $this->serverId );
		Logger::addBasic ( 'logid', $this->logid );
		Logger::addBasic ( 'server', $this->serverIp );
		if (! empty ( $this->group ))
		{
			Logger::addBasic ( 'group', $this->group );
		}
	}

	public function defaultInit($group, $serverIp, $logid, $db)
	{

		Logger::init ( LOG_ROOT . '/script.log', FrameworkConfig::LOG_LEVEL );
		set_error_handler ( array ($this, 'errorHandler' ) );
		$this->logid = $logid;
		$this->serverIp = $serverIp;
		$this->group = $group;
		$this->db = $db;
		$this->serverId = 0;
	}

	public function init($arrOption)
	{

	}

	/**
	 * 记录日志
	 * @param string $data
	 */
	static protected function log($data)
	{

		file_put_contents ( 'php://stderr', $data . "\n" );
	}

	/**
	 * 主执行函数
	 */
	public static function main()
	{

		error_reporting ( E_ALL | E_STRICT );
		global $argc, $argv;
		$arrOption = BaseScript::getOption ( $argv, 'f::c::s::g::d::h', 1 );
		if (isset ( $arrOption ['h'] ) || ! isset ( $arrOption ['f'] ))
		{
			BaseScript::log ( BaseScript::USAGE );
			return;
		}
		
		$file = $arrOption ['f'];
		if (! file_exists ( $file ))
		{
			BaseScript::log ( "file $file not found" );
			return;
		}
		
		require_once ($file);
		if (isset ( $arrOption ['c'] ))
		{
			$clazz = $arrOption ['c'];
		}
		else
		{
			$name = basename ( $file );
			$arrName = explode ( '.', $name );
			$clazz = $arrName [0];
		}
		
		if (isset ( $arrOption ['s'] ))
		{
			$serverIp = $arrOption ['s'];
		}
		else
		{
			$serverIp = ScriptConf::PRIVATE_HOST;
		}
		
		if (isset ( $arrOption ['g'] ))
		{
			$group = $arrOption ['g'];
		}
		else
		{
			$group = ScriptConf::PRIVATE_GROUP;
		}
		
		if (isset ( $arrOption ['d'] ))
		{
			$db = $arrOption ['d'];
		}
		else if (defined ( "ScriptConf::PRIVATE_DB" ))
		{
			$db = ScriptConf::PRIVATE_DB;
		}
		else
		{
			$db = "";
		}
		
		$logid = Util::genLogId ();
		
		$startTime = microtime ( true );
		$scripter = new $clazz ();
		$err = 'ok';
		if (! $scripter instanceof BaseScript)
		{
			self::log ( "script $file is not subclass of BaseScript" );
			$err = 'extends';
		}
		else
		{
			try
			{
				$scripter->defaultInit ( $group, $serverIp, $logid, $db );
				$startMethodTime = microtime ( true );
				$err = $scripter->execute ( $arrOption ['args'] );
				$endMethodTime = microtime ( true );
			}
			catch ( Exception $e )
			{
				Logger::fatal ( "uncaught exception:%s for method:%s", $e->getMessage (), 
						$scripter->getMethod () );
				Logger::info ( '%s', $e->getTraceAsString () );
				$endMethodTime = microtime ( true );
				$err = $e->getMessage ();
			}
		}
		
		$totalCost = intval ( ($endMethodTime - $startTime) * 1000 );
		$methodCost = intval ( ($endMethodTime - $startMethodTime) * 1000 );
		$frameCost = $totalCost - $methodCost;
		Logger::notice ( 
				"method:%s, err:%s, request count:%d, total cost:%d(ms), framework cost:%d(ms), method cost:%d(ms), request size:%d(byte), response size:%d(byte)", 
				$scripter->getMethod (), $err, 
				RPCContext::getInstance ()->getFramework ()->getRequestCount (), $totalCost, 
				$frameCost, $methodCost, 0, 0 );
	
	}

	/**
	 * 实际的执行函数
	 */
	protected abstract function executeScript($arrOption);

	public function getMethod()
	{

		return get_class ( $this );
	}
}

BaseScript::main ();
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
