<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserClient.php 37487 2013-01-29 09:52:21Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/module/groupwar/test/UserClient.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-01-29 17:52:21 +0800 (星期二, 29 一月 2013) $
 * @version $Revision: 37487 $
 * @brief 
 *  
 **/






/**
 * 用于模拟客户端
 * @author wuqilin
 *
 */

class UserClient
{

	private $server = '192.168.1.37';
	
	private $port = 7777;

	private $socket;

	private $public;
	
	private $private;

	private $clazz;

	private $requestType;

	private $token;

	private $async;

	private $callback;
	
	
	public $pid = 20012;	
	public $uid = 20100;	
	
	

	function __construct($server = NULL, $port = NULL, $pid = NULL, $uid = NULL, $login = true)
	{
		
		if (isset($server))
		{
			$this->server = $server;
		}
		if (isset($port))
		{
			$this->port = $port;
		}
		if ( isset($pid))
		{
			$this->pid = $pid;
		}
		if ( isset($uid) )
		{
			$this->uid = $uid;
		}

		$this->public = true;
		$this->clazz = null;
		$this->requestType = RequestType::RELEASE;
		$this->token = "0";
		$this->async = false;
		$this->private = false;

		$this->socket = null;

		$this->resetCallback();

		if( $login )
		{	
			$this->loginGame();
		}
			
	}
	
	function loginGame()
	{			
		self::setClass ( 'user' );
		$ret = self::login ( $this->pid );
		if (!is_array($ret) || $ret['res'] != 'ok')
		{
			throw new Exception( "login failed. pid:".$this->pid);
		}
		
		$ret = $this->getUsers();
		Logger::debug('getUsers. ret:%s', $ret);
		
		if(empty($ret))
		{
			throw new Exception('no user');
		}
		$this->uid = $ret[0]['uid'];
		
		$ret = self::userLogin($this->uid);
		if($ret != 'ok')
		{
			throw new Exception("userLogin failed. uid:".$this->uid." ret=".$ret."\n");
		}	
	}
	
	public function formalLogin($openDateTime=null, $clientInfo = '')
	{
		if(empty($openDateTime))
		{			
			$openDateTime = GameConf::SERVER_OPEN_YMD . GameConf::SERVER_OPEN_TIME;		
		}
		
		$arrReq = array(
				'pid' => BabelCrypt::encryptNumber($this->pid),
				'ptype' => 0,
				'openDateTime'=>$openDateTime,
				'timestamp' => time(),
				'uuid' => 'test client uuid',
				);
		
		ksort ( $arrReq );
		$tmp = '';
		foreach ( $arrReq as $key => $val )
		{
			$tmp .= $key . $val;
		}
		$arrReq['hash'] = md5 ( $tmp . BabelCryptConf::PlayHashKey );
		return $this->login($arrReq, $clientInfo);
	}
	
	public function doConsoleCmd($cmd)
	{
		$this->setClass ( 'console' );
		return $this->execute( $cmd );
	}
	
	
	function connect($server, $port)
	{
		$this->socket = socket_create ( AF_INET, SOCK_STREAM, SOL_TCP );
		$this->socketError ( 'socket_create', $this->socket );

		Logger::debug ( "connecting to server:%s at port:%d", $server, $port );
		socket_set_option ( $this->socket, SOL_SOCKET, SO_SNDTIMEO,
		array ('sec' => FrameworkConfig::PROXY_CONNECT_TIMEOUT, 'usec' => 0 ) );

		$ret = socket_connect ( $this->socket, $server, $port );
		$this->socketError ( 'socket_connect', $ret );

		socket_set_option ( $this->socket, SOL_SOCKET, SO_RCVTIMEO,
		array ('sec' =>30, 'usec' => 0 ) );
		socket_set_option ( $this->socket, SOL_SOCKET, SO_SNDTIMEO,
		array ('sec' => FrameworkConfig::PROXY_WIRTE_TIMEOUT, 'usec' => 0 ) );
	}
	
	function close()
	{
		if ($this->socket)
		{
			socket_close ( $this->socket );
			$this->socket = null;
		}
	}
	
	function setToken($token)
	{
		$this->token = "" . $token;
	}

	function setClass($clazz)
	{
		$this->clazz = $clazz;
	}

	function setRequestType($requestType)
	{
		$this->requestType = $requestType;
	}

	function setPublic($public)
	{
		$this->public = $public;
	}
	
	function setPrivate( $private)
	{
		$this->private = $private;
	}

	function setAsync($async)
	{
		$this->async = $async;
	}

	function resetCallback()
	{
		$this->callback = array (  'callbackName' => 'dummy');
	}
	
	function __call($method, $arrArg)
	{
		if (empty ( $this->socket ))
		{
			$this->connect ( $this->server, $this->port );
		}
		if (! empty ( $this->clazz ))
		{
			$method = $this->clazz . '.' . $method;
		}
		$arrRequest = array ('method' => $method, 'args' => $arrArg, 'type' => $this->requestType, 
				'time' => time (), 'token' => $this->token, 'callback' =>array (  'callbackName' => $method)  );

		if($this->private)
		{
			$arrRequest['private'] = true;
		}

		$this->callback = $method;
		$request = Util::amfEncode ( $arrRequest );
		if (empty ( $request ) || strlen ( $request ) > FrameworkConfig::MAX_REQUEST_SIZE)
		{
			Logger::warning ( "request:%s", bin2hex ( $request ) );
			throw new Exception ( 'sys' );
		}

		$flag = 0;
		if (strlen ( $request ) > FrameworkConfig::PROXY_COMPRESS_THRESHOLD)
		{
			$size = strlen ( $request );
			$flag |= 0x00ff0000;
			$request = gzcompress ( $request );
			Logger::debug ( "request too large, compress from %d to %d", $size,
			strlen ( $request ) );
		}
		$this->writeU32 ( strlen ( $request ) );
		if ($this->public)
		{
			$flag |= 0xff000000;
			$this->writeU32 ( $flag );
			$this->writeBytes ( $this->encrypt ( $request ) );
		}
		else
		{
			$this->writeU32 ( $flag );
		}
		$this->writeBytes ( $request );
		Logger::trace ( "userclient request:%s", $method );
		if (! $this->async)
		{
			return $this->receiveData ();
		}
	}

	public function receiveData($callback = NULL)
	{
		if(!isset($callback))
		{
			$callback = $this->callback;
		}
		Logger::addBasic ( 'uid', $this->uid );
		Logger::trace("userclient wait for {$callback}");
		while ( true )
		{
			$bodyLength = $this->readU32 ();
			$flag = $this->readU32 ();
			if (($flag & 0xff000000))
			{
				$this->readBytes ( 16 );
			}
			$response = $this->readBytes ( $bodyLength );
			$uncompress = false;
			if (($flag & 0x00ff0000))
			{
				Logger::debug ( "uncompress data" );
				$uncompress = true;
			}
			$arrResponse = Util::amfDecode ( $response, $uncompress );
			Logger::trace ( "userclient callback:[%s] return:[%s]", $callback, $arrResponse );

			if (isset ( $arrResponse ['token'] ))
			{
				$this->token = $arrResponse ['token'];
			}

			if (($flag & 0x0000ff00))
			{
				foreach($arrResponse ['ret'] as  $value)
				{
					if (is_string ($value ))
					{
						$value = Util::amfDecode ( $value, false );
					}
					Logger::trace("userclient get {$value['callback']['callbackName']}");
					
					if( $value ['callback']['callbackName'] == $callback)
					{
						break;
					}
				}
				$arrResponse = $value;
			}

			if ($arrResponse ['err'] == 'ping')
			{
				Logger::trace("userclient wait for {$callback}, err=ping");
				continue;
			}
			if ($arrResponse ['err'] == 'fake' )
			{
				Logger::trace("userclient wait for {$callback}, err=fake");
				return array('ret' => 'fake');
			}
			if ($arrResponse ['err'] != 'ok' )
			{
				Logger::trace("userclient wait for {$callback}, err=".$arrResponse ['err']);
				throw new Exception ( $arrResponse ['err'] );
			}
			
			if (isset ( $arrResponse ['callback'] ) && $callback != $arrResponse ['callback']['callbackName'])
			{
				Logger::debug("$callback  vs ".$arrResponse ['callback']['callbackName']);
				continue;
			}
			$this->resetCallback();
			return $arrResponse ['ret'];
		}
	}
	public function receiveAnyData()
	{		
		while ( true )
		{
			$bodyLength = $this->readU32 ();
			$flag = $this->readU32 ();
			if (($flag & 0xff000000))
			{
				$this->readBytes ( 16 );
			}
			$response = $this->readBytes ( $bodyLength );
			$uncompress = false;
			if (($flag & 0x00ff0000))
			{
				Logger::debug ( "uncompress data" );
				$uncompress = true;
			}
			$arrResponse = Util::amfDecode ( $response, $uncompress );

			Logger::trace ( "userclient return:[%s]",  $arrResponse );
				
			if (isset ( $arrResponse ['token'] ))
			{
				$this->token = $arrResponse ['token'];
			}
	
			$returnData = array();
			if (($flag & 0x0000ff00))
			{
				foreach($arrResponse ['ret'] as  $key => $value)
				{
					if (is_string ($value ))
					{
						$value = Util::amfDecode ( $value, false );
					}		
					$returnData[] = $value;
				}				
			}
			else
			{
				$returnData = array($arrResponse);
			}

			return $returnData;
		}
	}

	public function receiveSomeData($arrCallback)
	{
		$returnData = array();
		while(!empty($arrCallback) )
		{
			$arrRet =  $this->receiveAnyData();
			
			$arrGet = array();
			foreach($arrRet as $ret)
			{
				$callback = $ret['callback']['callbackName'];
				$arrGet[] = $callback;
				if( in_array($callback, $arrCallback) )
				{
					$returnData[$callback] = $ret;
				}
				else
				{
					Logger::debug('ignore callback:%s', $callback);
				}
			}
			
			Logger::debug ( "get callback:%s", $arrGet );
			$arrCallback = array_diff($arrCallback, $arrGet);
		}
		return $returnData;
	}

	private function readU32()
	{

		$bytes = $this->readBytes ( 4 );
		$length = 0;
		$length += ord ( $bytes [0] ) << 24;
		$length += ord ( $bytes [1] ) << 16;
		$length += ord ( $bytes [2] ) << 8;
		$length += ord ( $bytes [3] );
		return $length;
	}

	private function readBytes($length)
	{

		$content = '';
		while ( $length )
		{
			$ret = @socket_read ( $this->socket, $length );
			$this->socketError ( 'socket_read', $ret );
			$length -= strlen ( $ret );
			$content .= $ret;
		}
		return $content;
	}

	private function writeU32($length)
	{

		$bytes = '';
		$bytes .= chr ( ($length & 0xff000000) >> 24 );
		$bytes .= chr ( ($length & 0x00ff0000) >> 16 );
		$bytes .= chr ( ($length & 0x0000ff00) >> 8 );
		$bytes .= chr ( ($length & 0x000000ff) );
		$this->writeBytes ( $bytes );
	}

	private function writeBytes($bytes)
	{

		$length = strlen ( $bytes );
		while ( $length )
		{
			$ret = @socket_write ( $this->socket, $bytes );
			$this->socketError ( 'socket_write', $ret );
			$bytes = substr ( $bytes, $ret );
			$length -= $ret;
		}
	}

	private function socketError($method, $ret)
	{

		if ($ret == false)
		{
			$errno = socket_last_error ( $this->socket );
			Logger::warning('socketError:%d', $errno);
			if ($errno == SOCKET_EINTR || $errno == SOCKET_EAGAIN)
			{
				return;
			}
			if($errno == 0)
			{
				$bt = debug_backtrace();
				Logger::warning('bt:%s', $bt);
				throw new Exception ( 'no data' );
			}
			Logger::fatal ( "%s:%s", $method, socket_strerror ( $errno ) );
			socket_close ( $this->socket );
			$this->socket = null;
			throw new Exception ( 'lcclient' );
		}
	}

	private function encrypt($data)
	{

		$raw = FrameworkConfig::MESS_CODE;
		for($index = 0; $index < strlen ( $data ); $index ++)
		{
			$a = ord ( $data [$index] );
			if ($index == strlen ( $data ) - 1)
			{
				$b = strlen ( $data );
			}
			else
			{
				$b = ord ( $data [$index + 1] );
			}
			$raw .= chr ( $a ^ $b );
		}
		return md5 ( $raw, true );
	}

	function __destruct()
	{

		if ($this->socket)
		{
			socket_close ( $this->socket );
			$this->socket = null;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
