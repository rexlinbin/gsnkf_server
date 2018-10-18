<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ArenaLock.class.php 49110 2013-05-29 11:36:14Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/arena/ArenaLock.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2013-05-29 11:36:14 +0000 (Wed, 29 May 2013) $
 * @version $Revision: 49110 $
 * @brief 
 *  
 **/

class ArenaLock
{
	private $arrKey = array();

	private $locker = null;
	
	private $preKey = ''; 

	public function __construct($preKey='arena#')
	{
		$this->preKey = $preKey;
		$this->locker = new Locker();
	} 
	
	public function lock()
	{
		$args = func_get_args();
		if (empty($args))
		{
			throw new Exception('sys');
		}
		$args = array_map("strval", $args);
		sort($args);
		try
		{
			foreach ($args as $arg)
			{
				$key = $this->preKey . $arg;				
				$this->locker->lock($key);
				$this->arrKey[] = $key;
			}
			return true;
		}
		catch ( Exception $e )
		{
			Logger::warning('lock exception. exception msg:%s', $e->getMessage());
			$this->unlock();
			return false;
		}		
	}
	
	public function unlock()
	{
		$this->arrKey = array_reverse($this->arrKey);
		foreach ($this->arrKey as $key)
		{
			$this->locker->unlock($key);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */