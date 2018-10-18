<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PocketItem.class.php 191087 2015-08-14 02:35:40Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/PocketItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-08-14 02:35:40 +0000 (Fri, 14 Aug 2015) $
 * @version $Revision: 191087 $
 * @brief 
 *  
 **/
/**
 * va_item_text:array	物品扩展信息
 * {
 * 	  	'pocketLevel':等级
 * 		'pocketExp':总经验值
 * }
 * @author tianming
 */
class PocketItem extends Item
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
		$itemText[PocketDef::ITEM_ATTR_NAME_POCKET_LEVEL] = PocketDef::ITEM_ATTR_NAME_POCKET_INIT_LEVEL;
		$itemText[PocketDef::ITEM_ATTR_NAME_POCKET_EXP] = PocketDef::ITEM_ATTR_NAME_POCKET_INIT_EXP;
		return $itemText;
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
		$attrs = ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_ATTRS);
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
		
		return $info;
	}
	
	/**
	 * 得到锦囊的类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_TYPE);
	}
	
	/**
	 * 获得锦囊的基础经验值
	 * 
	 * @return mixed
	 */
	public function getValue()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_VALUE);
	}
	
	/**
	 * 每经验消耗银币
	 * 
	 * @return mixed
	 */
	public function getValueCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_VALUECOST);
	}
	
	/**
	 * 获得等级上限
	 */
	public function getLimit()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_LEVELLIMIT);
	}
	
	
	public function getUpgradeValue($level)
	{
		//经验表是从1级开始的
		$expId = ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_EXPID);
		if (!isset(btstore_get()->EXP_TBL[$expId]))
		{
			throw new ConfigException('invalid exp table id:%d, templateid:%d', $expId, $this->getItemTemplateID());
		}
		return btstore_get()->EXP_TBL[$expId][$level];
	}
	
	public function getLevel()
	{
		return $this->mItemText[PocketDef::ITEM_ATTR_NAME_POCKET_LEVEL];
	}
	
	public function setLevel($level)
	{
		$this->mItemText[PocketDef::ITEM_ATTR_NAME_POCKET_LEVEL] = $level;
	}
	
	public function getExp()
	{
		return $this->mItemText[PocketDef::ITEM_ATTR_NAME_POCKET_EXP];
	}
	
	public function setExp($exp)
	{
		$this->mItemText[PocketDef::ITEM_ATTR_NAME_POCKET_EXP] = $exp;
	}
	
	public function addExp($addExp)
	{
		$exp = $this->getExp();
		$level = $this->getLevel();
		$limit = $this->getLimit();
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
	
	public function reset()
	{
		$this->setLevel(PocketDef::ITEM_ATTR_NAME_POCKET_INIT_LEVEL);
		$this->setExp(PocketDef::ITEM_ATTR_NAME_POCKET_INIT_EXP);
	}
	
	public function getAwakeAbility()
	{
		$ability = 0;
		$level = $this->getLevel();
		$effect = ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_EFFECT);
		foreach ($effect as $key => $value)
		{
			if ($level < $key) 
			{
				break;
			}
			$ability = $value;
		}

		return $ability;
	}
	
	public function isExp()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), PocketDef::ITEM_ATTR_NAME_POCKET_ISEXP);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */