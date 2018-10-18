<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroFragItem.class.php 52169 2013-06-22 10:06:24Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/HeroFragItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-06-22 10:06:24 +0000 (Sat, 22 Jun 2013) $
 * @version $Revision: 52169 $
 * @brief 
 *  
 **/

class HeroFragItem extends FragmentItem
{
	public function getUniversalFragNum()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_HEROFRAG_UNIVERSAL);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */