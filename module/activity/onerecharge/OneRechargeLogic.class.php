<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: OneRechargeLogic.class.php 251586 2016-07-14 08:33:59Z YangJin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/onerecharge/OneRechargeLogic.class.php $
 * @author $Author: YangJin $(linjiexin@babeltime.com)
 * @date $Date: 2016-07-14 08:33:59 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251586 $
 * @brief
 *
 **/
class OneRechargeLogic
{
	public static function getInfo($uid)
	{
		$ret = array();
		$dataManager = OneRechargeManager::getInstance($uid);
		$hadReward = $dataManager->getHadRewardInfo();
		$toReward = OneRechargeUtil::getToReward($uid, $hadReward);
		$remainStatus = $dataManager->getRemainStatus();
		if (!empty($toReward) && (OneRechargeDef::NOT_REMAIN == $remainStatus))
		{
			$dataManager->changeRemainStatus(OneRechargeDef::REMAIN);
			$dataManager->update();
		}

		$ret[OneRechargeDef::HAD_REWARD] = $hadReward;
		$ret[OneRechargeDef::TO_REWARD] = $toReward;
		Logger::debug('hadReward:%s, toReward:%s', $hadReward, $toReward);
		return $ret;
	}

	public static function gainReward($uid, $rewardId, $select)
	{
		/** 一堆check **/

		$conf = OneRechargeUtil::getConf();
		// 1.检查配置是否存在
		if (empty($conf['data'][$rewardId]))
		{
			throw new FakeException('rewardId:%d from client is not in conf', $rewardId);
		}
		$dataConf = $conf['data'][$rewardId];
		// 2.活动结束前一个小时,只用来补发奖励到奖励中心,这期间不能领奖,虽然前端UI会消失,但是也要拦截
		$endTime = $conf['end_time'];
		$nowTime = Util::getTime();
		if ($endTime - $nowTime <= 3600)
		{
			throw new FakeException('now is in reward to rewardCenter period, cant gainReward:%d', $rewardId);
		}

		// 3.检查是否已经签到领奖过了 以及 有没有达到单笔充值的金币数量条件
		$dataManager = OneRechargeManager::getInstance($uid);
		$hadReward = $dataManager->getHadRewardInfo();
		$toReward = OneRechargeUtil::getToReward($uid, $hadReward);
		if (!isset($toReward[$rewardId]))
		{
			throw new FakeException('rewardId:%d has no remainNum to gain', $rewardId);
		}

		// 4.背包满的判断       TODO存在背包不空但是无法装下所有奖励物品的情况，类似的情况在很多活动中都存在
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->isFull())
		{
			throw new FakeException('bag is full, cant get reward, rewardId:%d', $rewardId);
		}

		// 5.检查是否有奖励剩余的状态
		$ifStillRemain = false;
		foreach ($toReward as $id => $remainNum)
		{
			if ($id != $rewardId)
			{
				$ifStillRemain = true;
				break;
			}
			else if ($remainNum > 1)
			{
				$ifStillRemain = true;
				break;
			}
		}

		$status = $dataManager->getRemainStatus();
		if (OneRechargeDef::REMAIN == $status && !$ifStillRemain)
		{
			$dataManager->changeRemainStatus(OneRechargeDef::NOT_REMAIN);
		}

		// 5.5.检查select==0时奖励确实可全部领取，或者select>0时奖励确实是N选1且select未超过范围
		$allRewardArr = self::getRewardArr($dataConf);
		$oneFromN = self::rewardIsOneFromN($dataConf);

		if ($select <= 0 && $oneFromN)
		    throw new FakeException('reward type is 1_from_N, select should > 0, but select is %d', $select);
		if ($select !=0 && !$oneFromN)
		    throw new FakeException('reward type is not 1_from_N, select should be 0, not : %d', $select);
		if ($select > 0 && empty($allRewardArr[$select - 1]))
		    throw new FakeException('select item is not in reward array, select is %d', $select);

		/** 记录奖励并发奖 **/

		// 6.记录奖励id数组 并 打关键信息info日志
		$rewardArr = array();
		$dataManager->recordReward($rewardId, $select);
		$thisRewardNum = empty($hadReward[$rewardId])? 1:(1 + count($hadReward[$rewardId]));
		if ($oneFromN)
		    $rewardArr[0] = $allRewardArr[$select - 1];
		else
		    $rewardArr = $allRewardArr;
		Logger::info('canToReward:%s, thisRewardId:%d, reqRecharge:%d, thisRewardNum:%d, select:%d, rewardArr:%s',
			$toReward, $rewardId, $dataConf[OneRechargeDef::REQ], $thisRewardNum, $select, $rewardArr);

		//$dataManager->recordRewardInfo($rewardId, 1);
		//$thisRewardNum = empty($hadReward[$rewardId]) ? 1: (1 + $hadReward[$rewardId]);
		//Logger::info('canToReward:%s, thisRewardId:%d, reqRecharge:%d, thisRewardNum:%d, rewardArr:%s',
			//	$toReward, $rewardId, $dataConf[OneRechargeDef::REQ], $thisRewardNum, $dataConf[OneRechargeDef::REWARD]);

		// 7.发奖
		$ret = RewardUtil::reward3DArr($uid, $rewardArr, StatisticsDef::ST_FUNCKEY_ONE_RECHARGE_GET, false, false);
        //$ret = RewardUtil::reward3DArr($uid, $dataConf[OneRechargeDef::REWARD], StatisticsDef::ST_FUNCKEY_ONE_RECHARGE_GET, false, false);
		$dataManager->update();
		RewardUtil::updateReward($uid, $ret);
		return 'ok';
	}

	private static function rewardIsOneFromN($dataConf)
	{
		if (isset($dataConf[OneRechargeDef::ONE_FROM_N]))
		{   //这里是为了兼容老版本的活动，老版本的奖励都是全部领取
		    if ($dataConf[OneRechargeDef::ONE_FROM_N] == OneRechargeDef::ONE_FROM_N_YES)
		        return true;
		    else if ($dataConf[OneRechargeDef::ONE_FROM_N] == OneRechargeDef::ONE_FROM_N_NO)
		        return false;
		    else
		        throw new FakeException('reward type is not 1 or 2, data info: %s', $dataConf);
		}
	    return false;
	}

    private static function getRewardArr($dataConf)
    {
        return $dataConf[OneRechargeDef::REWARD];
    }
	public static function doReward()
	{
		$conf = EnActivity::getConfByName(ActivityName::ONERECHARGE);
		$beginTime = $conf['start_time'];
		$endTime = $conf['end_time'];
		if( Util::getTime() > $endTime ) //活动结束
		{
			throw new InterException('onRecharge activtiy ended. cannot doReward. now:%d, endtime:%d', Util::getTime(), $endTime);
		}
		$offset = 0;
		$arrRet = array();
		do
		{
			$arrayPart = OneRechargeDao::selectAllRemainRewardUsersData(OneRechargeDef::REMAIN, $offset, OneRechargeDef::MAX_FETCH);
			$arrRet = array_merge($arrRet, $arrayPart);
			$offset += count($arrayPart);

		}while(OneRechargeDef::MAX_FETCH == count($arrayPart));

		$userList = Util::arrayIndex($arrRet, OneRechargeDef::UID);
		foreach($userList as $uid => $data)
		{
			try
			{
				//如果刷新时间不是这一次活动的,不补发奖励
				if ($data[OneRechargeDef::REFRESH_TIME] < $beginTime)
				{
					continue;
				}

				//计算出$uid剩余未领奖励的信息,涉及到2次DB拉取,一次是自己的已领取信息,另外一次是充值信息
				$dataManager = OneRechargeManager::getInstance($uid);
				$hadReward = $dataManager->getHadRewardInfo();
				$toReward = OneRechargeUtil::getToReward($uid, $hadReward);

				$arrLeftReward = array();

				foreach ($toReward as $rewardId => $num)
				{
					$oneFromN = self::rewardIsOneFromN($conf['data'][$rewardId]);//奖励是否N选1
				    $defaultSelect = $oneFromN?1:0;//如果是N选1，过期未领默认选1，策划王晨
					for ($i = 0; $i < $num; ++$i)
					{
						$allRewardArr = $conf['data'][$rewardId][OneRechargeDef::REWARD];
						$rewardArr = array();
						if ($oneFromN)
						    $rewardArr[0] = $allRewardArr[$defaultSelect - 1];
						else
						    $rewardArr = $allRewardArr;
						$arrLeftReward = array_merge($arrLeftReward, $rewardArr);
						//新：把这些未领取的奖励标记成已领取
						$dataManager->recordReward($rewardId, $defaultSelect);
					}
					// 旧：把这些未领取的奖励标记成已领取
					//$dataManager->recordRewardInfo($rewardId, $num);
				}

				Logger::debug('uid:%d has remain rewardArr:%s', $uid, $arrLeftReward);

				// 先把奖励剩余状态字段设置为已领取完,再发奖到奖励中心
				$dataManager->changeRemainStatus(OneRechargeDef::NOT_REMAIN);
				$dataManager->update();
				RewardUtil::reward3DtoCenter($uid, array($arrLeftReward), RewardSource::ACT_ONE_RECHARGE_REWARD);
				Logger::info('send left onRechargeReward to center. uid:%d, reward:%s', $uid, $arrLeftReward);

				//每处理完一个uid后就歇1毫秒
				usleep(1000);
			}
			catch (Exception $e)
			{
				Logger::fatal('send onRechargeReward failed. uid:%d, error:%s', $uid, $e->getMessage() );
			}

		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */