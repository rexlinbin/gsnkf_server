<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChariotUtil.class.php 251596 2016-07-14 09:54:37Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chariot/ChariotUtil.class.php $
 * @author $Author: QingYao $(yaoqing@babeltime.com)
 * @date $Date: 2016-07-14 09:54:37 +0000 (Thu, 14 Jul 2016) $
 * @version $Revision: 251596 $
 * @brief 
 *  
 **/
class ChariotUtil
{
	public static function isChariotOpen($uid=0)
	{
		if (EnSwitch::isSwitchOpen(SwitchDef::CHARIOT,$uid) == false)
		{
			return false;
		}
		return true;
	}
	public static function getChariotConf($id)
	{
		return btstore_get()->ITEMS[$id]->toArray();
	}
	
	public static function getChariontNormalConf()
	{
		return btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_CHARIOT_POS_TYPE_LV]->toArray();
	}
	
	public static function getChariotSuitConf()
	{
		return btstore_get()->CHARIOTSUIT->toArray();
	}
	
	public static function getChariotType($id)
	{
		$conf=self::getChariotConf($id);
		return $conf[ItemDef::ITEM_ATTR_NAME_CHARIOT_TYPE];
	}
	
	public static function getChariotTypeBypos($pos)
	{
		$conf=self::getChariontNormalConf();
		foreach ($conf as $info)
		{
			if ($info[0]==$pos)
			{
				return $info[1];
			}
		}
		throw new FakeException('no type to chariot pos:%d',$pos);
	}
	
	public static function getChariotPosNeedLevel($pos)
	{
		$conf=self::getChariontNormalConf();
		foreach ($conf as $info)
		{
			if ($info[0]==$pos)
			{
				return $info[2];
			}
		}
		throw new FakeException('no level to chariot pos:%d',$pos);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */