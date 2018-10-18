<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnUnion.class.php 241838 2016-05-10 07:35:30Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/union/EnUnion.class.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-05-10 07:35:30 +0000 (Tue, 10 May 2016) $
 * @version $Revision: 241838 $
 * @brief 
 *  
 **/
class EnUnion
{
	public static function getAddUnion($uid)
	{
		$conf = btstore_get()->UNION_FATE;
		
		$addUnion = array();
		$union = UnionObj::getInstance($uid);
		$fateLists = $union->getFateLists();
		foreach ($fateLists as $id => $list)
		{
			$arrNeed = $conf[$id][UnionDef::NEED_ARR]->toArray();
			$diff = array_diff($arrNeed, $list);
			if (empty($diff)) 
			{
				$addUnion = array_merge($addUnion, $conf[$id][UnionDef::UNION_ID]->toArray());
			}
		}
		Logger::trace('fateLists:%s addUnion:%s', $fateLists, $addUnion);
		
		return $addUnion;
	}
	
	public static function getAddFunc($uid)
	{
		$conf = btstore_get()->UNION_LOYAL;
	
		$addFunc = array();
		$union = UnionObj::getInstance($uid);
		$loyalLists = $union->getLoyalLists();
		foreach ($loyalLists as $id => $list)
		{
			$arrNeed = $conf[$id][UnionDef::NEED_ARR]->toArray();
			$diff = array_diff($arrNeed, $list);
			if (empty($diff))
			{
				$addFunc[$conf[$id][UnionDef::TYPE]][] = $id;
			}
		}
		Logger::trace('loyalLists:%s addFunc:%s', $loyalLists, $addFunc);
	
		return $addFunc;
	}
	
	//暂时只支持数值型
	public static function getAddFuncByUnion($uid, $type)
	{
		$ret = 0;
		$conf = btstore_get()->UNION_LOYAL;
		$addFunc = self::getAddFunc($uid);
		if (!empty($addFunc[$type])) 
		{
			foreach ($addFunc[$type] as $id)
			{
				$ret += $conf[$id][UnionDef::NUM];
			}
		}
		Logger::trace('ret:%d', $ret);
		return $ret;
	}
	
	public static function getAddAttr($uid)
	{
		$hconf = btstore_get()->HEROES;
		$iconf = btstore_get()->ITEMS;
		
		$addAttr = array();
		$union = UnionObj::getInstance($uid);
		$fateLists = $union->getFateLists();
		$loyalLists = $union->getLoyalLists();
		$martialLists = $union->getMartialLists();
		foreach ($fateLists as $list)
		{
			foreach ($list as $tid)
			{
				if (isset($hconf[$tid])) 
				{
					$addAttr[] = $hconf[$tid][CreatureAttr::FATE_ATTR];
				}
				else 
				{
					$addAttr[] = $iconf[$tid][UnionDef::FATE_ATTR];
				}
			}
		}
		foreach ($loyalLists as $list)
		{
			foreach ($list as $tid)
			{
				$addAttr[] = $hconf[$tid][CreatureAttr::LOYAL_ATTR];
			}
		}
		$conf = btstore_get()->UNION_MARTIAL;
		foreach ($martialLists as $id => $list)
		{
			foreach ($list as $tid)
			{
				if (isset($hconf[$tid]))
				{
					$addAttr[] = $hconf[$tid][CreatureAttr::FATE_ATTR];
				}
				else
				{
					$addAttr[] = $iconf[$tid][UnionDef::FATE_ATTR];
				}
			}
			$arrNeed = $conf[$id][UnionDef::NEED_ARR]->toArray();
			$diff = array_diff($arrNeed, $list);
			if (empty($diff))
			{
				$addAttr[] = $conf[$id][UnionDef::ADD_ATTR]->toArray();
			}
		}
		
		Logger::trace('fateLists:%s loyalLists:%s martialLists:%s addAttr:%s', $fateLists, $loyalLists, $martialLists, $addAttr);
		
		$addAttr = Util::arrayAdd2V($addAttr);
		return $addAttr;
	}
	
	public static function getAddAttrByUnion($uid)
	{
		$addAttr = self::getAddAttr($uid);
		return HeroUtil::adaptAttr($addAttr);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */