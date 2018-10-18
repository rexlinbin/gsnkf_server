<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkUserFunc.php 65976 2013-09-24 03:30:04Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkUserFunc.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-09-24 03:30:04 +0000 (Tue, 24 Sep 2013) $
 * @version $Revision: 65976 $
 * @brief 
 *  
 **/
class CheckUserFunc extends BaseScript
{
	/* (non-PHPdoc)
	 * @see BaseScript::executeScript()
	*/
	protected function executeScript($arrOption)
	{
		if(!empty($arrOption[0]) && $arrOption[0] == 'help')
		{
			$this->usage();
			return;
		}
		
		$rand = rand(0000, 9999);
		$proxy = new ServerProxy();
		
		echo "getUserByUname:\n";
		$uname = "26";
		$arrField = array('pid');
		$arrRet = $proxy->getUserByUname($uname, $arrField);
		print_r($arrRet);
		
		echo "getUserByPid:\n";
		$pid = $arrRet['pid'];
		$arrField = array('uid');
		$arrRet = $proxy->getUserByPid($pid, $arrField);
		print_r($arrRet);
		$uid = $arrRet['uid'];
		
		echo "getArrUserByPid:\n";
		$arrPid = array($pid);
		$arrField = array('uid');
		$arrRet = $proxy->getArrUserByPid($arrPid, $arrField);
		print_r($arrRet);
		
		echo "getMultiInfoByPid:\n";
		$arrPid = array($pid);
		$arrField['user'] = array('uid');
		$afterLastLoginTime = 0;
		$arrRet = $proxy->getMultiInfoByPid($arrPid, $arrField, $afterLastLoginTime);
		print_r($arrRet);
		
		echo "addGold:\n";
		$curTime = Util::getTime();
		$orderId = $rand;
		$goldNum = 100;
		$returnNum = 1;
		$arrRet = $proxy->addGold($uid, $orderId, $goldNum, $returnNum);
		print_r($arrRet);
		echo "\n";
		
		echo "getTotalCount:\n";
		$arrRet = $proxy->getTotalCount();
		print_r($arrRet);
		echo "\n";
		
		echo "getTop:\n";
		$type = 'arena';
		$offset = 0;
		$limit = 10;
		$arrRet = $proxy->getTop($type, $offset, $limit);
		print_r($arrRet);
		
		echo "getBattleRecord:\n";
		$brid = 10;
		$arrRet = $proxy->getBattleRecord($brid);
		print_r($arrRet);
		echo "\n";
		
		echo "getArrOrder:\n";
		$arrField = array('uid', 'order_id');
		$beginTime = $curTime - 10;
		$endTime = $curTime + 10;
		$offset = 0;
		$limit = 1;
		$arrRet = $proxy->getArrOrder($arrField, $beginTime, $endTime, $offset, $limit);
		print_r($arrRet);
		
		echo "getOrder:\n";
		$orderId = $arrRet[0]['order_id'];
		$arrField = array('uid');
		$arrRet = $proxy->getOrder($orderId, $arrField);
		print_r($arrRet);
		
		echo "getTopGuild:\n";
		$arrRet = $proxy->getTopGuild(10);
		print_r($arrRet);		
		
		echo "refreshActivityConf:\n";
		$arrRet = $proxy->refreshActivityConf(10);
		print_r($arrRet);
		
		echo "getActivityConf:\n";
		$arrRet = $proxy->getActivityConf();
		print_r($arrRet);
	}
	
	private function usage()
	{
		echo "usage: btscript game001 CheckUserFunc.php\n";
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */