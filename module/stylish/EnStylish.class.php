<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnStylish.class.php 241069 2016-05-05 03:06:10Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/stylish/EnStylish.class.php $
 * @author $Author: MingTian $(pengnana@babeltime.com)
 * @date $Date: 2016-05-05 03:06:10 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241069 $
 * @brief 
 *  
 **/
class EnStylish
{
	public static function getAddAttr($uid, $htid)
	{
		$arrAttr = array();
		$titleConf = btstore_get()->TITLE;

		$stylishObj = StylishObj::getInstance($uid);
		foreach ($stylishObj->getTitle() as $id => $info)
		{
			//激活属性，限时称号和非限时称号第一次激活开始永久累加
			$arrAttr[] = $titleConf[$id][StylishDef::TITLE_ACTIVE_ATTR]->toArray();
		}
		//装备属性，用户装备时才能累加，1 for master, 2 for all
		$userTitle = EnUser::getUserObj($uid)->getTitle();
		if (in_array($userTitle, $stylishObj->getActiveTitle())
		&& ($titleConf[$userTitle][StylishDef::TITLE_EQUIP_TYPE] == 2
		|| $titleConf[$userTitle][StylishDef::TITLE_EQUIP_TYPE] == 1
		&& HeroUtil::isMasterHtid($htid)))
		{
			Logger::trace('EnStylish add equip attr');
			$arrAttr[] = $titleConf[$userTitle][StylishDef::TITLE_EQUIP_ATTR]->toArray();
		}
		$arrAttr = Util::arrayAdd2V($arrAttr);
		$arrAttr = HeroUtil::adaptAttr($arrAttr);
		Logger::info('EnStylish getAddAttr:%s', $arrAttr);
		return $arrAttr;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */