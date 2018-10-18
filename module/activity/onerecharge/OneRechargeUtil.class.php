<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OneRechargeUtil.class.php 248900 2016-06-30 02:11:06Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/OneRechargeUtil.class.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-06-30 02:11:06 +0000 (Thu, 30 Jun 2016) $
 * @version $Revision: 248900 $
 * @brief
 *
 **/
class OneRechargeUtil
{
	public static function getConf()
	{
		$conf =  EnActivity::getConfByName(ActivityName::ONERECHARGE);
		return $conf;
	}

	public static function getToReward($uid, $hadReward)
	{
		$arrRewardOrder = array();	//存放达到奖励要求的订单
		$arrConfReqGold = array();	//临时数组,用于存放奖励id与对应的充值金额,主要目的是为了倒序遍历
		$confGainNum = array();	//存放每档奖励要求的金币数量与可领取数量之间的映射
		$arrToReward = array();	//存放实际可以领取的奖励档位以及可领取数量
		if (!EnActivity::isOpen(ActivityName::ONERECHARGE))
		{
			throw new FakeException('activity oneRecharge is not opened');
		}

		$conf =  EnActivity::getConfByName(ActivityName::ONERECHARGE);
		foreach ($conf['data'] as $rewardId => $data)
		{
			$arrConfReqGold[$rewardId]['rewardId'] = $rewardId;
			$arrConfReqGold[$rewardId][OneRechargeDef::REQ] = $data[OneRechargeDef::REQ];
			$confGainNum[$data[OneRechargeDef::REQ]] = $data[OneRechargeDef::DAY_NUM];
		}

		$reverseConf = array_reverse($arrConfReqGold);
		$rechargeArr = EnUser::getChargeOrderByTime($conf['start_time'], Util::getTime(), $uid);

		$ifHaveArriveRewardOrder = false;
		foreach ($reverseConf as $index => $value)
		{
			$arrRewardOrder[$index]['rewardId'] = $value['rewardId'];
			$arrRewardOrder[$index][OneRechargeDef::REQ] = 0;
		}
		foreach ($rechargeArr as $rechargeInfo)
		{
			foreach ($reverseConf as $index => $value)
			{
				if ($rechargeInfo['gold_num'] >= $value[OneRechargeDef::REQ])
				{
					$ifHaveArriveRewardOrder = true;
					$arrRewardOrder[$index][OneRechargeDef::REQ] += 1;
					break;
				}
			}
		}

		if (!$ifHaveArriveRewardOrder)
		{
			return $arrToReward;
		}
		$leftNum = 0; // 从上一档奖励留下来的次数
		foreach ($arrRewardOrder as $rewardOrder)
		{
			$rewardId = intval($rewardOrder['rewardId']);
			$arriveRewardNum = $rewardOrder[OneRechargeDef::REQ];
		//	$hadRewardNum = empty($hadReward[$rewardId]) ? 0: $hadReward[$rewardId];
			$hadRewardNum = empty($hadReward[$rewardId]) ? 0: count($hadReward[$rewardId]);//详见OneRechargeManager.recordReward
			$reqGold = $conf['data'][$rewardId][OneRechargeDef::REQ];
			if ($arriveRewardNum > $confGainNum[$reqGold])
			{
				$gapNum = $arriveRewardNum - $confGainNum[$reqGold];
				$leftNum += $gapNum;
				$remainNum = $confGainNum[$reqGold] - $hadRewardNum;
			}
			else
			{
				$stillNeedNum =  $confGainNum[$reqGold] - $arriveRewardNum;
				if ($leftNum >= $stillNeedNum)
				{
					$remainNum = $confGainNum[$reqGold] - $hadRewardNum;
					$leftNum -= $stillNeedNum;
				}
				else
				{
					$remainNum = $leftNum + $arriveRewardNum - $hadRewardNum;
					$leftNum = 0;
				}
			}

			if ($remainNum > 0)
			{
				$arrToReward[$rewardId] = $remainNum;
			}

		}

		return $arrToReward;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */