<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CrontabExecutor.php 60629 2013-08-21 09:51:53Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/CrontabExecutor.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-08-21 09:51:53 +0000 (Wed, 21 Aug 2013) $
 * @version $Revision: 60629 $
 * @brief
 *
 **/
class CrontabExecutor extends BaseScript
{

	private function printUsage()
	{

		$usage = "btscript CrontabExecutor.php [offset|normal] [sync|async] script arg1 arg2 ...";
		$this->log ( $usage );
		exit ( 0 );
	}

	private function execOffset($script, $arrArg, $arrData, $isSync)
	{

		$arrPid = array ();
		foreach ( $arrData as $key => $db )
		{
			global $argc, $argv;
			$argc = count ( $arrArg ) + 7;
			$argv [0] = __FILE__;
			$argv [1] = '-f';
			$argv [2] = $script;
			$argv [3] = '-g';
			$argv [4] = $key;
			$argv [5] = '-d';
			$argv [6] = $db;
			foreach ( $arrArg as $i => $arg )
			{
				$argv [7 + $i] = $arg;
			}
			
			Logger::debug ( "child process started with argc:%d, args:%s", $argc, $argv );
			
			$pid = pcntl_fork ();
			if ($pid == 0)
			{
				//子进程
				require_once ($script);
				BaseScript::main ();
				exit ( 0 );
			}
			else if ($pid == - 1)
			{
				Logger::fatal ( "create process for game:%s script:%s failed", $key, $script );
			}
			else
			{
				//父进程
				if ($isSync)
				{
					$this->waitPid ( $pid, $key, $script );
				}
				else
				{
					$arrPid [$pid] = $key;
					usleep ( ScriptConf::CRONTAB_FORK_INTERVAL );
				}
			}
		}
		
		foreach ( $arrPid as $pid => $key )
		{
			$this->waitPid ( $pid, $key, $script );
		}
	}

	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	protected function executeScript($arrOption)
	{

		if (count ( $arrOption ) < 3)
		{
			$this->printUsage ();
		}
		
		$isOffset = false;
		switch ($arrOption [0])
		{
			case 'offset' :
				$isOffset = true;
				break;
			case 'normal' :
				break;
			default :
				$this->printUsage ();
		}
		
		$isSync = false;
		switch ($arrOption [1])
		{
			case 'sync' :
				$isSync = true;
				break;
			case 'async' :
				break;
			default :
				$this->printUsage ();
		}
		
		$script = $arrOption [2];
		if (! is_file ( $script ))
		{
			$this->printUsage ();
		}
		
		$arrArg = array_slice ( $arrOption, 3 );
		
		$store = btstore_get ();
		foreach ( ScriptConf::$ARR_PRELOAD_BTSTORE as $key )
		{
			$store->$key;
		}
		
		$arrGames = $this->getAllGames ( $isOffset );
		$slept = 0;
		$arrPid = array ();
		foreach ( $arrGames as $offset => $arrData )
		{
			if ($slept < $offset)
			{
				Logger::debug ( 'offset enabled, should sleep %d seconds', $offset );
				sleep ( $offset - $slept );
				$slept = $offset;
			}
			
			$pid = pcntl_fork ();
			if ($pid == 0)
			{
				$this->execOffset ( $script, $arrArg, $arrData, $isSync );
				exit ( 0 );
			}
			else if ($pid == - 1)
			{
				Logger::fatal ( "create process for offset:%s script:%s failed", $offset, $script );
			}
			else
			{
				$arrPid [$pid] = $offset;
			}
		}
		
		foreach ( $arrPid as $pid => $offset )
		{
			$this->waitPid ( $pid, $offset, $script );
		}
	}

	private function waitPid($pid, $key, $script)
	{

		$status = 0;
		Logger::debug ( "waiting child process:%d", $pid );
		$ret = pcntl_waitpid ( $pid, $status );
		Logger::debug ( "child process:%d ended with status:%d", $pid, $ret );
		if ($ret != $pid)
		{
			Logger::fatal ( "wait game:%s script:%s failed", $key, $script );
		}
	}

	private function pregFile($cfgFile, $pattern, &$arrMatch)
	{

		Logger::debug ( 'check file:%s', $cfgFile );
		if (! is_file ( $cfgFile ))
		{
			Logger::fatal ( "file:%s not found", $cfgFile );
			return false;
		}
		
		$file = fopen ( $cfgFile, 'r' );
		$offset = - 1;
		while ( ! feof ( $file ) )
		{
			$data = fgets ( $file );
			if (preg_match ( $pattern, $data, $arrMatch ))
			{
				return true;
			}
		}
		
		return false;
	}

	private function getAllGames($isOffset)
	{

		$dir = opendir ( CONF_ROOT . '/gsc' );
		$arrRet = array ();
		while ( true )
		{
			$child = readdir ( $dir );
			if (empty ( $child ))
			{
				break;
			}
			
			if (substr ( $child, 0, 4 ) != 'game')
			{
				continue;
			}
			
			$cfgFile = CONF_ROOT . '/gsc/' . $child . '/Game.cfg.php';
			$arrMatch = array ();
			if ($this->pregFile ( $cfgFile, '#^\s*const\s+BOSS_OFFSET\s*=\s*(\d+)\s*;#', $arrMatch ))
			{
				$offset = $arrMatch [1];
				Logger::debug ( 'game:%s, offset:%d', $child, $offset );
			}
			else
			{
				Logger::fatal ( "game:%s has no offset found", $child );
				continue;
			}
			
			$cfgFile = ScriptConf::LCSERVER_CFG_ROOT . '/' . $child . '.args';
			$arrMatch = array ();
			if ($this->pregFile ( $cfgFile, '#.*-d\s+([a-zA-Z0-9_]+).*#', $arrMatch ))
			{
				$db = $arrMatch [1];
				Logger::debug ( 'game:%s, db:%s found', $child, $db );
			}
			else
			{
				Logger::fatal ( "game:%s has no db found", $child );
				continue;
			}
			
			if ($isOffset)
			{
				$arrRet [$offset] [$child] = $db;
			}
			else
			{
				$arrRet [0] [$child] = $db;
			}
		}
		
		if (! ksort ( $arrRet ))
		{
			Logger::fatal ( "sort game array failed" );
			throw new Exception ( 'inter' );
		}
		
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */