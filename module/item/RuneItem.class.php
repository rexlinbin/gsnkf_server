<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RuneItem.class.php 171527 2015-05-07 07:58:11Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/RuneItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-05-07 07:58:11 +0000 (Thu, 07 May 2015) $
 * @version $Revision: 171527 $
 * @brief 
 *  
 **/

class RuneItem extends Item
{	
	/**
	 * 物品的属性信息
	 * 
	 * @return array
	 */
	public function info()
	{
		$info = array();
		$attrs = ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_RUNE_ATTR);
		//基础属性
		foreach ($attrs as $attrId => $attrValue)
		{
			$attrName = PropertyKey::$MAP_CONF[$attrId];
			if (!isset($info[$attrName]))
			{
				$info[$attrName] = 0;
			}
			$info[$attrName] += $attrValue; 
		}
		
		return $info;
	}
	
	/**
	 * 得到符印的类型
	 *
	 * @return int
	 */
	public function getType()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_RUNE_TYPE);
	}
	
	/**
	 * 得到属性的类型
	 * 
	 * @return int
	 */
	public function getFeature()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_RUNE_FEATURE);
	}
	
	/**
	 * 得到分解的天工令
	 * 
	 * @return int
	 */
	public function getResolve()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_RUNE_RESOLVE);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */