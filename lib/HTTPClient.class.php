<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HTTPClient.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/HTTPClient.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

class HTTPClient
{

	private $conn;

	private $arrCookie;

	private $arrHeader;

	private $connectTimeout;

	private $executeTimeout;

	private $targetURL;

	const STATUS_OK = 200;

	public function __construct($url)
	{

		$this->reset ();
		$this->conn = curl_init ();
		$this->targetURL = $url;
		$this->checkError ( 'curl_init', $this->conn );
	}

	public function reset()
	{

		if (! empty ( $this->conn ))
		{
			curl_close ( $this->conn );
		}
		$this->conn = null;
		$this->arrCookie = array ();
		$this->arrHeader = array ();
		$this->connectTimeout = FrameworkConfig::PROXY_CONNECT_TIMEOUT;
		$this->executeTimeout = FrameworkConfig::PROXY_EXECUTE_TIMEOUT;
	}

	private function checkError($method, $ret)
	{

		if (! empty ( $ret ))
		{
			return;
		}
		Logger::warning ( "targetURL:%s, $method failed:%s", $this->targetURL,
				curl_error ( $this->conn ) );
		throw new Exception ( 'network' );
	}

	public function setCookie($name, $value)
	{

		$this->arrCookie [] = sprintf ( "%s=%s", $name, $value );
	}

	public function setConnectTimeout($connectTimeout)
	{

		$this->connectTimeout = $connectTimeout;
	}

	public function setExecuteTimeout($executeTimeout)
	{

		$this->executeTimeout = $executeTimeout;
	}

	public function setHeader($key, $value)
	{

		$this->arrHeader [] = sprintf ( "%s: %s", $key, $value );
	}

	public function resetTargetURL($URL)
	{
		$this->targetURL = $URL;
	}
	
	
	public function post($postData, $needResult = true)
	{

		$arrOpts = array (CURLOPT_POSTFIELDS => $postData, CURLOPT_POST => true,
				CURLOPT_HTTPGET => false );
		return $this->execute ( $arrOpts, $needResult );
	}

	public function get($needResult = true)
	{

		$arrOpts = array (CURLOPT_HTTPGET => true );
		return $this->execute ( $arrOpts, $needResult );
	}

	private function execute($arrOpts, $needResult)
	{

		$arrOpts [CURLOPT_RETURNTRANSFER] = $needResult;
		$arrOpts [CURLOPT_CONNECTTIMEOUT] = $this->connectTimeout;
		$arrOpts [CURLOPT_TIMEOUT] = $this->executeTimeout;
		$arrOpts [CURLOPT_URL] = $this->targetURL;

		if (! empty ( $this->arrHeader ))
		{
			$arrOpts [CURLOPT_HTTPHEADER] = $this->arrHeader;
		}

		if (! empty ( $this->arrCookie ))
		{
			$arrOpts [CURLOPT_COOKIE] = implode ( '; ', $this->arrCookie );
		}
		$ret = curl_setopt_array ( $this->conn, $arrOpts );
		$this->checkError ( 'curl_setopt_array', $ret );

		Logger::debug ( "request send to http server" );
		$respData = curl_exec ( $this->conn );

		if ($needResult)
		{
			Logger::debug ( "response read from http server" );
			$this->checkError ( 'curl_exec', $respData );

			$status = curl_getinfo ( $this->conn, CURLINFO_HTTP_CODE );
			if ($status != self::STATUS_OK)
			{
				Logger::fatal ( "http server return status:%d", $status );
				throw new Exception ( 'inter' );
			}
		}
		return $respData;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
