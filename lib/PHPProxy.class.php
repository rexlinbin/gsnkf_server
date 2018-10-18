<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PHPProxy.class.php 183883 2015-07-13 08:13:31Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/PHPProxy.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2015-07-13 08:13:31 +0000 (Mon, 13 Jul 2015) $
 * @version $Revision: 183883 $
 * @brief
 *
 **/

class PHPProxy
{

	private $socket;

	private $clazz;

	private $requestType;

	private $token;

	private $async;

	private $arrRequest;

	private $dummyReturn;

	private $db;

	private $group;

	function __construct($module, $clazz = null, $async = false, $requestType = RequestType::RELEASE)
	{

		$this->module = $module;
		$this->socket = null;
		$this->clazz = $clazz;
		$this->requestType = $requestType;
		$this->async = $async;
		$this->arrRequest = array ();
		$this->dummyReturn = false;
		$this->db = null;
		$this->group = null;
	}

	private function connect()
	{

		$this->socket = socket_create ( AF_UNIX, SOCK_STREAM, 0 );
		$this->socketError ( 'socket_create', $this->socket );
		
		socket_set_option ( $this->socket, SOL_SOCKET, SO_SNDTIMEO, 
				array ('sec' => FrameworkConfig::PROXY_CONNECT_TIMEOUT, 'usec' => 0 ) );
		
		$ret = socket_connect ( $this->socket, FrameworkConfig::PHPPROXY_PATH );
		$this->socketError ( 'socket_connect', $ret );
		
		socket_set_option ( $this->socket, SOL_SOCKET, SO_RCVTIMEO, 
				array ('sec' => FrameworkConfig::PROXY_READ_TIMEOUT, 'usec' => 0 ) );
		socket_set_option ( $this->socket, SOL_SOCKET, SO_SNDTIMEO, 
				array ('sec' => FrameworkConfig::PROXY_WIRTE_TIMEOUT, 'usec' => 0 ) );
	}

	function setDummyReturn($return)
	{

		$this->dummyReturn = $return;
	}

	function setClass($clazz)
	{

		$this->clazz = $clazz;
	}

	function setRequestType($requestType)
	{

		$this->requestType = $requestType;
	}

	function setAsync($async)
	{

		$this->async = $async;
	}

	function setDb($db)
	{

		$this->db = $db;
	}

	function setGroup($group)
	{

		$this->group = $group;
	}

	private function makeRequest($arrRequest, $encrypt = false)
	{

		$compressed = false;
		$request = Util::amfEncode ( $arrRequest, $compressed, 
				FrameworkConfig::PROXY_COMPRESS_THRESHOLD );
		if (empty ( $request ) || strlen ( $request ) > FrameworkConfig::MAX_REQUEST_SIZE)
		{
			Logger::warning ( "request:%s", bin2hex ( $request ) );
			throw new Exception ( 'sys' );
		}
		
		$flag = 0;
		$bodySize = strlen ( $request );
		if ($compressed)
		{
			$flag |= 0x00ff0000;
		}
		
		if ($encrypt)
		{
			$request = $this->encrypt ( $request ) . $request;
			$flag |= 0xff000000;
		}
		
		$request = $this->toU32 ( $bodySize ) . $this->toU32 ( $flag ) . $request;
		return $request;
	}

	function getModuleInfo($module, $newGroup)
	{

		if ($this->socket == null)
		{
			$this->connect ();
		}
		
		$this->token = "" . RPCContext::getInstance ()->getFramework ()->getLogid ();
		$group = RPCContext::getInstance ()->getFramework ()->getGroup ();
		//$db = RPCContext::getInstance ()->getFramework ()->getDb (); 如果外部指定了db，应该使用指定的db wuqilin 20150713
		$db = $this->db;
		if (empty ( $db ))
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}
		$arrRequest = array ('token' => $this->token, 'method' => 'module.info', 
				'args' => array ($module, $newGroup ), 'group' => $group, 'db' => $db );
		$request = $this->makeRequest ( $arrRequest );
		$this->writeBytes ( $request );
		
		Logger::trace ( "phpproxy request:%s", $arrRequest );
		return $this->getReturnData ();
	}

	function getZkInfo($path)
	{

		if ($this->socket == null)
		{
			$this->connect ();
		}
		
		$this->token = "" . RPCContext::getInstance ()->getFramework ()->getLogid ();
		$arrRequest = array ('token' => $this->token, 'method' => 'module.zkInfo', 
				'args' => array ($path ) );
		$request = $this->makeRequest ( $arrRequest );
		$this->writeBytes ( $request );
		
		Logger::trace ( "phpproxy request:%s", $arrRequest );
		return $this->getReturnData ();
	}

	function __call($method, $arrArg)
	{

		$this->arrRequest = array ();
		if ($this->socket == null)
		{
			$this->connect ();
		}
		
		$this->token = "" . RPCContext::getInstance ()->getFramework ()->getLogid ();
		if (! empty ( $this->clazz ))
		{
			$method = $this->clazz . '.' . $method;
		}
		$this->arrRequest = array ('method' => $method, 'args' => $arrArg, 
				'type' => $this->requestType, 'time' => time (), 'token' => $this->token, 
				'callback' => $method, 'return' => $this->dummyReturn );
		$request = $this->makeRequest ( $this->arrRequest );
		
		$group = $this->group;
		if (empty ( $group ))
		{
			$group = RPCContext::getInstance ()->getFramework ()->getGroup ();
		}
		
		$db = $this->db;
		if (empty ( $db ))
		{
			$db = RPCContext::getInstance ()->getFramework ()->getDb ();
		}
		$arrRequest = array ('token' => $this->token, 'method' => 'proxy', 'group' => $group, 
				'db' => $db, 'args' => array ($this->module, $request ) );
		$request = $this->makeRequest ( $arrRequest );
		$retryCount = 0;
		while ( true )
		{
			$this->writeBytes ( $request );
			Logger::trace ( "phpproxy request:%s", $this->arrRequest );
			if (! $this->async)
			{
				$arrRet = $this->getReturnData ( true );
				if ($arrRet ['err'] == 'retry' && $retryCount < FrameworkConfig::MAX_RETRY_NUM)
				{
					$retryCount ++;
					usleep ( 10000 );
					continue;
				}
				
				if ($arrRet ['err'] != 'ok')
				{
					if ($arrRet ['err'] != "fake")
					{
						Logger::fatal ( "proxy request:%s, return:%s", $this->arrRequest, 
								$arrRet ['err'] );
					}
					throw new Exception ( $arrRet ['err'] );
				}
				
				return $arrRet ['ret'];
			}
			else
			{
				break;
			}
		}
	}

	public function getReturnData($raw = false)
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
		
		if (($flag & 0x0000ff00))
		{
			if (count ( $arrResponse ['ret'] ) > 1)
			{
				Logger::fatal ( "invalid response, more than one repsonse found" );
				throw new Exception ( 'inter' );
			}
			
			if (is_string ( $arrResponse ['ret'] [0] ))
			{
				$arrResponse = Util::amfDecode ( $arrResponse ['ret'] [0], false );
			}
			else
			{
				$arrResponse = $arrResponse ['ret'] [0];
			}
		}
		
		Logger::trace ( "phpproxy return:%s", $arrResponse );
		
		if ($raw)
		{
			return $arrResponse;
		}
		
		if ($arrResponse ['err'] != 'ok')
		{
			if ($arrResponse ['err'] != 'fake')
			{
				Logger::fatal ( "proxy request:%s, return:%s", $this->arrRequest, 
						$arrResponse ['err'] );
			}
			throw new Exception ( $arrResponse ['err'] );
		}
		
		return $arrResponse ['ret'];
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

	private function toU32($length)
	{

		$bytes = '';
		$bytes .= chr ( ($length & 0xff000000) >> 24 );
		$bytes .= chr ( ($length & 0x00ff0000) >> 16 );
		$bytes .= chr ( ($length & 0x0000ff00) >> 8 );
		$bytes .= chr ( ($length & 0x000000ff) );
		return $bytes;
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
			if ($errno == SOCKET_EINTR)
			{
				return;
			}
			
			if (! empty ( $this->arrRequest ))
			{
				Logger::fatal ( "request:%s failed", $this->arrRequest );
			}
			Logger::fatal ( "%s:%s", $method, socket_strerror ( $errno ) );
			socket_close ( $this->socket );
			$this->socket = null;
			throw new Exception ( 'timeout' );
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
			@socket_close ( $this->socket );
			$this->socket = null;
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
