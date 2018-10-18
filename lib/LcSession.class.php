<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: LcSession.class.php 89091 2014-02-07 03:46:40Z wuqilin $
 * 
 **********************************************************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/LcSession.class.php $
 * @author $Author: wuqilin $(baihaoping@babeltime.com)
 * @date $Date: 2014-02-07 03:46:40 +0000 (Fri, 07 Feb 2014) $
 * @version $Revision: 89091 $
 * @brief 
 * 
 **/
class LcSession implements ISession
{

	/**
	 * 當前維護的session
	 * @var array
	 */
	private $arrSession;

	/**
	 * 只讀的session
	 * @var array
	 */
	private $arrISession;

	/**
	 * session發生了改變
	 * @var array
	 */
	private $sessionChanged;

	public function __construct()
	{

		$this->arrSession = array ('global.dummy' => true );
		$this->arrISession = array ('global.dummy' => true );
		$this->sessionChanged = false;
	}

	/* (non-PHPdoc)
	 * @see ISession::start()
	 */
	public function start($arrSession, $arrISession = null)
	{

		Logger::debug ( "session started" );
		$this->arrSession = $arrSession;
		$this->arrISession = $arrISession;
	}

	/* (non-PHPdoc)
	 * @see ISession::end()
	 */
	public function end()
	{

		Logger::debug ( "session end" );
		if (! $this->sessionChanged)
		{
			Logger::debug ( "session not changed, ignore session encode" );
			return;
		}
		
		if (isset ( $this->arrSession [SessionConf::SESSION_KEY] ))
		{
			Logger::debug ( "no need to encode session" );
			return;
		}
		
		Logger::debug ( "encode session now" );
		$arrReserved = array ();
		$globalPrefix = 'global.';
		$length = strlen ( $globalPrefix );
		foreach ( $this->arrSession as $key => $value )
		{
			$reserved = isset ( SessionConf::$ARR_RESERVED_KEYS [$key] );
			if ($reserved || substr ( $key, 0, $length ) == $globalPrefix)
			{
				$arrReserved [$key] = $value;
				unset ( $this->arrSession [$key] );
			}
		}
		
		if (! empty ( $this->arrSession ))
		{
			if (SessionConf::SESSION_COMPRESS)
			{
				$compress = true;
				$arrReserved [SessionConf::SESSION_KEY] = Util::amfEncode ( $this->arrSession, 
						$compress );
			}
			else
			{
				$arrReserved [SessionConf::SESSION_KEY] = Util::amfEncode ( $this->arrSession );
			}
		}
		
		$this->arrSession = $arrReserved;
	}

	/* (non-PHPdoc)
	 * @see ISession::getSession()
	 */
	public function getSession($key)
	{

		if (isset ( $this->arrSession [$key] ))
		{
			return $this->arrSession [$key];
		}
		else if (isset ( $this->arrISession [$key] ))
		{
			return $this->arrISession [$key];
		}
		else if (isset ( $this->arrSession [SessionConf::SESSION_KEY] ))
		{
			Logger::debug ( "key:%s not found, try to decode session", $key );
			if (SessionConf::SESSION_COMPRESS)
			{
				$arrData = Util::amfDecode ( $this->arrSession [SessionConf::SESSION_KEY], true );
			}
			else
			{
				$arrData = Util::amfDecode ( $this->arrSession [SessionConf::SESSION_KEY] );
			}
			
			unset ( $this->arrSession [SessionConf::SESSION_KEY] );
			$this->arrSession += $arrData;
			return $this->getSession ( $key );
		}
		else
		{
			return null;
		}
	}

	/* (non-PHPdoc)
	 * @see ISession::getSessions()
	 */
	public function getSessions()
	{

		return $this->arrSession;
	}

	/* (non-PHPdoc)
	 * @see ISession::setSession()
	 */
	public function setSession($key, $value)
	{

		if (isset ( $this->arrSession [$key] ))
		{
			if ($this->arrSession [$key] == $value)
			{
				Logger::debug ( "session:%s not changed", $key );
				return;
			}
			else
			{
				Logger::debug ( "session old:%s, new:%s", $this->arrSession [$key], $value );
			}
		}
		
		$this->arrSession [$key] = $value;
		$this->sessionChanged = true;
		Logger::debug ( "session changed by setSession key:%s", $key );
	}

	/* (non-PHPdoc)
	 * @see ISession::resetSession()
	 */
	public function resetSession()
	{

		$globalPrefix = 'global.';
		$length = strlen ( $globalPrefix );
		foreach ( $this->arrSession as $key => $value )
		{
			if (substr ( $key, 0, $length ) == $globalPrefix)
			{
				continue;
			}
			unset ( $this->arrSession [$key] );
		}
		$this->sessionChanged = true;
	}

	/* (non-PHPdoc)
	 * @see ISession::unsetSession()
	 */
	public function unsetSession($key)
	{

		if (! array_key_exists ( $key, $this->arrSession ))
		{
			return;
		}
		
		unset ( $this->arrSession [$key] );
		$this->sessionChanged = true;
		Logger::debug ( "session changed by unsetSession key:%s", $key );
	}

	/* (non-PHPdoc)
	 * @see ISession::changed()
	 */
	public function changed()
	{

		return $this->sessionChanged;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */