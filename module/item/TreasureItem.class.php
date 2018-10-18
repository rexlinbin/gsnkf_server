<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TreasureItem.class.php 253492 2016-07-28 03:50:11Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/TreasureItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-07-28 03:50:11 +0000 (Thu, 28 Jul 2016) $
 * @version $Revision: 253492 $
 * @brief 
 *  
 **/
/**
 * va_item_text:array	物品扩展信息
 * {
 * 	  	'treasureLevel':等级
 * 		'treasureExp':总经验值
 * 		'treasureEvolve':精炼等级
 * 		'treasureDevelop':进阶等级
 * 		'treasureInlay':镶嵌数组
 * 		{
 * 			1 => $itemId
 * 			2 => $itemId
 * 		}
 * }
 * @author tianming
 */

class TreasureItem extends Item
{
	/**
	 * 产生物品
	 *
	 * @param int $itemTplId		物品模板ID
	 * @return array 				物品的等级信息
	 */
	public static function createItem($itemTplId)
	{
		$itemText = array();
		$canUpgrade = ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_UPGRADE);
		$canEvolve = ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_EVOLVE);
		$canDevelop = ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP);
		$canInlay = ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_INLAY);
		if ($canUpgrade) 
		{
			$itemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL;
			$itemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EXP] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EXP;
		}
		if ($canEvolve) 
		{
			$itemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EVOLVE;
		}
		if ($canDevelop) 
		{
			$itemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_DEVELOP;
		}
		if ($canInlay) 
		{
			$itemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] = TreasureDef::$ITEM_ATTR_NAME_TREASURE_INIT_INLAY;
		}
		return $itemText;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Item::itemInfo()
	 */
	public function itemInfo()
	{
		$this->getLevel();
		$this->getExp();
		$this->getEvolve();
		$this->getDevelop();
		$inlay = $this->getInlay();
		$arrInlayItem = $this->getArrInlayItem();
		
		$itemInfo = Item::itemInfo();
		foreach ($inlay as $index => $itemId)
		{
			$itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY][$index] = $arrInlayItem[$itemId]->itemInfo();
		}
		
		//解决阵容信息取宝物精华va为空前端崩溃的问题
		if (!isset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL]))
		{
			$itemInfo[ItemDef::ITEM_SQL_ITEM_TEXT][TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL] = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL;
		}
		
		return $itemInfo;
	}
	
	/**
	 * 物品的属性信息
	 * 
	 * @return array
	 */
	public function info()
	{
		$info = array();
		if ($this->canUpgrade()) 
		{
			$level = $this->getLevel();
			$attrs = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_ATTRS);
			$extra = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EXTRA);
			//基础属性+成长属性
			foreach ($attrs as $attrId => $attrValue)
			{
				$attrName = PropertyKey::$MAP_CONF[$attrId];
				if (!isset($info[$attrName]))
				{
					$info[$attrName] = 0;
				}
				$info[$attrName] += $attrValue[0]; 
				if (isset($attrValue[1])) 
				{
					$info[$attrName] += $attrValue[1] * $level;
				}
			}
			//附加解锁属性
			foreach ($extra as $needLevel => $attr)
			{
				if ($needLevel > $level) 
				{
					break;
				}
				foreach ($attr as $attrId => $attrValue)
				{
					$attrName = PropertyKey::$MAP_CONF[$attrId];
					if (!isset($info[$attrName]))
					{
						$info[$attrName] = 0;
					}
					$info[$attrName] += $attrValue;
				}
			}
		}
		
		if ($this->canEvolve()) 
		{
			$evolve = $this->getEvolve();
			$evolveAttrs = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_ATTRS);
			$evolveExtra = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXTRA);
			//精炼属性
			foreach ($evolveAttrs as $attrId => $attrValue)
			{
				$attrName = PropertyKey::$MAP_CONF[$attrId];
				if (!isset($info[$attrName]))
				{
					$info[$attrName] = 0;
				}
				$info[$attrName] += $attrValue * $evolve;
			}
			//精炼附加解锁属性
			foreach ($evolveExtra as $needEvolve => $attr)
			{
				if ($needEvolve > $evolve)
				{
					break;
				}
				foreach ($attr as $attrId => $attrValue)
				{
					$attrName = PropertyKey::$MAP_CONF[$attrId];
					if ( !isset($info[$attrName]) )
					{
						$info[$attrName] = 0;
					}
					$info[$attrName] += $attrValue;
				}
			}
		}
		
		if ($this->canDevelop()) 
		{
			$level = $this->getLevel();
			$develop = $this->getDevelop();
			$developAttrs = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_ATTRS);
			$developExtra = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXTRA);
			foreach ($developAttrs as $needDevelop => $attr)
			{
				if ($needDevelop > $develop) 
				{
					break;
				}
				foreach ($attr as $attrId => $attrValue)
				{
					$attrName = PropertyKey::$MAP_CONF[$attrId];
					if (!isset($info[$attrName]))
					{
						$info[$attrName] = 0;
					}
					$info[$attrName] += $attrValue;
				}
			}
			foreach ($developExtra as $value)
			{
				if ($level >= $value[0] && $develop >= $value[1]) 
				{
					$attrName = PropertyKey::$MAP_CONF[$value[2]];
					if (!isset($info[$attrName]))
					{
						$info[$attrName] = 0;
					}
					$info[$attrName] += $value[3];
				}
			}
		}
		
		if ($this->canInlay()) 
		{
			$arrInlayItem = $this->getArrInlayItem();
			foreach ($arrInlayItem as $item)
			{
				$attrInfo = $item->info();
				foreach ($attrInfo as $attrName => $attrValue)
				{
					if (!isset($info[$attrName]))
					{
						$info[$attrName] = 0;
					}
					$info[$attrName] += $attrValue;
				}
			}
		}
		
		return $info;
	}

	/**
	 * 装备重置
	 */
	public function reset()
	{
		if ($this->canUpgrade()) 
		{
			$this->setLevel(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL);
			$this->setExp(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EXP);
		}
		if ($this->canEvolve()) 
		{
			$this->setEvolve(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EVOLVE);
		}
		if ($this->canDevelop()) 
		{
			$this->setDevelop(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_DEVELOP);
		}
		if ($this->canInlay()) 
		{
			$this->setInlay(TreasureDef::$ITEM_ATTR_NAME_TREASURE_INIT_INLAY);
		}
	}
	
	public function getArrInlayItem()
	{
		$inlay = $this->getInlay();
		return ItemManager::getInstance()->getItems($inlay);
	}
	
	/**
	 *
	 * 得到宝物的等级
	 *
	 * @return int
	 */
	public function getLevel()
	{
		if ($this->canUpgrade()) 
		{
			if (!isset($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL])) 
			{
				$this->setLevel(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL);
			}
			return $this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL];
		}
		else 
		{
			return TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL;
		}
	}
	
	/**
	 * 设置宝物的等级
	 *
	 * @param int $level
	 */
	public function setLevel($level)
	{
		$this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL] = $level;
	}
	
	/**
	 *
	 * 得到宝物的总经验
	 *
	 * @return int
	 */
	public function getExp()
	{
		if ($this->canUpgrade()) 
		{
			if (!isset($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EXP]))
			{
				$level = $this->getLevel();
				if ($level == TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_LEVEL)
				{
					$exp = TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EXP;
				}
				else
				{
					$exp = $this->getUpgradeValue($level - 1);
				}
				$this->setExp($exp);
			}
			return $this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EXP];
		}
		else 
		{
			return TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EXP;
		}
	}
	
	/**
	 * 设置宝物的经验值
	 *
	 * @param int $level
	 */
	public function setExp($exp)
	{
		$this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EXP] = $exp;
	}
	
	/**
	 * 增加宝物的经验值
	 * 
	 * @param int $addExp
	 */
	public function addExp($addExp)
	{
		$exp = $this->getExp();
		$level = $this->getLevel();
		$limit = $this->getLimitLevel();
		$upgradeValue = $this->getUpgradeValue($level);
		while ($exp + $addExp >= $upgradeValue)
		{
			$level++;
			if ($level == $limit)
			{
				break;
			}
			$upgradeValue = $this->getUpgradeValue($level);
		}
		$this->setExp($exp + $addExp);
		$this->setLevel($level);
	}
	
	/**
	 *
	 * 得到宝物的精炼等级
	 *
	 * @return int
	 */
	public function getEvolve()
	{
		if ($this->canEvolve())
		{
			//修旧数据
			if (!isset($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE]))
			{
				$this->setEvolve(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EVOLVE);
			}
			return $this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE];
		}
		else
		{
			return TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_EVOLVE;
		}
	}
	
	/**
	 * 设置宝物的精炼等级
	 *
	 * @param int $evolve
	 */
	public function setEvolve($evolve)
	{
		$this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE] = $evolve;
	}
	
	public function getDevelop()
	{
		if ($this->canDevelop()) 
		{
			//修旧数据
			if (!isset($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP]))
			{
				$this->setDevelop(TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_DEVELOP);
			}
			return $this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP];
		}
		else 
		{
			return TreasureDef::ITEM_ATTR_NAME_TREASURE_INIT_DEVELOP;
		}
	}
	
	public function setDevelop($develop)
	{
		$this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP] = $develop;
	}
	
	public function getInlay()
	{
		if ($this->canInlay()) 
		{
			//修旧数据
			if (!isset($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY]))
			{
				$this->setInlay(TreasureDef::$ITEM_ATTR_NAME_TREASURE_INIT_INLAY);
			}
			$arrItem = ItemManager::getInstance()->getItems($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY]);
			foreach ($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] as $index => $itemId)
			{
				if (empty($arrItem[$itemId]))
				{
					Logger::fatal('treasItemId:%d is not inlay runeItemId:%d', $this->mItemId, $itemId);
					$this->delInlay($index);
				}
			}
			return $this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY];
		}
		else 
		{
			return TreasureDef::$ITEM_ATTR_NAME_TREASURE_INIT_INLAY;
		}
	}
	
	public function setInlay($inlay)
	{
		$this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] = $inlay;
	}
	
	public function addInlay($index, $itemId)
	{
		$this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY][$index] = $itemId;
	}
	
	public function delInlay($index)
	{
		unset($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY][$index]);
	}
	
	public function delInlayItem($itemId)
	{
		foreach ($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] as $index => $value)
		{
			if ($value == $itemId) 
			{
				$this->delInlay($index);
			}
		}
	}
	
	public function getItemQuality()
	{
		if ($this->getDevelop() >= 0) 
		{
			$quality = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_QUALITY);
			return $this->getDevelop() >= TreasureDef::RED_INIT_DEVELOP ? $quality[TreasureDef::RED_INIT_DEVELOP] : $quality[TreasureDef::ORANGE_INIT_DEVELOP];
		}
		else
		{
			return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_QUALITY);
		}
	}
	
	/**
	 * 得到宝物的类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_TYPE);
	}
	
	/**
	 * 得到宝物的评分
	 * 
	 * @return int
	 */
	public function getScore()
	{
		$level = $this->getLevel();
		if ($this->getDevelop() >= 0) 
		{
			$scoreBaseArr = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_SCORE);
			$scoreBase = $this->getDevelop() >= TreasureDef::RED_INIT_DEVELOP ? $scoreBaseArr[TreasureDef::RED_INIT_DEVELOP] : $scoreBaseArr[TreasureDef::ORANGE_INIT_DEVELOP];
		}
		else 
		{
			$scoreBase = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_SCORE_BASE);
		}
		$scoreAdd = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_SCORE_ADD);
		return $scoreBase + $scoreAdd * $level;
	}
	
	/**
	 * 得到宝物的基础价值
	 * 
	 * @return int
	 */
	public function getBaseValue()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_BASE);
	}
	
	/**
	 * 得到宝物的等级价值
	 * 
	 * @return int
	 */
	public function getUpgradeValue($level)
	{
		//经验表是从0级开始的
		$upgrade = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_VALUE_UPGRADE);
		return $upgrade[$level];
	}
	
	/**
	 * 得到宝物的升级花费(游戏币：银币)
	 *
	 * @return int
	 */
	public function getUpgradeExpend($level)
	{
		//是从0级开始的
		$expend = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EXPEND_UPGRADE);
		return $expend[$level];
	}
	
	/**
	 * 得到宝物的升级总花费(游戏币：银币)
	 * 
	 * @return int
	 */
	public function getUpgradeExpendSum()
	{
		$sum = 0;
		$oldExp = 0;
		$level = $this->getLevel();
		for ($i = 0; $i < $level; $i++)
		{
			$expend = $this->getUpgradeExpend($i);
			$exp = $this->getUpgradeValue($i);
			$sum += ($exp - $oldExp) * $expend;
			$oldExp = $exp;
		}
		$expend = $this->getUpgradeExpend($level);
		$exp = $this->getExp();
		$sum += ($exp - $oldExp) * $expend;
		return $sum;
	}
	
	/**
	 * 得到宝物的精炼花费(银币和物品)
	 *
	 */
	public function getEvolveExpend($evolve)
	{
		//是从1级开始的
		$expend = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_EXPEND);
		return $expend[$evolve + 1]->toArray();
	}
	
	/**
	 * 得到宝物的进阶花费(银币和物品)
	 *
	 */
	public function getDevelopExpend($develop)
	{
		//是从0级开始的
		$expend = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_DEVELOP_EXPEND);
		return $expend[$develop]->toArray();
	}
	
	/**
	 * 获得宝物炼化基础精华ID组
	 * @return array
	 * {
	 * 		$itemTplId => $itemNum
	 * }
	 */
	public function getEvolveResolve()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_RESOLVE);
	}
	
	/**
	 * 获得宝物炼化返还经验宝物ID
	 * @return int
	 */
	public function getResolveItem()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_RESOLVE_ITEM);
	}
	
	/**
	 * 获得重生花费金币
	 * @return int
	 */
	public function getRebornCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_REBORN_COST);
	}
	
	/**
	 * 得到宝物的碎片组
	 * 
	 * @return array
	 */
	public static function getFragments($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, TreasureDef::ITEM_ATTR_NAME_TREASURE_FRAGMENTS)->toArray();
	}
	
	/**
	 * 得到宝物的最大强化等级
	 * @return string
	 */
	public function getLimitLevel()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_LEVEL_LIMIT);
	}
	
	/**
	 * 得到宝物的最大精炼等级
	 * @return string
	 */
	public function getEvolveLimit()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_EVOLVE_LIMIT);
	}
	
	/**
	 * 是否可以升级
	 *
	 * @return bool
	 */
	public function canUpgrade()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_UPGRADE);
	}
	
	/**
	 * 是否可以精炼
	 *
	 * @return bool
	 */
	public function canEvolve()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_EVOLVE);
	}
	
	/**
	 * 是否可以进阶
	 * 
	 * @return bool
	 */
	public function canDevelop()
	{
		$canDevelop = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP);
		return $canDevelop[TreasureDef::ORANGE_INIT_DEVELOP];
	}
	
	/**
	 * 是否可以二次进阶
	 *
	 * @return bool
	 */
	public function canDevelop2Red()
	{
		$canDevelop = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_DEVELOP);
		return $canDevelop[TreasureDef::RED_INIT_DEVELOP];
	}
	
	/**
	 * 是否可以镶嵌
	 *
	 * @return bool
	 */
	public function canInlay()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_CAN_INLAY);
	}
	
	/**
	 * 是否没有属性加成(一键装备)
	 *
	 * @return bool
	 */
	public function isNoAttr()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_NO_ATTR);
	}
	
	public function isInlayOpen($uid, $index)
	{
		$ret = false;
		
		$userLevel = EnUser::getUserObj($uid)->getLevel();
		$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TREAS_INLAY];
		if (isset($needLevel[$index]) && $userLevel >= $needLevel[$index]) 
		{
			$ret = true;
		}

		$develop = $this->getDevelop();
		$open = ItemAttr::getItemAttr($this->getItemTemplateID(), TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY_OPEN);
		foreach ($open as $key => $value)
		{
			if ($value == $index && $develop >= $key) 
			{
				$ret = true;
			}
		}
		
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */