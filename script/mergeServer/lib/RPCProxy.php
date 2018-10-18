<?php
/***************************************************************************
 * 
 * Copyright(c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RPCProxy.php 260912 2016-09-06 05:50:57Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/mergeServer/lib/RPCProxy.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-09-06 05:50:57 +0000 (Tue, 06 Sep 2016) $
 * @version $Revision: 260912 $
 * @brief 
 *  
 **/

require_once  dirname ( __FILE__ )  . '/Util.class.php';
 
class RPCProxy
{
	private $socket;

	private $public;

	private $clazz;

	private $requestType;

	private $token;

	private $async;

	private $callback;

	function __construct($server, $port, $public = false, $clazz = null, $async = false, $requestType = 1)
	{
		$this->public = $public;
		$this->clazz = $clazz;
		$this->requestType = $requestType;
		$this->token = "0";
		$this->async = $async;
		$this->server = $server;
		$this->port = $port;
		$this->socket = null;
	}

	function connect($server, $port)
	{
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$this->socketError('socket_create', $this->socket);

		printf("connecting to server:%s at port:%d\n", $server, $port);
		socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));

		$ret = socket_connect($this->socket, $server, $port);
		$this->socketError('socket_connect', $ret);

		socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
		socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));
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

	function setAsync($async)
	{
		$this->async = $async;
	}

	function __call($method, $arrArg)
	{
		if(empty($this->socket))
		{
			$this->connect($this->server, $this->port);
		}

		if(!empty($this->clazz))
		{
			$method = $this->clazz . '.' . $method;
		}

		$arrRequest = array('method' => $method, 'args' => $arrArg, 'type' => $this->requestType,
							'time' => time(), 'token' => $this->token, 'callback' => $method);
		$this->callback = $method;
		$request = Util::amfEncode($arrRequest);
		if(empty($request) || strlen($request) > 1024000)
		{
			printf("invalid request:%s\n", bin2hex($request));
			exit(1);
		}

		$flag = 0;
		if(strlen($request) > 102400)
		{
			$size = strlen($request);
			$flag |= 0x00ff0000;
			$request = gzcompress($request);
			printf("request too large, compress from %d to %d\n", $size, strlen($request));
		}
		$this->writeU32(strlen($request));
		if($this->public)
		{
			$flag |= 0xff000000;
			$this->writeU32($flag);
			$this->writeBytes($this->encrypt($request));
		}
		else
		{
			$this->writeU32($flag);
		}
		$this->writeBytes($request);
		//printf("rpcproxy request:%s\n", var_export($arrRequest, true));
		if(!$this->async)
		{
			return $this->getReturnData();
		}
	}

	public function getReturnData()
	{
		while(true)
		{
			$bodyLength = $this->readU32();
			$flag = $this->readU32();
			if(($flag & 0xff000000))
			{
				$this->readBytes(16);
			}
			$response = $this->readBytes($bodyLength);
			$uncompress = false;
			if(($flag & 0x00ff0000))
			{
				printf("uncompress data\n");
				$uncompress = true;
			}
			$arrResponse = Util::amfDecode($response, $uncompress);
			//printf("rpcproxy return:%s\n", var_export($arrResponse, true));

			if(isset($arrResponse ['token']))
			{
				$this->token = $arrResponse ['token'];
			}

			if(($flag & 0x0000ff00))
			{
				if(count($arrResponse ['ret']) > 1)
				{
					printf("invalid response, more than one repsonse found\n");
					exit(1);
				}

				if(is_string($arrResponse ['ret'] [0]))
				{
					$arrResponse = Util::amfDecode($arrResponse ['ret'] [0], false);
				}
				else
				{
					$arrResponse = $arrResponse ['ret'] [0];
				}
			}

			if($arrResponse ['err'] == 'ping')
			{
				continue;
			}
			if($arrResponse ['err'] != 'ok')
			{
				printf("err:%s\n", $arrResponse ['err']);
				exit(1);
			}

			if(isset($arrResponse ['callback']) && $this->callback != $arrResponse ['callback'])
			{
				continue;
			}

			return $arrResponse ['ret'];
		}
	}

	private function readU32()
	{
		$bytes = $this->readBytes(4);
		$length = 0;
		$length += ord($bytes [0]) << 24;
		$length += ord($bytes [1]) << 16;
		$length += ord($bytes [2]) << 8;
		$length += ord($bytes [3]);
		return $length;
	}

	private function readBytes($length)
	{
		$content = '';
		while($length)
		{
			$ret = @socket_read($this->socket, $length);
			$this->socketError('socket_read', $ret);
			$length -= strlen($ret);
			$content .= $ret;
		}
		return $content;
	}

	private function writeU32($length)
	{
		$bytes = '';
		$bytes .= chr(($length & 0xff000000) >> 24);
		$bytes .= chr(($length & 0x00ff0000) >> 16);
		$bytes .= chr(($length & 0x0000ff00) >> 8);
		$bytes .= chr(($length & 0x000000ff));
		$this->writeBytes($bytes);
	}

	private function writeBytes($bytes)
	{
		$length = strlen($bytes);
		while($length)
		{
			$ret = @socket_write($this->socket, $bytes);
			$this->socketError('socket_write', $ret);
			$bytes = substr($bytes, $ret);
			$length -= $ret;
		}
	}

	private function socketError($method, $ret)
	{
		if($ret == false)
		{
			$errno = socket_last_error($this->socket);
			if($errno == SOCKET_EINTR)
			{
				return;
			}
			printf("%s:%s\n", $method, socket_strerror($errno));
			socket_close($this->socket);
			$this->socket = null;
			exit(1);
		}
	}

	private function encrypt($data)
	{
		$raw = 'BabelTime';
		for($index = 0; $index < strlen($data); $index ++)
		{
			$a = ord($data [$index]);
			if($index == strlen($data) - 1)
			{
				$b = strlen($data);
			}
			else
			{
				$b = ord($data [$index + 1]);
			}
			$raw .= chr($a ^ $b);
		}
		return md5($raw, true);
	}

	function __destruct()
	{
		if($this->socket)
		{
			socket_close($this->socket);
			$this->socket = null;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */