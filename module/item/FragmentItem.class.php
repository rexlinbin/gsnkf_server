<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: FragmentItem.class.php 52167 2013-06-22 10:06:14Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/FragmentItem.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2013-06-22 10:06:14 +0000 (Sat, 22 Jun 2013) $
 * @version $Revision: 52167 $
 * @brief
 *
 **/

class FragmentItem extends DirectItem
{
	/**
	 * 得到合成物品所需碎片的数量
	 *
	 * @return int
	 */
	public function getFragNum()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_FRAGMENT_NUM);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */