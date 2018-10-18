<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnItemInfo.class.php 108750 2014-05-16 06:51:33Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/itemInfo/EnItemInfo.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-05-16 06:51:33 +0000 (Fri, 16 May 2014) $
 * @version $Revision: 108750 $
 * @brief 
 *  
 **/
class EnItemInfo
{
	public static function getArmBookNum($uid)
	{
		$num = 0;
		$book = ItemBookDao::getArmBook($uid);
		if (!empty($book)) 
		{
			$num = count($book[ItemBookDao::BOOK]['arm']);
		}
		return $num;
	}
	
	public static function getTreasBookNum($uid)
	{
		$num = 0;
		$book = ItemBookDao::getTreasBook($uid);
		if (!empty($book))
		{
			$num = count($book[ItemBookDao::BOOK]['treas']);
		}
		return $num;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */