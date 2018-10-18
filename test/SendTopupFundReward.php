<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SendTopupFundReward.php 197763 2015-09-10 05:23:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/SendTopupFundReward.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-09-10 05:23:41 +0000 (Thu, 10 Sep 2015) $
 * @version $Revision: 197763 $
 * @brief 
 *  
 **/

/**
 * 充值累计活动补偿使用
 * 先查询所有用户的充值总额，根据活动的档次，补偿相应的物品。
 * 在logic机器上执行：
 * /home/pirate/programs/php/bin/php  /home/pirate/rpcfw/lib/ScriptRunner.php -g game001 -d pirate001 -f SendTopupFundReward.php check gameId
 */
class SendTopupFundReward extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 SendTopupFundReward.php check|fix gameId\n";
		
		$startTime = strtotime('2015-02-28 12:00:00');
		$endTime = strtotime('2015-03-02 23:59:00');
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		$game = $arrOption[1];
		
		$batchNum = 10;
		$offset = 0;
		$data = new CData();
		while (true)
		{
			printf("offset:%d, limit:%d\n", $offset, $batchNum);
			Logger::info('get users. offset:%d, limit:%d', $offset, $batchNum);
				
			$arrRet = $data->select(array('uid', 'uname'))->from('t_user')
			->where('uid','>',20000)->orderBy('uid', true)
			->limit($offset, $batchNum)->query();
			$offset += $batchNum;
				
			foreach($arrRet as $value)
			{
				$uid = $value['uid'];
				$uname = $value['uname'];
				if (self::isReward($game, $uid)) 
				{
					Logger::info('uid:%d, uname:%s is reward already', $uid, $uname);
					continue;
				}
				$sum = EnUser::getRechargeGoldByTime($startTime, $endTime, $uid);
				$reward4uid = self::getReward($sum);
				if(!empty($reward4uid))
				{
					Logger::info('uid:%d, uname:%s charge:%d has reward:%s', $uid, $uname, $sum, $reward4uid);
					if ($fix)
					{
						$rewardArr = array(RewardType::ARR_ITEM_TPL => $reward4uid);
						EnReward::sendReward($uid, RewardSource::SYSTEM_GENERAL, $rewardArr);
					}
				}
			}
			
			if(count($arrRet) < $batchNum)
			{
				break;
			}
		}
	}
	
	public function isReward($game, $uid)
	{
		$reward = array(
				1 => array(65299,58769,34193,46769,50104),
				2 => array(20032,20026,20036,24222,21289,),
				3 => array(28913,22152,),
				4 => array(27728,26757,27391,),
				5 => array(26929,20098,29036,31060,28548,),
				6 => array(20168,),
				7 => array(28514,),
				8 => array(27555,),
		);
		return in_array($uid, $reward[$game]) ? true : false;
	}
	
	public function getReward($sum)
	{
		$reward = array(
				1000 => array(60006,10),
				3000 => array(501010,1),
				5000 => array(60011,10),
				10000 => array(30003,35),
				15000 => array(30013,35),
		);
		$ret = array();
		foreach ($reward as $key => $value)
		{
			if ($sum < $key) 
			{
				break;
			}
			list($itemTplId, $itemNum) = $value;
			$ret[$itemTplId] = $itemNum;
		}
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */