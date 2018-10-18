<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: DirectItem.class.php 219186 2016-01-04 12:41:14Z BaoguoMeng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/DirectItem.class.php $
 * @author $Author: BaoguoMeng $(jhd@babeltime.com)
 * @date $Date: 2016-01-04 12:41:14 +0000 (Mon, 04 Jan 2016) $
 * @version $Revision: 219186 $
 * @brief
 *
 **/

class DirectItem extends Item
{
	/**
	 * (non-PHPdoc)
	 * @see Item::useReqInfo()
	 */
	public function useReqInfo()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_USE_REQ);
	}

	/**
	 * (non-PHPdoc)
	 * @see Item::useAcqInfo()
	 */
	public function useAcqInfo()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_USE_ACQ);
	}
	
	public function isAddVipExp()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_IS_ADD_VIP_EXP);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */