<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRobRobotTest.test.php 145187 2014-12-10 10:11:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/test/GuildRobRobotTest.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-10 10:11:39 +0000 (Wed, 10 Dec 2014) $
 * @version $Revision: 145187 $
 * @brief 
 *  
 **/
 
require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/test/UserClient.php";


class MyGuildRobRobot extends UserClient
{
	protected $err = false;
	protected $errTryNum = 1;

	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		printf('pid:%d login ok\n', $pid);
		$this->setClass('guildrob');
	}
}

class MyLog
{
	private static $fid;

	public static function init($filename)
	{
		self::$fid = fopen($filename, 'w');
	}

	private static function log($arrArg, $print = 0)
	{

		$arrMicro = explode ( " ", microtime () );
		$content = '[' . date ( 'Ymd H:i:s ' );
		$content .= sprintf ( "%06d", intval ( 1000000 * $arrMicro [0] ) );
		$content .= "]";

		foreach ( $arrArg as $idx => $arg )
		{
			if ($arg instanceof BtstoreElement)
			{
				$arg = $arg->toArray ();
			}
			if (is_array ( $arg ))
			{
				$arrArg [$idx] = var_export ( $arg, true );
			}
		}
		$content .= call_user_func_array ( 'sprintf', $arrArg );
		$content .= "\n";

		if($print)
		{
			echo $content;
		}
		fprintf(self::$fid, $content);

	}
	public static function debug()
	{
		$arrArg = func_get_args ();
		self::log($arrArg, false);
	}
	public static function info()
	{
		$arrArg = func_get_args ();
		self::log($arrArg, true);
	}
	public static function fatal()
	{
		$arrArg = func_get_args ();
		self::log($arrArg, true);
	}
}

class GuildRobRobotTest extends BaseScript
{
	private $ipaddr = ScriptConf::PRIVATE_HOST;
	
	protected function executeScript($arrOption)
	{
		MyLog::init('guild_rob_robot');
		
		$totalBattleTime = intval(btstore_get()->GUILD_ROB['battle_time']) + intval(btstore_get()->GUILD_ROB['ready_time']);
		
		var_dump($arrOption);
		if (count($arrOption) < 1)
		{
			printf("param is not enough\n");
			return;
		}
		$operationType = $arrOption[0];
		
		if ($operationType == 'create') 
		{
			if (count($arrOption) < 3)
			{
				printf("create param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$targetGuildId = $arrOption[2];
			
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->create($targetGuildId);
			var_dump($ret);
		}
		else if ($operationType == 'enter') 
		{
			if (count($arrOption) < 4)
			{
				printf("enter param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$robId = $arrOption[2];
			$transferId = $arrOption[3];
			
			if ($transferId < 0 || $transferId > 5) 
			{
				var_dump("transfer id error");
				return;
			}
				
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->enter($robId);
			var_dump($ret);
			$ret = $robot->getEnterInfo();
			var_dump($ret);
			
			$beginTime = time();
			while (true)
			{
				if (time() > $beginTime + $totalBattleTime + 10) 
				{
					printf("=======================   exit begin[%d],battleTime[%d],now[%d]	============================\n", $beginTime, $totalBattleTime, time());
					exit();
				}
				printf("================================================================\n");
				printf("================================================================\n");
				printf("=======================   join		============================\n");
				printf("================================================================\n");
				printf("================================================================\n");
				$ret = $robot->removeJoinCd();
				var_dump($ret);
				$ret = $robot->join($transferId);
				var_dump($ret);
				
				$count = 0;
				$randNum = rand(15, 20);
				while (++$count < $randNum)
				{
					$ret = $robot->receiveAnyData();
					var_dump($ret);
				}
			}
			
			$ret = $robot->leave();
			var_dump($ret);
		}
		else if ($operationType == 'remove') 
		{
			if (count($arrOption) < 3)
			{
				printf("enter param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$robId = $arrOption[2];
				
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->enter($robId);
			var_dump($ret);
			$ret = $robot->getEnterInfo();
			var_dump($ret);
			$ret = $robot->removeJoinCd();
			var_dump($ret);
			
			while (true)
			{
				$ret = $robot->receiveAnyData();
				var_dump($ret);
			}
			break;
		}
		else if ($operationType == 'speed')
		{
			if (count($arrOption) < 4)
			{
				printf("enter param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$robId = $arrOption[2];
			$transferId = $arrOption[3];
				
			if ($transferId < 0 || $transferId > 5)
			{
				var_dump("transfer id error");
				return;
			}
		
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->enter($robId);
			var_dump($ret);
			$ret = $robot->getEnterInfo();
			var_dump($ret);
			$ret = $robot->join($transferId);
			var_dump($ret);
			$ret = $robot->speedUp(2);
			var_dump($ret);
			$ret = $robot->speedUp(2);
			var_dump($ret);
				
			while (true)
			{
				$ret = $robot->receiveAnyData();
				var_dump($ret);
			}
			break;
		}
		else if ($operationType == 'enterSpecBarn')
		{
			if (count($arrOption) < 4)
			{
				printf("enter param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$robId = $arrOption[2];
			$specBarnPos = $arrOption[3];
			
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->enter($robId);
			var_dump($ret);
			$ret = $robot->getEnterInfo();
			var_dump($ret);
			
			while (true)
			{
				printf("================================================================\n");
				printf("================================================================\n");
				printf("=======================  enterSpecBarn =========================\n");
				printf("================================================================\n");
				printf("================================================================\n");
				$ret = $robot->enterSpecBarn($specBarnPos);
				var_dump($ret);
			
				$count = 0;
				$randNum = rand(2, 5);
				while (++$count < $randNum)
				{
					$ret = $robot->receiveAnyData();
					var_dump($ret);
				}
				
				$ret = $robot->leave();
				var_dump($ret);
				$ret = $robot->enter($robId);
				var_dump($ret);
				$ret = $robot->getEnterInfo();
				var_dump($ret);
			}
			
			while (true)
			{
				$ret = $robot->receiveAnyData();
				var_dump($ret);
			}
		}
		else if ($operationType == 'enterSpecBarn2')
		{
			if (count($arrOption) < 4)
			{
				printf("enter param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$robId = $arrOption[2];
			$specBarnPos = $arrOption[3];
				
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->enter($robId);
			var_dump($ret);
			$ret = $robot->getEnterInfo();
			var_dump($ret);
			$ret = $robot->enterSpecBarn($specBarnPos);
			var_dump($ret);
				
			while (true)
			{
				$ret = $robot->receiveAnyData();
				var_dump($ret);
			}
		}
		else if ($operationType == 'getRankByKill')
		{
			if (count($arrOption) < 3)
			{
				printf("enter param is not enough\n");
				return;
			}
			$operationPid = $arrOption[1];
			$robId = $arrOption[2];
			
			$robot = new MyGuildRobRobot($this->ipaddr, 7777, $operationPid);
			$ret = $robot->enter($robId);
			var_dump($ret);
			$ret = $robot->getEnterInfo();
			var_dump($ret);
			$ret = $robot->getRankByKill();
			var_dump($ret);
			$ret = $robot->getRankByKill(true);
			var_dump($ret);
		}
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */