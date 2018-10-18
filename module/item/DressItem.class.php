<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DressItem.class.php 138365 2014-11-03 10:37:57Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/DressItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-11-03 10:37:57 +0000 (Mon, 03 Nov 2014) $
 * @version $Revision: 138365 $
 * @brief 
 *  
 **/
class DressItem extends Item
{
	/**
	 *
	 * 产生物品
	 *
	 * @param int $itemTplId		物品模板ID
	 * @return array				等级
	 */
	public static function createItem($itemTplId)
	{
		$itemText = array();
	
		//初始化物品强化等级
		$itemText[ItemDef::ITEM_ATTR_NAME_DRESS_LEVEL] = 0;
	
		return $itemText;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see Item::itemInfo()
	 */
	public function itemInfo()
	{
		//修旧数据
		if (!isset($this->mItemText[ItemDef::ITEM_ATTR_NAME_DRESS_LEVEL]))
		{
			$this->setLevel(0);
		}
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
		$attrs = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_DRESS_ATTRS);
		//基础属性+成长属性
		foreach ($attrs as $attrId => $attrValue)
		{
			$attrName = PropertyKey::$MAP_CONF[$attrId];
			if ( !isset($info[$attrName]) )
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
	 * 时装重置
	 */
	public function reset()
	{
		$this->setLevel(0);
	}
	
	public function getLevel()
	{
		//修旧数据
		if (!isset($this->mItemText[ItemDef::ITEM_ATTR_NAME_DRESS_LEVEL]))
		{
			$this->mItemText[ItemDef::ITEM_ATTR_NAME_DRESS_LEVEL] = 0;
		}
		return $this->mItemText[ItemDef::ITEM_ATTR_NAME_DRESS_LEVEL];
	}
	
	public function setLevel($level)
	{
		$this->mItemText[ItemDef::ITEM_ATTR_NAME_DRESS_LEVEL] = $level;
	}
	
	public function getCost($level)
	{
		$cost = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_DRESS_COST);
		return $cost[$level];
	}
	
	public function getResolve()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_DRESS_RESOLVE);
	}
	
	public function getRebornCost()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_DRESS_REBORN);
	}
	
	public static function getExtraAttrs($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_DRESS_EXTRA);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */