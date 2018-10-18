<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: GoodWillItem.class.php 135456 2014-10-09 03:08:06Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/GoodWillItem.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-10-09 03:08:06 +0000 (Thu, 09 Oct 2014) $
 * @version $Revision: 135456 $
 * @brief
 *
 **/

class GoodWillItem extends Item
{
	/**
	 *
	 * 得到物品的增加的好感度
	 *
	 * @return int
	 */
	public function getGoodWill()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_GOODWILL_EXP);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */