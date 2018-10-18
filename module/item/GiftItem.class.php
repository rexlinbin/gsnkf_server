<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: GiftItem.class.php 124974 2014-08-06 02:52:30Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/GiftItem.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-08-06 02:52:30 +0000 (Wed, 06 Aug 2014) $
 * @version $Revision: 124974 $
 * @brief
 *
 **/

class GiftItem extends DirectItem
{
	/**
	 * 获得礼物的选项
	 * @param int $itemTplId
	 * @return mixed
	 */
	public function getOptions()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_GIFT_OPTIONS);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
