<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: PetFragItem.class.php 98552 2014-04-09 07:34:35Z MingTian $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/PetFragItem.class.php $
 * @author $Author: MingTian $(jhd@babeltime.com)
 * @date $Date: 2014-04-09 07:34:35 +0000 (Wed, 09 Apr 2014) $
 * @version $Revision: 98552 $
 * @brief
 *
 **/

class PetFragItem extends DirectItem
{
	/**
	 * 得到合成物品所需碎片的数量
	 *
	 * @return int
	 */
	public function getFragNum()
	{
		return ItemAttr::getItemAttr($this->mItemTplId, ItemDef::ITEM_ATTR_NAME_PETFRAG_NUM);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */