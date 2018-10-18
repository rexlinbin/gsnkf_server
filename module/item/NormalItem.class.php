<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: NormalItem.class.php 219137 2016-01-04 09:49:33Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/NormalItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-01-04 09:49:33 +0000 (Mon, 04 Jan 2016) $
 * @version $Revision: 219137 $
 * @brief 
 *  
 **/
class NormalItem extends Item
{
	public function canDonate()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_CAN_DONATE);
	}
	
	public function getFame()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_FAME);
	}
	
	public function isHeroJHItem()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_IS_HERO_JH_ITEM);
	}
	
	public function resolveHeroJHGet()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_RESOLVE_HERO_JH_GET);
	}
	
	public function getTallyExp()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_NORMAL_TALLYEXP);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */