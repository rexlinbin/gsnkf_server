<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroUtil.class.php 202860 2015-10-16 12:53:44Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/HeroUtil.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2015-10-16 12:53:44 +0000 (Fri, 16 Oct 2015) $
 * @version $Revision: 202860 $
 * @brief 
 *  
 **/



class HeroUtil
{
	public static function isHero($hid)
	{
		return $hid > 10000000;
	}
	
	/**
	 * 武将htid的范围  （10000， 1000000）  一万  到  一百万
	 * 怪物htid的范围 （ 1000000， 10000000） 一百万  到  一千万
	 * hid 的范围  （10010000，...) 一千零一万  （这里留了一个一万是为了竞技场守卫留着的）
	 * @param int $htid
	 * @return boolean
	 */
	public static function isHeroByHtid($htid)
	{
		return ($htid > 10000) && ($htid < 1000000);				
	}
	
	public static function isMasterHtid($htid)
	{
		return Creature::getCreatureConf($htid, CreatureAttr::CAN_BE_RESOLVED) == 0;
	}
	

	/**
	 * 检查是否为有效的htid
	 * @param int $htid
	 */
	public static function checkHtid($htid)
	{
		if (isset(btstore_get()->HEROES[$htid]))
		{
			return true;
		}
		return false;
	}

	/**
	 * 返回htid最开始时从哪个baseHtid进阶过来的(只支持hero，不支持monster)
	 * @param int $htid
	 */
	public static function getBaseHtid($htid)
	{
		$baseHtid = $htid;
		do
		{
			$htid = $baseHtid;
			$baseHtid = Creature::getHeroConf($htid, CreatureAttr::BASE_HTID);
		}while($baseHtid != $htid);
		
		return $baseHtid;
	}
	
	public static function getAwakeAbilityConf($id)
	{
		if( !isset(btstore_get()->AWAKE_ABILITY[$id]) )
		{
			throw new ConfigException('not found awake ability:%d', $id);
		}
		return btstore_get()->AWAKE_ABILITY[$id]->toArray();
	}
	
	public static function getAwakeAbilitySkillBuff($id)
	{
		$conf	=	self::getAwakeAbilityConf($id);
		if(isset($conf['arrAttrId']))
		{
			unset($conf['arrAttrId']);
		}
		if(isset($conf['arrAttrValue']))
		{
			unset($conf['arrAttrValue']);
		}
		if(isset($conf['arrAddAttrForFmt']))
		{
			unset($conf['arrAddAttrForFmt']);
		}
		if(count($conf) != 3)
		{
			throw new FakeException('AwakeAbilitySkillBuff config size is not 3.');
		}
		return $conf;
	}
	
	/**
	 * 系统支持的最大等级
	 * @return number
	 */
	public static function getMaxLevel($htid)
	{
	    $maxLevel    =    Creature::getHeroConf($htid, CreatureAttr::MAX_ENFORCE_LV);
	    if(!empty($maxLevel) && ($maxLevel < UserConf::MAX_LEVEL))
	    {
	        return $maxLevel;
	    }
		return UserConf::MAX_LEVEL;
	}
	
	/**
	 * 
	 * @param array $arrDress
	 * [
	 *     
	 * ]
	 * @return array
	 * [
	 *     pos=>dress_tmpl_id
	 * ]
	 */
	public static function simplifyDressInfo($arrEquipInfo)
	{
	    Logger::trace('simplifyDressInfo %s.',$arrEquipInfo);
	    if(!isset($arrEquipInfo[HeroDef::EQUIP_DRESS]))
	    {
	        return array();
	    }
	    $ret = array();
	    foreach($arrEquipInfo[HeroDef::EQUIP_DRESS] as $pos => $dressInfo)
	    {
	        if(empty($dressInfo))
	        {
	            continue;
	        }
	        $dressTmplId = $dressInfo['item_template_id'];
	        $ret[$pos] = $dressTmplId;
	    }
	    return $ret;
	}
	
	/**
	 * 将武将属性数组中的数字key（配置中属性id） 转换成 battle模块使用的字符串key
	 * @param array $arrAttr
	 */
	public static function adaptAttr($arrAttr)
	{
		$newArr = array();
		foreach($arrAttr as $key => $value)
		{
			if( !isset(PropertyKey::$MAP_CONF[$key]) )
			{
			    if(is_int($key) == FALSE)
			    {
			        Logger::trace('key %s is not int',$key);
			    }
				throw new ConfigException('invalid attrId:%d', $key);
			}
			$newArr[ PropertyKey::$MAP_CONF[$key] ] = $value;
		}
		return $newArr;
	}
	
	
	public static function adaptAttrReverse( $arrAttr )
	{
		$newArr = array();
		$mapConfReverse = array_flip( PropertyKey::$MAP_CONF );
		foreach($arrAttr as $key => $value)
		{
			if( !isset($mapConfReverse[$key]) )
			{
				if(is_string($key) == FALSE)
				{
					Logger::trace('key %s is not string',$key);
				}
				throw new ConfigException('invalid attrName:%d', $key);
			}
			$newArr[ $mapConfReverse[$key] ] = $value;
		}
		return $newArr;
	}
	

	public static function getSetEquipFunc($type)
	{
        return "HeroObj::setEquipByPos";
// 		switch ($type)
// 		{
// 			case HeroDef::EQUIP_ARMING:
// 				return 'HeroObj::setArmingByPos';
// 			case HeroDef::EQUIP_SKILL_BOOK:
// 				return 'HeroObj::setSbByPos';
// 			case HeroDef::EQUIP_TREASURE:
// 			    return 'HeroObj::setTreasureByPos';
// 			case HeroDef::EQUIP_DRESS:
// 			    return 'HeroObj::setDressByPos';
// 			case HeroDef::EQUIP_FIGHTSOUL:
// 			    return 'HeroObj::setFightSoulByPos';
// 			case HeroDef::EQUIP_GODWEAPON:
// 			    return 'HeroObj::setGodWeaponByPos';
// 			case HeroDef::EQUIP_POCKET:
// 			    return 'HeroObj::setPocketByPos';
// 			default:
// 				throw new InterException('invalid equip type:%s', $type);
// 		}
	}
	
	/**
	 * 
	 * @param unknown_type $uid
	 * @param unknown_type $num
	 */	
	public static function getHeroesWithFiveStar($uid,$num=PHP_INT_MAX)
	{
	    $htidWithFiveStar    =    array();
	    $fiveStarHeroes    =    btstore_get()->FIVESTARHERO->toArray();
	    $htids    =    $fiveStarHeroes;
	    $data    =    new CData();
	    $arrHero = self::getUnusedHeroesWithFiveStar($uid);
	    if(count($arrHero) >= $num)
	    {
	        $arrHids    =    Util::arrayIndexCol($arrHero, 'hid', 'htid');
	        return $arrHids;
	    }
	    while(count($htids) > 0)
	    {
	        $fetchSize    =    min(array(count($htids),CData::MAX_FETCH_SIZE));
	        $fetchHtids    =    array_slice($htids, 0, $fetchSize);
	        $htids    =    array_slice($htids, $fetchSize);
	        $tmpHeroes   =  HeroDao::getPartHeroesByHtids($uid, $fetchHtids, $num - count($arrHero));
	        $arrHero    =    array_merge($arrHero,$tmpHeroes);
	        if(count($arrHero) >= $num)
	        {
	            break;
	        } 
	    }
	    $arrHids    =    Util::arrayIndexCol($arrHero, 'hid', 'htid');
	    return $arrHids;
	}
	
	private static function getUnusedHeroesWithFiveStar($uid)
	{
	    $userObj = EnUser::getUserObj($uid);
	    $heroes = $userObj->getAllUnusedHero();
	    $arrHero = array();
	    foreach($heroes as $hid => $heroInfo)
	    {
	        $htid = $heroInfo['htid'];
	        if(Creature::getHeroConf($htid, CreatureAttr::STAR_LEVEL) < 5)
	        {
	            continue;
	        }
	        $arrHero[] = array('hid'=>$hid,'htid'=>$heroInfo['htid']);
	    }
	    return $arrHero;
	}
	
	
	public static function isFiveStarHero($htid)
	{
	    $fiveStarHeroes    =    btstore_get()->FIVESTARHERO->toArray();
	    if(in_array($htid, $fiveStarHeroes))
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	public static function getHtidByHid($hid)
	{
	    $ret    =    HeroDao::getByArrHid(array($hid), array('htid'), true);
	    if(empty($ret) || (empty($ret[$hid])))
	    {
	        return 0;
	    }
	    return $ret[$hid]['htid'];
	}
	
	/**
	 * 
	 * @param array $arrHid
	 * @param array $arrField
	 * @param bool $noCache
	 * @return array
	 * [
	 *     hid=>heroinfo
	 * ]
	 */
	public static function getArrHero($arrHid, $arrField, $noCache=false)
	{
	    $ret = HeroDao::getByArrHid($arrHid, $arrField, $noCache);
	    return $ret;
	}

    /**
     * 根据丹药数量，计算某种属性加成
     * @param $index int 页数(策划Pill表id)
     * @param $num int 丹药个数
     * @return array
     */
    public static function calAddAttrByPillNum($index, $num)
    {
        $arrAttr = array();
        for($i = 1; $i <= $num; $i++)
        {
            $arrAttrOfEach = self::getEachAddAttrByOrder($index, $i);
            foreach($arrAttrOfEach as $attrId => $attrValue)
            {
                if(!isset($arrAttr[$attrId]))
                {
                    $arrAttr[$attrId] = $attrValue;
                }
                else
                {
                    $arrAttr[$attrId] += $attrValue;
                }
            }
        }
        return $arrAttr;
    }

    /**
     * 计算某一个丹药提供的加成
     * @param $index
     * @param $order int 吃丹药的顺序，从1到最大限制
     * @return array
     * @throws
     */
    public static function getEachAddAttrByOrder($index, $order)
    {
        $arrAttrOfEach = array();
        $pillConf = btstore_get()->PILL[$index];
        $pillAttop = $pillConf[PillDef::PILL_ATTOP];    //表示第一次服用丹药增加的属性
        $pillAtted = $pillConf[PillDef::PILL_ATTED];    //表示每下一次服用丹药降低的属性
        $pillEdNumber = $pillConf[PillDef::ED_NUMBER];  //表示服用丹药达到该数量后，继续服用丹药属性不会减少
        $order = ($order >= $pillEdNumber) ? $pillEdNumber : $order;
        if($order < 1)
        {
            Logger::warning("order:%d smaller than zero", $order);
            $order = 1;
        }
        foreach($pillAttop as $attrId => $attrValue)
        {
            $attrValue -= $pillAtted[$attrId] * ($order - 1);
            if($attrValue < 0)
            {
                Logger::warning("attrValue:%d smaller than zero", $attrValue);
                $attrValue = 0;
            }
            if(!isset($arrAttrOfEach[$attrId]))
            {
                $arrAttrOfEach[$attrId] = $attrValue;
            }
            else
            {
                $arrAttrOfEach[$attrId] += $attrValue;
            }
        }

        return $arrAttrOfEach;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */