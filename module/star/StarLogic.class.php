<?php
/**************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: StarLogic.class.php 242360 2016-05-12 08:37:45Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/star/StarLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-12 08:37:45 +0000 (Thu, 12 May 2016) $
 * @version $Revision: 242360 $
 * @brief 
 *  
 **/

/**********************************************************************************************************************
 * Class       : StarLogic
 * Description : 名将系统的业务逻辑实现类
 * Inherit     :
 **********************************************************************************************************************/
 
 class StarLogic
 {
 	/**
 	 * 获取用户拥有的所有名将信息
 	 * 
 	 * @param int $uid 用户id
 	 * @return array mixed 所有名将的信息
 	 */
 	public static function getAllStarInfo($uid)
 	{
 		Logger::trace('StarLogic::getAllStarInfo Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 		
 		// 获取所有名将信息 
 		$myStar = MyStar::getInstance($uid);
 		$allStarInfo = $myStar->getAllInfo();
 		
 		Logger::trace('StarLogic::getAllStarInfo End.');

 		// 成功，返回所有名将信息
 		return array(
 				'ret' => 'ok',
 				'allStarInfo' => $allStarInfo,
 		);	
 	}
 	
 	/**
 	 * 通过赠送礼物增加名将的好感度值
 	 * 
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @param int $giftTid 礼物模板id
 	 * @param int $giftNum 礼物数量
 	 * @throws Exception
 	 * @return boolean 是否产生暴击
 	 */
 	public static function addFavorByGift($uid, $sid, $giftTid, $giftNum)
 	{
 		Logger::trace('StarLogic::addFavorByGift Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 			
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		
 		// 用户没有这个名将的信息
 		if (empty($starInfo)) 
 		{
 			throw new FakeException('User does not have this star:%d!', $sid);
 		}
 		// 物品类型是否名将礼物
 		$giftType = ItemManager::getInstance()->getItemType($giftTid);
 		if (ItemDef::ITEM_TYPE_GOODWILL != $giftType) 
 		{
 			throw new FakeException('gift:%d is not star goodwill item.', $giftTid);
 		}
//  		//暂时不用		
//  		//获取名将对应的喜好礼物信息
//			$stid = $myStar->getStarStid($sid);
//  		$favorGifts = self::getFavorGifts($stid);
//  		//礼物不是名将的喜好礼物
//  		if (!in_array($giftTid, $favorGifts)) 
//  		{
//  			throw new FakeException('Star:%d does not like the gift:%d!', $sid, $giftTid);
//  		}
		
		// 获取用户的背包, 减去礼物
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->deleteItembyTemplateID($giftTid, $giftNum) == FALSE)
		{
			throw new FakeException('Bag decrease gift failed! gift_tid:%d, gift_num:%d', $giftTid, $giftNum);
		}
		
		//礼物对应的好感度值
		$giftFavor = btstore_get()->ITEMS[$giftTid][ItemDef::ITEM_ATTR_NAME_GOODWILL_EXP];
		$ret = self::addFavor($uid, $sid, $giftFavor * $giftNum);
		
		$bag->update();
		$myStar->update();
		EnUser::getUserObj($uid)->update();
		
		//加入每日任务
		EnActive::addTask(ActiveDef::FAVOR);
		
		Logger::trace('StarLogic::addFavorByGift End.');
		return $ret; 		
 	}
 	
 	/**
 	 * 一键送礼
 	 * 
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @throws FakeException
 	 * @return string 'ok'
 	 */
 	public static function addFavorByAllGifts($uid, $sid)
 	{
 		Logger::trace('StarLogic::addFavorByAllGifts Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 			
 		// 用户没有这个名将的信息
 		if (empty($starInfo))
 		{
 			throw new FakeException('User does not have this star:%d!', $sid);
 		}
 		$totalExp = $myStar->getStarExp($sid);
 		
//暂时不用			
//  		获取名将对应的喜好礼物信息
//			$stid = $myStar->getStarStid($sid);
//  		$favorGifts = self::getFavorGifts($stid);
 		
//  		检查用户背包
//  		$bag = BagManager::getInstance()->getBag($uid);
//  		foreach ($favorGifts as $giftTid)
//  		{
//  			$giftNum = $bag->getItemNumByTemplateID($giftTid);
//  			if ($giftNum) 
//  			{
//  				self::addFavorByGift($uid, $sid, $giftTid, $giftNum);
//  			}
//  		}	

		//获取用户所有的好感度礼物
		$bag = BagManager::getInstance()->getBag($uid);
 		$arrItemId = $bag->getItemIdsByItemType(ItemDef::ITEM_TYPE_GOODWILL);
 		$items = ItemManager::getInstance()->getItems($arrItemId);
 		$sortArray = array();
 		foreach ($items as $itemId => $item)
 		{
 			$sortArray[] = array(
 					'id' => $itemId,
 					'quality' => $item->getItemQuality(),
 			);
 		}
 		//按quality升序
 		$sortCmp = new SortByFieldFunc(array('quality' => SortByFieldFunc::ASC));
 		usort($sortArray, array($sortCmp, 'cmp'));
 		$arrItemId = Util::arrayExtract($sortArray, 'id');
 		//加礼物数量上限是50
 		$count = 0;
 		$fatal = 0;
 		$err = false;
 		$delItems = array();
 		foreach ($arrItemId as $itemId)
 		{
 			$item = $items[$itemId];
 			$itemTplId = $item->getItemTemplateID();
 			$favor = btstore_get()->ITEMS[$itemTplId][ItemDef::ITEM_ATTR_NAME_GOODWILL_EXP];
 			$itemNum = $item->getItemNum();
 			for ($i = 0; $i < $itemNum; $i++)
 			{
 				try 
 				{
 					$isFatal = self::addFavor($uid, $sid, $favor);
 				}
 				catch (Exception $e)
 				{
 					$err = true;
 					break;
 				}
 				$fatal = $isFatal ? $fatal + 1 : $fatal;
 				if (!isset($delItems[$itemTplId])) 
 				{
 					$delItems[$itemTplId] = 0;
 				}
 				$delItems[$itemTplId]++;
 				$count++;
 				if ($count >= StarConf::STAR_GIFT_LIMIT) 
 				{
 					break;
 				}
 			}
 			if ($err || $count >= StarConf::STAR_GIFT_LIMIT)
 			{
 				break;
 			}
 		}
 		
 		//减去消耗的物品
 		if ($bag->deleteItemsByTemplateID($delItems) == false) 
 		{
 			throw new FakeException('No enough items:%s', $delItems);
 		}
 		
 		//计算加上的总的经验值
 		$exp = $myStar->getStarExp($sid) - $totalExp;
 		
 		$bag->update();
 		$myStar->update();
 		EnUser::getUserObj($uid)->update();
 		
 		//加入每日任务
 		EnActive::addTask(ActiveDef::FAVOR, $count);
 		
 		Logger::trace('StarLogic::addFavorByAllGifts End.');
 		
 		return array('fatal' => $fatal, 'exp' => $exp);
 	}

 	/**
 	 * 通过金币赠送增加名将的好感度
 	 * 
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @throws Exception
 	 * @return boolean 是否产生暴击
 	 */
 	public static function addFavorByGold($uid, $sid)
 	{
 		Logger::trace('StarLogic::addFavorByGold Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		
 		// 用户没有这个名将的信息
 		if (empty($starInfo)) 
 		{
 			throw new FakeException('User does not have this star:%d!', $sid);
 		}
 		
 		// 获取用户当天使用的金币赠送次数和固定金币赠送次数
 		$sendNum = $myStar->getSendNum();	
 		$user = EnUser::getUserObj($uid);	
 		$freeNum = self::getFreeSend($user->getVip());
 		$allInfo = btstore_get()->STAR_ALL;
 		$maxNum = $allInfo[StarDef::STAR_GOLD_MAX];
 		// 金币赠送次数已经用完
 		if ($sendNum >= $freeNum + $maxNum)
 		{
 			throw new FakeException('Fail to add favor by gold, times out!');
 		}
 		
 		$needGold = 0;
 		// 判断免费次数是否用完
 		if ($sendNum >= $freeNum) 
 		{
 			// 计算消耗的金币数量
 			$needGold = $allInfo[StarDef::STAR_GOLD_BASE]
 						+ $allInfo[StarDef::STAR_GOLD_INCRE] * ($sendNum - $freeNum);
 		}
 			
 		// 减金币, 金币是否足够	
 		if (!$user->subGold($needGold, StatisticsDef::ST_FUNCKEY_STAR_FAVOR))
 		{
 			throw new FakeException('Fail to sub gold, not enough for %d!', $needGold);
 		}
 		
 		// 更新金币赠送次数，金币赠送时间
 		$myStar->setSendNum($sendNum + 1);	
 		
 		// 计算金币赠送应该给名将增加的好感度值
 		$addExp = $allInfo[StarDef::STAR_GOLD_FAVOR];		
 		self::addFavor($uid, $sid, $addExp);
 		
 		// 更新
 		$user->update();
 		$myStar->update();
 		BagManager::getInstance()->getBag($uid)->update();
 		
 		Logger::trace('StarLogic::addFavorByGold End.');
 		return 'ok';			
 	}
 	
 	/**
 	 * 通过行为事件增进名将的感情
 	 *
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @param int $actId 行为事件id
 	 * @throws Exception
 	 * @return array mixed 触发信息
 	 */
 	public static function addFavorByAct($uid, $sid, $actId)
 	{
 		Logger::trace('StarLogic::addFavorByAct Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		
 		// 用户没有这个名将的信息，抛出异常
 		if (empty($starInfo)) 
 		{
 			throw new FakeException('User:%d does not have this star_id:%d!', $uid, $sid);
 		}
 		
 		// 获取名将的增进感情的所有行为事件信息
 		$stid = $myStar->getStarStid($sid);
 		$actConfs = self::getActConfs($stid);
 		if (!isset($actConfs[$actId]))
 		{
 			throw new FakeException('Star:%d does not have this act_id:%d!', $sid, $actId);
 		}
 		
 		// 获取用户当天使用这个行为的次数，计算行为事件当前需要消耗的耐力数: 耐力基础值+次数*耐力递增值
 		$actNum = $myStar->getActNum($actId);
 		$needStamina = $actConfs[$actId][StarDef::STAR_STAMINA_BASE]
 					 	+ $actConfs[$actId][StarDef::STAR_STAMINA_INCRE] * $actNum;
 		
 		// 如果用户的耐力值不够
 		$user = EnUser::getUserObj($uid);
 		if (!$user->subStamina($needStamina)) 
 		{
 			throw new FakeException('Fail to sub stamina, not enough for %d!', $needStamina);
 		}
 		
 		// 更新行为事件的次数
 		$myStar->setActNum($actId, $actNum + 1);
 				
 		// 获得行为事件的奖励信息，根据奖励类型，领取奖励
 		$level = $myStar->getStarLevel($sid);
 		$actReward = self::getActReward($actId, $level);
 		self::addRewardByType($uid, $sid, $actReward);
 		
 		// 更新到数据库
 		$user->update();
 		$myStar->update();
 		BagManager::getInstance()->getBag($uid)->update();
 		
 		// 触发答题事件,并设置进session
 		$trigerId = self::getTrigerId();
 		RPCContext::getInstance()->setSession(StarDef::TRIGER_SESSION_KEY, array($sid => $trigerId));
 		
 		Logger::trace('StarLogic::addFavorByAct End.');
 		return array('ret' => 'ok',
 					 'trigerId' => $trigerId
 		);
 	}
 	
 	/**
 	 * 答题 
 	 * 
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @param int $actId 行为id
 	 * @param int $trigerId	答题包id
 	 * @param int $optionId	选项id
 	 * @throws Exception
 	 * @return ok
 	 */
 	public static function answer($uid, $sid, $trigerId, $optionId)
 	{
 		Logger::trace('StarLogic::answer Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		
 		// 用户没有这个名将的信息，抛出异常
 		if (empty($starInfo)) 
 		{
 			throw new FakeException('User:%d does not have this star_id:%d!', $uid, $sid);
 		}
 		
 		// 检查trigerId是否一致
 		$triger = RPCContext::getInstance()->getSession(StarDef::TRIGER_SESSION_KEY);
 		Logger::trace('triger:%s in session', $triger);
 		RPCContext::getInstance()->unsetSession(StarDef::TRIGER_SESSION_KEY);
 		if (empty($triger) || key($triger) != $sid || current($triger) != $trigerId) 
 		{
 			throw new FakeException('User:%d  star:%d does not have this triger_id:%d!', $uid, $sid, $trigerId);
 		}
 		
 		// 获取答题选项对应的奖励信息，根据奖励类型，领取奖励
 		$optionReward = self::getOptionReward($uid, $trigerId, $optionId);
 		self::addRewardByType($uid, $sid, $optionReward);

 		// 更新到数据库
 		$myStar->update();
 		EnUser::getUserObj($uid)->update();	
 		BagManager::getInstance()->getBag($uid)->update();

 		Logger::trace('StarLogic::answer End.');
 		return 'ok';
 	}
 	
 	public static function swap($uid, $sida, $sidb)
 	{
 		Logger::trace('StarLogic::swap Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::STAR) == false)
 		{
 			throw new FakeException('user:%d does not open the star', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$aStarInfo = $myStar->getStarInfo($sida);
 		$bStarInfo = $myStar->getStarInfo($sidb);
 		if (empty($aStarInfo) || empty($bStarInfo)) 
 		{
 			throw new FakeException('User:%d does not have this starId:%d or starId:%d!', $uid, $sida, $sidb);
 		}
 		//检查品质是否相同
 		$conf = btstore_get()->STAR;
 		$aStid = $myStar->getStarStid($sida);
 		$bStid = $myStar->getStarStid($sidb);
 		$aQuality = $conf[$aStid][StarDef::STAR_QUALITY];
 		$bQuality = $conf[$bStid][StarDef::STAR_QUALITY];
 		if ($aQuality!= $bQuality) 
 		{
 			throw new FakeException('a star quality:%d is not same with b star quality:%d', $aQuality, $bQuality);
 		}
 		
 		//检查花费
 		$user = EnUser::getUserObj($uid);
 		$cost = btstore_get()->STAR_ALL[StarDef::STAR_SWAP_COST][$aQuality];
 		if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_STAR_SWAP) == false) 
 		{
 			throw new FakeException('User:%d has no enough gold for:%d!', $uid, $cost);
 		}
 		
 		//交换经验值,同星级名将的经验表是一样的，直接交换
 		$aExp = $myStar->getStarExp($sida);
 		$bExp = $myStar->getStarExp($sidb);
 		$aLevel = $myStar->getStarLevel($sida);
 		$bLevel = $myStar->getStarLevel($sidb);
 		$myStar->setStarExp($sida, $bExp);
 		$myStar->setStarExp($sidb, $aExp);
 		$myStar->setStarLevel($sida, $bLevel);
 		$myStar->setStarLevel($sidb, $aLevel);
 		
 		// 影响属性加成，修改战斗数据
 		$user->modifyBattleData();
 		$user->update();
 		$myStar->update();
 		
 		Logger::trace('StarLogic::swap End.');
 		
 		return 'ok';
 	}
 	
 	public static function draw($uid, $sid)
 	{
 		Logger::trace('StarLogic::draw Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::MASTERSKILL) == false)
 		{
 			throw new FakeException('user:%d does not open the masterskill', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		if (empty($starInfo))
 		{
 			throw new FakeException('User:%d does not have this starId:%d!', $uid, $sid);
 		}
 			
 		//检查该名将是否可以翻牌
 		$stid = $myStar->getStarStid($sid);
 		if (!self::canFeel($stid))
 		{
 			throw new FakeException('startid:%d can not be draw!', $stid);
 		}
 		
 		//检查该武将是否还有奖励未领取
 		$draw = $myStar->getStarDraw($sid);
 		if (!empty($draw)) 
 		{
 			throw new FakeException('startid:%d can not be draw before reward!', $stid);
 		}
 			
 		//检查翻牌次数是否足够
 		$user = EnUser::getUserObj($uid);
 		$drawNum = $myStar->getDrawNum();
 		$drawFree = btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_FREE];
 		//有免费翻牌次数
 		if ($drawNum >= $drawFree)
 		{
 			$drawBuy = $drawNum - $drawFree;
 			$drawLimit = btstore_get()->VIP[$user->getVip()]['starDrawLimit'];
 			//有金币翻牌次数
 			if ($drawBuy < $drawLimit) 
 			{
 				$drawCost = btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_COST];
 				$cost = min($drawCost[0] + $drawCost[1] * $drawBuy, $drawCost[2]);
 				if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_STAR_DRAW) == false) 
 				{
 					throw new FakeException('no enough gold for:%d!', $cost);
 				}
 			}
 			else 
 			{
 				throw new FakeException('buy num is reach limit:%d!', $drawLimit);
 			}
 		}
 		$myStar->setDrawNum($drawNum + 1);
 		
 		//随机牌型
 		$drawCombination = btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_COMBINATION]->toArray();
 		$keys = Util::noBackSample($drawCombination, 1);
 		$pattern = self::getPattern($keys[0]);
 		$draw = array_merge(array($keys[0]), $pattern);
 		$myStar->setStarDraw($sid, $draw);
 		
 		//更新
 		$user->update();
 		$myStar->update();
 		
 		//完成任务
 		EnActive::addTask(ActiveDef::LEARNSKILL);

 		Logger::trace('StarLogic::draw End.');
 		
 		return $draw;
 	}
 	
 	public static function shuffle($uid, $sid)
 	{
 		Logger::trace('StarLogic::shuffle Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::MASTERSKILL) == false)
 		{
 			throw new FakeException('user:%d does not open the masterskill', $uid);
 		}
 			
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		if (empty($starInfo))
 		{
 			throw new FakeException('User:%d does not have this starId:%d!', $uid, $sid);
 		}
 		
 		//检查该名将是否可以洗牌
 		$stid = $myStar->getStarStid($sid);
 		if (!self::canFeel($stid))
 		{
 			throw new FakeException('startid:%d can not be shuffle!', $stid);
 		}
 			
 		//检查该武将是否还有奖励未领取
 		$draw = $myStar->getStarDraw($sid);
 		if (empty($draw))
 		{
 			throw new FakeException('startid:%d can not be shuffle before draw!', $stid);
 		}
 		
 		//检查牌型是否最大
 		$index = key(btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_COMBINATION]->toArray());
 		if ($draw[StarDef::PATTERN] == $index) 
 		{
 			throw new FakeException('startid:%d can not be shuffle, pattern:%d!', $stid, $index);
 		}
 		
 		//扣金币
 		$user = EnUser::getUserObj($uid);
 		$cost = btstore_get()->STAR_TEACH[StarDef::STAR_SPECIAL_COST];
 		if ($user->subGold($cost, StatisticsDef::ST_FUNCKEY_STAR_SHUFFLE) == false) 
 		{
 			throw new FakeException('no enough gold for:%d!', $cost);
 		}
 		
 		//洗牌
 		$pattern = self::getPattern($index);
 		$draw = array_merge(array($index), $pattern);
 		$myStar->setStarDraw($sid, $draw);
 		
 		//更新
 		$user->update();
 		$myStar->update();
 		
 		Logger::trace('StarLogic::shuffle End.');
 		
 		return $draw;
 	}
 	
 	public static function getReward($uid, $sid)
 	{
 		Logger::trace('StarLogic::getReward Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::MASTERSKILL) == false)
 		{
 			throw new FakeException('user:%d does not open the masterskill', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		if (empty($starInfo))
 		{
 			throw new FakeException('User:%d does not have this starId:%d!', $uid, $sid);
 		}
 			
 		//检查该名将是否可以领奖
 		$stid = $myStar->getStarStid($sid);
 		if (!self::canFeel($stid))
 		{
 			throw new FakeException('startid:%d can not be reward!', $stid);
 		}
 		
 		//检查该武将是否还有奖励未领取
 		$draw = $myStar->getStarDraw($sid);
 		if (empty($draw))
 		{
 			throw new FakeException('startid:%d can not be reward before draw!', $stid);
 		}
 		$myStar->unsetStarDraw($sid);
 		
 		//领取感悟值
 		$index = $draw[0];
 		$addFeel = btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_COMBINATION][$index]['feel'];
 		self::addFeel($uid, $sid, $addFeel);
 		
 		//更新
 		$myStar->update();
 		EnUser::getUserObj($uid)->update();
 		
 		Logger::trace('StarLogic::getReward End.');
 		
 		return 'ok';
 	}
 	
 	public static function changeSkill($uid, $sid)
 	{
 		Logger::trace('StarLogic::changeSkill Start.');
 		
 		if (EnSwitch::isSwitchOpen(SwitchDef::MASTERSKILL) == false)
 		{
 			throw new FakeException('user:%d does not open the masterskill', $uid);
 		}
 		
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$reset = false;
 		if (empty($sid)) 
 		{
 			$reset = true;
 			$sid = $myStar->getEquipSkill();
 		}
 		$starInfo = $myStar->getStarInfo($sid);
 		if (empty($starInfo))
 		{
 			throw new FakeException('User:%d does not have this starId:%d!', $uid, $sid);
 		}
 			
 		//检查该名将是否可以更换技能
 		$stid = $myStar->getStarStid($sid);
 		if (!self::canFeel($stid))
 		{
 			throw new FakeException('startid:%d skill can not be change!', $stid);
 		}
 		
 		//检查武将是否有武将
 		$feelSkills = self::getFeelSkills($stid);
 		if (empty($feelSkills))
 		{
 			throw new FakeException('startid:%d has no skills!', $stid);
 		}
 		
 		//获得名将的技能id
 		$feelSkill = $myStar->getStarFeelSkill($sid);
 		if (empty($feelSkill)) 
 		{
 			throw new FakeException('startid:%d skill is not exist!', $stid);
 		}
 		
 		//更换技能
 		$user = EnUser::getUserObj($uid);
 		if ($reset) 
 		{
 			$myStar->unsetEquipSkill();
 		}
 		else 
 		{
 			$myStar->setEquipSkill($sid);
 			$rageSkill = $myStar->getStarFeelSkill($sid);
 			if (!empty($rageSkill))
 			{
 				$user->learnMasterSkill(PropertyKey::RAGE_SKILL, $rageSkill, MASTERSKILL_SOURCE::STAR);
 			}
 			$attackSkill = StarLogic::getNormalSkill($uid, $stid);
 			if (!empty($attackSkill))
 			{
 				$user->learnMasterSkill(PropertyKey::ATTACK_SKILL, $attackSkill, MASTERSKILL_SOURCE::STAR);
 			}
 		}
 		
 		//更新
 		$myStar->update();
 		$user->modifyBattleData();
 		$user->update();
 			
 		Logger::trace('StarLogic::changeSkill End.');
 		
 		return 'ok';
 	}
 	
 	public static function quickDraw($uid, $sid)
 	{
 		Logger::trace('StarLogic::quickDraw Start.');
 			
 		if (EnSwitch::isSwitchOpen(SwitchDef::MASTERSKILL) == false)
 		{
 			throw new FakeException('user:%d does not open the masterskill', $uid);
 		}
 			
 		// 获取名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		if (empty($starInfo))
 		{
 			throw new FakeException('User:%d does not have this starId:%d!', $uid, $sid);
 		}
 	
 		//检查该名将是否可以翻牌
 		$stid = $myStar->getStarStid($sid);
 		if (!self::canFeel($stid))
 		{
 			throw new FakeException('startid:%d can not be draw!', $stid);
 		}
 			
 		//检查该武将是否还有奖励未领取
 		$draw = $myStar->getStarDraw($sid);
 		if (!empty($draw))
 		{
 			throw new FakeException('startid:%d can not be draw before reward!', $stid);
 		}
 	
 		//检查翻牌次数是否足够
 		$conf = btstore_get()->STAR_TEACH;
 		$drawNum = $myStar->getDrawNum();
 		$drawFree = $conf[StarDef::STAR_DRAW_FREE];
 		//有免费翻牌次数
 		if ($drawNum >= $drawFree)
 		{
 			throw new FakeException('User:%d has no draw num!', $uid);
 		}
 		$myStar->setDrawNum($drawFree);
 			
 		//随机牌型
 		$drawCombination = $conf[StarDef::STAR_DRAW_COMBINATION]->toArray();
 		$addFeel = 0;
 		for ($i = 0; $i < $drawFree - $drawNum; $i++)
 		{
 			$keys = Util::noBackSample($drawCombination, 1);
 			$addFeel += $conf[StarDef::STAR_DRAW_COMBINATION][$keys[0]]['feel'];
 		}
 		self::addFeel($uid, $sid, $addFeel);
 			
 		//更新
 		$myStar->update();
 		EnUser::getUserObj($uid)->update();
 		
 		//完成任务
 		EnActive::addTask(ActiveDef::LEARNSKILL, $drawFree - $drawNum);
 	
 		Logger::trace('StarLogic::quickDraw End.');
 			
 		return $addFeel;
 	}
 	
 	/**
 	 * 根据名将的好感度等级领取相应的奖励
 	 * 需要update bag
 	 * 
 	 * @param int $uid 用户id
 	 * @param int $stid	名将模板id
 	 * @param int $sid 名将id
 	 * @param int $level 好感度等级
 	 */
 	public static function addAbilityReward($uid, $stid, $sid, $level)
 	{
 		Logger::trace('StarLogic::addAbilityReward Start.');

 		// 获取名将对应等级的能力信息	
 		$abilityConf = self::getAbilityConf($stid, $level);
 		
 		// 给用户加物品
 		if (!empty($abilityConf[StarDef::STAR_ABILITY_ITEM])) 
 		{
 			$items = $abilityConf[StarDef::STAR_ABILITY_ITEM];
 			$bag = BagManager::getInstance($uid)->getBag();
 			if ( $bag->addItemsByTemplateID($items) == false )
 			{
 				throw new FakeException('full bag. item tpls:%s', $items);
 			}
 		}
 		
 		// 给用户发奖励
 		$reward = array();
 		if (!empty($abilityConf[StarDef::STAR_ABILITY_REWARD]))
 		{
 			$reward = $abilityConf[StarDef::STAR_ABILITY_REWARD]->toArray();
 		}
 		// 给用户加耐力
 		if (!empty($abilityConf[StarDef::STAR_ABILITY_STAMINA]))
 		{
 			$reward[StarConf::STAR_TYPE_STAMINA_LIMIT] = $abilityConf[StarDef::STAR_ABILITY_STAMINA];
 		}
 		if (!empty($reward)) 
 		{
 			self::addRewardByType($uid, $sid, $reward);
 		}
 		
 		Logger::trace('StarLogic::addAbilityReward End.');
 	}
 	
 	/**
 	 * 根据奖励类型，领取相应的奖励
 	 * 需要update user, bag, myStar
 	 *
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @param int $reward 奖励信息
 	 */
 	public static function addRewardByType($uid, $sid, $reward)
 	{
 		Logger::trace('StarLogic::addRewardByType Start.');
 		
 		// 获取用户对象
 		$user = EnUser::getUserObj($uid);
 		foreach ($reward as $type => $num)
 		{
 			// 判断奖励类型，并领取奖励
 			switch ($type)
 			{
 				case StarConf::STAR_TYPE_SILVER:
 					$user->addSilver($num);
 					break;
 				case StarConf::STAR_TYPE_GOLD:
 					$user->addGold($num, StatisticsDef::ST_FUNCKEY_STAR_REWARD);
 					break;
 				case StarConf::STAR_TYPE_SOUL:
 					$user->addSoul($num);
 					break;
 				case StarConf::STAR_TYPE_STAMINA:
 					$user->addStamina($num);
 					break;
 				case StarConf::STAR_TYPE_EXECUTION:
 					$user->addExecution($num);
 					break;
 				case StarConf::STAR_TYPE_GOODWILL:
 					self::addFavor($uid, $sid, $num);
 					break;
 				case StarConf::STAR_TYPE_EXP:
 					$user->addExp($num);
 					break;
 				case StarConf::STAR_TYPE_STAMINA_LIMIT:
 					$user->addStaminaMaxNum($num);
 					break;
 				default:
 					throw new ConfigException('star id:%d has wrong reward type:%d', $sid, $type);
 			}
 		}
 		
 		Logger::trace('StarLogic::addRewardByType End.');
 	}
 	
 	/**
 	 * 增加名将的好感度值
 	 * 需要update myStar
 	 * 
 	 * @param int $uid 用户id
 	 * @param int $sid 名将id
 	 * @param int $addExp 增加的好感度值
 	 * @return array mixed 增加好感度后的名将信息
 	 */
 	public static function addFavor($uid, $sid, $addExp)
 	{
 		Logger::trace('StarLogic::addFavor Start.');
 		$ret = false;

 		// 获取名将对象, 名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 		
 		// 用户没有这个名将的信息
 		if (empty($starInfo)) 
 		{
 			throw new FakeException('User does not have this star id:%d!', $sid);
 		}
 		
 		// 检查名将等级
 		$stid = $myStar->getStarStid($sid);
 		$level = $myStar->getStarLevel($sid);
 		$maxLevel = self::getMaxLevel($stid);
 		if ($level >= $maxLevel)
 		{
 			throw new FakeException('star:%d is reach max level:%d', $sid, $maxLevel);
 		}
 		
 		// 检查用户等级
 		$favorAbility = self::getFavorAbility($stid, $level + 1);
 		$needLevel = $favorAbility[1];
 		$user = EnUser::getUserObj($uid);
		if ($user->getLevel() < $needLevel) 
		{
			throw new FakeException('User level is not enough to add favor!');
		}
 		
		// 计算当前喂养前好感度经验百分比
 		$totalExp = $myStar->getStarExp($sid);
 		$lowLevelExp = self::getExpByLevel($stid, $level);
 		$highLevelExp = self::getExpByLevel($stid, $level + 1);
 		$currLevelExp = $highLevelExp - $lowLevelExp;
 		if (0 != $currLevelExp) 
 		{
 			$currExp = $totalExp - $lowLevelExp;
 			$expRatio = $currExp / $currLevelExp;
 		}
 		// 加好感度经验值
 		$totalExp += $addExp;
 		$oldLevel = $level;
 		// 计算名将当前的好感度等级和好感度值
 		$favorLevel = self::getFavorLevel($stid);
 		self::getLevelByExp($level, $totalExp, $maxLevel, $favorLevel);
 		$newLevel = $level;
 		//计算所有名将好感度等级
 		$allStarInfo = $myStar->getAllInfo();
 		$totalFavor = 0;
 		foreach ($allStarInfo[StarDef::STAR_LIST] as $star)
 		{
 			$totalFavor += $star[StarDef::STAR_LEVEL];
 		}
 			
 		// 检查名将是否能升级
 		if ($newLevel > $oldLevel)
 		{
 			for ($i = $oldLevel + 1; $i <= $newLevel; $i++)
 			{
 				// 根据名将的能力发奖励
 				self::addAbilityReward($uid, $stid, $sid, $i);
 				// 升级到4心以上发消息
 				if ($i >= 4) 
 				{
 					ChatTemplate::sendStarFavor($user->getTemplateUserInfo(), $stid, $i);
 				}
 			}
 			//成就系统，以后要改
 			EnAchieve::notify($uid, AchieveDef::STAR_ALL_FAVOR, $totalFavor + $newLevel - $oldLevel, $totalFavor);
 			// 设置名将新的好感度等级
 			$myStar->setStarLevel($sid, $newLevel);
 			// 影响属性加成，修改战斗数据
 			$user->modifyBattleData();
 		}
 		else if (!empty($currLevelExp))
 		{
 			$ratio1 = btstore_get()->STAR_ALL[StarDef::STAR_RATIO_ONE];
 			$ratio2 = btstore_get()->STAR_ALL[StarDef::STAR_RATIO_TWO];
 			$baseRatio = self::getBaseRatio($stid, $oldLevel);
 			$ratio = $baseRatio * ($ratio1 + $ratio2 * $expRatio) / UNIT_BASE / 100;
 			$rand = rand(1, 100);
 			//产生暴击
 			if ($rand <= $ratio) 
 			{
 				// 设置名将新的好感度经验值和等级
 				$newLevel = $oldLevel + 1;
 				$totalExp = $highLevelExp;
 				self::addAbilityReward($uid, $stid, $sid, $newLevel);
 				if ($newLevel >= 4)
 				{
 					ChatTemplate::sendStarFavor($user->getTemplateUserInfo(), $stid, $newLevel);
 				}
 				//成就系统，以后要改
 				EnAchieve::notify($uid, AchieveDef::STAR_ALL_FAVOR, $totalFavor + $newLevel - $oldLevel, $totalFavor);
 				$myStar->setStarLevel($sid, $newLevel);
 				// 影响属性加成，修改战斗数据
 				$user->modifyBattleData();
 				$ret = true;
 			}
 		} 

 		// 设置名将新的好感度值
 		$myStar->setStarExp($sid, $totalExp);
 		
 		if ($newLevel > $oldLevel) 
 		{
 			EnAchieve::updateHeroFavor($uid, $totalFavor + $newLevel - $oldLevel);
 			$arrMinLevel = array();
 			foreach ($myStar->getAllStarLevel() as $key => $value)
 			{
 				if (HeroUtil::isFiveStarHero($key)) 
 				{
 					$arrMinLevel[] = $value;
 				}
 			}
 			rsort($arrMinLevel);
 			for ($i = 1; $i <= count($arrMinLevel); $i++)
 			{
 				$minLevel = min(array_slice($arrMinLevel, 0, $i));
 				EnNewServerActivity::updateAddPurpleFavor($uid, $i, $minLevel);
 			}
 		}
 		
 		Logger::trace('StarLogic::addFavor End.');
 		
 		return $ret;
 	}
 	
 	public static function addFeel($uid, $sid, $addFeel)
 	{
 		Logger::trace('StarLogic::addFeel Start.');
 		
 		// 获取名将对象, 名将信息
 		$myStar = MyStar::getInstance($uid);
 		$starInfo = $myStar->getStarInfo($sid);
 			
 		// 用户没有这个名将的信息
 		if (empty($starInfo))
 		{
 			throw new FakeException('User does not have this star id:%d!', $sid);
 		}
 		
 		// 检查名将的感悟度等级
 		$stid = $myStar->getStarStid($sid);
 		$feelLevel = $myStar->getStarFeelLevel($sid);
 		$maxLevel = self::getMaxFeelLevel($stid);
 		if ($feelLevel >= $maxLevel) 
 		{
 			throw new FakeException('star:%d feel level:%d is reach max level:%d', $sid, $feelLevel, $maxLevel);
 		}
 			
 		// 给名将加感悟值
 		$oldFeelLevel = $feelLevel;
 		$feelTotalExp = $myStar->getStarFeelExp($sid) + $addFeel;
 		$feelLevelConf = self::getFeelLevel($stid);
 		self::getLevelByExp($feelLevel, $feelTotalExp, $maxLevel, $feelLevelConf);
 		$newFeelLevel = $feelLevel;
 		
 		//判断是否升级
 		if ($newFeelLevel > $oldFeelLevel) 
 		{
 			//检查用户等级
 			$feelAbility = self::getFeelAbility($stid, $newFeelLevel);
 			$needLevel = $feelAbility[1];
 			$user = EnUser::getUserObj($uid);
 			if ($user->getLevel() < $needLevel)
 			{
 				throw new FakeException('User level is not enough to add feel!');
 			}
 			//检查技能是否升级
 			$myStar->setStarFeelLevel($sid, $newFeelLevel);
 			$skill = self::getSkillByFeelLevel($stid, $newFeelLevel);
 			if ($myStar->getStarFeelSkill($sid) != $skill) 
 			{
 				$masterSkill = $user->getMasterSkill();
 				$beforeSkill = $myStar->getStarFeelSkill($sid);
 				$myStar->setStarFeelSkill($sid, $skill);
 				if ($myStar->getEquipSkill() == $sid && isset($masterSkill[PropertyKey::RAGE_SKILL])
 					&& $masterSkill[PropertyKey::RAGE_SKILL][0] == $beforeSkill
 					&& $masterSkill[PropertyKey::RAGE_SKILL][1] == MASTERSKILL_SOURCE::STAR) 
 				{
 					$user->learnMasterSkill(PropertyKey::RAGE_SKILL, $skill, MASTERSKILL_SOURCE::STAR);
 				}
 			}
 			$user->modifyBattleData();
 			//加成就
 			$skillLevel = self::getSkillLevel($stid, $skill);
 			EnAchieve::updateActorIncSkillLev($uid, $skillLevel);
 			$allStarSkill = $myStar->getAllStarSkill();
 			$skillNum = count(array_diff($allStarSkill, array(0)));
 			EnAchieve::updateActorLearnSkill($uid, $skillNum);
 		}
 		//加感悟值
 		$myStar->setStarFeelExp($sid, $feelTotalExp);
 		
 		Logger::trace('StarLogic::addFeel End.');
 	}
 
 	/**
 	 * 获得名将的配置表信息
 	 * 
 	 * @param int $stid
 	 * @throws FakeException
 	 * @return array $starConf
 	 */
 	public static function getStarConf($stid)
 	{
 		Logger::trace('StarLogic::getStarConf Start.');
 	
 		if (!isset(btstore_get()->STAR[$stid]))
 		{
 			throw new FakeException('star template id:%d is not exist!', $stid);
 		}
 		$starConf = btstore_get()->STAR[$stid];
 			
 		Logger::trace('StarLogic::getStarConf End.');
 		return $starConf;
 	}
 	
 	/**
 	 * 获取名将的喜好礼物数组
 	 * 
 	 * @param int $stid
 	 * @return array $favorGifts
 	 */
 	public static function getFavorGifts($stid)
 	{
 		Logger::trace('StarLogic::getFavorGifts Start.');
 			
 		$starConf = self::getStarConf($stid);
 		$favorGifts = $starConf[StarDef::STAR_FAVOR_GIFT]->toArray();
 			
 		Logger::trace('StarLogic::getFavorGifts End.');
 		return $favorGifts;
 	}
 	
 	/**
 	 * 获得名将好感度等级对应的属性数组
 	 *
 	 * @param int $stid
 	 * @param int $level 
 	 * @return array $favorAbility
 	 */
 	public static function getFavorAbility($stid, $level)
 	{
 		Logger::trace('StarLogic::getFavorAbility Start.');
 			
 		$starConf = self::getStarConf($stid);
 		if (!isset($starConf[StarDef::STAR_FAVOR_ABILITY][$level]))
 		{
 			throw new ConfigException('star template id:%d favor ability level:%d is not exist!', $stid, $level);
 		}
 		$favorAbility = $starConf[StarDef::STAR_FAVOR_ABILITY][$level];
 			
 		Logger::trace('StarLogic::getFavorAbility End.');
 		return $favorAbility;
 	}
 	
 	/**
 	 * 获得名将感悟度等级对应的属性数组
 	 *
 	 * @param int $stid
 	 * @param int $level
 	 * @return array $feelAbility
 	 */
 	public static function getFeelAbility($stid, $level)
 	{
 		Logger::trace('StarLogic::getFeelAbility Start.');
 	
 		$starConf = self::getStarConf($stid);
 		if (!isset($starConf[StarDef::STAR_FEEL_ABILITY][$level]))
 		{
 			throw new ConfigException('star template id:%d feel ability level:%d is not exist!', $stid, $level);
 		}
 		$feelAbility = $starConf[StarDef::STAR_FEEL_ABILITY][$level];
 	
 		Logger::trace('StarLogic::getFeelAbility End.');
 		return $feelAbility;
 	}
 	
 	/**
 	 * 获得名将感悟度等级对应的技能id组
 	 *
 	 * @param int $stid
 	 * @return array $feelSkills
 	 */
 	public static function getFeelSkills($stid)
 	{
 		Logger::trace('StarLogic::getFeelSkills Start.');
 	
 		$starConf = self::getStarConf($stid);
 		$feelSkills = $starConf[StarDef::STAR_FEEL_SKILLS]->toArray();
 	
 		Logger::trace('StarLogic::getFeelSkills End.');
 		return $feelSkills;
 	}
 	
 	/**
 	 * 获得名将对应的普通技能
 	 *
 	 * @param int $uid
 	 * @param int $stid
 	 * @return int $normalSkill
 	 */
 	public static function getNormalSkill($uid, $stid)
 	{
 		Logger::trace('StarLogic::getNormalSkill Start.');
 	
 		$user = EnUser::getUserObj($uid);
 		$starConf = self::getStarConf($stid);
 		$normalSkill = $starConf[StarDef::STAR_NORMAL_SKILLS][$user->getUtid()];
 	
 		Logger::trace('StarLogic::getNormalSkill End.');
 		return $normalSkill;
 	}
 	
 	/**
 	 * 名将是否可以拜师
 	 * 
 	 * @param int $stid
 	 * @return Boolean
 	 */
 	public static function canFeel($stid)
 	{
 		Logger::trace('StarLogic::canFeel Start.');
 	
 		$starConf = self::getStarConf($stid);
 		$canFeel = $starConf[StarDef::STAR_CAN_FEEL];
 		
 		Logger::trace('StarLogic::canFeel End.');
 		return $canFeel;
 	}
 	
 	/**
 	 * 获得名将的好感度经验表id
 	 * 
 	 * @param int $stid
 	 * @return int $levelId
 	 */
 	public static function getLevelId($stid)
 	{
 		Logger::trace('StarLogic::getLevelId Start.');
 	
 		$starConf = self::getStarConf($stid);
 		$levelId = $starConf[StarDef::STAR_LEVEL_ID];
 		
 		Logger::trace('StarLogic::getLevelId End.');
 		return $levelId;
 	}

 	/**
 	 * 获得名将的好感度经验表信息
 	 * 
 	 * @param int $stid
 	 * @throws ConfigException
 	 * @return array $levelConf
 	 */
 	public static function getLevelConf($stid)
 	{
 		Logger::trace('StarLogic::getLevelConf Start.');
 	
 		$levelId = self::getLevelId($stid);
 		if (!isset(btstore_get()->STAR_LEVEL[$levelId]))
 		{
 			throw new ConfigException('star template id:%d level id:%d is not exist!', $stid, $levelId);
 		}
 		$levelConf = btstore_get()->STAR_LEVEL[$levelId];
 	
 		Logger::trace('StarLogic::getLevelConf End.');
 		return $levelConf;
 	}
 	
 	/**
 	 * 获得名将的好感度等级最大值
 	 *  
 	 * @param int $stid
 	 * @return int $maxLevel
 	 */
 	public static function getMaxLevel($stid)
 	{
 		Logger::trace('StarLogic::getMaxLevel Start.');
 			
 		$levelConf = self::getLevelConf($stid);
 		$maxLevel = $levelConf[StarDef::STAR_MAX_LEVEL];
 	
 		Logger::trace('StarLogic::getMaxLevel End.');
 		return $maxLevel;
 	}
 	
 	/**
 	 * 获得名将的感悟等级最大值
 	 *
 	 * @param int $stid
 	 * @return int $maxLevel
 	 */
 	public static function getMaxFeelLevel($stid)
 	{
 		Logger::trace('StarLogic::getMaxFeelLevel Start.');
 	
 		$starConf = self::getStarConf($stid);
 		$feelAbility = $starConf[StarDef::STAR_FEEL_ABILITY]->toArray();
 		$keys = array_keys($feelAbility);
 		$maxLevel = $keys[count($keys) - 1];
 	
 		Logger::trace('StarLogic::getMaxFeelLevel End.');
 		return $maxLevel;
 	}
 	
 	/**
 	 * 获得名将的好感经验表
 	 * 
 	 * @param int $stid
 	 * @return array $favorLevel
 	 */
 	public static function getFavorLevel($stid)
 	{
 		Logger::trace('StarLogic::getFavorLevel Start.');
 		
 		$levelConf = self::getLevelConf($stid);
 		$favorLevel = $levelConf[StarDef::STAR_FAVOR_LEVEL];
 		
 		Logger::trace('StarLogic::getFavorLevel End.');
 		return $favorLevel;
 	}
 	
 	/**
 	 * 获得名将的感悟经验表
 	 *
 	 * @param int $stid
 	 * @return array $feelLevel
 	 */
 	public static function getFeelLevel($stid)
 	{
 		Logger::trace('StarLogic::getFeelLevel Start.');
 			
 		$starConf = self::getStarConf($stid);
 		$expId = $starConf[StarDef::STAR_FEEL_EXP];
 		$feelLevel = btstore_get()->EXP_TBL[$expId];
 			
 		Logger::trace('StarLogic::getFeelLevel End.');
 		return $feelLevel;
 	}
 	
 	/**
 	 * 计算经验值对应的等级
 	 *
 	 * @param int $level 
 	 * @param int $totalExp
 	 * @param int $maxLevel
 	 * @param array $conf
 	 */
 	public static function getLevelByExp(&$level, &$totalExp, $maxLevel, $conf)
 	{
 		Logger::trace('StarLogic::getLevelByExp Start.');

 		if ($totalExp >= $conf[$maxLevel])
 		{
 			$level = $maxLevel;
 		}
 		else
 		{
 			foreach ($conf as $needLv => $needExp)
 			{
 				// 经验值不够，直接退出循环
 				if ($totalExp < $needExp)
 				{
 					break;
 				}
 				$level = $needLv;
 			}
 		}
 	
 		Logger::trace('StarLogic::getLevelByExp End.');
 	}
 	
 	/**
 	 * 获得等级对应的总经验值
 	 *
 	 * @param int $stid
 	 * @param int $level
 	 */
 	public static function getExpByLevel($stid, $level)
 	{
 		Logger::trace('StarLogic::getExpByLevel Start.');
 	
 		$maxLevel = self::getMaxLevel($stid);
 		$favorLevel = self::getFavorLevel($stid);
 		$level = min($level, $maxLevel);
 		$exp = $favorLevel[$level];
 			
 		Logger::trace('StarLogic::getExpByLevel End.');
 		return $exp;
 	}
 	
 	/**
 	 * 获得感悟等级下的技能id
 	 * 
 	 * @param int $stid
 	 * @param int $feelLevel
 	 * @return int $skill
 	 */
 	public static function getSkillByFeelLevel($stid, $feelLevel)
 	{
 		Logger::trace('StarLogic::getSkillByFeelLevel Start.');
 		
 		$skill = 0;
 		$feelSkills = self::getFeelSkills($stid);
 		foreach ($feelSkills as $key => $value)
 		{
 			if ($feelLevel < $key) 
 			{
 				break;
 			}
 			$skill = $value[0];
 		}
 		
 		Logger::trace('StarLogic::getSkillByFeelLevel End.');
 		return $skill;
 	}
 	
 	/**
 	 * 获得技能的等级
 	 * 
 	 * @param int $stid
 	 * @param int $skill
 	 * @return Ambigous <number, unknown>
 	 */
 	public static function getSkillLevel($stid, $skill)
 	{
 		Logger::trace('StarLogic::getSkillLevel Start.');
 			
 		$level = 0;
 		$feelSkills = self::getFeelSkills($stid);
 		foreach ($feelSkills as $key => $value)
 		{
 			if ($value[0] == $skill)
 			{
 				$level = $value[1];
 				break;
 			}
 		}
 			
 		Logger::trace('StarLogic::getSkillLevel End.');
 		return $level;
 	}
 	
 	/**
 	 * 获取名将好感度等级对应的能力配置表信息
 	 *
 	 * @param int $stid
 	 * @param int $level
 	 * @return array $abilityConf
 	 */
 	public static function getAbilityConf($stid, $level)
 	{
 		Logger::trace('StarLogic::getAbilityConf Start.');

 		$favorAbility = self::getFavorAbility($stid, $level);
 		if (!isset(btstore_get()->STAR_ABILITY[$favorAbility[0]]))
 		{
 			throw new ConfigException('abilityId:%d is not exist!', $favorAbility[0]);
 		}
 		$abilityConf = btstore_get()->STAR_ABILITY[$favorAbility[0]];
 	
 		Logger::trace('StarLogic::getAbilityConf End.');
 		return $abilityConf;
 	}
 	
 	/**
 	 * 获取名将好感度等级对应的修行能力配置表信息
 	 *
 	 * @param int $stid
 	 * @param int $level
 	 * @return array $feelAbilityConf
 	 */
 	public static function getFeelAbilityConf($stid, $level)
 	{
 		Logger::trace('StarLogic::getFeelAbilityConf Start.');
 	
 		$feelAbility = self::getFeelAbility($stid, $level);
 		if (!isset(btstore_get()->STAR_ABILITY_FEEL[$feelAbility[0]]))
 		{
 			throw new ConfigException('abilityId:%d is not exist!', $feelAbility[0]);
 		}
 		$feelAbilityConf = btstore_get()->STAR_ABILITY_FEEL[$feelAbility[0]];
 	
 		Logger::trace('StarLogic::getFeelAbilityConf End.');
 		return $feelAbilityConf;
 	}
 	
 	/**
 	 * 获取名将所有的行为事件配置表信息
 	 *
 	 * @param int $stid	名将模板id
 	 * @return array $actConfs 行为信息
 	 */
 	public static function getActConfs($stid)
 	{
 		Logger::trace('StarLogic::getActConfs Start.');
 			
 		$starConf = self::getStarConf($stid);
 		$favorAct = $starConf[StarDef::STAR_FAVOR_ACT];
 			
 		$actConfs = array();
 		foreach ($favorAct as $actId)
 		{
 			if (!isset(btstore_get()->STAR_ACT[$actId]))
 			{
 				throw new ConfigException('actId:%d is not exist!', $actId);
 			}
 			$actConfs[$actId] = btstore_get()->STAR_ACT[$actId];
 		}
 			
 		Logger::trace('StarLogic::getActConfs End.');
 		return $actConfs;
 	}
 	
 	/**
 	 * 获取行为事件中对应名将等级的奖励信息
 	 *
 	 * @param int $actId 行为id
 	 * @param int $level 名将等级
 	 * @return array $rewardInfo 奖励信息
 	 */
 	public static function getActReward($actId, $level)
 	{
 		Logger::trace('StarLogic::getActReward Start.');
 	
 		if (!isset(btstore_get()->STAR_ACT[$actId]))
 		{
 			throw new FakeException('act:%d is not exist!', $actId);
 		}
 		$type = btstore_get()->STAR_ACT[$actId][StarDef::STAR_REWARD_TYPE];
 		if (!array_key_exists($type, StarConf::$REWARD_VALID_TYPES))
 		{
 			throw new ConfigException('act:%d reward type:%d is not exists!', $actId, $type);
 		}
 		$rewardNum = btstore_get()->STAR_ACT[$actId][StarDef::STAR_REWARD_NUM];
 		if (!isset($rewardNum[$level]))
 		{
 			throw new ConfigException('act:%d reward level:%d is not exists!', $actId, $level);
 		}
 	
 		Logger::trace('StarLogic::getActReward End.');
 	
 		return 	array($type => $rewardNum[$level]);
 	}
 	
 	/**
 	 * 获得名将好感度等级对应的基础暴击率
 	 * 
 	 * @param int $stid
 	 * @param int $level
 	 * @throws ConfigException
 	 * @return int $baseRatio
 	 */
 	public static function getBaseRatio($stid, $level)
 	{
 		Logger::trace('StarLogic::getBaseRatio Start.');
 			
 		$levelId = self::getLevelId($stid);
 		if (!isset(btstore_get()->STAR_RATIO[$levelId]))
 		{
 			throw new ConfigException('star template id:%d ratio id:%d is not exist!', $stid, $levelId);
 		}
 		$favorRatio = btstore_get()->STAR_RATIO[$levelId][StarDef::STAR_FAVOR_RATIO];
 		$maxLevel = self::getMaxLevel($stid);
		$level = min($level, $maxLevel);
		$baseRatio = $favorRatio[$level];
		
 		Logger::trace('StarLogic::getBaseRatio End.');
 		return $baseRatio;
 	}
 	
 	/**
 	 * 获取行为事件对应的触发事件信息
 	 *
 	 * @return array mixed 触发事件信息
 	 */
 	public static function getTrigerId()
 	{
 		Logger::trace('StarLogic::getTrigerId Start.');
 			
 		// 随机概率
 		$rand = rand(1, 100);
 		if ($rand > StarConf::STAR_TRIGER_PROBABILITY)
 		{
 			return 0;
 		}
 		// 符合给定概率，随机出一个题目id
 		$trigerConf = btstore_get()->STAR_TRIGER->toArray();
 		$trigerId = array_rand($trigerConf);
 			
 		Logger::trace('StarLogic::getTrigerId End.');
 		return $trigerId;
 	}
 	
 	/**
 	 * 获取答题包中指定题目和选项的奖励信息
 	 *
 	 * @param int $uid 用户id
 	 * @param int $trigerId	答题包id
 	 * @param int $optionId	选项id
 	 * @return array mixed 奖励信息
 	 */
 	public static function getOptionReward($uid, $trigerId, $optionId)
 	{
 		Logger::trace('StarLogic::getOptionReward Start.');
 			
 		// 答题包id是否存在
 		if (!isset(btstore_get()->STAR_TRIGER[$trigerId]))
 		{
 			throw new FakeException('The trigerId:%d is not exist!', $trigerId);
 		}
 			
 		// 读配置表，获取答题奖励信息
 		if (!isset(btstore_get()->STAR_TRIGER[$trigerId][$optionId]))
 		{
 			throw new FakeException('The trigerId:%d does not have this optione_id:%d!', $trigerId, $optionId);
 		}
 		$optionInfo = btstore_get()->STAR_TRIGER[$trigerId][$optionId];
 			
 		// 奖励奖励的数值是否与等级相关，1相关，0无关
 		if ($optionInfo[2] == 1)
 		{
 			// 获取用户等级
 			$userLevel = EnUser::getUserObj($uid)->getLevel();
 			// 数量 = 基数*用户等级
 			$num = $optionInfo[1] * $userLevel;
 		}
 		else
 		{
 			$num = $optionInfo[1];
 		}
 		Logger::trace('StarLogic::getOptionReward End.');
 		return array($optionInfo[0] => $num);
 	}
 	
 	/**
 	 * 获取用户当天的免费的金币赠送次数
 	 * 暂时没有这个功能
 	 *
 	 * @param int $vip 用户的vip等级
 	 * @return int $freeNum	免费赠送次数
 	 */
 	public static function getFreeSend($vip)
 	{
 		$freeNum = 0;
 	
 		if (isset(btstore_get()->VIP[$vip]['freeStarTimes']))
 		{
 			$freeNum = btstore_get()->VIP[$vip]['freeStarTimes'];
 		}
 	
 		return $freeNum;
 	}
 	
 	public static function getPattern($index)
 	{
 		$drop = btstore_get()->STAR_TEACH[StarDef::STAR_DRAW_DROP]->toArray();
 		$pattern = array();
 		switch ($index)
 		{
 			case 1://5张卡牌相同
 				$key = array_rand($drop, 1);
 				$pattern = array($drop[$key],$drop[$key],$drop[$key],$drop[$key],$drop[$key]);
 				break;
 			case 2://4张卡牌相同+1张
 				$keys = array_rand($drop, 2);
 				$pattern = array($drop[$keys[0]],$drop[$keys[0]],$drop[$keys[0]],$drop[$keys[0]],$drop[$keys[1]]);
 				break;
 			case 3://3张卡牌相同+1对卡牌相同
 				$keys = array_rand($drop, 2);
 				$pattern = array($drop[$keys[0]],$drop[$keys[0]],$drop[$keys[0]],$drop[$keys[1]],$drop[$keys[1]]);
 				break;
 			case 4://3张卡牌相同
 				$keys = array_rand($drop, 3);
 				$pattern = array($drop[$keys[0]],$drop[$keys[0]],$drop[$keys[0]],$drop[$keys[1]],$drop[$keys[2]]);
 				break;
 			case 5://2对卡牌相同
 				$keys = array_rand($drop, 3);
 				$pattern = array($drop[$keys[0]],$drop[$keys[0]],$drop[$keys[1]],$drop[$keys[1]],$drop[$keys[2]]);
 				break;
 			case 6://1对卡牌相同
 				$keys = array_rand($drop, 4);
 				$pattern = array($drop[$keys[0]],$drop[$keys[0]],$drop[$keys[1]],$drop[$keys[2]],$drop[$keys[3]]);
 				break;
 			case 7://没有卡牌相同
 				$keys = array_rand($drop, 5);
 				$pattern = array($drop[$keys[0]],$drop[$keys[1]],$drop[$keys[2]],$drop[$keys[3]],$drop[$keys[4]]);
 				break;
 			default:
 				throw new ConfigException('invalid index:%d', $index);
 		}
 		shuffle($pattern);
 		Logger::trace("draw pattern:%s", $pattern);
 		
 		return $pattern;
 	}
 }
/* vim: set ts=4 sw=4 sts=4 tw=100 noet:*/