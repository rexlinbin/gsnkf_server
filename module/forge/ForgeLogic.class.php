<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ForgeLogic.class.php 258134 2016-08-24 07:17:53Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/forge/ForgeLogic.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-08-24 07:17:53 +0000 (Wed, 24 Aug 2016) $
 * @version $Revision: 258134 $
 * @brief 
 *  
 **/

class ForgeLogic
{
	/**
	 * 强化
	 * 
	 * @param int $uid				用户id
	 * @param int $itemId			物品id
	 * @param int $level			强化等级
	 * @throws FakeException
	 * @return string 'ok'
	 */
	public static function reinforce($uid, $itemId, $level)
	{
		Logger::trace('ForgeLogic::reinforce Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
// 		if (EnSwitch::isSwitchOpen(SwitchDef::ITEMENFORCE) == false)
// 		{
// 			throw new FakeException('user:%d does not open the item reinforce', $uid);
// 		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备
		if ( $item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
		
		//得到用户对象
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$vip = $user->getVip();
		$fatalInfo = btstore_get()->VIP[$vip]['fatalWeight'];
		//得到装备目前的强化等级
		$rate = $item->getReinforceRate();
		$currLevel = $item->getLevel();
		$reinforceCost = $item->getReinforceCost();

		if ( $currLevel + $level > $userLevel * $rate )
		{
			throw new FakeException('itemId:%d current reinforce level:%d add level:%d > user level:%d * rate:%d!', $itemId, $currLevel, $level, $userLevel, $rate);
		}
		if( $currLevel + $level > ArmDef::ARM_REINFORCE_LEVEL_MAX ) 
		{
			throw new FakeException('itemId:%d current reinforce level:%d add level:%d >= max level!', $itemId, $currLevel, $level);
		}
		
		$reqInfo = $item->getReinforceReq();
		if ($currLevel == 0) 
		{
			$sumCost = $reqInfo[$level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER];
		}
		else 
		{
			$sumCost = $reqInfo[$currLevel + $level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER]
						- $reqInfo[$currLevel][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER];
		}
		Logger::trace('sum cost silver:%d, current level:%d', $sumCost, $currLevel);
		$item->setReinforceCost($reinforceCost + $sumCost);
		
		$arrItem = array();
		for ($i = $currLevel + 1; $i <= $currLevel + $level; $i++)
		{
			if (!empty($reqInfo[$i][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS])) 
			{
				$arrItem[] = $reqInfo[$i][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS];
			}	
		}
		$arrItem = Util::arrayAdd2V($arrItem);
		
		//检查silver是否足够
		if ( $user->subSilver($sumCost) == FALSE )
		{
			throw new FakeException('no enough silver!');
		}
		
		$bag = BagManager::getInstance()->getBag();
		//检查物品是否足够		
		if ( $bag->deleteItemsbyTemplateID($arrItem) == FALSE )
		{
			throw new FakeException('delete Items failed, items:%s!', $arrItem);
		}
		
		//注意：暴击不支持一次强化多个等级，计算银币有误
		$fatalNum = 0;
		$levelNum = 0;
		for ($i = 0; $i < $level; $i++)
		{
			$trueLevel = 1;
			$keys = Util::backSample($fatalInfo, 1);
			if ($keys[0] > $trueLevel) 
			{
				$trueLevel = $keys[0];
				$fatalNum ++;
			}
			$levelNum += $trueLevel;
			//强化,直到到达最高的等级
			if ( $item->reinforce($trueLevel) == FALSE )
			{
				throw new InterException('itemId:%d reinforce failed!', $itemId);
			}
		}
		
		//物品如果不在背包里则战斗优化
		if ( $bag->isItemExist($itemId) == false)
		{
			$user->modifyBattleData();
		}
		
		$user->update();
		$bag->update();
		
		EnAchieve::updateArmReinforceLevel($uid, $item->getLevel());
		
		//强化伙伴身上装备
		if (!$bag->isItemExist($itemId))
		{
			self::activateArmAchieve($uid);
		}
		
		Logger::trace('ForgeLogic::reinforce End.');
		return array(
				'cost_num' => $sumCost,
				'fatal_num' => $fatalNum,
				'level_num' => $levelNum
		);
	}
	
	/**
	 * 自动强化装备
	 * 
	 * @param int $uid
	 * @param int $itemId
	 * @throws FakeException
	 * @return array
	 * {
	 * 		{
	 * 			'fatal_num':int
	 *			'level_num':int
	 *		}
	 * }
	 */
	public static function autoReinforce($uid, $itemId)
	{
		Logger::trace('ForgeLogic::autoReinforce Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
// 		if (EnSwitch::isSwitchOpen(SwitchDef::ITEMENFORCE) == false)
// 		{
// 			throw new FakeException('user:%d does not open the item reinforce', $uid);
// 		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备
		if ( $item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
		
		//得到用户对象
		$bag = BagManager::getInstance()->getBag();
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$vip = $user->getVip();
		$fatalInfo = btstore_get()->VIP[$vip]['fatalWeight'];
		//得到装备目前的强化等级,强化系数和强化花费
		$rate = $item->getReinforceRate();
		$reqInfo = $item->getReinforceReq();
		$currLevel = $item->getLevel();
		$reinforceCost = $item->getReinforceCost();
		
		$level = 1;
		$arrRet = array();
		while (true)
		{
			if ( $currLevel >= $userLevel * $rate )
			{
				break;
			}
			if( $currLevel >= ArmDef::ARM_REINFORCE_LEVEL_MAX )
			{
				break;
			}
			//计算花费银币		
			if ($currLevel == 0)
			{
				$sumCost = $reqInfo[$level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER];
			}
			else
			{
				$sumCost = $reqInfo[$currLevel + $level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER]
				- $reqInfo[$currLevel][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_SILVER];
			}
			Logger::trace('sum cost silver:%d, current level:%d', $sumCost, $currLevel);
			if ( $user->getSilver() < $sumCost)
			{
				break;
			}
			//计算花费物品
			$arrItem = array();
			if (!empty($reqInfo[$currLevel + $level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS]))
			{
				$arrItem = $reqInfo[$currLevel + $level][armdef::ITEM_ATTR_NAME_ARM_REINFORCE_ITEMS];
				$flag = true;
				foreach ($arrItem as $itemTplId => $num)
				{
					if ( $bag->getItemNumByTemplateID($itemTplId) < $num)
					{
						$flag = false;
						break;
					}
				}
				if ($flag == false) 
				{
					break;
				}
			}
			$fatal = 0;
			$keys = Util::backSample($fatalInfo, 1);
			if ($keys[0] > $level)
			{
				$fatal = 1;
				$level = $keys[0];
			}
			//强化,直到最大等级
			if ( $item->reinforce($level) == FALSE )
			{
				break;
			}
			$item->setReinforceCost($reinforceCost + $sumCost);
			$user->subSilver($sumCost);
			$bag->deleteItemsByTemplateID($arrItem);
			$arrRet[] = array(
					'cost_num' => $sumCost,
					'fatal_num' => $fatal,
					'level_num' => $level,	
			);
			$reinforceCost = $item->getReinforceCost();
			$currLevel = $item->getLevel();
			$level = 1;
		}
			
		//物品如果不在背包里则战斗优化
		if ( $bag->isItemExist($itemId) == false)
		{
			$user->modifyBattleData();
		}

		$user->update();
		$bag->update();
		
		EnAchieve::updateArmReinforceLevel($uid, $item->getLevel());
		
		//强化伙伴身上装备
		if (!$bag->isItemExist($itemId))
		{
			self::activateArmAchieve($uid);
		}
		
		Logger::trace('ForgeLogic::autoReinforce End.');
		return $arrRet;
	}
	
	public static function developArm($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::developArm Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ARMDEVELOP_2_RED) == false)
		{
			throw new FakeException('user:%d does not open the develop arm', $uid);
		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//批量拉物品
		$arrItemId = array_merge($itemIds, array($itemId));
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM)
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
	
		//装备不能进阶
		if (!$item->canDevelop())
		{
			throw new FakeException('itemId:%d can not develop!', $itemId);
		}
	
		//进阶达到上限
		if ($item->getDevelop() >= $item->getDevelopLimit())
		{
			throw new FakeException('itemId:%d develop:%d reach limit:%d!', $item->getDevelop(), $item->getDevelopLimit());
		}
	
		//检查花费
		$develop = $item->getDevelop();
		$item->setDevelop($develop + 1);
		$developExpend = $item->getDevelopExpend($develop + 1);
		RewardUtil::delMaterial($uid, $developExpend, null, 1, $itemIds);
		
		//在武将身上要刷新战斗力
		$bag = BagManager::getInstance()->getBag($uid);
		$user = EnUser::getUserObj($uid);
		if (!$bag->isItemExist($itemId))
		{
			$user->modifyBattleData();
		}
			
		Logger::trace('ForgeLogic::developArm End.');
	
		return $item->itemInfo();
	}
	
	/**
	 * 升级宝物
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function upgrade($uid, $itemId, $itemIds, $arrNum)
	{
		Logger::trace('ForgeLogic::upgrade Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		if (EnSwitch::isSwitchOpen(SwitchDef::TREASUREENFORCE) == false)
		{
			throw new FakeException('user:%d does not open the treasure upgrade', $uid);
		}
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TREASURE) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		
		//批量拉物品
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$items = ItemManager::getInstance()->getItems($arrItemId);
		$item = $items[$itemId];
		unset($items[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('itemId:%d is not a treasure!', $itemId);
		}
		
		//检查物品是否能升级
		if (!$item->canUpgrade())
		{
			throw new FakeException('itemId:%d can not upgrade!', $itemId);
		}
		
		//检查宝物等级
		$level = $item->getLevel();
		$limit = $item->getLimitLevel();
		if ($level >= $limit)
		{
			throw new FakeException('itemId:%d level is reach the limit:%d', $itemId, $limit);
		}
		
		$i = 0;
		$sumBaseValue = 0;
		$arrDecrease = array();
		$treasType = $item->getType();
		$bag = BagManager::getInstance()->getBag($uid);
		foreach ($items as $id => $itemObj)
		{
			//检查宝物是否属于该用户
			if (EnUser::isCurUserOwnItem($id, ItemDef::ITEM_TYPE_TREASURE) == FALSE)
			{
				throw new FakeException('itemId:%d is not belong to user:%d!', $id, $uid);
			}
			//检查物品是否为宝物
			if ($itemObj === NULL || $itemObj->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
			{
				throw new FakeException('itemId:%d is not a treasure!', $id);
			}
			$arrDecrease[$id] = empty($arrNum) ? $itemObj->getItemNum() : $arrNum[$i];
			$i++;	
			if ($arrDecrease[$id] > 50) 
			{
				throw new FakeException('itemId:%d num is larger than 50!', $id);
			}
			if ($itemObj->getItemNum() < $arrDecrease[$id]) 
			{
				throw new FakeException('itemId:%d num is less than:%d!', $id, $arrDecrease[$id]);
			}
			if ($treasType != $itemObj->getType())
			{
				throw new FakeException('itemId:%d treas type:%d is not equal treas type:%d', $id, $itemObj->getType(), $treasType);
			}
			if ($bag->isItemExist($id) == false) 
			{
				throw new FakeException('itemId:%d is not in the bag', $id);
			}
			$inlay = $itemObj->getInlay();
			if (!empty($inlay)) 
			{
				throw new FakeException('itemId:%d has inlay, can not be used to upgrade', $id);
			}
			if ($itemObj->getBaseValue() == 0)
			{
				throw new FakeException('itemId:%d can not be used to upgrade', $id);
			}
			$sumBaseValue += ($itemObj->getBaseValue() + $itemObj->getExp()) * $arrDecrease[$id];
		}
		
		//计算升级所需经验和银币
		$silver = 0;
		$oldLevel = $level;
		$oldExp = $item->getExp();
		$exp = $oldExp + $sumBaseValue;
		$upgradeValue = $item->getUpgradeValue($level);
		while ($exp >= $upgradeValue) 
		{
			$expend = $item->getUpgradeExpend($level);
			$silver += $expend * ($upgradeValue - $oldExp);
			$level++;
			$oldExp = $upgradeValue;
			if ($level == $limit) 
			{
				break;
			}
			$upgradeValue = $item->getUpgradeValue($level);
		}
		$expend = $item->getUpgradeExpend($level);
		$silver += $expend * ($exp - $oldExp);
		$user = EnUser::getUserObj($uid);
		if ($user->subSilver($silver) == false)
		{
			throw new FakeException('user:%d has not enough silver:%d for upgrade', $uid, $silver);
		}
		$item->setLevel($level);
		$item->setExp($exp);
		//升级成功
		if ($level > $oldLevel) 
		{
			if ( $bag->isItemExist($itemId) == false)
			{
				$user->modifyBattleData();
			}
		}
		
		//无论成功或失败，都删除使用掉的物品
		foreach ($arrDecrease as $id => $num)
		{
			$bag->decreaseItem($id, $num);
		}
		
		$user->update();
		$bag->update();
		
		if ($level > $oldLevel) 
		{
			EnAchieve::updateTreasureLevel($uid, $level);
			if (!$bag->isItemExist($itemId))
			{
				self::activateTreasAchieve($uid);
			}
		}
			
		Logger::trace('ForgeLogic::upgrade End.');
		
		return $item->itemInfo();
	}
	
	/**
	 * 精炼宝物
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function evolve($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::evolve Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		if (EnSwitch::isSwitchOpen(SwitchDef::TREASUREEVOLVE) == false)
		{
			throw new FakeException('user:%d does not open the treasure evolve', $uid);
		}
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TREASURE) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		//检查物品是否为宝物
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$items = ItemManager::getInstance()->getItems($arrItemId);
		$item = $items[$itemId];
		unset($items[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('itemId:%d is not a treasure!', $itemId);
		}
		//检查物品是否能精炼
		if (!$item->canEvolve() || $item->getLevel() < 1)
		{
			throw new FakeException('itemId:%d can not evolve!', $itemId);
		}
		$evolve = $item->getEvolve();
		$limit = $item->getEvolveLimit();
		if ($evolve >= $limit)
		{
			throw new FakeException('itemId:%d evolve is reach the limit:%d', $itemId, $limit);
		}
		
		$evolveExpend = $item->getEvolveExpend($evolve);
		$user = EnUser::getUserObj($uid);
		if (!empty($evolveExpend['silver'])) 
		{
			if ($user->subSilver($evolveExpend['silver']) == false) 
			{
				throw new FakeException('user:%d has not enough silver:%d for evolve', $uid, $evolveExpend['silver']);
			}
		}
		
		$arrMergeItemTplId = array();
		$bag = BagManager::getInstance()->getBag($uid);
		if (!empty($evolveExpend['item'])) 
		{
			foreach ($items as $id => $itemObj)
			{
				if ($bag->isItemExist($id) == false) 
				{
					throw new FakeException('itemId:%d is not in the bag', $id);
				}
				//检查物品是否为宝物
				if ($itemObj === NULL || $itemObj->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
				{
					throw new FakeException('itemId:%d is not a treasure!', $id);
				}
				//如果被精炼的宝物强化等级大于0
				if ($itemObj->getLevel() >= 1 || ($itemObj->canEvolve() && $itemObj->getEvolve() >= 1)) 
				{
					throw new FakeException('itemId:%d can not be used to evolve', $id);
				}
				$itemTplId = $itemObj->getItemTemplateID();
				if (!in_array($itemTplId, $arrMergeItemTplId)) 
				{
					$arrMergeItemTplId[] = $itemTplId;
				}
				if (isset($evolveExpend['item'][$itemTplId])) 
				{
					$num = $evolveExpend['item'][$itemTplId];
					$itemNum = $itemObj->getItemNum();
					$delNum = min($num, $itemNum);
					$bag->decreaseItem($id, $delNum);
					$evolveExpend['item'][$itemTplId] -= $delNum;
				}
			}
			$sum = 0;
			foreach ($evolveExpend['item'] as $itemTplId => $num)
			{
				$sum += $num;
			}
			if ($sum > 0) 
			{
				throw new FakeException('no enough items:%s to evolve!', $evolveExpend['item']);
			}
		}
		
		// 当精炼宝物成功的时候，将材料（宝物精华）进行合并，以前宝物精华不可叠加，现在可以叠加啦
		foreach ($arrMergeItemTplId as $aMergeItemTplId)
		{
			$bag->mergeItem($aMergeItemTplId);
		}
		
		$item->setEvolve($evolve + 1);
		if ($bag->isItemExist($itemId) == false)
		{
			$user->modifyBattleData();
		}
		
		$user->update();
		$bag->update();
		
		EnAchieve::updateTreasureEvolveLevel($uid, $evolve + 1);
		
		if (!$bag->isItemExist($itemId))
		{
			self::activateTreasAchieve($uid);
		}
		EnNewServerActivity::updateAnyMagicTreasure($uid, $evolve + 1);
		
		Logger::trace('ForgeLogic::evolve End.');
		
		return $item->itemInfo();
	}
	
	public static function develop($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::develop Start.');
	
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}

		$itemId = intval($itemId);
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TREASURE) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		$arrItemId = array_merge($itemIds, array($itemId));
		foreach ($arrItemId as $key => $value)
		{
			$arrItemId[$key] = intval($value);
		}
		$items = ItemManager::getInstance()->getItems($arrItemId);
		$item = $items[$itemId];
		unset($items[$itemId]);
		
		//检查物品是否为宝物
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('itemId:%d is not a treasure!', $itemId);
		}
		
		//检查物品是否能进阶
		if (!$item->canDevelop())
		{
			throw new FakeException('itemId:%d can not develop!', $itemId);
		}
		
		//检查物品是否可以二次进阶
		$develop = $item->getDevelop();
		if ($develop + 1 >= TreasureDef::RED_INIT_DEVELOP
		&& !$item->canDevelop2Red()) 
		{
			throw new FakeException('itemId:%d can not develop to red!', $itemId);
		}
		
		$user = EnUser::getUserObj($uid);
		if (!isset(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TREAS_DEVELOP_LEVEL][$develop + 1])) 
		{
			throw new FakeException('item develop reach limit:%d!', $develop);
		}
		
		//检查用户等级是否足够
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TREAS_DEVELOP_LEVEL][$develop + 1];
		if ($user->getLevel() < $needLevel) 
		{
			throw new FakeException('user can not develop, need level:%d!', $needLevel);
		}
		
		//检查花费
		$developExpend = $item->getDevelopExpend($develop + 1);
		$developExpend = self::transExpend($developExpend);
		if (!empty($developExpend['silver']))
		{
			if ($user->subSilver($developExpend['silver']) == false)
			{
				throw new FakeException('user:%d has not enough silver:%d for develop', $uid, $developExpend['silver']);
			}
		}
	
		$bag = BagManager::getInstance()->getBag($uid);
		if (!empty($developExpend['item']))
		{
			foreach ($items as $id => $itemObj)
			{
				if ($bag->isItemExist($id) == false)
				{
					throw new FakeException('itemId:%d is not in the bag', $id);
				}
				if ($itemObj == NULL)
				{
					throw new FakeException('itemId:%s is not a item!', $id);
				}
				$itemTplId = $itemObj->getItemTemplateID();
				if (isset($developExpend['item'][$itemTplId]))
				{
					$num = $developExpend['item'][$itemTplId];
					$itemNum = $itemObj->getItemNum();
					$delNum = min($num, $itemNum);
					$bag->decreaseItem($id, $delNum);
					$developExpend['item'][$itemTplId] -= $delNum;
				}
			}
			$sum = 0;
			foreach ($developExpend['item'] as $itemTplId => $num)
			{
				$sum += $num;
			}
			if ($sum > 0)
			{
				throw new FakeException('no enough items:%s to evolve!', $developExpend['item']);
			}
		}
	
		$item->setDevelop($develop + 1);
		if ($bag->isItemExist($itemId) == false)
		{
			$user->modifyBattleData();
		}
	
		$user->update();
		$bag->update();
	
		Logger::trace('ForgeLogic::develop End.');
	
		return $item->itemInfo();
	}
	
	/**
	 * 道具升级战魂
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function promote($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::promote Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == false)
		{
			throw new FakeException('user:%d does not open the hunt', $uid);
		}
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_FIGHTSOUL) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		//批量拉物品
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		unset($arrItem[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
		{
			throw new FakeException('itemId:%d is not a fightsoul!', $itemId);
		}
		
		$sumValue = 0;
		$fightSoulQuality = $item->getItemQuality();
		$bag = BagManager::getInstance()->getBag($uid);
		foreach ($arrItem as $id => $itemObj)
		{
			//检查物品是否属于该用户
			if (EnUser::isCurUserOwnItem($id, ItemDef::ITEM_TYPE_FIGHTSOUL) == FALSE)
			{
				throw new FakeException('itemId:%d is not belong to user:%d!', $id, $uid);
			}
			//检查物品是否为战魂
			if ($itemObj === NULL || $itemObj->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
			{
				throw new FakeException('itemId:%d is not a fightsoul!', $id);
			}
			if ($itemObj->getItemQuality() == ItemDef::ITEM_QUALITY_ORANGE || $itemObj->getItemQuality() > $fightSoulQuality)
			{
				throw new FakeException('itemId:%d fightsoul quality:%d is larger than:%d', $id, $itemObj->getItemQuality(), $fightSoulQuality);
			}
			if ($bag->isItemExist($id) == false)
			{
				throw new FakeException('itemId:%d is not in the bag', $id);
			}
			$sumValue += $itemObj->getValue() + $itemObj->getExp();
		}
		
		//获取战魂等级
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$level = $item->getLevel();
		$limit = $item->getLimit($userLevel);
		if ($level >= $limit)
		{
			throw new FakeException('itemId:%d level is reach the limit:%d', $itemId, $limit);
		}
		
		//计算升级所需经验
		$oldLevel = $level;
		$exp = $item->getExp();
		$upgradeValue = $item->getUpgradeValue($level + 1);
		$exp += $sumValue;
		while ($exp >= $upgradeValue)
		{ 
			$level++;
			if ($level == $limit)
			{
				break;
			}
			$upgradeValue = $item->getUpgradeValue($level + 1);
		}
		$item->setLevel($level);
		$item->setExp($exp);
		//升级成功
		if ($level > $oldLevel)
		{
			if ( $bag->isItemExist($itemId) == false)
			{
				$user->modifyBattleData();
			}
		}
		
		//无论成功或失败，都删除使用掉的物品
		foreach ($itemIds as $id)
		{
			$bag->deleteItem($id);
		}
		
		$user->update();
		$bag->update();
		
		if ($level > $oldLevel) 
		{
			EnAchieve::updateFightSoulLevel($uid, $level);
			self::activateFightSoulAchieve($uid);
		}
			
		Logger::trace('ForgeLogic::promote End.');
		
		return $item->itemInfo();
	}
	
	/**
	 * 经验升级战魂
	 *
	 * @param int $itemId			物品id
	 * @param int $addLevel			增加的等级
	 * @return array $itemInfo 		物品信息
	 */
	public static function promoteByExp($uid, $itemId, $addLevel)
	{
		Logger::trace('ForgeLogic::promoteByExp Start.');
	
		if (EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == false)
		{
			throw new FakeException('user:%d does not open the hunt', $uid);
		}
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_FIGHTSOUL) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//获得物品对象
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
		{
			throw new FakeException('itemId:%d is not a fightsoul!', $itemId);
		}
	
		//检查战魂等级上限
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$level = $item->getLevel();
		$limit = $item->getLimit($userLevel);
		if ($level >= $limit)
		{
			throw new FakeException('itemId:%d level is reach the limit:%d', $itemId, $limit);
		}
	
		//升1级和升5级需要判断经验值是否够升1级的
		$fsExp = $user->getFsExp();
		$upgradeExp = $item->getUpgradeValue($level + 1);
		$needExp = $upgradeExp - $item->getExp();
		if ($fsExp < $needExp) 
		{
			throw new FakeException('user fs_exp is not enough for:%d', $needExp);
		}
		
		//升5级，经验值不够就有多少加多少,且加的经验值不能超过等级上限
		$realAddLevel = min($addLevel, $limit - $level);
		$upgradeExp = $item->getUpgradeValue($level + $realAddLevel);
		$needExp = min($upgradeExp - $item->getExp(), $fsExp);
		if (!$user->subFsExp($needExp)) 
		{
			throw new FakeException('user fs_exp is not enough for:%d', $needExp);
		}
		$item->addExp($needExp);
	
		//修改战斗数据
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->isItemExist($itemId))
		{
			$user->modifyBattleData();
		}
	
		$user->update();
		$bag->update();
	
		EnAchieve::updateFightSoulLevel($uid, $item->getLevel());
		
		if (!$bag->isItemExist($itemId))
		{
			self::activateFightSoulAchieve($uid);
		}
			
		Logger::trace('ForgeLogic::promoteByExp End.');
		
		$ret = $item->itemInfo();
		$ret['fs_exp'] = $needExp;
	
		return $ret;
	}
	
	public static function fightSoulDevelop($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::fightSoulDevelop Start.');
		
		$itemId = intval($itemId);
		foreach ( $itemIds as $key => $value )
		{
			$itemIds[$key] = intval($value);
		}
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == false)
		{
			throw new FakeException('user:%d does not open the hunt', $uid);
		}
		
		//批量拉物品
		$arrItemId = array_merge($itemIds, array($itemId));
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
		{
			throw new FakeException('itemId:%d is not a fightsoul!', $itemId);
		}
		
		//战魂不能进阶
		if (!$item->canDevelop()) 
		{
			throw new FakeException('itemId:%d can not develop!', $itemId);
		}
		
		//战魂进阶需要等级
		if ($item->getLevel() < $item->getDevelopLevel()) 
		{
			throw new FakeException('itemId:%d level can not develop!', $itemId);
		}
		
		//如果橙进红
		$user = EnUser::getUserObj($uid);
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_FS_2_RED];
		if ($item->getItemQuality() == ItemDef::ITEM_QUALITY_ORANGE && $user->getLevel() < $needLevel) 
		{
			throw new FakeException('user level is not enough!');
		}
		
		//战魂进阶后经验继承
		$exp = $item->getExp();
		$evolve = $item->getEvolve();
		$itemTplId = $item->getDevelopId();
		if (ItemManager::getInstance()->getItemType($itemTplId) != ItemDef::ITEM_TYPE_FIGHTSOUL) 
		{
			throw new FakeException('itemTplId:%d is not a fightsoul!', $itemTplId);
		}
		$developCost = $item->getDevelopCost();
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->deleteItem($itemId))
		{
			throw new FakeException('itemId:%d is not in bag!', $itemId);
		}
		$itemId = ItemManager::getInstance()->addItem($itemTplId);
		$itemId = $itemId[0];
		$item = ItemManager::getInstance()->getItem($itemId);
		$level = $item->getLevel();
		$limit = $item->getLimit($user->getLevel());
		$upgradeValue = $item->getUpgradeValue($level + 1);
		while ($exp >= $upgradeValue)
		{
			$level++;
			if ($level == $limit)
			{
				break;
			}
			$upgradeValue = $item->getUpgradeValue($level + 1);
		}
		$item->setLevel($level);
		$item->setExp($exp);
		//精炼等级继承（橙色战魂才有精炼等级）
		if ($item->canEvolve()) 
		{
			$item->setEvolve($evolve);
		}
		if (!$bag->addItem($itemId)) 
		{
			throw new FakeException('bag is full!', $itemId);
		}
		
		//战魂进阶消耗
		RewardUtil::delMaterial($uid, $developCost, null, 1, $itemIds);
			
		Logger::trace('ForgeLogic::fightSoulDevelop End.');
		
		return $item->itemInfo();
	}
	
	public static function fightSoulEvolve($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::fightSoulEvolve Start.');
	
		$itemId = intval($itemId);
		foreach ( $itemIds as $key => $value )
		{
			$itemIds[$key] = intval($value);
		}
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == false)
		{
			throw new FakeException('user:%d does not open the hunt', $uid);
		}
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_FIGHTSOUL) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//批量拉物品
		$arrItemId = array_merge($itemIds, array($itemId));
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
		{
			throw new FakeException('itemId:%d is not a fightsoul!', $itemId);
		}
	
		//战魂不能精炼
		if (!$item->canEvolve())
		{
			throw new FakeException('itemId:%d can not evolve!', $itemId);
		}
	
		//战魂精炼等级上限
		if ($item->getEvolve() >= $item->getEvolveLimit())
		{
			throw new FakeException('itemId:%d evolve reach limit!', $itemId);
		}
	
		//战魂精炼消耗
		$evolve = $item->getEvolve();
		$evolveCost = $item->getEvolveCost($evolve);
		$item->setEvolve($evolve + 1);
		RewardUtil::delMaterial($uid, $evolveCost, null, 1, $itemIds);
		
		//在武将身上要刷新战斗力
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->isItemExist($itemId))
		{
			EnUser::getUserObj($uid)->modifyBattleData();
		}
			
		Logger::trace('ForgeLogic::fightSoulEvolve End.');
	
		return $item->itemInfo();
	}
	
	public static function upgradeDress($uid, $itemId)
	{
		Logger::trace('ForgeLogic::upgradeDress Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ENFORCEDRESS) == false)
		{
			throw new FakeException('user:%d does not open the dress enforce', $uid);
		}
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_DRESS) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_DRESS)
		{
			throw new FakeException('itemId:%d is not a dress!', $itemId);
		}
		
		$level = $item->getLevel();
		$cost = $item->getCost($level + 1);
		$user = EnUser::getUserObj($uid);
		if (!empty($cost['level'])) 
		{
			if ($user->getLevel() < $cost['level']) 
			{
				throw new FakeException('no enough level:%d!', $cost['level']);
			}
		}
		if (!empty($cost['silver'])) 
		{
			if ($user->subSilver($cost['silver']) == false)
			{
				throw new FakeException('no enough silver:%d!', $cost['silver']);
			}
		}
		$bag = BagManager::getInstance()->getBag($uid);
		if (!empty($cost['item'])) 
		{
			if ($bag->deleteItemsByTemplateID($cost['item']) == false) 
			{
				throw new FakeException('no enough items:%s!', $cost['item']);
			}
		}
		
		$item->setLevel($level + 1);
		if ($bag->isItemExist($itemId) == false)
		{
			$user->modifyBattleData();
		}
		
		$user->update();
		$bag->update();
		
		Logger::trace('ForgeLogic::upgradeDress End.');
		
		return $item->itemInfo();
	}
	
	/**
	 * 升级锦囊
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function upgradePocket($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::upgradePocket Start.');
	
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_POCKET) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//批量拉物品
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		unset($arrItem[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_POCKET)
		{
			throw new FakeException('itemId:%d is not a pocket!', $itemId);
		}
		
		if ($item->isExp()) 
		{
			throw new FakeException('itemId:%d can not upgrade!', $itemId);
		}
	
		$sumValue = 0;
		$pocketQuality = $item->getItemQuality();
		$bag = BagManager::getInstance()->getBag($uid);
		foreach ($arrItem as $id => $itemObj)
		{
			//检查物品是否属于该用户
			if (EnUser::isCurUserOwnItem($id, ItemDef::ITEM_TYPE_POCKET) == FALSE)
			{
				throw new FakeException('itemId:%d is not belong to user:%d!', $id, $uid);
			}
			//检查物品是否为锦囊
			if ($itemObj === NULL || $itemObj->getItemType() != ItemDef::ITEM_TYPE_POCKET)
			{
				throw new FakeException('itemId:%d is not a pocket!', $id);
			}
			if (!$itemObj->isExp() && $itemObj->getItemQuality() > $pocketQuality)
			{
				throw new FakeException('itemId:%d pocket quality:%d is larger than:%d', $id, $itemObj->getItemQuality(), $pocketQuality);
			}
			if ($itemObj->isLock()) 
			{
				throw new FakeException('itemId:%d is lock', $id);
			}
			if ($bag->isItemExist($id) == false)
			{
				throw new FakeException('itemId:%d is not in the bag', $id);
			}
			$sumValue += $itemObj->getValue() + $itemObj->getExp();
		}
	
		//获取等级和上限
		$level = $item->getLevel();
		$limit = $item->getLimit();
		if ($level >= $limit)
		{
			throw new FakeException('itemId:%d level is reach the limit:%d', $itemId, $limit);
		}
		
		//检查所需银币
		$user = EnUser::getUserObj($uid);
		$valueCost = $item->getValueCost();
		$sumCost = $sumValue * $valueCost;
		if (!$user->subSilver($sumCost)) 
		{
			throw new FakeException('user:%d have no enough silver:%d', $sumCost);
		}
	
		//计算升级所需经验
		$item->addExp($sumValue);
		$newLevel = $item->getLevel();
		//升级成功
		if ($newLevel > $level)
		{
			if ( $bag->isItemExist($itemId) == false)
			{
				$user->modifyBattleData();
			}
		}
	
		//无论成功或失败，都删除使用掉的物品
		foreach ($itemIds as $id)
		{
			$bag->deleteItem($id);
		}
	
		$user->update();
		$bag->update();
			
		Logger::trace('ForgeLogic::upgradePocket End.');
	
		return $item->itemInfo();
	}
	
	/**
	 * 升级兵符
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function upgradeTally($uid, $itemId, $itemIds, $arrNum)
	{
		Logger::trace('ForgeLogic::upgradeTally Start.');
		
		$itemId = intval($itemId);
		foreach ($itemIds as $key => $value)
		{
			$itemIds[$key] = intval($value);
		}
		
		if (EnSwitch::isSwitchOpen(SwitchDef::TALLY) == false)
		{
			throw new FakeException('user:%d does not open the tally', $uid);
		}
	
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TALLY) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//批量拉物品
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		unset($arrItem[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TALLY)
		{
			throw new FakeException('itemId:%d is not a tally!', $itemId);
		}
		
		if (!$item->canUpgrade()) 
		{
			throw new FakeException('itemId:%d can not upgrade!', $itemId);
		}
	
		$i = 0;
		$sumValue = 0;
		$arrDecrease = array();
		$bag = BagManager::getInstance()->getBag($uid);
		foreach ($arrItem as $id => $itemObj)
		{
			//检查物品是否为普通物品
			if ($itemObj === NULL || $itemObj->getItemType() != ItemDef::ITEM_TYPE_NORMAL)
			{
				throw new FakeException('itemId:%d is not a normal!', $id);
			}
			if (!$bag->isItemExist($id))
			{
				throw new FakeException('itemId:%d is not in the bag', $id);
			}
			$arrDecrease[$id] = empty($arrNum) ? $itemObj->getItemNum() : $arrNum[$i];
			if ($itemObj->getItemNum() < $arrDecrease[$id])
			{
				throw new FakeException('itemId:%d num is less than:%d!', $id, $arrDecrease[$id]);
			}
			$sumValue += $itemObj->getTallyExp() * $arrDecrease[$id];
			$i++;
		}
	
		//检查所需银币
		$user = EnUser::getUserObj($uid);
		$upgradeCost = $item->getUpgradeCost();
		$sumCost = $sumValue * $upgradeCost;
		if (!$user->subSilver($sumCost))
		{
			throw new FakeException('user:%d have no enough silver:%d', $uid, $sumCost);
		}
	
		//计算升级所需经验
		$level = $item->getLevel();
		$item->addExp($sumValue);
		$newLevel = $item->getLevel();
		//升级成功
		if ($newLevel > $level)
		{
			if (!$bag->isItemExist($itemId))
			{
				$user->modifyBattleData();
			}
		}
	
		//无论成功或失败，都删除使用掉的物品
		foreach ($arrDecrease as $id => $num)
		{
			$bag->decreaseItem($id, $num);
		}
	
		$user->update();
		$bag->update();
			
		Logger::trace('ForgeLogic::upgradeTally End.');
		$ret = $item->itemInfo();
		$ret['silver'] = $sumCost;
	
		return $ret;
	}
	
	/**
	 * 进阶兵符
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function developTally($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::developTally Start.');
	
		$itemId = intval($itemId);
		foreach ($itemIds as $key => $value)
		{
			$itemIds[$key] = intval($value);
		}
	
		if (EnSwitch::isSwitchOpen(SwitchDef::TALLY) == false)
		{
			throw new FakeException('user:%d does not open the tally', $uid);
		}
	
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TALLY) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//批量拉物品
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		unset($arrItem[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TALLY)
		{
			throw new FakeException('itemId:%d is not a tally!', $itemId);
		}
	
		if (!$item->canDevelop())
		{
			throw new FakeException('itemId:%d can not develop!', $itemId);
		}
		
		//战魂精炼消耗
		$develop = $item->getDevelop();
		$developCost = $item->getDevelopCost($develop);
		$item->setDevelop($develop + 1);
		RewardUtil::delMaterial($uid, $developCost, null, 1, $itemIds);
		
		//在武将身上要刷新战斗力
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->isItemExist($itemId))
		{
			EnUser::getUserObj($uid)->modifyBattleData();
		}
			
		Logger::trace('ForgeLogic::developTally End.');
	
		return $item->itemInfo();
	}
	
	/**
	 * 精炼兵符
	 *
	 * @param int $itemId			物品id
	 * @param int $itemIds			消耗的物品id组
	 * @return array $itemInfo 		物品信息
	 */
	public static function evolveTally($uid, $itemId, $itemIds)
	{
		Logger::trace('ForgeLogic::evolveTally Start.');
	
		$itemId = intval($itemId);
		foreach ($itemIds as $key => $value)
		{
			$itemIds[$key] = intval($value);
		}
	
		if (EnSwitch::isSwitchOpen(SwitchDef::TALLY) == false)
		{
			throw new FakeException('user:%d does not open the tally', $uid);
		}
	
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TALLY) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		//批量拉物品
		$arrItemId = $itemIds;
		$arrItemId[] = $itemId;
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$item = $arrItem[$itemId];
		unset($arrItem[$itemId]);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TALLY)
		{
			throw new FakeException('itemId:%d is not a tally!', $itemId);
		}
	
		if (!$item->canEvolve())
		{
			throw new FakeException('itemId:%d can not evolve!', $itemId);
		}
	
		//战魂精炼消耗
		$evolve = $item->getEvolve();
		$evolveCost = $item->getEvolveCost($evolve);
		$item->setEvolve($evolve + 1);
		RewardUtil::delMaterial($uid, $evolveCost, null, 1, $itemIds);
	
		//在武将身上要刷新战斗力
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->isItemExist($itemId))
		{
			EnUser::getUserObj($uid)->modifyBattleData();
		}
			
		Logger::trace('ForgeLogic::evolveTally End.');
	
		return $item->itemInfo();
	}
	
	/**
	 * 随机洗练
	 * 
	 * @param int $uid				用户id
	 * @param int $itemId			物品id
	 * @param int $special			是否金币洗练
	 * @throws FakeException
	 * @return array mixed
	 */
	public static function randRefresh($uid, $itemId, $special)
	{
		Logger::trace('ForgeLogic::randRefresh Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备
		if ( $item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}	

		//减少金币和belly
		$user = EnUser::getUserObj($uid);
		if ( $special == FALSE )
		{
			//得到随机洗练花费银币
			$cost = $item->getRandRefreshCost();
			if ( $user->subSilver($cost) == FALSE )
			{
				throw new FakeException('no enough silver!');
			}
		}
		else
		{
			if (!$user->subGold(ForgeConfig::ARM_RAND_REFRESH_GOLD, StatisticsDef::ST_FUNCKEY_FORGE_RAND_REFRESH))
			{
				throw new FakeException('no enough gold!');
			}
		}
		
		//随机洗练物品
		$refreshInfo = $item->randRefresh();
		
		//更新用户信息
		$user->update();
		
		//更新物品信息
		ItemManager::getInstance()->update();
		
		
		Logger::trace('ForgeLogic::randRefresh End.');
		
		return array (
				'ret' => TRUE,
				'potence' => $refreshInfo,
		);	
	}
	
	/**
	 * 随机洗练替换
	 * 
	 * @param int $uid				用户id
	 * @param int $itemId			物品id
	 * @throws FakeException
	 * @return boolean
	 */
	public static function randRefreshAffirm($uid, $itemId)
	{
		Logger::trace('ForgeLogic::randRefresh Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备
		if ( $item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
		
		if ( $item->randRefreshAffirm() == TRUE )
		{
			ItemManager::getInstance()->update();
				
			//物品如果不在背包里则战斗优化
			$bag = BagManager::getInstance()->getBag();
			if ( !$bag->isItemExist($itemId))
			{
				$user = EnUser::getUserObj($uid);
				$user->modifyBattleData();
			}	
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * 固定洗练
	 *
	 * @param int $uid				用户id
	 * @param int $itemId			物品id
	 * @param int $type				洗练方式
	 * @param int $num				洗练次数
	 * @throws FakeException
	 * @return array mixed
	 */
	public static function fixedRefresh($uid, $itemId, $type, $num)
	{
		Logger::trace('ForgeLogic::fixedRefresh Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ARMREFRESH) == false)
		{
			throw new FakeException('user:%d does not open the arm refresh', $uid);
		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备
		if ( $item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
		
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		if (!in_array($type, btstore_get()->VIP[$vip]['armRefreshLv']->toArray()))
		{
			throw new FakeException('user vip:%d is not enough to use type:%d refresh', $vip, $type);
		}
		
		$bag = BagManager::getInstance()->getBag($uid);
		$cost = $item->getFixedRefreshCost($type);
		//物品
		if (!empty($cost[0])) 
		{
			foreach ($cost[0] as $itemTplId => $itemNum)
			{
				$cost[0][$itemTplId] = $itemNum * $num;
			}
			if ($bag->deleteItemsByTemplateID($cost[0]) == false) 
			{
				throw new FakeException('delete Items failed, items:%s!', $cost[0]);
			}
		}
		//银币
		if (!empty($cost[1])) 
		{
			if ($user->subSilver($cost[1] * $num) == false) 
			{
				throw new FakeException('no enough silver:%d!', $cost[1] * $num);
			}
		}
		//金币
		if (!empty($cost[2]))
		{
			if ($user->subGold($cost[2] * $num, StatisticsDef::ST_FUNCKEY_FORGE_FIXED_REFRESH) == false)
			{
				throw new FakeException('no enough gold:%d!', $cost[2] * $num);
			}
		}
		
		//固定洗练信息
		$refreshInfo = $item->fixedRefresh($type, $num);
		
		//更新用户信息
		$user->update();
		$bag->update();
		
		//加入每日任务
		EnActive::addTask(ActiveDef::ARMRFH, $num);
		EnAchieve::updateArmRefreshNum($uid, $num);
		
		Logger::trace('ForgeLogic::fixedRefresh End.');
		
		return array (
				'ret' => true,
				'potence' => $refreshInfo,
		);	
	}
	
	/**
	 * 固定洗练替换
	 *
	 * @param int $uid				用户id
	 * @param int $itemId			物品id
	 * @throws FakeException
	 * @return boolean
	 */
	public static function fixedRefreshAffirm($uid, $itemId)
	{
		Logger::trace('ForgeLogic::fixedRefresh Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
		if (EnSwitch::isSwitchOpen(SwitchDef::ARMREFRESH) == false)
		{
			throw new FakeException('user:%d does not open the arm refresh', $uid);
		}
	
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($itemId) == FALSE )
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备
		if ( $item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
	
		if ( $item->fixedRefreshAffirm() == TRUE )
		{
			$bag = BagManager::getInstance()->getBag();
		
			//物品如果不在背包里则战斗优化
			if ( !$bag->isItemExist($itemId))
			{
				$user = EnUser::getUserObj($uid);
				$user->modifyBattleData();
			}
			//实际是为了使用ItemManager的update
			$bag->update();
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
	
	/**
	 * 获得潜能转移的信息
	 * 
	 * @param int $uid
	 * @return array $info
	 */
	public static function getPotenceTransferInfo($uid)
	{
		Logger::trace('ForgeLogic::getPotenceTransferInfo Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
		$info = ForgeDao::selectForge($uid);
		
		if (empty($info)) 
		{
			$arrField = array(
					ForgeDef::FORGE_USER_ID => $uid,
					ForgeDef::FORGE_TRANSFER_NUM => 0,
					ForgeDef::FORGE_TRANSFER_TIME => 0
			);
			ForgeDao::insertForge($arrField);
			return $arrField;
		}
		if ($info[ForgeDef::FORGE_TRANSFER_TIME] < Util::getTime()) 
		{
			$info[ForgeDef::FORGE_TRANSFER_NUM] = 0;
			return $info;
		}
		
		Logger::trace('ForgeLogic::getPotenceTransferInfo End.');
		
		return $info;
	}
	
	/**
	 * 潜能转移
	 * 
	 * @param int $uid
	 * @param int $srcItemId
	 * @param int $desItemId
	 * @param int $type
	 * @throws FakeException
	 * @throws ConfigException
	 * @return array mixed
	 */
	public static function potenceTransfer($uid, $srcItemId, $desItemId, $type)
	{
		Logger::trace('ForgeLogic::potenceTransfer Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::FORGE) == false)
		{
			throw new FakeException('user:%d does not open the forge', $uid);
		}
		
		if ($srcItemId == $desItemId) 
		{
			throw new FakeException('srcItemId:%d is equal to desItemId:%d!', $srcItemId, $desItemId);
		}
		
		//检查装备是否属于该用户
		if ( EnUser::isCurUserOwnItem($srcItemId) == FALSE )
		{
			throw new FakeException('srcItemId:%d is not belong to user:%d!', $srcItemId, $uid);
		}
		if ( EnUser::isCurUserOwnItem($desItemId) == FALSE )
		{
			throw new FakeException('desItemId:%d is not belong to user:%d!', $desItemId, $uid);
		}
		
		$srcItem = ItemManager::getInstance()->getItem($srcItemId);
		//检查物品是否为装备
		if ( $srcItem === NULL || $srcItem->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('srcItemId:%d is not a arm!', $srcItemId);
		}
		$desItem = ItemManager::getInstance()->getItem($desItemId);
		if ( $desItem === NULL || $desItem->getItemType() != ItemDef::ITEM_TYPE_ARM )
		{
			throw new FakeException('desItemId:%d is not a arm!', $desItemId);
		}
		
		//源物品品质必须>=红色, 目标物品品质必须>=红色
		if ( $srcItem->getItemQuality() < ItemDef::ITEM_QUALITY_RED )
		{
			throw new FakeException('srcItemId:%d quality < red!', $srcItemId);
		}
		if ( $desItem->getItemQuality() < ItemDef::ITEM_QUALITY_RED )
		{
			throw new FakeException('desItemId:%d quality < red!', $desItemId);
		}
		
		//检查物品品质是否合法, 源物品品质<=目标物品品质
		if ( $srcItem->getItemQuality() > $desItem->getItemQuality() )
		{
			throw new FakeException('srcItemId:%d quality > desItemId:%d quality!', $srcItemId, $desItemId);
		}
		
		//检查源物品和目标物品是否可以随机洗练
		if ( $srcItem->canRandomRefresh() == FALSE || $desItem->canRandomRefresh() == FALSE )
		{
			throw new FakeException('srcItemId:%d or desItemId:%d can not rand refresh!', $srcItemId, $desItemId);
		}
			
		//检查type是否合法
		if (!in_array($type, ForgeDef::$VALID_POTENCE_TRANSFER_TYPES))
		{
			throw new FakeException('user:%d potence transfer type:%d is not valid!', $uid, $type);
		}
			
		$srcItemText = $srcItem->getItemText();
		$srcPotence = $srcItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE];
		if ( empty($srcPotence) )
		{
			throw new FakeException('srcItemId:%d potence is empty!', $srcItemId);
		}
		
		$user = EnUser::getUserObj($uid);
		$vip = $user->getVip();
		if ( !isset(btstore_get()->VIP[$vip]['freePotTranTimes']) )
		{
			throw new ConfigException('vip:%d freePotTranTimes info is empty!', $vip);
		}
		$transferReq = btstore_get()->VIP[$vip]['freePotTranTimes'];
		
		$values = array();
		switch ( $type )
		{
			case ForgeDef::POTENCE_TRANSFER_TYPE_GOLD:
				if ( $user->subGold($transferReq[1], StatisticsDef::ST_FUNCKEY_FORGE_POTENCE_TRANSFER) == false )
				{
					throw new FakeException('no enough gold:%d! user have:%d', $transferReq[1], $user->getGold());
				}
				$user->update();
				break;
			case ForgeDef::POTENCE_TRANSFER_TYPE_ITEM:
				$bag = BagManager::getInstance()->getBag();
				if ( $bag->deleteItembyTemplateID($transferReq[2], 1) == false )
				{
					throw new FakeException('no enough item:%d!', $transferReq[2]);
				}
				$bag->update();
				break;
			case ForgeDef::POTENCE_TRANSFER_TYPE_FREE:
				$info = self::getPotenceTransferInfo($uid);
				if ( $info[ForgeDef::FORGE_TRANSFER_NUM] >= $transferReq[0] )
				{
					throw new FakeException('over max free transfer num!');
				}
				$info[ForgeDef::FORGE_TRANSFER_NUM] += 1;
				$info[forgedef::FORGE_TRANSFER_TIME] = self::refreshTransferTime($info[forgedef::FORGE_TRANSFER_TIME]);
				ForgeDao::updateForge($uid, $info);
				break;
		}
		
		$srcPotenceId = $srcItem->getRandPotenceId();
		$desPotenceId = $desItem->getRandPotenceId();	
		$desPotence = Potence::transferPotence($srcPotenceId, $desPotenceId, $srcPotence, 
				ForgeDef::$VALID_FIXED_REFRESH_TYPES);
		
		//更新源物品和目标物品
		$srcItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] = array();
		unset($srcItemText[ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE]);
		unset($srcItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]);
		$srcItem->setItemText($srcItemText);
		
		$desItemText = $desItem->getItemText();
		$desItemText[ArmDef::ITEM_ATTR_NAME_ARM_POTENCE] = $desPotence;
		unset($desItemText[ArmDef::ITEM_ATTR_NAME_ARM_RAND_POTENCE]);
		unset($desItemText[ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE]);
		$desItem->setItemText($desItemText);
		
		ItemManager::getInstance()->update();
		
		//物品如果不在背包里则战斗优化
		$bag = BagManager::getInstance()->getBag();
		if ( !$bag->isItemExist($srcItemId) ||  !$bag->isItemExist($desItemId))
		{
			$user->modifyBattleData();
		}
		
		Logger::trace('ForgeLogic::potenceTransfer End.');
		
		return array(
				'ret' => 'ok',
				'items' => array (
						$srcItemId => $srcItem->itemInfo(),
						$desItemId => $desItem->itemInfo(),
				),
		);
		
	}
	
	/**
	 *
	 * 刷新潜能转移时间
	 *
	 * @param array $time
	 * @return int $time					
	 */
	public static function refreshTransferTime($time)
	{
		Logger::trace('ForgeLogic::refreshTransferTime Start.');
		
		if ( $time == 0 )
		{
			$date = date("Y-m-d", Util::getTime());
			$weekday = date("N", Util::getTime());
			$time = strtotime($date) + (ForgeDef::WEEKEND - $weekday + 1) * ForgeDef::DAYTIME;
		}
		for ($i = 0; $i < ForgeDef::MAXLOOP; $i++)
		{
			if ( $time > Util::getTime() )
			{
				return $time;
			}
			else
			{
				$time += ForgeDef::WEEKTIME;
			}
		}
		Logger::fatal('refresh potence transfer time failed! extend max execute time!');
	}
	
	public static function compose($uid, $method, $itemId)
	{
		Logger::trace('ForgeLogic::compose Start.');
		
		if (EnSwitch::isSwitchOpen(SwitchDef::DRAGON) == false)
		{
			throw new FakeException('user:%d does not open the forge!', $uid);
		}
		
		//检查该方法是否存在
		if (empty(btstore_get()->FOUNDRY[$method])) 
		{
			throw new FakeException('method:%d is not exist!', $method);
		}
		
		//检查该物品是否为装备
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM)
		{
			throw new FakeException('itemId:%d is not a arm!', $itemId);
		}
		
		//检查该物品的模板id是否正确的
		$conf = btstore_get()->FOUNDRY[$method];
		$base = $conf[ForgeDef::FOUNDRY_BASE]->toArray();
		$itemTplId = $item->getItemTemplateID();
		if (!in_array($itemTplId, $base))
		{
			throw new FakeException('itemId:%d template id:%d is not in %s!', $itemId, $itemTplId, $base);
		}
		$index = array_search($itemTplId, $base);
		
		//检查原装备的品质
		$baseQuality = $conf[ForgeDef::BASE_QUALITY];
		if (!empty($baseQuality) && $item->getItemQuality() != $baseQuality) 
		{
			throw new FakeException('itemId:%d quality:%d is not %d!', $itemId, $item->getItemQuality(), $baseQuality);
		}
		
		//检查该装备是否在背包中
		$bag = BagManager::getInstance()->getBag($uid);
		if ($bag->isItemExist($itemId) == false)
		{
			throw new FakeException('itemId:%d is not in bag!', $itemId);
		}
	
		//检查材料是否足够
		if ($bag->deleteItemsByTemplateID($conf[ForgeDef::FOUNDRY_ITEM][$index]) == false)
		{
			throw new FakeException('no enough items:%s', $conf[ForgeDef::FOUNDRY_ITEM][$index]);
		}
		
		//检查花费是否足够
		$user = EnUser::getUserObj($uid);
		foreach ($conf[ForgeDef::FOUNDRY_COST][$index] as $key => $value)
		{
			switch ($key)
			{
				case 1:
					if ($user->subJewel($value) == false)
					{
						throw new FakeException('no enough jewel for:%d', $value);
					}
					break;
				case 2:
					if ($user->subGold($value, StatisticsDef::ST_FUNCKEY_FORGE_COMPOSE_COST) == false) 
					{
						throw new FakeException('no enough gold for:%d', $value);
					}
					break;
				case 3:
					if ($user->subSilver($value) == false)
					{
						throw new FakeException('no enough silver for:%d', $value);
					}
					break;
				default:
					throw new ConfigException('invalid cost type:%d', $key);
			}
		}
		
		$user->addSilver($item->getReinforceCost());
		$arrItemId = ItemManager::getInstance()->addItem($conf[ForgeDef::FOUNDRY_FORM]);
		$newItemId = $arrItemId[0];
		$newItem = ItemManager::getInstance()->getItem($newItemId);
		$newItem->setPotence($item->getPotence());
		//旧物品有进阶等级才继承
		if ($item->getDevelop() > ArmDef::ARM_DEVELOP_DEFAULT) 
		{
			$newItem->setDevelop($item->getDevelop());
		}
		$fixPotence = $item->getFixedPotence();
		if (!empty($fixPotence))
		{
			$newItem->setFixedPotence($fixPotence);
		}
		
		//检查新装备的品质
		$formQuality = $conf[ForgeDef::FORM_QUALITY];
		if (!empty($formQuality) && $newItem->getItemQuality() != $formQuality)
		{
			throw new FakeException('itemId:%d quality:%d is not %d!', $newItemId, $newItem->getItemQuality(), $formQuality);
		}
		
		if ($bag->deleteItem($itemId) == false) 
		{
			throw new InterException('itemId:%d is not exist!', $itemId);
		}
		if ($bag->addItem($newItemId, true) == false) 
		{
			throw new InterException('tmp bag is full!');
		}
		
		$bag->update();
		$user->update();
		
		Logger::trace('ForgeLogic::compose End.');
		
		return 'ok';
	}
	
	public static function composeRune($uid, $method, $arrItemId)
	{
		//检查该方法是否存在
		if (empty(btstore_get()->RUNE_COMPOSE[$method]))
		{
			throw new FakeException('method:%d is not exist!', $method);
		}
		
		//检查消耗的物品是否是符印和道具
		$bag = BagManager::getInstance()->getBag($uid);
		$items = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($items as $itemId => $item)
		{
			if ($item === NULL 
			|| !in_array($item->getItemType(), array(ItemDef::ITEM_TYPE_RUNE, ItemDef::ITEM_TYPE_NORMAL)))
			{
				throw new FakeException('itemId:%d is not a rune or a normal!', $itemId);
			}
			if (!$bag->isItemExist($itemId)) 
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
		}
		
		//扣东西
		$cost = btstore_get()->RUNE_COMPOSE[$method]['cost'];
		RewardUtil::delMaterial($uid, $cost, null, 1, $arrItemId, false);
		
		//加东西
		$form = btstore_get()->RUNE_COMPOSE[$method]['form'];
		RewardUtil::reward3DArr($uid, $form, 0);
		
		//更新
		$bag->update();
		
		return 'ok';
	}
	
	public static function inlay($uid, $treasItemId, $runeItemId, $index, $resItemId)
	{
		Logger::trace('ForgeLogic::inlay Start.');
		
		//检查宝物是否属于该用户
		if (EnUser::isCurUserOwnItem($treasItemId, ItemDef::ITEM_TYPE_TREASURE) == FALSE)
		{
			throw new FakeException('treasItemId:%d is not belong to user:%d!', $treasItemId, $uid);
		}
		
		$arrItem = ItemManager::getInstance()->getItems(array($treasItemId, $runeItemId, $resItemId));
		$treasItem = $arrItem[$treasItemId];
		if ($treasItem === NULL || $treasItem->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('treasItemId:%d is not a treasure!', $treasItemId);
		}
		$runeItem = $arrItem[$runeItemId];
		if ($runeItem === NULL || $runeItem->getItemType() != ItemDef::ITEM_TYPE_RUNE)
		{
			throw new FakeException('runeItemId:%d is not a rune!', $runeItemId);
		}
		//如果前端提供原始宝物，说明符印不在背包里，就把符印从原来宝物上卸下来
		$bag = BagManager::getInstance()->getBag($uid);
		$resItem = isset($arrItem[$resItemId]) ? $arrItem[$resItemId] : NULL;
		if (!empty($resItemId))
		{
			if ($resItem === NULL || $resItem->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
			{
				throw new FakeException('resItemId:%d is not a treasure!', $resItemId);
			}
			if ($bag->isItemExist($runeItemId)) 
			{
				throw new FakeException('runeItemId:%d is in bag!', $runeItemId);
			}
			$inlay = $resItem->getInlay();
			if (!in_array($runeItemId, $inlay)) 
			{
				throw new FakeException('runeItemId:%d is not inlay resItemId:%d!', $runeItemId, $resItemId);
			}
			$resItem->delInlayItem($runeItemId);
		}
		else 
		{
			//从背包里面删掉符印
			if (!$bag->removeItem($runeItemId))
			{
				throw new FakeException('runeItemId:%d can not remove from bag!', $runeItemId);
			}
		}
		//宝物的类型和符印的类型一致吗
		if ($treasItem->getType() != $runeItem->getType())
		{
			throw new FakeException('treasItemId:%d type is not same with runeItemId:%d type!', $treasItemId, $runeItemId);
		}
		//宝物可以镶嵌吗
		if (!$treasItem->canInlay()) 
		{
			throw new FakeException('treasItemId:%d can not inlay!', $treasItemId);
		}
		//宝物对应的孔可以镶嵌吗
		if (!$treasItem->isInlayOpen($uid, $index)) 
		{
			throw new FakeException('treasItemId:%d inlay index:%d is not open!', $treasItemId, $index);
		}
		
		//宝物这个位置是否有另外一个符印呢,有的话把这个符印放回背包
		$inlay = $treasItem->getInlay();
		$arrItem = ItemManager::getInstance()->getItems($inlay);
		if (isset($inlay[$index])) 
		{
			$otherRuneItemId = $inlay[$index];
			unset($arrItem[$otherRuneItemId]);
			if(!$bag->addItem($otherRuneItemId))
			{
				throw new InterException('rune bag is full!');
			}
		}
		//这个符印是否和宝物上面的符印重复呢,模板和属性
		$itemTplId = $runeItem->getItemTemplateID();
		$itemFeature = $runeItem->getFeature();
		foreach ($arrItem as $item)
		{
			if ($item->getItemTemplateID() == $itemTplId
			|| $item->getFeature() == $itemFeature) 
			{
				throw new FakeException('treasItemId:%d inlay has runeItemId:%d itemTplId:%d or feature:%d!', $treasItemId, $runeItemId, $itemTplId, $itemFeature);
			}
		}
		//终于可以把符印往宝物上面镶嵌了
		$treasItem->addInlay($index, $runeItemId);
		
		$user = EnUser::getUserObj($uid);
		if (!$bag->isItemExist($treasItemId) 
		|| !empty($resItemId) && !$bag->isItemExist($resItemId)) 
		{
			$user->modifyBattleData();
		}
	
		$user->update();
		$bag->update();
		
		Logger::trace('ForgeLogic::inlay End.');
		
		return 'ok';
	}
	
	public static function outlay($uid, $itemId, $index)
	{
		Logger::trace('ForgeLogic::outlay Start.');
	
		//检查宝物是否属于该用户
		if (EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_TREASURE) == FALSE)
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
	
		$item = ItemManager::getInstance()->getItem($itemId);
		if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('itemId:%d is not a treasure!', $itemId);
		}
		
		//宝物这个位置是否有符印呢
		$inlay = $item->getInlay();
		if (!isset($inlay[$index])) 
		{
			throw new FakeException('itemId:%d inlay has no index:%d!', $itemId, $index);
		}
		$item->delInlay($index);
		
		//放回背包
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->addItem($inlay[$index])) 
		{
			throw new FakeException('rune bag is full!');
		}
	
		$user = EnUser::getUserObj($uid);
		if (!$bag->isItemExist($itemId))
		{
			$user->modifyBattleData();
		}
	
		$user->update();
		$bag->update();
	
		Logger::trace('ForgeLogic::outlay End.');
	
		return 'ok';
	}
	
	public static function lock($uid, $itemId)
	{
		Logger::trace('ForgeLogic::lock Start.');
		
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备或锦囊
		if ($item === NULL 
		|| $item->getItemType() != ItemDef::ITEM_TYPE_ARM 
		&& $item->getItemType() != ItemDef::ITEM_TYPE_POCKET )
		{
			throw new FakeException('itemId:%d is not a arm or pocket!', $itemId);
		}
		
		//检查物品是否属于该用户
		if (EnUser::isCurUserOwnItem($itemId, $item->getItemType()) == FALSE)
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		//检查装备是否紫色
		if ($item->getItemType() == ItemDef::ITEM_TYPE_ARM 
		&& $item->getItemQuality() != ItemDef::ITEM_QUALITY_PURPLE) 
		{
			throw new FakeException('itemId:%d is not purple!', $itemId);
		}
		//检查锦囊是否经验锦囊
		if ($item->getItemType() == ItemDef::ITEM_TYPE_POCKET
		&& $item->isExp())
		{
			throw new FakeException('itemId:%d is exp!', $itemId);
		}
		//是否锁定
		if ($item->isLock()) 
		{
			throw new FakeException('itemId:%d is lock!', $itemId);
		}
		$item->lock();
		
		ItemManager::getInstance()->update();
		
		Logger::trace('ForgeLogic::lock End.');
		
		return 'ok';
	}
	
	public static function unlock($uid, $itemId)
	{
		Logger::trace('ForgeLogic::unlock Start.');
	
		$item = ItemManager::getInstance()->getItem($itemId);
		//检查物品是否为装备或锦囊
		if ($item === NULL 
		|| $item->getItemType() != ItemDef::ITEM_TYPE_ARM
		&& $item->getItemType() != ItemDef::ITEM_TYPE_POCKET )
		{
			throw new FakeException('itemId:%d is not a arm or pocket!', $itemId);
		}
		
		//检查物品是否属于该用户
		if (EnUser::isCurUserOwnItem($itemId, $item->getItemType()) == FALSE)
		{
			throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
		}
		
		//检查装备是否紫色
		if ($item->getItemType() == ItemDef::ITEM_TYPE_ARM 
		&& $item->getItemQuality() != ItemDef::ITEM_QUALITY_PURPLE)
		{
			throw new FakeException('itemId:%d is not purple!', $itemId);
		}
		//检查锦囊是否经验锦囊
		if ($item->getItemType() == ItemDef::ITEM_TYPE_POCKET
		&& $item->isExp())
		{
			throw new FakeException('itemId:%d is exp!', $itemId);
		}
		//是否锁定
		if ($item->isLock() == 0)
		{
			throw new FakeException('itemId:%d is unlock!', $itemId);
		}
		$item->unlock();
	
		ItemManager::getInstance()->update();
	
		Logger::trace('ForgeLogic::unlock End.');
	
		return 'ok';
	}
	
	public static function transExpend($expend)
	{
		$ret = array();
		foreach ($expend as $value)
		{
			switch ($value[0])
			{
				case RewardConfType::SILVER:
					if (!isset($ret['silver'])) 
					{
						$ret['silver'] = 0;
					}
					$ret['silver'] += $value[2];
					break;
				case RewardConfType::ITEM_MULTI:
					if (!isset($ret['item'][$value[1]])) 
					{
						$ret['item'][$value[1]] = 0;
					}
					$ret['item'][$value[1]] += $value[2];
					break;
			}
		}
		return $ret;
	}
	
	public static function transferTreasure($uid, $itemId, $itemTplId)
	{
		//检查用户等级
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TREASURE_TRANSFER_NEED_LEVEL];
		if ($userLevel < $needLevel)
		{
			throw new FakeException("Can not transfer treasure, user level:%d, need level:%d", $userLevel, $needLevel);
		}
		 
		//检查物品模板id
		if (ItemManager::getInstance()->getItemType($itemTplId) != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('itemTplId:%d is not a treasure', $itemTplId);
		}
		 
		//检查物品是否宝物
		$item = ItemManager::getInstance()->getItem($itemId);
		if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
		{
			throw new FakeException('itemId:%d is not a treasure', $itemId);
		}
		 
		//检查宝物转换规则
		if ($item->getItemTemplateID() == $itemTplId)
		{
			throw new FakeException('itemId:%d can not transfer to self', $itemId);
		}
		 
		//检查是否同属性宝物（配置保证）
		$index = -1;
		$arrTrans = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TREASURE_TRANSFER_ARR]->toArray();
		foreach ($arrTrans as $key => $value)
		{
			if (in_array($item->getItemTemplateID(), $value))
			{
				$index = $key;
				break;
			}
		}
		if (!isset($arrTrans[$index]) || !in_array($itemTplId, $arrTrans[$index]))
		{
			throw new FakeException('itemId:%d can not transfer to itemTplId:%d', $itemId, $itemTplId);
		}
		 
		//检查转换花费
		$cost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TREASURE_TRANSFER_COST];
		if (!$user->subGold($cost, StatisticsDef::ST_FUNCKEY_FORGE_TRANSFER_TREASURE))
		{
			throw new FakeException('uid:%d has no enough gold:%d for transfer itemId:%d', $uid, $cost, $itemId);
		}
		 
		//检查物品是否在背包中
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->deleteItem($itemId))
		{
			throw new FakeException('itemId:%d is not in bag', $itemId);
		}
		 
		//物品继承属性
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId);
		$newItemId = $arrItemId[0];
		$newItem = ItemManager::getInstance()->getItem($newItemId);
		$newItem->setLevel($item->getLevel());
		$newItem->setExp($item->getExp());
		$newItem->setEvolve($item->getEvolve());
		$newItem->setDevelop($item->getDevelop());
		$newItem->setInlay($item->getInlay());
		 
		//加到背包
		if (!$bag->addItem($newItemId))
		{
			throw new FakeException('bag is full!');
		}
		 
		//更新
		$user->update();
		$bag->update();
		 
		return $newItemId;
	}
	
	public static function transferTally($uid, $itemId, $itemTplId)
	{
		//检查用户等级
		$user = EnUser::getUserObj($uid);
		$userLevel = $user->getLevel();
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TALLY_TRANSFER_NEED_LEVEL];
		if ($userLevel < $needLevel)
		{
			throw new FakeException("Can not transfer tally, user level:%d, need level:%d", $userLevel, $needLevel);
		}
			
		//检查物品模板id
		if (ItemManager::getInstance()->getItemType($itemTplId) != ItemDef::ITEM_TYPE_TALLY)
		{
			throw new FakeException('itemTplId:%d is not a tally', $itemTplId);
		}
			
		//检查物品是否兵符
		$item = ItemManager::getInstance()->getItem($itemId);
		if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TALLY)
		{
			throw new FakeException('itemId:%d is not a tally', $itemId);
		}
			
		//检查宝物转换规则
		if ($item->getItemTemplateID() == $itemTplId)
		{
			throw new FakeException('itemId:%d can not transfer to self', $itemId);
		}
			
		//只能转换配置里面的兵符
		$arrTrans = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TALLY_TRANSFER_ARR]->toArray();
		if (!in_array($itemTplId, $arrTrans) || !in_array($item->getItemTemplateID(), $arrTrans)) 
		{
			throw new FakeException('itemId:%d can not transfer to itemTplId:%d', $itemId, $itemTplId);
		}
			
		//检查转换花费
		$cost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TALLY_TRANSFER_COST][$item->getDevelop()];
		if (!$user->subGold($cost, StatisticsDef::ST_FUNCKEY_FORGE_TRANSFER_TALLY))
		{
			throw new FakeException('uid:%d has no enough gold:%d for transfer itemId:%d', $uid, $cost, $itemId);
		}
			
		//检查物品是否在背包中
		$bag = BagManager::getInstance()->getBag($uid);
		if (!$bag->deleteItem($itemId))
		{
			throw new FakeException('itemId:%d is not in bag', $itemId);
		}
			
		//物品继承属性
		$arrItemId = ItemManager::getInstance()->addItem($itemTplId);
		$newItemId = $arrItemId[0];
		$newItem = ItemManager::getInstance()->getItem($newItemId);
		$newItem->setLevel($item->getLevel());
		$newItem->setExp($item->getExp());
		$newItem->setEvolve($item->getEvolve());
		$newItem->setDevelop($item->getDevelop());
			
		//加到背包
		if (!$bag->addItem($newItemId))
		{
			throw new FakeException('bag is full!');
		}
			
		//更新
		$user->update();
		$bag->update();
			
		return $newItemId;
	}
	
	public static function activateArmAchieve($uid)
	{
		$arrItemId = array();
		$arrHeroEquip = array();
		$heroManager = EnUser::getUserObj($uid)->getHeroManager();
		$squad = $heroManager->getAllHeroObjInSquad();
		foreach ($squad as $hero)
		{
			$arrEquip = $hero->getEquipByType(HeroDef::EQUIP_ARMING);
			foreach ($arrEquip as $pos => $itemId)
			{
				if (empty($itemId))
				{
					unset($arrEquip[$pos]);
				}
			}
			$arrItemId = array_merge($arrItemId, $arrEquip);
			$arrHeroEquip[] = $arrEquip;
		}
		ItemManager::getInstance()->getItems($arrItemId);
		$arrMinArmForce = array();
		foreach ($arrHeroEquip as $arrEquip)
		{
			if (count($arrEquip) != ArmDef::ARM_TYPE_NUM)
			{
				continue;
			}
			$arrItem = ItemManager::getInstance()->getItems($arrEquip);
			$minArmForce = -1;
			foreach ($arrItem as $item)
			{
				$minArmForce = $minArmForce == -1 ? $item->getLevel() : min($minArmForce, $item->getLevel());
			}
			$arrMinArmForce[] = $minArmForce;
		}
		rsort($arrMinArmForce);
		for ($i = 1; $i <= count($arrMinArmForce); $i++)
		{
			$minArmForce = min(array_slice($arrMinArmForce, 0, $i));
			EnNewServerActivity::updateFriendEquipStrong($uid, $i, $minArmForce);
		}
	}
	
	public static function activateTreasAchieve($uid)
	{
		$arrItemId = array();
		$arrHeroEquip = array();
		$heroManager = EnUser::getUserObj($uid)->getHeroManager();
		$squad = $heroManager->getAllHeroObjInSquad();
		foreach ($squad as $hero)
		{
			$arrEquip = $hero->getEquipByType(HeroDef::EQUIP_TREASURE);
			foreach ($arrEquip as $pos => $itemId)
			{
				if (empty($itemId))
				{
					unset($arrEquip[$pos]);
				}
			}
			$arrItemId = array_merge($arrItemId, $arrEquip);
			$arrHeroEquip[] = $arrEquip;
		}
		ItemManager::getInstance()->getItems($arrItemId);
		$arrMinTreasForce = array();
		$arrMinTreasEvolve = array();
		foreach ($arrHeroEquip as $arrEquip)
		{
			if (count($arrEquip) != TreasureDef::TREASURE_TYPE_NUM)
			{
				continue;
			}
			$arrItem = ItemManager::getInstance()->getItems($arrEquip);
			$minTreasForce = -1;
			$minTreasEvolve = -1;
			foreach ($arrItem as $item)
			{
				$minTreasForce = $minTreasForce == -1 ? $item->getLevel() : min($minTreasForce, $item->getLevel());
				$minTreasEvolve = $minTreasEvolve == -1 ? $item->getEvolve() : min($minTreasEvolve, $item->getEvolve());
			}
			$arrMinTreasForce[] = $minTreasForce;
			$arrMinTreasEvolve[] = $minTreasEvolve;
		}
		rsort($arrMinTreasForce);
		rsort($arrMinTreasEvolve);
		if (count($arrMinTreasForce) == 6) 
		{
			$minTreasForce = min($arrMinTreasForce);
			EnNewServerActivity::updateTwelveTreasureOnFormation($uid, $minTreasForce);
			$minTreasEvolve = min($arrMinTreasEvolve);
			EnNewServerActivity::updateTwelveMagicTreasure($uid, $minTreasEvolve);
		}
	}
	
	public static function activateFightSoulAchieve($uid)
	{
		$arrItemId = BagManager::getInstance()->getBag($uid)->getItemIdsByItemType(ItemDef::ITEM_TYPE_FIGHTSOUL);
		$heroManager = EnUser::getUserObj($uid)->getHeroManager();
		$squad = $heroManager->getAllHeroObjInSquad();
		foreach ($squad as $hero)
		{
			$arrItemId = array_merge($arrItemId, $hero->getEquipByType(HeroDef::EQUIP_FIGHTSOUL));
		}
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$arrMinFightSoulLevel = array();
		foreach ($arrItem as $item)
		{
			if ($item->getItemQuality() == ItemDef::ITEM_QUALITY_PURPLE) 
			{
				$arrMinFightSoulLevel[] = $item->getLevel();
			}
		}
		rsort($arrMinFightSoulLevel);
		for ($i = 1; $i <= count($arrMinFightSoulLevel); $i++)
		{
			$minFightSoulLevel = min(array_slice($arrMinFightSoulLevel, 0, $i));
			EnNewServerActivity::updateStrongPurpleFightsoul($uid, $i, $minFightSoulLevel);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */