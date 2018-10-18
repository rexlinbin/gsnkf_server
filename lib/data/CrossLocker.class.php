<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CrossLocker.class.php 183116 2015-07-09 04:57:33Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/data/CrossLocker.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-07-09 04:57:33 +0000 (Thu, 09 Jul 2015) $
 * @version $Revision: 183116 $
 * @brief 
 *  
 **/
 
class CrossLocker
{
	private $proxy;
	private $db;

	function __construct($db)
	{
		$proxy = new PHPProxy('data');
		$proxy->setDb($db);
		$arrModule = $proxy->getModuleInfo('data', $db);
		Logger::trace("cross locker db[%s] dataproxy info[%s]", $db, $arrModule);
		
		$this->proxy = new RPCProxy($arrModule['host'], $arrModule ['port']);
		$this->proxy->setClass('locker');
		$this->db = $db;
	}

	protected function checkProxy()
	{
		if (empty($this->proxy))
		{
			Logger::fatal("proxy already disconntected");
			throw new Exception ('inter');
		}
	}

	function lock($key)
	{
		try
		{
			$this->checkProxy();
			$key = $this->db . '.' . $key;
			$this->proxy->setToken(RPCContext::getInstance()->getFramework()->getLogid());
			return $this->proxy->lock($key);
		}
		catch (Exception $e)
		{
			Logger::fatal("lock for key:%s time out", $key);
			$this->proxy = null;
			throw new Exception('dummy');
		}
	}

	function unlock($key)
	{
		try
		{
			$this->checkProxy();
			$key = $this->db . '.' . $key;
			$this->proxy->setToken(RPCContext::getInstance()->getFramework()->getLogid());
			return $this->proxy->unlock($key);
		}
		catch (Exception $e)
		{
			Logger::fatal ("unlock failed:%s", $e->getMessage ());
			$this->proxy = null;
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */