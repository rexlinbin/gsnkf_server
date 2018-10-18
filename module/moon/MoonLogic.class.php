<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MoonLogic.class.php 247447 2016-06-21 13:17:04Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/moon/MoonLogic.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-06-21 13:17:04 +0000 (Tue, 21 Jun 2016) $
 * @version $Revision: 247447 $
 * @brief 
 *  
 **/
 
class MoonLogic
{
	/**
	 * 获得基本信息
	 * 
	 * @param int $uid
	 * @return array
	 */
	public static function getMoonInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		$userObj = EnUser::getUserObj($uid);
		$moonObj = MoonObj::getInstance($uid);
		
		$ret['tg_num'] = $userObj->getTgNum();
		$ret['atk_num'] = $moonObj->getAtkNum();
		$ret['buy_num'] = $moonObj->getBuyNum();
		$ret['max_pass_copy'] = $moonObj->getMaxPassCopy();
		$ret['nightmare_atk_num'] = $moonObj->getNightmareCanAtkNum();
		$ret['nightmare_buy_num'] = $moonObj->getNightmareBuyNum();
		$ret['max_nightmare_pass_copy'] = $moonObj->getMaxNightmarePassCopy();
		$ret['grid'] = $moonObj->getGridInfo();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;		
	}
	
	/**
	 * 攻打某个副本某个格子上的怪
	 * 
	 * @param int $uid
	 * @param int $copyId
	 * @param int $gridId
	 * @return array
	 */
	public static function attackMonster($uid, $copyId, $gridId)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
	
		$ret = self::dealGrid($uid, $copyId, $gridId, MoonGridType::MONSTER);
		
		// 每日活动增加
		EnActive::addTask(ActiveDef::MOON);
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 打开某个副本某个格子上的宝箱
	 *
	 * @param int $uid
	 * @param int $copyId
	 * @param int $gridId
	 * @return array
	 */
	public static function openBox($uid, $copyId, $gridId)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
	
		if (BagManager::getInstance()->getBag($uid)->isFull())
		{
			throw new FakeException('bag is full when open box');
		}
		$ret = self::dealGrid($uid, $copyId, $gridId, MoonGridType::BOX);
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 处理某个副本的某个格子，可能是怪，可能是宝箱
	 * 
	 * @param int $uid
	 * @param int $copyId
	 * @param int $gridId
	 * @param string $type
	 * @throws FakeException
	 * @throws InterException
	 * @return array
	 */
	public static function dealGrid($uid, $copyId, $gridId, $type)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$moonObj = MoonObj::getInstance($uid);
		
		// 检查这个副本是否是当前副本，已经通关或者锁定的副本无法处理格子
		if (!$moonObj->isCurrCopy($copyId)) 
		{
			throw new FakeException('param copy[%d] already pass or lock, curr copy[%d]', $copyId, $moonObj->getCurrCopy());
		}
		
		// 检查这个格子是否已经处理过啦
		if ($moonObj->isGridDone($gridId) || $moonObj->isGridLock($gridId)) 
		{
			throw new FakeException('copy id[%d] grid id[%d] status[%d], not in unlock status', $copyId, $gridId, $moonObj->gridStatus($gridId));
		}
		
		// 检查当前这个格子是怪还是宝箱
		if ($type != btstore_get()->MOON_COPY[$copyId]['grid'][$gridId]['type']) 
		{
			throw new FakeException('unmatch grid type, copy id[%d], grid id[%d], param type[%s], curr grid type[%s]', $copyId, $gridId, $type, btstore_get()->MOON_COPY[$copyId]['grid'][$gridId]['type']);
		}
		
		// 开始处理格子
		$ret = array();
		if (MoonGridType::BOX == $type) // 是宝箱 
		{
			// 标记
			$moonObj->doneGrid($gridId);
			$arrUnlockGrid = MoonConf::$UNLOCK_GRID[$gridId];
			$arrUnlockGrid = $moonObj->unlockGrid($arrUnlockGrid);
			$moonObj->update();
			
			// 领奖
			$rewardId = btstore_get()->MOON_COPY[$copyId]['grid'][$gridId]['boxId'];
			if (!isset(btstore_get()->MOON_REWARD[$rewardId])) 
			{
				throw new ConfigException('no config of reward id[%d]', $rewardId);
			}
			$arrReward = btstore_get()->MOON_REWARD[$rewardId]->toArray();
			$rewardRet = RewardUtil::reward3DArr($uid, $arrReward, StatisticsDef::ST_FUNCKEY_MOON_BOX_REWARD);
			if ($rewardRet[UpdateKeys::USER])
			{
				EnUser::getUserObj($uid)->update();
			}
			if ($rewardRet[UpdateKeys::BAG])
			{
				BagManager::getInstance()->getBag($uid)->update();
			}
			
			// 返回值
			$ret = array
			(
					'ret' => 'ok',
					'open_grid' => $arrUnlockGrid,
					'open_boss' => $moonObj->allGridDone() ? 1 : 0,
			);
		}
		else // 是怪，需要打
		{
			// 获得两方的战斗数据
			$userBattleFormation = EnUser::getUserObj($uid)->getBattleFormation();
			list($monsterFormation, $battleType, $endCondition) = MoonUtil::getMonsterBattleFormation($copyId, $gridId);
			
			// 战斗
			$atkRet = EnBattle::doHero($userBattleFormation, $monsterFormation, $battleType, NULL, $endCondition);
			
			// 是否击杀
			$arrUnlockGrid = array();
			$kill = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
			if ($kill) 
			{
				$moonObj->doneGrid($gridId);
				$arrUnlockGrid = MoonConf::$UNLOCK_GRID[$gridId];
				$arrUnlockGrid = $moonObj->unlockGrid($arrUnlockGrid);
				$moonObj->update();
			}
			
			$ret = array
			(
					'ret' => 'ok',
					'fightRet' => $atkRet['client'],
					'appraise' => $atkRet['server']['appraisal'],
					'open_grid' => $arrUnlockGrid,
					'open_boss' => $moonObj->allGridDone() ? 1 : 0,
			);
		}
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}

	/**
	 * 攻打某个已通关或者当前副本的BOSS
	 * 
	 * @param int $uid
	 * @param int $copyId
	 * @param int $nightmare
	 * @throws FakeException
	 * @return array
	 */
	public static function attackBoss($uid, $copyId, $nightmare)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		$ret = array();
		
		//检查梦魇参数
		if($nightmare != MoonTypeDef::BOSS_NORMAL_TYPE && $nightmare != MoonTypeDef::BOSS_NIGHTMARE_TYPE)
		{
			throw new FakeException('invalid nightmare type:%s.', $nightmare);
		}
		
		// 检查背包是否满啦
		if (BagManager::getInstance()->getBag($uid)->isFull())
		{
			throw new FakeException('bag is full when attack boss');
		}
		
		$moonObj = MoonObj::getInstance($uid);
		
		if ($nightmare == MoonTypeDef::BOSS_NORMAL_TYPE) 
		{
			   // 检查当前副本是否解锁，没解锁的副本是肯定不能攻打BOSS的
			    $mid = $moonObj->isCopyLock($copyId);
				if($mid) 
				{
					throw new FakeException('param copy[%d] is lock now, curr copy[%d].', $copyId, $moonObj->getCurrCopy());
				}
				//需要检查攻击次数 ,普通模式
				if($moonObj->getAtkNum() <= 0)
				{
					throw new FakeException('no attack num[%d] when attack passed copy[%d]', $moonObj->getAtkNum(), $copyId);
				}		
		}
		
		if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE) 
		{	
			    // 检查梦魇副攻击顺序是否正确
				if(false == $moonObj->isNightmareOrderOK($copyId))
				{
					throw new FakeException('param copy[%d] is cannot atk now, curr max nightmare copy[%d].wrong order.', $copyId, $moonObj->getMaxNightmarePassCopy());
				}
				//检查梦魇攻击次数
				$num = $moonObj->getNightmareCanAtkNum();
				if($num <= 0)
				{
					throw new FakeException('no nightmare attack num[%d] when attack passed copy[%d]', $moonObj->getNightmareAtkNum(), $copyId);
				}
		}
		
		// 如果是当前的副本，但是格子还没处理完
		if ($moonObj->isCurrCopy($copyId) && !$moonObj->allGridDone()) 
		{
			throw new FakeException('curr copy[%d] can not attack boss because not all grid done, grid info[%s]', $copyId, $moonObj->getGridInfo());
		}
			
		// 获得双方战斗数据
		$userBattleFormation = EnUser::getUserObj($uid)->getBattleFormation();
		list($bossFormation, $battleType, $endCondition) = MoonUtil::getBossBattleFormation($copyId, $nightmare);
			
		// 战斗
		$atkRet = EnBattle::doHero($userBattleFormation, $bossFormation, $battleType, NULL, $endCondition);
		$kill = BattleDef::$APPRAISAL[$atkRet['server']['appraisal']] <= BattleDef::$APPRAISAL['D'] ? TRUE : FALSE;
		
		// 减少次数，标记
		$openCopy = 0;
		if ($kill) 
		{
			//梦魇模式
			if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE)
			{
				//未通关过的梦魇副本,最大通关记录增加
				if(false == $moonObj->isCopyNightmarePass($copyId))
				{
					$moonObj->passNightmareCopy();
				}
				$moonObj->addNightmareAtkNum();
				$moonObj->update();
			}
			//普通模式
			else 
			{
				if ($moonObj->isCurrCopy($copyId))
				{
					$moonObj->passCurrCopy();
					$openCopy = $moonObj->getCurrCopy();
					if ($openCopy == $moonObj->getMaxPassCopy()) // 这是已经通关了最后一个副本，没有新的副本啦
					{
						$openCopy = 0;
					}
				}
				$moonObj->decreAtkNum();
				$moonObj->update();
			}
			
		}
		
		// 奖励
		$arrDropItem = array();
		if ($kill) 
		{	
			//梦魇模式
			if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE)
			{
				// 击杀奖励
				$arrReward = btstore_get()->MOON_COPY[$copyId]['nightmare_reward']->toArray();
				Logger::trace('ATTACK_BOSS : kill nitghtmare boss fix reward[%s]', $arrReward);
					
				// 掉落物品奖励，这里只支持掉落物品，不支持别的
				$arrDropInfo = Drop::dropMixed(intval(btstore_get()->MOON_COPY[$copyId]['drop_nightmare']));
				$arrDropItem = empty($arrDropInfo[DropDef::DROP_TYPE_ITEM]) ? array() : $arrDropInfo[DropDef::DROP_TYPE_ITEM];
			}
			//普通模式
			else 
			{
				// 击杀奖励
				$arrReward = btstore_get()->MOON_COPY[$copyId]['kill_reward']->toArray();
				Logger::trace('ATTACK_BOSS : kill boss fix reward[%s]', $arrReward);
			
				// 掉落物品奖励，这里只支持掉落物品，不支持别的
				$arrDropInfo = Drop::dropMixed(intval(btstore_get()->MOON_COPY[$copyId]['drop']));
				$arrDropItem = empty($arrDropInfo[DropDef::DROP_TYPE_ITEM]) ? array() : $arrDropInfo[DropDef::DROP_TYPE_ITEM];
			}		
			
			
			foreach ($arrDropItem as $aDropTemplate => $aDropNum)
			{
				$arrReward[] = array(RewardConfType::ITEM_MULTI, $aDropTemplate, $aDropNum);
			}
			Logger::trace('ATTACK_BOSS : kill boss drop reward[%s]', $arrDropItem);
			
			Logger::trace('ATTACK_BOSS : kill boss all reward[%s]', $arrReward);
			
			// 发奖
			$rewardRet = RewardUtil::reward3DArr($uid, $arrReward, StatisticsDef::ST_FUNCKEY_MOON_BOSS_REWARD);
			if ($rewardRet[UpdateKeys::USER])
			{
				EnUser::getUserObj($uid)->update();
			}
			if ($rewardRet[UpdateKeys::BAG])
			{
				BagManager::getInstance()->getBag($uid)->update();
			}
		}
		
		// 每日活动增加
		if($nightmare == MoonTypeDef::BOSS_NORMAL_TYPE)
		{
			EnActive::addTask(ActiveDef::MOON);
		}
		else 
		{
			EnActive::addTask(ActiveDef::MOON_NIGHTMARE);
		}
		
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['fightRet'] = $atkRet['client'];
		$ret['appraise'] = $atkRet['server']['appraisal'];
		$ret['drop'] = $arrDropItem;
		$ret['open_copy'] = $openCopy;
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		Logger::info('moon::attackBoss uid:%s type:%s copdId:%s drop:%s.',$uid, $nightmare , $copyId, $arrDropItem);
		return $ret;
	}
	
	/**
	 * 花金币增加购买次数
	 * 
	 * @param int $uid
	 * @param int $nightmare
	 * @throws FakeException
	 * @return string
	 */
	public static function addAttackNum($uid, $nightmare)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$moonObj = MoonObj::getInstance($uid);
		if($nightmare == MoonTypeDef::BOSS_NORMAL_TYPE)
		{
			// 检查购买次数达到上限
			$buyLimit = MoonUtil::getBuyLimit();
			$buyNum = $moonObj->getBuyNum();
			if ($buyNum >= $buyLimit)
			{
				throw new FakeException('no buy num, curr[%d], limit[%d]', $buyNum, $buyLimit);
			}
			
			// 减金币
			$cost = MoonUtil::getBuyCost($buyNum + 1);
			$userObj = EnUser::getUserObj($uid);
			if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_MOON_BUY_NUM_COST))
			{
				throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
			}
			$userObj->update();
			
			// 增加次数
			$moonObj->buyAtkNum();
			$moonObj->update();
		}
		
		else if($nightmare == MoonTypeDef::BOSS_NIGHTMARE_TYPE)
		{
			// 检查购买次数达到上限
			$buyLimit = MoonUtil::getBuyNightmareLimit();
			$buyNum = $moonObj->getNightmareBuyNum();
			if ($buyNum >= $buyLimit)
			{
				throw new FakeException('nightmare no buy num, curr[%d], limit[%d]', $buyNum, $buyLimit);
			}
				
			// 减金币
			$cost = MoonUtil::getNightmareBuyCost($buyNum + 1);
			$userObj = EnUser::getUserObj($uid);
			if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_MOON_BUY_NUM_COST))
			{
				throw new FakeException('nightmare not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
			}
			$userObj->update();
				
			// 增加次数
			$moonObj->buyNightmareAtkNum();
			$moonObj->update();
		}
		else 
		{
			throw new FakeException('invalid nightmare type:%s.', $nightmare);
		}
		
		$ret = 'ok';
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		Logger::info('moon::addAttackNum uid:%s type:%s use goldNum:%s.',$uid , $nightmare, $cost);
		return $ret;
	}
	
	/**
	 * 在商店买宝箱
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function buyBox($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$moonObj = MoonObj::getInstance($uid);
		
		// 检查购买次数达到上限
		$buyLimit = MoonUtil::getBuyBoxLimit();
		$buyNum = $moonObj->getBoxNum();
		if ($buyNum >= $buyLimit) 
		{
			throw new FakeException('no buy box num, curr[%d], limit[%d]', $buyNum, $buyLimit);
		}
		
		// 减金币
		$cost = MoonUtil::getBuyBoxCost($buyNum + 1);
		$userObj = EnUser::getUserObj($uid);
		if (!$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_MOON_TGSHOP_BUY_BOX_COST)) 
		{
			throw new FakeException('not enough gold, need[%d], curr[%d]', $cost, $userObj->getGold());
		}
		$userObj->update();
		
		// 增加次数
		$moonObj->increBoxNum();
		$moonObj->update();
		
		// 每日活动增加
		
		EnActive::addTask(ActiveDef::MOONBOX);
		
		// 获得掉落奖励并且发奖
		$arrDropInfo = MoonUtil::getBoxDropReward();
		$rewardRet = RewardUtil::reward3DArr($uid, $arrDropInfo, StatisticsDef::ST_FUNCKEY_MOON_TGSHOP_BOX_REWARD);
		if ($rewardRet[UpdateKeys::USER])
		{
			EnUser::getUserObj($uid)->update();
		}
		if ($rewardRet[UpdateKeys::BAG])
		{
			BagManager::getInstance()->getBag($uid)->update();
		}
		
		$ret = array();
		$ret['ret'] = 'ok';
		$ret['drop'] = $arrDropInfo;
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		
		return $ret;
	}
	
	/**
	 * 获得商店信息
	 * 
	 * @param int $uid
	 * @return array
	 */
	public static function getShopInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$shop = new MoonShop($uid);
		if ($shop->needSysRefresh())
		{
			$shop->refreshGoodsList(TRUE);
		}
	
		$shopInfo = $shop->getShopInfo();
		if(empty($shopInfo['goods_list']))
		{
			$shop->refreshGoodsList();
			$shopInfo = $shop->getShopInfo();
		}
		$shop->update();
		
		$shopInfo['buy_box_count'] = MoonObj::getInstance($uid)->getBoxNum();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $shopInfo);
		return $shopInfo;
	}
	
	/**
	 * 购买某个商品
	 * 
	 * @param int $uid
	 * @param int $goodsId
	 * @throws FakeException
	 * @return array
	 */
	public static function buyGoods($uid, $goodsId)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		if(empty($goodsId))
		{
			throw new FakeException('error params goodsId[%d]', $goodsId);
		}
		
		$shop = new MoonShop($uid);
		$goodsList = $shop->getGoodsList();
		if(!in_array($goodsId, $goodsList))
		{
			throw new FakeException('can not buy goodsId[%d] because not in goodsList[%s]', $goodsId, $goodsList);
		}
		
		$ret = $shop->exchange($goodsId);
		$shop->update();
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	/**
	 * 玩家刷新商品列表
	 * 
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function refreshGoodsList($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$shop = new MoonShop($uid);
		
		//检查免费刷新次数是否还有剩余
		$freeRfrNum = btstore_get()->MOON_SHOP['free_refresh_num'] - $shop->getFreeRfrNum();
		if ($freeRfrNum  > 0)
		{
			// 免费刷新次数还有剩余就优先使用免费刷新
			$shop->freeRfrGoodsList();
		}
		else //玩家每天的免费次数用完后才用付费刷新
		{
			// 检查玩家刷新次数是否超限
			$usrRfrNum = $shop->getUsrRfrNum();
			if ($usrRfrNum >= intval(btstore_get()->VIP[EnUser::getUserObj($uid)->getVip()]['tgShopRefreshLimit']))
			{
				throw new FakeException('usr refresh num[%d] reach limit[%d]', $usrRfrNum, intval(btstore_get()->VIP[EnUser::getUserObj($uid)->getVip()]['tgShopRefreshLimit']));
			}
		
			// 根据今天刷新次数得到刷新消耗金币
			$costConfig = btstore_get()->MOON_SHOP['usr_refresh_cost']->toArray();
			$index = 0;
			$needGold = 0;
			foreach ($costConfig as $num => $cost)
			{
				++$index;
				if ($usrRfrNum + 1 <= $num || $index == count($costConfig))
				{
					$needGold = intval($cost);
					break;
				}
			}
		
			// 扣金币
			$userObj = EnUser::getUserObj($uid);
			if(!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MOON_TGSHOP_REFRESH_COST))
			{
				throw new FakeException('no enough gold, need[%d] curr[%d]', $needGold, $userObj->getGold());
			}
			$userObj->update();
		
			// 刷新列表
			$shop->usrRfrGoodsList();
		}
		
		$shopInfo = $shop->getShopInfo();
		$shop->update();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $shopInfo);
		return $shopInfo;
	}
	
	public static function buyTally($uid, $goodsId, $num)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		if(empty($goodsId))
		{
			throw new FakeException('error params goodsId[%d]', $goodsId);
		}
		
		$shop = new BingfuShop($uid);
		$goodsList = $shop->getGoodsList();
		//不在商品列表里不可购买
		if(!in_array($goodsId, $goodsList))
		{
			throw new FakeException('bingfushop: can not buy goodsId[%d] because not in goodsList[%s]', $goodsId, $goodsList);
		}
		
		$ret = $shop->exchange($goodsId, $num);
		$shop->update();
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	public static function getTallyInfo($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		$shop = new BingfuShop($uid);
		if ($shop->needSysRefresh())
		{
			$shop->refreshGoodsList(TRUE);
		}
	
		$shopInfo = $shop->getShopInfo();
		if(empty($shopInfo['goods_list']))
		{
			$shop->refreshGoodsList();
			$shopInfo = $shop->getShopInfo();
		}
		$shop->update();
				
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $shopInfo);
		return $shopInfo;
	}
	/**
	 * 玩家刷新兵符商品列表
	 *
	 * @param int $uid
	 * @throws FakeException
	 * @return array
	 */
	public static function refreshTallyGoodsList($uid)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
	
		$shop = new BingfuShop($uid);
		$needGold = 0;//便于打日志，移到前面
		
		//检查免费刷新次数是否还有剩余
		$freeRfrNum = btstore_get()->BINGFU_RULE['freeRefreshNum'] - $shop->getFreeRfrNum();
		if ($freeRfrNum  > 0)
		{
			// 免费刷新次数还有剩余就优先使用免费刷新
			$shop->freeRfrGoodsList();
			
		}
		else //玩家每天的免费次数用完后才用付费刷新
		{
			// 检查玩家刷新次数是否超限
			$usrRfrNum = $shop->getUsrRfrNum();
			if ($usrRfrNum >= intval(btstore_get()->BINGFU_RULE['refreshNum']))
			{
				throw new FakeException('bingfushop usr refresh num[%d] reach limit[%d]', $usrRfrNum, intval(btstore_get()->BINGFU_RULE['refreshNum']));
			}
	
			// 根据今天刷新次数得到刷新消耗金币
			$costConfig = btstore_get()->BINGFU_RULE['goldGost']->toArray();
			$index = 0;
			
			foreach ($costConfig as $num => $cost)
			{
				++$index;
				if ($usrRfrNum + 1 <= $num || $index == count($costConfig))//超出最大配置，取最后金币消耗
				{
					$needGold = intval($cost);
					break;
				}
			}
	
			// 扣金币
			$userObj = EnUser::getUserObj($uid);
			if(!$userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_MOON_BINGFU_SHOP_COST))
			{
				throw new FakeException('refreshTallyGoodsList:no enough gold, need[%d] curr[%d]', $needGold, $userObj->getGold());
			}
			$userObj->update();
	
			// 刷新列表
			$shop->usrRfrGoodsList();
		}
	
		$shopInfo = $shop->getShopInfo();
		$shop->update();
	
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $shopInfo);
		Logger::info('refreshTallyGoodsList once.freeRfrNum:%s GoldRfrNum:%s.use GoldNum:%s.', $shop->getFreeRfrNum(),$shop->getUsrRfrNum(), $needGold);
		return $shopInfo;
	}
	
	public static function sweep($uid,$nightmare)
	{
		//检查梦魇参数
		if($nightmare != MoonTypeDef::BOSS_NORMAL_TYPE && $nightmare != MoonTypeDef::BOSS_NIGHTMARE_TYPE)
		{
			throw new FakeException('invalid nightmare type:%s.', $nightmare);
		}
		// 检查背包是否满啦
		if (BagManager::getInstance()->getBag($uid)->isFull())
		{
			throw new FakeException('bag is full when sweep boss');
		}
		$moonObj=MoonObj::getInstance($uid);
		$num=0;
		$ret=array();
		$totalRewardArr=array();
		
		if ($nightmare== MoonTypeDef::BOSS_NORMAL_TYPE)
		{
			$copyId=$moonObj->getMaxPassCopy();
			if ($copyId==0)
			{
				throw new FakeException('not pass any boss');
			}
			$num=$moonObj->getAtkNum();
			if ($num<=0)
			{
				throw new FakeException('no normal atk num');
			}
			$modify=$num;
			while ($modify>0)
			{
				$modify--;
				$moonObj->decreAtkNum();
				
				// 击杀奖励
				$arrReward = btstore_get()->MOON_COPY[$copyId]['kill_reward']->toArray();
				// 掉落物品奖励，这里只支持掉落物品，不支持别的
				$arrDropInfo = Drop::dropMixed(intval(btstore_get()->MOON_COPY[$copyId]['drop']));
				$arrDropItem = empty($arrDropInfo[DropDef::DROP_TYPE_ITEM]) ? array() : $arrDropInfo[DropDef::DROP_TYPE_ITEM];
				foreach ($arrDropItem as $aDropTemplate => $aDropNum)
				{
					$arrReward[] = array(RewardConfType::ITEM_MULTI, $aDropTemplate, $aDropNum);
				}
				$totalRewardArr=array_merge($totalRewardArr,$arrReward);
				$ret[]=$arrReward;
			}
		}
		else 
		{
			$copyId=$moonObj->getMaxNightmarePassCopy();
			if ($copyId==0)
			{
				throw new FakeException('not pass any boss');
			}
			$num = $moonObj->getNightmareCanAtkNum();
			if ($num<=0)
			{
				throw new FakeException('no night atk num');
			}
			$modify=$num;
			while ($modify>0)
			{
				$modify--;
				$moonObj->addNightmareAtkNum();
				
				$arrReward = btstore_get()->MOON_COPY[$copyId]['nightmare_reward']->toArray();
				// 掉落物品奖励，这里只支持掉落物品，不支持别的
				$arrDropInfo = Drop::dropMixed(intval(btstore_get()->MOON_COPY[$copyId]['drop_nightmare']));
				$arrDropItem = empty($arrDropInfo[DropDef::DROP_TYPE_ITEM]) ? array() : $arrDropInfo[DropDef::DROP_TYPE_ITEM];
				foreach ($arrDropItem as $aDropTemplate => $aDropNum)
				{
					$arrReward[] = array(RewardConfType::ITEM_MULTI, $aDropTemplate, $aDropNum);
				}
				$totalRewardArr=array_merge($totalRewardArr,$arrReward);
				$ret[]=$arrReward;
			}
		}
		// 发奖
		$rewardRet = RewardUtil::reward3DArr($uid, $totalRewardArr, StatisticsDef::ST_FUNCKEY_MOON_BOSS_REWARD);
		
		if ($rewardRet[UpdateKeys::USER])
		{
			EnUser::getUserObj($uid)->update();
		}
		if ($rewardRet[UpdateKeys::BAG])
		{
			BagManager::getInstance()->getBag($uid)->update();
		}
		$moonObj->update();
		Logger::info('user:%d sweep moon copy,reward:%s',$uid,$ret);
		
		// 每日活动增加
		if($nightmare == MoonTypeDef::BOSS_NORMAL_TYPE)
		{
		    EnActive::addTask(ActiveDef::MOON, $num);
		}
		else
		{
		    EnActive::addTask(ActiveDef::MOON_NIGHTMARE, $num);
		}
		
		return $ret;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */