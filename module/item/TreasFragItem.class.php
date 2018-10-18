<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TreasFragItem.class.php 82484 2013-12-23 07:50:20Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/TreasFragItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-12-23 07:50:20 +0000 (Mon, 23 Dec 2013) $
 * @version $Revision: 82484 $
 * @brief 
 *  
 **/
class TreasFragItem
{
	/**
	 * 得到合成的宝物id
	 *
	 * @return int
	 */
	public static function getTreasureId($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TREASFRAG_FORM);
	}
	
	/**
	 * 得到基础掠夺概率
	 *
	 * @return int
	 */
	public static function getBaseRobRatio($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TREASFRAG_ROBRATIO_BASE);
	}
	
	/**
	 * 得到NPC掠夺概率
	 *
	 * @return int
	 */
	public static function getNpcRobRatio($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TREASFRAG_ROBRATIO_NPC);
	}
	
	/**
	 * 得到特殊掉落值
	 * 
	 * @param int $itemTplId
	 * @return int
	 */
	public static function getSpecialNum($itemTplId)
	{
		return ItemAttr::getItemAttr($itemTplId, ItemDef::ITEM_ATTR_NAME_TREASFRAG_SPECIAL_NUM);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */