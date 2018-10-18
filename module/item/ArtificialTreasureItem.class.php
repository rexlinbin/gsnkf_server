<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArtificialTreasureItem.class.php 179599 2015-06-17 03:28:59Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/ArtificialTreasureItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2015-06-17 03:28:59 +0000 (Wed, 17 Jun 2015) $
 * @version $Revision: 179599 $
 * @brief 
 *  
 **/
class ArtificialTreasureItem extends TreasureItem
{
	public function getArrInlayItem()
	{
		$arrInlayItem = array();
		if ( empty($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY]) )
		{
			return $arrInlayItem;
		}
		foreach ($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] as $itemInfo)
		{
			$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
			$arrInlayItem[$itemId] = ItemManager::__getItem($itemInfo);
		}
		return $arrInlayItem;
	}
	public function getInlay()
	{
		$arrInlayId = array();
		if (empty($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY]))
		{
			return $arrInlayId;
		}
		foreach ($this->mItemText[TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] as $index => $itemInfo)
		{
			$arrInlayId[$index] = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
		}
		return $arrInlayId;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */