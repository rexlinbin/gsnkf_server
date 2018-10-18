<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DelUserItem.php 176159 2015-06-02 07:11:03Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/DelUserItem.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-06-02 07:11:03 +0000 (Tue, 02 Jun 2015) $
 * @version $Revision: 176159 $
 * @brief 
 *  
 **/
class DelUserItem extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$usage = "usage::btscript game001 DelUserItem.php check|fix filename\n";
		
		$startTime = strtotime('2014-05-28 07:00:00');
		$endTime = strtotime('2014-05-28 10:12:00');
		
		$dropId = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GOLDBOX_DROP];
		$dropInfo = Drop::getDropInfo($dropId);
		//只有2种类型，物品和宝物碎片
		if (count($dropInfo) != 2)
		{
			throw new ConfigException('drop id:%d is wrong with type sum', $dropId);
		}
		if (!isset($dropInfo[DropDef::DROP_TYPE_ITEM]) 
		|| !isset($dropInfo[DropDef::DROP_TYPE_TREASFRAG])) 
		{
			throw new ConfigException('drop id:%d is wrong with item type', $dropId);
		}
		$dropItemTplIds = array_merge($dropInfo[DropDef::DROP_TYPE_ITEM], $dropInfo[DropDef::DROP_TYPE_TREASFRAG]);
		
		$fix = false;
		if(isset($arrOption[0]) &&  $arrOption[0] == 'fix')
		{
			$fix = true;
		}
		
		if(empty($arrOption[1]))
		{
			echo "No input file!\n";
			return;
		}
		$fileName = $arrOption[1];

		$file = fopen("$fileName", 'r');
		echo "read $fileName\n";

		while (!feof($file))
		{
			$line = fgets($file);
			if (empty($line))
			{
				break;
			}
			//uid num string	
			$info = explode(" ", $line);
			$uid = intval($info[0]);
			$num = intval($info[1]) + 1;
			$tmp = unserialize($info[2]);
			$itemTplIds = array();
			$sortAry = array();
			$allNum = 0;
			foreach($tmp as $key => $value)
			{
				if (!in_array($key, DropDef::$DROP_TYPE_TO_STRTYPE)) 
				{
					throw new FakeException('wrong drop type');
				}
				if ($key == DropDef::DROP_TYPE_STR_SILVER 
				|| $key == DropDef::DROP_TYPE_STR_SOUL) 
				{
					continue;
				}
				foreach ($value as $itemTplId => $itemNum)
				{
					$itemQuality = ItemManager::getInstance()->getItemQuality($itemTplId);
					if ($itemQuality == 5) 
					{
						$allNum += $itemNum;
					}
					//排除非特殊掉落的
					if (!in_array($itemTplId, $dropItemTplIds)) 
					{
						unset($value[$itemTplId]);
					}
					else 
					{
						if (!isset($itemTplIds[$itemTplId]))
						{
							$itemTplIds[$itemTplId] = 0;
						}
						$itemTplIds[$itemTplId] += $itemNum;
						$itemType = ItemManager::getInstance()->getItemType($itemTplId);
						for ($i = 0; $i < $itemNum; $i++)
						{
							$sortAry[$itemType][] = $itemTplId;
						}
					}
				}
			}
			if ($num > array_sum($itemTplIds))
			{
				Logger::fatal('uid:%d expected num:%d, truely num:%d.', $uid, $num, array_sum($itemTplIds));
			}
			else 
			{
				//保留用户自己获得的紫色物品,宝物碎片>装备碎片>宝物>装备
				$keepNum = array_sum($itemTplIds) - $num + 1;
				for ($i = 0; $i < $keepNum; $i++)
				{
					$itemTplId = 0;
					if (isset($sortAry[ItemDef::ITEM_TYPE_TREASFRAG][0]))
					{
						$itemTplId = $sortAry[ItemDef::ITEM_TYPE_TREASFRAG][0];
						array_shift($sortAry[ItemDef::ITEM_TYPE_TREASFRAG]);
					}
					elseif (isset($sortAry[ItemDef::ITEM_TYPE_FRAGMENT][0]))
					{
						$itemTplId = $sortAry[ItemDef::ITEM_TYPE_FRAGMENT][0];
						array_shift($sortAry[ItemDef::ITEM_TYPE_FRAGMENT]);
					}
					elseif (isset($sortAry[ItemDef::ITEM_TYPE_TREASURE][0]))
					{
						$itemTplId = $sortAry[ItemDef::ITEM_TYPE_TREASURE][0];
						array_shift($sortAry[ItemDef::ITEM_TYPE_TREASURE]);
					}
					elseif (isset($sortAry[ItemDef::ITEM_TYPE_ARM][0]))
					{
						$itemTplId = $sortAry[ItemDef::ITEM_TYPE_ARM][0];
						array_shift($sortAry[ItemDef::ITEM_TYPE_ARM]);
					}
					if (!empty($itemTplId)) 
					{
						$itemTplIds[$itemTplId]--;
					}
					else 
					{
						throw new InterException('can not find any correct item tpl id');
					}
				}
			}
			if ($fix) 
			{
				Util::kickOffUser($uid);
			}
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
			$user = EnUser::getUserObj($uid);
			$bag = BagManager::getInstance()->getBag($uid);
			$tpls = '';
			foreach ($itemTplIds as $itemTplId => $itemNum)
			{
				if (empty($itemNum)) 
				{
					continue;
				}
				$tpls .= $itemTplId.':'.$itemNum.',';
			}
			Logger::info('fix user:%d uname:%s vip:%d level:%d extraDropNum:%d allPurpleNum:%d delPurplelNum:%d tpls:%s', $uid, $user->getUname(), $user->getVip(), $user->getLevel(), $num, $allNum, array_sum($itemTplIds), $tpls);
			
			//先处理碎片类型
			foreach ($itemTplIds as $itemTplId => $itemNum)
			{
				if (empty($itemNum)) 
				{
					continue;
				}
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				//从背包中删除
				switch ($itemType)
				{
					case ItemDef::ITEM_TYPE_FRAGMENT:
						//装备碎片,直接删掉
						$delNum = $bag->getItemNumByTemplateID($itemTplId);
						if ($delNum > 0) 
						{
							$delNum = min($itemNum, $delNum);
							$bag->deleteItembyTemplateID($itemTplId, $delNum);
							$itemTplIds[$itemTplId] -= $delNum; 
						}
						//剩余数量足够合成装备
						$fragNum = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_FRAGMENT_NUM);
						if ($itemTplIds[$itemTplId] >= $fragNum) 
						{
							$multi = floor($itemTplIds[$itemTplId] / $fragNum);
							$acqInfo = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_USE_ACQ);
							$items = $acqInfo[ItemDef::ITEM_ATTR_NAME_USE_ACQ_ITEMS];
							foreach ($items as $tplId => $tplNum){}
							if (!isset($itemTplIds[$tplId])) 
							{
								$itemTplIds[$tplId] = 0;
							}
							$itemTplIds[$tplId] += $tplNum * $multi;
							$itemTplIds[$itemTplId] -= $multi * $fragNum;
						}
						break;
					/*case ItemDef::ITEM_TYPE_TREASFRAG:
						//宝物碎片,直接删掉
						$tid = substr($itemTplId, 0, 6);
						$ret = $fragseizeInst->getFragsByTid($tid);
						$delNum = $ret[$itemTplId];
						if ($delNum > 0) 
						{
							$delNum = min($itemNum, $delNum);
							$fragseizeInst->subFrags(array($itemTplId => $delNum));
							$itemTplIds[$itemTplId] -= $delNum;
						}
						break;*/
				}	
			}
			//再处理物品类型
			foreach ($itemTplIds as $itemTplId => $itemNum)
			{
				if (empty($itemNum))
				{
					continue;
				}
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				//从背包中删除
				switch ($itemType)
				{
					case ItemDef::ITEM_TYPE_ARM:
						$arrItemId = self::getItemIdsInBagByItemTplId($uid, $itemTplId);
						//装备,按等级排序,优先删除较小的
						foreach ($arrItemId as $itemId => $itemLevel)
						{
							self::armReborn(array($itemId));
							$bag->deleteItem($itemId);
							$itemTplIds[$itemTplId] --;
							if ($itemTplIds[$itemTplId] == 0)
							{
								break;
							}
						}
						break;
					case ItemDef::ITEM_TYPE_TREASURE:
						$arrItemId = self::getItemIdsInBagByItemTplId($uid, $itemTplId);
						//宝物,按等级排序,优先删除较小的
						foreach ($arrItemId as $itemId => $itemLevel)
						{
							self::treasureReborn(array($itemId));
							$bag->deleteItem($itemId);
							$itemTplIds[$itemTplId] --;
							if ($itemTplIds[$itemTplId] == 0)
							{
								break;
							}
						}
						break;
				}
			}
			Logger::info('after del from bag, item tpls:%s', $itemTplIds);
			$arrHero = $user->getHeroManager()->getAllHeroObjInSquad();
			foreach ($itemTplIds as $itemTplId => $itemNum)
			{
				if (empty($itemNum))
				{
					continue;
				}
				$itemType = ItemManager::getInstance()->getItemType($itemTplId);
				//从背包中删除
				switch ($itemType)
				{
					case ItemDef::ITEM_TYPE_ARM:
						$arrItemId = self::getItemIdsInHeroByItemTplId($itemTplId, $startTime);
						foreach ($arrItemId as $itemId => $hid)
						{
							$hero = $arrHero[$hid];
							$armItemIds = $hero->getEquipByType(HeroDef::EQUIP_ARMING);
							$armPos = array_search($itemId, $armItemIds);
							$hero->setArmingByPos(0, $armPos);
							$bag->addItem($itemId, true);
							self::armReborn(array($itemId));
							$bag->deleteItem($itemId);
							$itemTplIds[$itemTplId] --;
							if ($itemTplIds[$itemTplId] == 0)
							{
								break;
							}
						}
						break;
					case ItemDef::ITEM_TYPE_TREASURE:
						$arrItemId = self::getItemIdsInHeroByItemTplId($itemTplId, $startTime);
						foreach ($arrItemId as $itemId => $hid)
						{
							$hero = $arrHero[$hid];
							$treasItemIds = $hero->getEquipByType(HeroDef::EQUIP_TREASURE);
							$treasPos = array_search($itemId, $treasItemIds);
							$hero->setTreasureByPos(0, $treasPos);
							$bag->addItem($itemId, true);
							self::treasureReborn(array($itemId));
							$bag->deleteItem($itemId);
							$itemTplIds[$itemTplId] --;
							if ($itemTplIds[$itemTplId] == 0)
							{
								break;
							}
						}
						break;
				}
			}
			Logger::info('after del from hero, item tpls:%s', $itemTplIds);
			if ($fix)
			{
				$user->update();
				$bag->update();
				//$fragseizeInst->updateFrags();
			}
		}
		
		fclose($file);
		echo "ok\n";
	}
	
	public static function getItemIdsInBagByItemTplId($uid, $itemTplId)
	{
		$arrBagData = array();
		$select = array(BagDef::SQL_ITEM_ID, BagDef::SQL_GID);
		$where = array(BagDef::SQL_UID, '=', $uid);
		$returnData = BagDAO::selectBag($select, $where);
		
		foreach ($returnData as $value)
		{
			$gid = intval($value[BagDef::SQL_GID]);
			$itemId = intval($value[BagDef::SQL_ITEM_ID]);
			$bagName = Bag::getBagNameByGid($gid);
			$arrBagData[$bagName][$gid] = $itemId;
		}
		
		$itemType = ItemManager::getInstance()->getItemType($itemTplId);
		$bagName = ItemDef::$MAP_ITEM_TYPE_BAG_NAME[$itemType];
		$bag = $arrBagData[$bagName];
		$arrItemId = array_values($bag);
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$arrRet = array();
		foreach ($arrItem as $itemId => $item)
		{
			if ($item->getItemTemplateID() != $itemTplId) 
			{
				continue;
			}
			$arrRet[$itemId] = $item->getLevel();
		}
		asort($arrRet);
		return $arrRet;
	}
	
	public static function getItemIdsInHeroByItemTplId($itemTplId, $time)
	{
		$arrRet = array();
		$itemType = ItemManager::getInstance()->getItemType($itemTplId);
		if ($itemType == ItemDef::ITEM_TYPE_ARM) 
		{
			$equipType = HeroDef::EQUIP_ARMING;
		}
		if ($itemType == ItemDef::ITEM_TYPE_TREASURE) 
		{
			$equipType = HeroDef::EQUIP_TREASURE;
		}
		$user = EnUser::getUserObj();
		$arrHero = $user->getHeroManager()->getAllHeroObjInSquad();
		$arrItemId = array();
		foreach ($arrHero as $hero)
		{
			$itemIds = $hero->getEquipByType($equipType);
			$arrItem = ItemManager::getInstance()->getItems($itemIds);
			foreach ($arrItem as $itemId => $item)
			{
				if ($item->getItemTemplateID() == $itemTplId
				&& $item->getItemTime() >= $time)
				{
					$arrRet[$itemId] = $item->getLevel();
					$arrItemId[$itemId] = $hero->getHid();
				}
			}
		}
		asort($arrRet);
		foreach ($arrRet as $itemId => $itemLevel)
		{
			$arrRet[$itemId] = $arrItemId[$itemId];
		}
		return $arrRet;
	}
	
	public static function armReborn($arrItemId)
	{
		if (empty($arrItemId))
		{
			return array();
		}
		
		$silver = 0;
		$items = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM)
			{
				throw new FakeException('itemId:%d is not a arm!', $itemId);
			}
			if ($item->getItemQuality() < ItemDef::ITEM_QUALITY_PURPLE)
			{
				throw new FakeException('itemId:%d quality is too small to reborn', $itemId);
			}
			if ($bag->isItemExist($itemId) == false)
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			$silver += $item->getReinforceCost();
			$potenceResolve = $item->getPotenceResolve();
			if (!empty($potenceResolve))
			{
				$itemTplId = key($potenceResolve);
				$itemNum = current($potenceResolve);
				if (!isset($items[$itemTplId]))
				{
					$items[$itemTplId] = 0;
				}
				$items[$itemTplId] += $itemNum;
			}
			$item->reset();
		}
		$user = EnUser::getUserObj();
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			$bag->addItemsByTemplateID($items, true);
		}
	}
	
	public static function treasureReborn($arrItemId)
	{
		if (empty($arrItemId))
		{
			return array();
		}
	
		$silver = 0;
		$extra = array();
		$items = array();
		$arrResolveItemId = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
			{
				throw new FakeException('itemId:%d is not a treasure!', $itemId);
			}
			if ($item->getItemQuality() < ItemDef::ITEM_QUALITY_BLUE)
			{
				throw new FakeException('itemId:%d quality is too small to reborn', $itemId);
			}
			if ($bag->isItemExist($itemId) == false)
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			$silver += $item->getUpgradeExpendSum();
			$totalExp = $item->getExp();
			$resolveItemTplId = $item->getResolveItem();
			$resolveItemValue = ItemAttr::getItemAttr($resolveItemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_BASE);
			if ($totalExp >= $resolveItemValue)
			{
				if (!isset($extra[$resolveItemTplId]))
				{
					$extra[$resolveItemTplId] = 0;
				}
				$extra[$resolveItemTplId] ++;
				$resolveItemId = ItemManager::getInstance()->addItem($resolveItemTplId);
				$resolveItemId = $resolveItemId[0];
				$resolveItem = ItemManager::getInstance()->getItem($resolveItemId);
				$exp = $resolveItem->getExp();
				$level = $resolveItem->getLevel();
				$levelLimit = $resolveItem->getLimitLevel();
				$upgradeValue = $resolveItem->getUpgradeValue($level);
				$exp += $totalExp - $resolveItemValue;
				while ($exp >= $upgradeValue)
				{
					$level++;
					if ($level == $levelLimit)
					{
						break;
					}
					$upgradeValue = $resolveItem->getUpgradeValue($level);
				}
				$resolveItem->setLevel($level);
				$resolveItem->setExp($exp);
				$arrResolveItemId[] = $resolveItemId;
			}
			$evolve = $item->getEvolve();
			for ($i = 0; $i < $evolve; $i++)
			{
				$evolveExpend = $item->getEvolveExpend($i);
				if (!empty($evolveExpend['silver']))
				{
					$silver += $evolveExpend['silver'];
				}
				if (!empty($evolveExpend['item']))
				{
					foreach ($evolveExpend['item'] as $itemTplId => $itemNum)
					{
						if (!isset($items[$itemTplId]))
						{
							$items[$itemTplId] = 0;
						}
						$items[$itemTplId] += $itemNum;
					}
				}
			}
			$item->reset();
		}
		$user = EnUser::getUserObj();
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			$bag->addItemsByTemplateID($items, true);
		}
		if (!empty($arrResolveItemId))
		{
			$bag->addItems($arrResolveItemId, true);
		}
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */