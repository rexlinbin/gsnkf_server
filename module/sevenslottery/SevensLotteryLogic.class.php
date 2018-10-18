<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: SevensLotteryLogic.class.php 255355 2016-08-10 06:29:50Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/sevenslottery/SevensLotteryLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-10 06:29:50 +0000 (Wed, 10 Aug 2016) $
 * @version $Revision: 255355 $
 * @brief 
 *  
 **/
class SevensLotteryLogic
{
	public static function getInfo($uid)
	{
		Logger::trace('SevensLotteryLogic::getInfo Start.');
		
		$currConf = SevensLotteryUtil::getConf();
		$nextConf = SevensLotteryUtil::getConf(true);
		list($periodStart, $periodEnd) = SevensLotteryUtil::getPeriodTime();
		$sevensLottery = SevensLotteryObj::getInstance($uid);
		
		$ret = array(
				'period_start' => $periodStart,
				'period_end' => $periodEnd,
				'curr_id' => $currConf[SevensLotteryDef::LOTTERY_ID],
				'next_id' => $nextConf[SevensLotteryDef::LOTTERY_ID],
				'free' => $sevensLottery->getFree(),
				'num' => $sevensLottery->getNum(),
				'point' => $sevensLottery->getPoint(),
				'lucky' => $sevensLottery->getLucky(),
		);
		
		Logger::trace('SevensLotteryLogic::getInfo End. ret:%s', $ret);
		return $ret;
	}
	
	public static function lottery($uid, $type)
	{
		Logger::trace('SevensLotteryLogic::getInfo Start.');
		
		$conf = SevensLotteryUtil::getConf();
		$limit = $conf[SevensLotteryDef::LOTTERY_LIMIT];
		$cost = $conf[SevensLotteryDef::LOTTERY_COST];
		$item = $conf[SevensLotteryDef::LOTTERY_ITEM];
		
		$user = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		$sevensLottery = SevensLotteryObj::getInstance($uid);
		
		//0免费>1道具>2金币
		$free = $sevensLottery->getFree();
		$num = $sevensLottery->getNum();
		if ($free > 0) 
		{
			if ($type != 0)
			{
				throw new FakeException('sevens lottery type is not free, free:%d', $free);
			}
			$sevensLottery->setFreeTime();
			Logger::info('sevens lottery type:free.');
		}
		elseif ($bag->deleteItemsByTemplateID($item))
		{
			if ($type != 1)
			{
				throw new FakeException('sevens lottery type is not item, item:%s', $item);
			}
			Logger::info('sevens lottery type:item.');
		}
		elseif ($num < $limit)
		{
			if ($type != 2)
			{
				throw new FakeException('sevens lottery type is not gold, free:%d, num:%d, limit:%d.', $free, $num, $limit);
			}
			if (!$user->subGold($cost, StatisticsDef::ST_FUNCKEY_SEVENS_LOTTERY_COST))
			{
				throw new FakeException('sevens lottery type:gold, free:%d, num:%d, limit:%d, cost:%d.', $free, $num, $limit, $cost);
			}
			$sevensLottery->addNum(1);
			Logger::info('sevens lottery type:gold, free:%d, num:%d, limit:%d, cost:%d.', $free, $num, $limit, $cost);
		}
		else
		{
			throw new FakeException('sevens lottery free:%d, num:%d, limit:%d.', $free, $num, $limit);
		}
		
		$ret = array();
		$reward = array();
		$lucky = $sevensLottery->getLucky();
		$luckyMax = $conf[SevensLotteryDef::LUCKY_MAX];
		
		//幸运值满了
		if ($lucky >= $luckyMax) 
		{
			//重置幸运值，用配置奖励
			$sevensLottery->setLucky(0);
			$reward = $conf[SevensLotteryDef::LUCKY_REWARD];
			Logger::info('sevens lottery use lucky:%d, max:%d.', $lucky, $luckyMax);
		}
		else 
		{
			$dropId = $conf[SevensLotteryDef::LOTTERY_DROP];
			$dropInfo = Drop::dropMixed($dropId);
			$dropType = key($dropInfo);
			if ($dropType != DropDef::DROP_TYPE_ITEM)
			{
				throw new ConfigException('unsupport dropId:%d drop type:%d', $dropId, $dropType);
			}
			foreach ($dropInfo[$dropType] as $itemTplId => $itemNum)
			{
				$reward[] = array(RewardConfType::ITEM_MULTI, $itemTplId, $itemNum);
			}
			$ret['item'] = $dropInfo[$dropType];
			$keys = Util::noBackSample($conf[SevensLotteryDef::LUCKY_RANGE], 1);
			$sevensLottery->addLucky($keys[0]);
			Logger::info('sevens lottery add lucky:%d use dropId:%d, drop:%s', $keys[0], $dropId, $dropInfo[$dropType]);
		}
		
		//加积分
		$point = $conf[SevensLotteryDef::LOTTERY_POINT];
		$sevensLottery->addPoint($point);
		
		//发奖
		$rewardInfo = RewardUtil::reward3DArr($uid, $reward, StatisticsDef::ST_FUNCKEY_SEVENS_LOTTERY_REWARD);
		
		//更新
		$user->update();
		$bag->update();
		$sevensLottery->update();
		RewardUtil::updateReward($uid, $rewardInfo);
		$ret['lucky'] = $sevensLottery->getLucky();
		
		Logger::trace('SevensLotteryLogic::getInfo End. ret:%s', $ret);
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */