<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Resolve.class.php 242029 2016-05-11 06:50:12Z DuoLi $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/resolve/Resolve.class.php $
 * @author $Author: DuoLi $(tianming@babeltime.com)
 * @date $Date: 2016-05-11 06:50:12 +0000 (Wed, 11 May 2016) $
 * @version $Revision: 242029 $
 * @brief 
 *  
 **/
class Resolve
{
	public static function HeroJHResolve($arrItemInfo, $preview = false)
	{
		Logger::trace('function[%s] param[%s] begin...', __FUNCTION__, func_get_args());
		
		// 检查红卡进化功能节点是否开启
		if (!EnSwitch::isSwitchOpen(SwitchDef::HERODEVELOP_2_RED)) 
		{
			throw new FakeException('switch not open');
		}
		
		// 批量拉取item对象
		$arrItemId = array_keys($arrItemInfo);
		$bag = BagManager::getInstance()->getBag();
		$userObj = EnUser::getUserObj();
		$arrItemObj = ItemManager::getInstance()->getItems($arrItemId);
		
		// 循环处理每个item
		$needSilver = 0;
		$getJH = 0;
		foreach ($arrItemInfo as $aItemId => $aItemNum)
		{
			// 没有对应的obj
			if (!isset($arrItemObj[$aItemId])) 
			{
				throw new FakeException('invalid item id[%d]', $aItemId);
			}
			
			$aItemObj = $arrItemObj[$aItemId];
			
			// 是否是normal_item，武将精华配置在normal_item中
			if ($aItemObj->getItemType() != ItemDef::ITEM_TYPE_NORMAL) 
			{
				throw new FakeException('not normal item, item id[%d]', $aItemId);
			}
			
			// 是否是武将精华物品
			if (!$aItemObj->isHeroJHItem()) 
			{
				throw new FakeException('not hero jh item, item id[%d]', $aItemId);
			}
			
			// 数量是否超过了拥有的数量
			if ($aItemObj->getItemNum() < $aItemNum) 
			{
				throw new FakeException('invalid num, item id[%d], curr num[%d], want num[%d]', $aItemId, $aItemObj->getItemNum(), $aItemNum);
			}
			
			// 减少物品数量
			$bag->decreaseItem($aItemId, $aItemNum);
			
			// 获得分解后的精华
			$getJH += $aItemObj->resolveHeroJHGet() * $aItemNum;
			$needSilver += intval(btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESOLVE_HERO_JH_COST_SILVE]) * $aItemNum;
		}
		
		// 扣银币，加精华
		if (!$userObj->subSilver($needSilver)) 
		{
			throw new FakeException('not enough silver, need[%d], curr[%d]', $needSilver, $userObj->getSilver());
		}
		$userObj->addJH($getJH);
		if( !$preview )
		{
			$bag->update();
			$userObj->update();
		}
		$ret = array('jh' => $getJH);
		
		Logger::trace('function[%s] param[%s] ret[%s] end...', __FUNCTION__, func_get_args(), $ret);
		return $ret;
	}
	
	
	public static function armResolve($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::armResolve Start.');
		
		if (empty($arrItemId)) 
		{
			return array();
		}
		
		$silver = 0;
		$items = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		for ($i = 0; $i < count($arrItem); $i++)
		{
			$itemId = $arrItemId[$i];
			$item = $arrItem[$itemId];
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_ARM)
			{
				throw new FakeException('itemId:%d is not a arm!', $itemId);
			}
			$itemQuality = $item->getItemQuality();
			$resolveId = $item->getExchangeId();
			$methodId = $item->getFoundryId();
			//红装不能炼化
			if ($itemQuality < ItemDef::ITEM_QUALITY_ORANGE && $resolveId == 0  
			|| $itemQuality == ItemDef::ITEM_QUALITY_ORANGE && $methodId == 0
			|| $itemQuality == ItemDef::ITEM_QUALITY_RED)
			{
				throw new FakeException('itemId:%d can not be resolved', $itemId);
			}
			if ($item->isLock() == 1) 
			{
				throw new FakeException('itemId:%d is lock', $itemId);
			}
			if ($bag->isItemExist($itemId) == false) 
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			if ($bag->deleteItem($itemId) == false)
			{
				throw new FakeException('itemId:%d delete from bag failed!', $itemId);
			}
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
			if (ItemDef::ITEM_QUALITY_ORANGE > $itemQuality) 
			{
				$sellInfo = $item->sellInfo();
				$silver += $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE];
				$resolve = self::getResolve($resolveId);
				$value = $resolve[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_VALUE];
				$args = $resolve[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_ARGS];
				$num = $resolve[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_NUM];
				$drops = $resolve[ArmDef::ITEM_ATTR_NAME_ARM_RESOLVE_DROPS]->toArray();
				$values = self::getResolveValues($value, $num, $args);
				shuffle($drops);
				foreach ($drops as $key => $dropId)
				{
					$drop = Drop::dropItem($dropId);
					if (count($drop) != 1 || current($drop) != 1)
					{
						throw new ConfigException('invalid dropId:%d does not drop one item!', $dropId);
					}
					$itemTplId = key($drop);
					$itemValue = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_VALUE);
					$itemNum = intval($values[$key] / $itemValue);
					if ($itemNum > 0)
					{
						if (!isset($items[$itemTplId])) 
						{
							$items[$itemTplId] = 0;
						}
						$items[$itemTplId] += $itemNum;
					}
				}
			}
			else 
			{
				//逆进化
				$silver += $item->getReinforceCost();
				if (!isset(btstore_get()->FOUNDRY[$methodId]))
				{
					throw new configException('foundry method:%d is not exist!', $methodId);
				}
				$conf = btstore_get()->FOUNDRY[$methodId];
				if ($item->getItemTemplateID() != $conf[ForgeDef::FOUNDRY_FORM]) 
				{
					throw new ConfigException('foundry method:%d form is not itemTplId:%d', $methodId, $item->getItemTemplateID());
				}
				$base = $conf[ForgeDef::FOUNDRY_BASE]->toArray();
				$index = count($base) - 1;
				$itemTplId = $base[$index];
				$ret = ItemManager::getInstance()->addItem($itemTplId);
				$arrItem[$ret[0]] = ItemManager::getInstance()->getItem($ret[0]);
				$bag->addItem($ret[0]);
				$arrItemId[] = $ret[0];
				foreach ($conf[ForgeDef::FOUNDRY_ITEM][$index] as $itemTplId => $itemNum)
				{
					if (!isset($items[$itemTplId]))
					{
						$items[$itemTplId] = 0;
					}
					$items[$itemTplId] += $itemNum;
				}
				foreach ($conf[ForgeDef::FOUNDRY_COST][$index] as $key => $value)
				{
					switch ($key)
					{
						case 1:
						case 2:
							throw new ConfigException('invalid cost type:%d', $key);
							break;
						case 3:
							$silver += $value;
							break;
						default:
							throw new ConfigException('invalid cost type:%d', $key);
					}
				}
			}
		}
		if (!empty($items)) 
		{
			if ($bag->addItemsByTemplateID($items) == false) 
			{
				throw new FakeException('full bag. add item tpls:%s failed', $items);
			}
		}
		
		if( !$preview)
		{
			$bag->update();
			if (!empty($silver))
			{
				$user = EnUser::getUserObj();
				$user->addSilver($silver);
				$user->update();
			}
		}
		Logger::trace('Resolve::armResolve End.');
		
		return array(
				'silver' => $silver,
				'item' => $items
		);
	}
	
	public static function armReborn($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::armReborn Start.');
		
		if (empty($arrItemId))
		{
			return array();
		}
		
		$gold = 0;
		$silver = 0;
		$items = array();
		$arrExpend = array();
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
			if ($item->getLevel() < 1)
			{
				throw new FakeException('itemId:%d can not be reborn', $itemId);
			}
			if ($item->isLock())
			{
				throw new FakeException('itemId:%d is lock', $itemId);
			}
			if ($bag->isItemExist($itemId) == false)
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			$gold += $item->getRebornCost();
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
			$arrExpend = array_merge($arrExpend, $item->getDevelopExpendSum());
			$item->reset();
		}
		$arrAdd = self::trans3D2Arr($arrExpend);
		if (!empty($arrAdd['silver'])) 
		{
			$silver += $arrAdd['silver'];
		}
		if (!empty($arrAdd['item'])) 
		{
			foreach ($arrAdd['item'] as $itemTplId => $itemNum)
			{
				if (!isset($items[$itemTplId]))
				{
					$items[$itemTplId] = 0;
				}
				$items[$itemTplId] += $itemNum;
			}
		}
		$user = EnUser::getUserObj();
		if (!empty($gold)) 
		{
			if ($user->subGold($gold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_ARM) == false) 
			{
				throw new FakeException('user has not enough gold:%d to reborn', $gold);
			}
		}
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			if ($bag->addItemsByTemplateID($items) == false)
			{
				throw new FakeException('full bag. add item tpls:%s failed', $items);
			}
		}
		if( !$preview )
		{
			$user->update();
			$bag->update();
		}
		Logger::trace('Resolve::armReborn End.');
		
		return array(
				'silver' => $silver,
				'item' => $items
		);
	}
	
	public static function treasureResolve($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::treasureResolve Start.');
	
		if (empty($arrItemId))
		{
			return array();
		}

		$silver = 0;
		$extra = array();
		$items = array();
		$arrInlay = array();
		$arrResolveItemId = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
			{
				throw new FakeException('itemId:%d is not a treasure!', $itemId);
			}
			if ($item->getItemQuality() < ItemDef::ITEM_QUALITY_PURPLE)
			{
				throw new FakeException('itemId:%d quality is too small to resolve', $itemId);
			}
			if ($bag->deleteItem($itemId) == false)
			{
				throw new FakeException('itemId:%d delete from bag failed!', $itemId);
			}
			$silver += $item->getUpgradeExpendSum();
			$totalExp = $item->getBaseValue() + $item->getExp();
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
				//TODO:批量炼化的时候，会产生大量的银书银马
				$arrResolveItemId[] = $resolveItemId;
			}
			$evolveResolve = $item->getEvolveResolve();
			foreach ($evolveResolve as $itemTplId => $itemNum)
			{
				if (!isset($items[$itemTplId])) 
				{
					$items[$itemTplId] = 0;
				}
				$items[$itemTplId] += $itemNum;
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
			$develop = $item->getDevelop();
			for ($i = 0; $i <= $develop; $i++)
			{
				$developExpend = $item->getDevelopExpend($i);
				$developExpend = ForgeLogic::transExpend($developExpend);
				if (!empty($developExpend['silver']))
				{
					$silver += $developExpend['silver'];
				}
				if (!empty($developExpend['item']))
				{
					foreach ($developExpend['item'] as $itemTplId => $itemNum)
					{
						if (!isset($items[$itemTplId]))
						{
							$items[$itemTplId] = 0;
						}
						$items[$itemTplId] += $itemNum;
					}
				}
			}
			$inlay = $item->getInlay();
			$arrInlay = array_merge($arrInlay, $inlay);
		}
		
		$tg = 0;
		$arrItem = ItemManager::getInstance()->getItems($arrInlay);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_RUNE)
			{
				throw new FakeException('itemId:%d is not a rune!', $itemId);
			}
			ItemManager::getInstance()->deleteItem($itemId);
			$tg += $item->getResolve();
			$sellInfo = $item->sellInfo();
			$silver += $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE];
		}

		$user = EnUser::getUserObj();
		if (!empty($tg))
		{
			$user->addTgNum($tg);
		}
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			if ($bag->addItemsByTemplateID($items) == false)
			{
				throw new FakeException('full bag. add item tpls:%s failed', $items);
			}
		}
		if (!empty($arrResolveItemId)) 
		{
			if ($bag->addItems($arrResolveItemId, true) == false)
			{
				throw new FakeException('full tmp bag. add item ids:%s failed', $arrResolveItemId);
			}
		}
		if (! $preview)
		{
			$user->update();
			$bag->update();
		}
		$items = Util::arrayAdd2V(array($items, $extra));
	
		Logger::trace('Resolve::treasureResolve End.');
	
		return array(
				'tg' => $tg,
				'silver' => $silver,
				'item' => $items,
		);
	}
	
	public static function treasureReborn($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::treasureReborn Start.');
	
		if (empty($arrItemId))
		{
			return array();
		}
	
		$gold = 0;
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
			if ($item->getLevel() < 1 && $item->getEvolve() < 1 && $item->getDevelop() < 0)
			{
				throw new FakeException('itemId:%d can not be reborn', $itemId);
			}
			if ($bag->isItemExist($itemId) == false)
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			$gold += $item->getRebornCost();
			$silver += $item->getUpgradeExpendSum();
			$totalExp = $item->getExp();
			$resolveItemTplId = $item->getResolveItem();
			$resolveItemValue = ItemAttr::getItemAttr($resolveItemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_BASE);
			if ($totalExp > $resolveItemValue) 
			{
				//加经验值，最多10个，每个给配置值，多出的经验值放最后一个
				for ($i = 1; $totalExp > 0; $i++)
				{
					if (!isset($extra[$resolveItemTplId]))
					{
						$extra[$resolveItemTplId] = 0;
					}
					$extra[$resolveItemTplId] ++;
					$resolveItemId = ItemManager::getInstance()->addItem($resolveItemTplId);
					$resolveItemId = $resolveItemId[0];
					$arrResolveItemId[] = $resolveItemId;
					$resolveItem = ItemManager::getInstance()->getItem($resolveItemId);
					$addExp = min($totalExp, ItemDef::UPPER_LIMIT_EXP_FOR_TREASURE);
					$totalExp -= $addExp;
					$resolveItem->addExp($addExp - $resolveItemValue);
					//1.剩余经验值小于经验物品的基础经验值，就把经验加在上一个物品里
					//2.物品数量超过上限，就把经验加在上一个物品里
					if ($totalExp < $resolveItemValue || $i >= ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM)
					{
						$resolveItem->addExp($totalExp);
						$totalExp = 0;
						break;
					}
				}
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
			$develop = $item->getDevelop();
			for ($i = 0; $i <= $develop; $i++)
			{
				$developExpend = $item->getDevelopExpend($i);
				$developExpend = ForgeLogic::transExpend($developExpend);
				if (!empty($developExpend['silver']))
				{
					$silver += $developExpend['silver'];
				}
				if (!empty($developExpend['item']))
				{
					foreach ($developExpend['item'] as $itemTplId => $itemNum)
					{
						if (!isset($items[$itemTplId]))
						{
							$items[$itemTplId] = 0;
						}
						$items[$itemTplId] += $itemNum;
					}
				}
			}
			$inlay = $item->getInlay();
			$arrResolveItemId = array_merge($arrResolveItemId, $inlay);
			$item->reset();
		}
		
		$user = EnUser::getUserObj();
		if (!empty($gold))
		{
			if ($user->subGold($gold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_TREASURE) == false)
			{
				throw new FakeException('user has not enough gold:%d to reborn', $gold);
			}
		}
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			if ($bag->addItemsByTemplateID($items) == false)
			{
				throw new FakeException('full bag. add item tpls:%s failed', $items);
			}
		}
		if (!empty($arrResolveItemId))
		{
			if ($bag->addItems($arrResolveItemId, true) == false)
			{
				throw new FakeException('full tmp bag. add item ids:%s failed', $arrResolveItemId);
			}
		}
		if( ! $preview)
		{
			$user->update();
			$bag->update();
		}
		$items = Util::arrayAdd2V(array($items, $extra));
	
		Logger::trace('Resolve::treasureReborn End.');
	
		return array(
				'silver' => $silver,
				'item' => $items,
		);
	}
	
	public static function dressResolve($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::dressResolve Start.');
	
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
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_DRESS)
			{
				throw new FakeException('itemId:%d is not a dress!', $itemId);
			}
			if ($bag->isItemExist($itemId) == false)
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			if ($bag->deleteItem($itemId) == false)
			{
				throw new FakeException('itemId:%d delete from bag failed!', $itemId);
			}
			$sellInfo = $item->sellInfo();
			$silver += $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE];
			$level = $item->getLevel();
			for ($i = $level; $i > 0; $i--)
			{
				$cost = $item->getCost($i);
				$silver += $cost['silver'];
				foreach ($cost['item'] as $itemTplId => $itemNum)
				{
					if (!isset($items[$itemTplId]))
					{
						$items[$itemTplId] = 0;
					}
					$items[$itemTplId] += $itemNum;
				}
			}
			$resolve = $item->getResolve();
			foreach ($resolve as $itemTplId => $itemNum)
			{
				if (!isset($items[$itemTplId]))
				{
					$items[$itemTplId] = 0;
				}
				$items[$itemTplId] += $itemNum;
			}
		}
		$user = EnUser::getUserObj();
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			if ($bag->addItemsByTemplateID($items) == false)
			{
				throw new FakeException('full bag. add item tpls:%s failed', $items);
			}
		}
		if( !$preview )
		{
			$user->update();
			$bag->update();
		}
		Logger::trace('Resolve::dressResolve End.');
		return array(
				'silver' => $silver,
				'item' => $items
		);
	}
	
	public static function dressReborn($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::dressReborn Start.');
	
		if (empty($arrItemId))
		{
			return array();
		}
	
		$gold = 0;
		$silver = 0;
		$items = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_DRESS)
			{
				throw new FakeException('itemId:%d is not a dress!', $itemId);
			}
			if ($item->getLevel() < 1)
			{
				throw new FakeException('itemId:%d can not be reborn', $itemId);
			}
			if ($bag->isItemExist($itemId) == false)
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			$level = $item->getLevel();
			$gold += $item->getRebornCost() * $level;
			for ($i = $level; $i > 0; $i--)
			{
				$cost = $item->getCost($i);
				$silver += $cost['silver'];
				foreach ($cost['item'] as $itemTplId => $itemNum)
				{
					if (!isset($items[$itemTplId]))
					{
						$items[$itemTplId] = 0;
					}
					$items[$itemTplId] += $itemNum;
				}
			}
			$item->reset();
		}
	
		$user = EnUser::getUserObj();
		if (!empty($gold))
		{
			if ($user->subGold($gold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_DRESS) == false)
			{
				throw new FakeException('user has not enough gold:%d to reborn', $gold);
			}
		}
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($items))
		{
			if ($bag->addItemsByTemplateID($items) == false)
			{
				throw new FakeException('full bag. add item tpls:%s failed', $items);
			}
		}
		
		if( !$preview )
		{
			$user->update();
			$bag->update();
		}
		Logger::trace('Resolve::dressReborn End.');
	
		return array(
				'silver' => $silver,
				'item' => $items
		);
	}
	
	public static function runeResolve($arrRuneItemId, $arrTreasItemId, $preview = false)
	{
		Logger::trace('Resolve::runeResolve Start.');
	
		if (empty($arrRuneItemId))
		{
			return array();
		}
	
		$tg = 0;
		$silver = 0;
		$arrInlay = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems(array_merge($arrRuneItemId, $arrTreasItemId));
		foreach ($arrTreasItemId as $treasItemId)
		{
			$treasItem = isset($arrItem[$treasItemId]) ? $arrItem[$treasItemId] : NULL;
			if ($treasItem === NULL || $treasItem->getItemType() != ItemDef::ITEM_TYPE_TREASURE)
			{
				throw new FakeException('itemId:%d is not a treasure!', $treasItemId);
			}
			if (!$bag->isItemExist($treasItemId)) 
			{
				throw new FakeException('itemId:%d is not in bag!', $treasItemId);
			}
			foreach ($treasItem->getInlay() as $index => $runeItemId)
			{
				$arrInlay[$runeItemId] = $treasItemId;
			}
		}
		foreach ($arrRuneItemId as $runeItemId)
		{
			$runeItem = isset($arrItem[$runeItemId]) ? $arrItem[$runeItemId] : NULL;
			if ($runeItem === NULL || $runeItem->getItemType() != ItemDef::ITEM_TYPE_RUNE)
			{
				throw new FakeException('itemId:%d is not a rune!', $runeItemId);
			}
			//如果是镶嵌在宝物上面
			if (isset($arrInlay[$runeItemId]))
			{
				$treasItemId = $arrInlay[$runeItemId];
				$treasItem = $arrItem[$treasItemId];
				$treasItem->delInlayItem($runeItemId);
				ItemManager::getInstance()->deleteItem($runeItemId);
			}
			else 
			{
				if (!$bag->deleteItem($runeItemId)) 
				{
					throw new FakeException('itemId:%d delete from bag failed!', $runeItemId);
				}
			}
			
			$tg += $runeItem->getResolve();
			$sellInfo = $runeItem->sellInfo();
			$silver += $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE];
		}
		
		$user = EnUser::getUserObj();
		if (!empty($silver)) 
		{
			$user->addSilver($silver);
		}
		if (!empty($tg)) 
		{
			$user->addTgNum($tg);
		}
		if( ! $preview )
		{
			$bag->update();
			$user->update();
		}
		Logger::trace('Resolve::runeResolve End.');
		return array(
				'silver' => $silver,
				'tg' => $tg,
		);
	}
	
	public static function pocketReborn($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::pocketReborn Start.');
		
		if (empty($arrItemId))
		{
			return array();
		}
		
		$gold = 0;
		$silver = 0;
		$items = array();
		$arrRebornItemId = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		$rebornCost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_POCKET_REBORN];
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_POCKET)
			{
				throw new FakeException('itemId:%d is not a pocket!', $itemId);
			}
			if ($item->isExp() || $item->getLevel() < 1)
			{
				throw new FakeException('itemId:%d can not be reborn', $itemId);
			}
			if ($item->isLock())
			{
				throw new FakeException('itemId:%d is lock', $itemId);
			}
			if (!$bag->isItemExist($itemId))
			{
				throw new FakeException('itemId:%d is not in bag!', $itemId);
			}
			$quality = $item->getItemQuality();
			$gold += $rebornCost[$quality];
			$exp = $item->getExp();
			$valueCost = $item->getValueCost();
			$silver += $exp * $valueCost;
			$rebornItemValue = ItemAttr::getItemAttr(PocketDef::REBORN_ITEM, PocketDef::ITEM_ATTR_NAME_POCKET_VALUE);
			if ($exp > $rebornItemValue) 
			{
				//加经验值，最多10个，每个给配置值，多出的经验值放最后一个
				for ($i = 1; $exp > 0; $i++)
				{
					if (!isset($items[PocketDef::REBORN_ITEM]))
					{
						$items[PocketDef::REBORN_ITEM] = 0;
					}
					$items[PocketDef::REBORN_ITEM]++;
					$rebornItemId = ItemManager::getInstance()->addItem(PocketDef::REBORN_ITEM);
					$rebornItemId = $rebornItemId[0];
					$arrRebornItemId[] = $rebornItemId;
					$rebornItem = ItemManager::getInstance()->getItem($rebornItemId);
					$addExp = min($exp, ItemDef::UPPER_LIMIT_EXP_FOR_POCKET);
					$exp -= $addExp;
					$rebornItem->addExp($addExp - $rebornItemValue);
					//1.剩余经验值小于经验物品的基础经验值，就把经验加在上一个物品里
					//2.物品数量超过上限，就把经验加在上一个物品里
					if ($exp < $rebornItemValue || $i >= ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM)
					{
						$rebornItem->addExp($exp);
						$exp = 0;
						break;
					}
				}
			}
			$item->reset();
		}
			
		$user = EnUser::getUserObj();
		if (!empty($gold))
		{
			if ($user->subGold($gold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_POCKET) == false)
			{
				throw new FakeException('user has not enough gold:%d to reborn', $gold);
			}
		}
		if (!empty($silver))
		{
			$user->addSilver($silver);
		}
		if (!empty($arrRebornItemId))
		{
			if ($bag->addItems($arrRebornItemId) == false)
			{
				throw new FakeException('full bag. add item ids:%s failed', $arrRebornItemId);
			}
		}
		if( !$preview)
		{
			$user->update();
			$bag->update();
		}
		Logger::trace('Resolve::pocketReborn End.');
	
		return array(
				'silver' => $silver,
				'item' => $items,
		);
	}
	
	public static function fightsoulReborn($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::fightsoulReborn Start.');
		
		if (empty($arrItemId))
		{
			return array();
		}
		
		$flag = false;
		$exp = 0;
		$arrCost = array();
		$rebornCost = 100 * count($arrItemId);
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			//战魂
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_FIGHTSOUL)
			{
				throw new FakeException('itemId:%d is not a fightsoul!', $itemId);
			}
			//等级大于0
			if ($item->getLevel() < 1 && $item->getEvolve() < 1)
			{
				throw new FakeException('itemId:%d level or evolve is less than 1', $itemId);
			}
			if (!$bag->isItemExist($itemId)) 
			{
				$flag = true;
			}
			$exp += $item->getExp();
			$arrCost = array_merge($arrCost, $item->getEvolveCostSum());
			$item->reset();
		}
		$user = EnUser::getUserObj();
		$arrAdd = self::trans3D2Arr($arrCost);
		if (!empty($exp)) 
		{
			$arrAdd['fs_exp'] = $exp;
			$user->addFsExp($exp);
		}
		if (!empty($arrAdd['silver']))
		{
			$user->addSilver($arrAdd['silver']);
		}
		if (!empty($arrAdd['item']))
		{
			if (!$bag->addItemsByTemplateID($arrAdd['item'])) 
			{
				throw new FakeException('full bag. add item tpls:%s failed', $arrAdd['item']);
			}
		}
		if (!$user->subSilver($rebornCost)) 
		{
			throw new FakeException('user has not enough silver:%d to reborn fightsoul', $rebornCost);
		}

		if( !$preview )
		{
			$bag->update();
			$user->update();
		}
		//清除战斗缓存
		if ($flag) 
		{
			$user->modifyBattleData();
		}
		
		Logger::trace('Resolve::fightsoulReborn End.');
		
		return $arrAdd;
	}
	
	public static function tallyResolve($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::tallyResolve Start.');
	
		if (empty($arrItemId))
		{
			return array();
		}
		
		$point = 0;
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TALLY)
			{
				throw new FakeException('itemId:%d is not a tally!', $itemId);
			}
			if (!$item->canResolve())
			{
				throw new FakeException('itemId:%d can not resolve', $itemId);
			}
			if (!$bag->deleteItem($itemId)) 
			{
				throw new FakeException('itemId:%d is not in bag', $itemId);
			}
			$point += $item->getResolvePoint();
		}
		
		$user = EnUser::getUserObj();
		$ret = array();
		if (!empty($point))
		{
			$ret['tally_point'] = $point;
			$user->addTallyPoint($point);
		}
	
		if( ! $preview )
		{
			$bag->update();
			$user->update();
		}
		Logger::trace('Resolve::tallyResolve End.');
	
		return $ret;
	}
	
	public static function tallyReborn($arrItemId, $preview = false)
	{
		Logger::trace('Resolve::tallyReborn Start.');
	
		if (empty($arrItemId))
		{
			return array();
		}
	
		$sumExp = 0;
		$sumGold = 0;
		$sumSilver = 0;
		$arrCost = array();
		$bag = BagManager::getInstance()->getBag();
		$arrItem = ItemManager::getInstance()->getItems($arrItemId);
		foreach ($arrItem as $itemId => $item)
		{
			if ($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_TALLY)
			{
				throw new FakeException('itemId:%d is not a tally!', $itemId);
			}
			if ($item->canResolve())
			{
				throw new FakeException('itemId:%d can not reborn', $itemId);
			}
			$sumExp += $item->getExp();
			$sumGold += $item->getRebornCost();
			$sumSilver += $item->getUpgradeCostSum();
			$arrCost = array_merge($arrCost, $item->getDevelopCostSum());
			$arrCost = array_merge($arrCost, $item->getEvolveCostSum());
			$item->reset();
		}
		$user = EnUser::getUserObj();
		if (!$user->subGold($sumGold, StatisticsDef::ST_FUNCKEY_MYSTERYSHOP_REBORN_TALLY))
		{
			throw new FakeException('user has not enough gold:%d to reborn tally', $sumGold);
		}
		$arrAdd = self::trans3D2Arr($arrCost);
		if (!empty($sumExp))
		{
			foreach (ItemDef::$TALLY_EXP_ITEMS as $itemTplId)
			{
				$itemValue = ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_TALLYEXP);
				$itemNum = intval($sumExp / $itemValue);
				$sumExp -= $itemValue * $itemNum;
				if ($itemNum > 0) 
				{
					if (!isset($arrAdd['item'][$itemTplId])) 
					{
						$arrAdd['item'][$itemTplId] = 0;
					}
					$arrAdd['item'][$itemTplId] += $itemNum;
				}
			}
		}
		if (!empty($sumSilver))
		{
			if (!isset($arrAdd['silver'])) 
			{
				$arrAdd['silver'] = 0;
			}
			$arrAdd['silver'] += $sumSilver; 
			$user->addSilver($arrAdd['silver']);
		}
		if (!empty($arrAdd['jewel'])) 
		{
			$user->addJewel($arrAdd['jewel']);
		}
		if (!empty($arrAdd['item']))
		{
			if (!$bag->addItemsByTemplateID($arrAdd['item']))
			{
				throw new FakeException('full bag. add item tpls:%s failed', $arrAdd['item']);
			}
		}
		
		if( ! $preview)
		{
			$user->update();
			$bag->update();
		}
		Logger::trace('Resolve::tallyReborn End.');
	
		return $arrAdd;
	}
	
	public static function trans3D2Arr($array)
	{
		$arr = array();
		foreach ($array as $value)
		{
			switch ($value[0])
			{
				case RewardConfType::SILVER:
					self::addKeyValue($arr, 'silver', $value[2]);
					break;
				case RewardConfType::ITEM_MULTI:
					self::addKeyItem($arr, 'item', $value[1], $value[2]);
					break;
				case RewardConfType::JEWEL:
					self::addKeyValue($arr, 'jewel', $value[2]);
					break;
				default:
					throw new FakeException('reward type:%d is not support yet!', $value[0]);
			}
		}
		return $arr;
	}
	
	public static function transArr23D($array)
	{
		$arr = array();
		foreach ($array as $key => $value)
		{
			switch ($key)
			{
				case 'silver':
					$arr[] = array(RewardConfType::SILVER, 0, $value);
					break;
				case 'item':
					foreach ($value as $itemTplId => $itemNum)
					{
						$arr[] = array(RewardConfType::ITEM_MULTI, $itemTplId, $itemNum);
					}
					break;
				default:
					throw new FakeException('reward type:%d is not support yet!', $value[0]);
			}
		}
		return $arr;
	}
	
	public static function addKeyValue(&$arr, $key, $value)
	{
		if (!isset($arr[$key]))
		{
			$arr[$key] = $value;
		}
		else
		{
			$arr[$key] += $value;
		}
	}
	
	public static function addKeyItem(&$arr, $key, $itemTplId, $itemNum)
	{
		if (!isset($arr[$key][$itemTplId]))
		{
			$arr[$key][$itemTplId] = $itemNum;
		}
		else
		{
			$arr[$key][$itemTplId] += $itemNum;
		}
	}
	
	private static function getResolve($resolveId)
	{
		if (!isset(btstore_get()->ARM_RESOLVE[$resolveId]))
		{
			throw new FakeException('invalid resolve id:%d!', $resolveId);
		}
		$resolve = btstore_get()->ARM_RESOLVE[$resolveId];
		
		if (empty($resolve))
		{
			throw new ConfigException("invalid resolve id=%d, config is empty!", $resolveId);
		}
		return $resolve;
	}
	
	/**
	 * 根据分解价值和参数计算所有分解值
	 * 
	 * @param int $value		要分解的值
	 * @param int $num			分解数量
	 * @param array $args		分解参数数组
	 * @return array $valus		分解后的值数组
	 */
	private static function getResolveValues($value, $num, $args)
	{
		$values = array();
		$current = $value;
		$args0 = $args[0] / UNIT_BASE;
		$args1 = $args[1] / UNIT_BASE;
		$args2 = $args[2] / UNIT_BASE;
		for ($i = 0; $i < $num - 1; $i++)
		{
			$rand = rand(min($args1 * $value, $current), min($current, $args0 * $value));
			$values[$i] = max($rand, 0);
			$current -= $values[$i];
		}
		$rand = rand(max($current - $args2 * $value, 0), $current);
		$values[$i] = max($rand, 0);
		
		Logger::trace('resolve values:%s', $values);
		return $values;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */