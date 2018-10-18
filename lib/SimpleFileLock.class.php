<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SimpleFileLock.class.php 157015 2015-02-04 13:19:16Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/SimpleFileLock.class.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2015-02-04 13:19:16 +0000 (Wed, 04 Feb 2015) $
 * @version $Revision: 157015 $
 * @brief 
 *  
 **/


class SimpleFileLock
{
	protected	$mPath;
	protected 	$mHandle;
	protected	$mAutoCreate;

	function __construct($path, $autoCreate = false)
	{
		$this->mPath = $path;
		$this->mAutoCreate = $autoCreate;
	}

	public function getHandle()
	{
		if ( !empty($this->mHandle) )
		{
			Logger::debug('handle has inited');
			return;
		}
		
		if ( file_exists($this->mPath) )
		{
			$this->mHandle = fopen($this->mPath, 'r');
		}
		else
		{
			if ( $this->mAutoCreate )
			{
				$this->mHandle = fopen($this->mPath, 'w');
				Logger::debug('create file:%s', $this->mPath);
				if ( empty($this->mHandle) )
				{
					throw new InterException('create file:%s failed', $this->mPath);
				}
			}
			else
			{
				throw new InterException('open file:%s failed', $this->mPath);
			}
		}
		return $this->mHandle;
	}

	public function lock()
	{
		if (empty($this->mHandle))
		{
			$this->getHandle();
		}
		$ret = flock($this->mHandle, LOCK_EX | LOCK_NB);

		Logger::debug('lock file:%s, ret:%d', $this->mPath, $ret);
		return $ret;
	}

	public function unlock()
	{
		if (empty($this->mHandle))
		{
			throw new InterException('not lock, cant unlock');
		}

		$ret = flock($this->mHandle, LOCK_UN);
		if (!$ret)
		{
			throw new InterException('unlock failed. path:%s', $this->mPath );
		}
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */