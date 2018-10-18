<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SetTestMode.test.php 178452 2015-06-12 02:13:47Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldpass/test/SetTestMode.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2015-06-12 02:13:47 +0000 (Fri, 12 Jun 2015) $
 * @version $Revision: 178452 $
 * @brief 
 *  
 **/
 
class SetTestMode extends BaseScript
{
	/**
	 * (non-PHPdoc)
	 * @see BaseScript::executeScript()
	 */
	public function executeScript($arrOption)
	{	
		// 默认的机器信息
		$arrInnerAddr = array('192.168.1.122','192.168.1.121','192.168.1.91');
		$crossAddr = '192.168.1.122';
		
		// 读取配置的机器信息
		if (!empty($arrOption)) 
		{
			$configFile = $arrOption[0];
			exec(sprintf('awk \'/inner/{print $2}\' %s ', $configFile), $arrRet);
			$arrInnerAddr = $arrRet;
			exec(sprintf('awk \'/cross/{print $2}\' %s ', $configFile), $arrRet);
			if (!empty($arrRet)) 
			{
				$crossAddr = $arrRet[0];
			}
			array_shift($arrOption);
		}
		
		// 是否是回滚
		if (!empty($arrOption) && strtolower($arrOption[0]) == 'resume')
		{
			$this->setMode($arrInnerAddr, 0);
			$this->setMode(array($crossAddr), 0);
			return;
		}
		
		// 设置各个服务器的test mode
		$currHour = date('H', Util::getTime());
		$nextHour = date('H', Util::getTime() + 3600);
		$testMode = $currHour % 2 ? 1 : 2;
		$this->setMode($arrInnerAddr, $testMode);
		$this->setMode(array($crossAddr), $testMode);
		
		// 设置跨服机crontab
		$arrCron = array();
		$arrCron[] = sprintf('01 * * * * $SIMPLE_BTSCRIPT $SCRIPT_ROOT/WorldPassEntry.php reward do');
		$arrCron[] = sprintf('30 * * * * $SIMPLE_BTSCRIPT $SCRIPT_ROOT/WorldPassEntry.php team   do');
		$this->setCronToMachine($arrCron, $crossAddr, 'cross');
		
	}
	
	public function setMode($arrAddr, $testMode)
	{
		$arrRet = array();
		foreach ($arrAddr as $aAddr)
		{
			$cmd = sprintf('ssh %s "/bin/sed -i \'/TEST_MODE/{s/[0-9-]\+/%d/;}\' /home/pirate/rpcfw/conf/WorldPass.cfg.php"', $aAddr, $testMode);
			printf("set test mode [%d] for host [%s]\n", $testMode, $aAddr);
			exec($cmd, $arrRet);
		}
	}
	
	public static function setCronToMachine($arrCron, $host, $field, $delOld = TRUE)
	{
		printf("set %s crontab to host:%s\n", $field, $host);
		foreach ($arrCron as $aCron)
		{
			printf("install new cron:%s\n", $aCron);
		}
		
		$tmpFile = '/tmp/crontab.worldpass';
		$bakFile = sprintf('/tmp/crontab.%s', date('Ymd-H-i-s'));
	
		if (empty($host))
		{
			exec(sprintf('crontab -l > %s && cat %s ', $bakFile, $bakFile), $arrRet);
		}
		else
		{
			exec(sprintf('ssh %s "crontab -l > %s && cat %s "', $host, $bakFile, $bakFile), $arrRet);
		}
	
		$arrBefore = array();
		$arrAfter = array();
		$foundTag = false;
		foreach ($arrRet as $key => $cronLine)
		{
			if ($delOld && preg_match(sprintf('/WorldPassEntry.php/'), $cronLine))
			{
				continue;
			}
			if ($foundTag == false)
			{
				$arrBefore[] = $cronLine;
			}
			else
			{
				$arrAfter[] = $cronLine;
			}
	
			if ($cronLine == sprintf('#worldpass %s', $field))
			{
				$foundTag = true;
			}
		}
	
		if ($foundTag == false)
		{
			$arrBefore[] = '';
			$arrBefore[] = sprintf('#worldpass %s',$field);
		}
	
		foreach ($arrCron as $aCron)
		{
			$arrBefore[] = $aCron;
		}
	
		$allCron = array_merge($arrBefore, $arrAfter);
		if (!empty($allCron[count($allCron) - 1]))
		{
			$allCron[] = "";
		}
	
		$allCronStr = implode("\n", $allCron);
		file_put_contents($tmpFile, $allCronStr);
	
		if (empty($host))
		{
			exec(sprintf('crontab %s', $tmpFile));
		}
		else
		{
			exec(sprintf('scp %s %s:%s && ssh %s "crontab %s"', $tmpFile, $host, $tmpFile, $host, $tmpFile));
		}
	}
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */