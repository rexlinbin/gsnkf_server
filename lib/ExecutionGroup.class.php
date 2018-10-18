<?php
/**********************************************************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ExecutionGroup.class.php 55205 2013-07-12 10:35:57Z HaopingBai $
 * 
 **********************************************************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/lib/ExecutionGroup.class.php $
 * @author $Author: HaopingBai $(baihaoping@babeltime.com)
 * @date $Date: 2013-07-12 18:35:57 +0800 (星期五, 12 七月 2013) $
 * @version $Revision: 55205 $
 * @brief 用于使用pcntl_fork一次性执行多个函数，并且等到多个进程都结束时再返回所有失败的函数
 * 使用如下
 * $eg = new ExecutionGroup();
 * $eg->addExecution('echo', array('hello'));
 * $eg->addExecutions('echo', array(array('world')));
 * $arrFailed = $eg->execute();
 * 
 **/
class ExecutionGroup
{

	private $arrExecution;

	private $compatible;

	/**
	 * 創建一個執行組
	 * @param bool $compatible 當pcntl模塊未被加載時是否使用兼容模式，所謂兼容模式即順序執行所有方法而非並行
	 * @throws Exception
	 */
	public function __construct($compatible = false)
	{

		$this->compatible = $compatible;
		if (! function_exists ( 'pcntl_fork' ))
		{
			Logger::fatal ( "pcntl module not loaded when using ExecutionGroup" );
			if (! $compatible)
			{
				throw new Exception ( 'inter' );
			}
		}
		else
		{
			$this->compatible = false;
		}
		$this->reset ();
	}

	private function reset()
	{

		$this->arrExecution = array ();
	}

	/**
	 * 添加一個需要執行的方法
	 * @param miexed $method 符合php中的callback規範的函數
	 * @param array $arrArg 調用函數對應的參數
	 */
	public function addExecution($method, $arrArg)
	{

		$this->arrExecution [] = array ('method' => $method, 'args' => $arrArg );
	}

	/**
	 * 一次性添加多個方法相同而僅有參數不同的方法
	 * @param mixed $method 調用的方法
	 * @param array $arrArgs 多個參數列表
	 */
	public function addExecutions($method, $arrArgs)
	{

		foreach ( $arrArgs as $arrArg )
		{
			$this->addExecution ( $method, $arrArg );
		}
	}

	/**
	 * 實際執行方法,開多個子進程執行，直到所有進程結束
	 * @param bool $log 出錯時是否產生日誌
	 * @return 所有失敗的執行列表
	 */
	public function execute($log = true)
	{

		$arrPid = array ();
		$arrFailedExecution = array ();
		foreach ( $this->arrExecution as $execution )
		{
			if ($this->compatible)
			{
				try
				{
					call_user_func_array ( $execution ['method'], $execution ['args'] );
				}
				catch ( Exception $e )
				{
					Logger::fatal ( "uncaught exception:%s", $e->getMessage () );
					$arrFailedExecution [] = $execution;
				}
				continue;
			}
			
			$pid = pcntl_fork ();
			if ($pid == 0)
			{
				try
				{
					call_user_func_array ( $execution ['method'], $execution ['args'] );
					exit ( 0 );
				}
				catch ( Exception $e )
				{
					if ($log)
					{
						Logger::fatal ( "call execution:%s failed:%s", $execution, 
								$e->getMessage () );
					}
					exit ( 1 );
				}
			}
			else if ($pid > 0)
			{
				$arrPid [$pid] = $execution;
			}
			else
			{
				if ($log)
				{
					Logger::fatal ( "fork failed for execution:%s", $execution );
				}
				$arrFailedExecution [] = $execution;
			}
		}
		
		$status = 0;
		foreach ( $arrPid as $pid => $execution )
		{
			pcntl_waitpid ( $pid, $status );
			if (pcntl_wifexited ( $status ))
			{
				$status = pcntl_wexitstatus ( $status );
				if ($status == 0)
				{
					continue;
				}
			}
			$arrFailedExecution [$pid] = $execution;
		}
		
		$this->reset ();
		return $arrFailedExecution;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */