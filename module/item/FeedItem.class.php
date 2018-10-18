<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FeedItem.class.php 50494 2013-06-07 09:58:21Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/FeedItem.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2013-06-07 09:58:21 +0000 (Fri, 07 Jun 2013) $
 * @version $Revision: 50494 $
 * @brief 
 *  
 **/

class FeedItem extends Item
{
	/**
	 *
	 * 得到饲料的增加的经验值
	 *
	 * @return int
	 */
	public function getFeedExp()
	{
		return ItemAttr::getItemAttr($this->getItemTemplateID(), ItemDef::ITEM_ATTR_NAME_FEED_EXP);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */