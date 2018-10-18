<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FightSoulItem.class.php 207978 2015-11-09 03:49:35Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/FightSoulItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-11-09 03:49:35 +0000 (Mon, 09 Nov 2015) $
 * @version $Revision: 207978 $
 * @brief 
 *  
 **/
/**
 * va_item_text:array	物品扩展信息
 * {
 * 	  	'fsLevel':等级
 * 		'fsExp':总经验值
 * 		'fsEvolve':精炼等级
 * }
 * @author tianming
 */
class FightSoulItem extends Item
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
		$itemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVEL] = FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_LEVEL;
		$itemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EXP] = FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_EXP;
		return $itemText;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Item::itemInfo()
	 */
	public function itemInfo()
	{
		return parent::itemInfo();
	}
	
	/**
	 * 物品的属性信息
	 * 
	 * @return array
	 */
	public function info()
	{
		$info = array();
		$level = $this->getLevel();
		$attrs = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_ATTRS);
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
		
		if ($this->canEvolve()) 
		{
			$evolve = $this->getEvolve();
			$attrRatio = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_ATTRRATIO);
			foreach ($info as $attrName => $attrValue)
			{
				$info[$attrName] += intval(($evolve * $attrRatio / UNIT_BASE) * $attrValue); 
			}
		}
		
		return $info;
	}
	
	/**
	 * 战魂重置
	 */
	public function reset()
	{
		$this->setLevel(FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_LEVEL);
		$this->setExp(FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_EXP);
		unset($this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVE]);
	}
	
	/**
	 * 得到战魂的类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_TYPE);
	}
	
	public function getValue()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_VALUE);
	}
	
	/**
	 * 得到战魂的评分
	 * @return number
	 */
	public function getScore()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_SCORE);
	}
	
	/**
	 * 得到战魂的排序
	 * @return number
	 */
	public function getSort()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_SORT);
	}
	
	public function getLimit($userLevel)
	{
		$levelLimit = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVELLIMIT);
		$baseRatio = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_BASERATIO);
		$levelRatio = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVELRATIO);
	
		$limit = min(intval(($baseRatio + $levelRatio * $userLevel / 100)/5)*5, $levelLimit);
		return $limit;
	}
	
	public function getUpgradeValue($level)
	{
		//经验表是从1级开始的
		$expId = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EXPID);
		if (!isset(btstore_get()->EXP_TBL[$expId]))
		{
			throw new ConfigException('invalid exp table id:%d, templateid:%d', $expId, $this->getItemTemplateID());
		}
		return btstore_get()->EXP_TBL[$expId][$level];
	}
	
	public function getLevel()
	{
		return $this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVEL];
	}
	
	public function setLevel($level)
	{
		$this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_LEVEL] = $level;
	}
	
	public function getExp()
	{
		return $this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EXP];
	}
	
	public function setExp($exp)
	{
		$this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EXP] = $exp;
	}
	
	public function addExp($addExp)
	{
		$userLevel = EnUser::getUserObj()->getLevel();
		$exp = $this->getExp();
		$level = $this->getLevel();
		$limit = $this->getLimit($userLevel);
		$upgradeValue = $this->getUpgradeValue($level + 1);
		while ($exp + $addExp >= $upgradeValue)
		{
			$level++;
			if ($level == $limit)
			{
				break;
			}
			$upgradeValue = $this->getUpgradeValue($level + 1);
		}
		$this->setExp($exp + $addExp);
		$this->setLevel($level);
	}
	
	public function getEvolve()
	{
		if ($this->canEvolve()) 
		{
			if (!isset($this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVE]))
			{
				$this->setEvolve(FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_EVOLVE);
			}
			return $this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVE];
		}
		else
		{
			return FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_INIT_EVOLVE;
		}
	}
	
	public function setEvolve($evolve)
	{
		$this->mItemText[FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVE] = $evolve;
	}
	
	public function canDevelop()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_CANDEVELOP);
	}
	
	public function getDevelopLevel()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPLV);
	}
	
	public function getDevelopCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPCOST)->toArray();
	}
	
	public function getDevelopId()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_DEVELOPID);
	}
	
	public function canEvolve()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVELIMIT) != 0;
	}
	
	public function getEvolveLimit()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVELIMIT);
	}
	
	public function getEvolveCost($evolve)
	{
		$cost = ItemAttr::getItemAttr($this->getItemTemplateID(), FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVECOST);
		return $cost[$evolve + 1]->toArray();
	}
	
	public function getEvolveCostSum()
	{
		$arrCost = array();
		$evolve = $this->getEvolve();
		for ($i = 0; $i < $evolve; $i++)
		{
			$arrCost = array_merge($arrCost, $this->getEvolveCost($i));
		}
		return $arrCost;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */