<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroLogic.class.php 246659 2016-06-16 09:23:35Z MingTian $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/HeroLogic.class.php $
 * @author $Author: MingTian $(lanhongyu@babeltime.com)
 * @date $Date: 2016-06-16 09:23:35 +0000 (Thu, 16 Jun 2016) $
 * @version $Revision: 246659 $
 * @brief
 *
 **/



class HeroLogic
{


	public static function getHero($hid)
	{
		return HeroDao::getByHid($hid, HeroDef::$HERO_FIELDS);
	}
	
	public static function getArrHero($arrHid, $arrField=null, $noCache=false, $db = '')
	{
		if ($arrField==null)
		{
			$arrField = HeroDef::$HERO_FIELDS;
		}
		return HeroDao::getByArrHid($arrHid, $arrField, $noCache, $db);
	}
	
	public static function getAllUsedHeroByUid($uid)
	{
	    $ret = HeroDao::getArrHeroeByUid($uid, HeroDef::$HERO_FIELDS);
		return Util::arrayIndex($ret, "hid");
	}
	

	public static function getInitData($uid, $hid, $htid,$level=1)
	{
	    if($level == 1)
	    {
	        $soul    =    0;
	    }
	    else
	    {
	        $expTblId	= Creature::getCreatureConf($htid,CreatureAttr::EXP_ID);
	        $expTbl		= btstore_get()->EXP_TBL[$expTblId];
	        $soul        =    $expTbl[$level];
	    }
		$heroAttr = array(
				'hid' => $hid,
				'htid' => $htid,
				'uid' => $uid,
				'soul' => $soul,
				'evolve_level'=>0,
		        'level' => $level,
				'destiny' => 0, 
				'upgrade_time' => Util::getTime(),
				'delete_time' => 0,
				'va_hero' => array(
						HeroDef::VA_FIELD_TALENT => self::getInitTalentInfo(),
				        HeroDef::VA_FIELD_TRANSFER => 0,
                        HeroDef::VA_FIELD_PILL => array(),
				)
		);
		foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
		{
		    $heroAttr['va_hero'][$equipType] = self::getInitEquipInfo($equipType);
		}
		
		return $heroAttr;
		
	}
	
	
	public static function getInitEquipInfo($equipType)
	{
	    switch($equipType)
	    {
	        case HeroDef::EQUIP_ARMING:
	            return ArmDef::$ARM_NO_ARMING;
	            break;
	        case HeroDef::EQUIP_DRESS:
	            return ItemDef::$HERO_NO_DRESS;
	            break;
	        case HeroDef::EQUIP_TREASURE:
	            return TreasureDef::$TREASURE_NO_ARMING;
	            break;
            default:
                return array();
	    }
	}
	
	public static function getValidEquipPos($uid, $equipType)
	{
	    switch($equipType)
	    {
	        case HeroDef::EQUIP_ARMING:
	            return ArmDef::$ARM_NO_ARMING;
	            break;
	        case HeroDef::EQUIP_DRESS:
	            return ItemDef::$HERO_NO_DRESS;
	            break;
	        case HeroDef::EQUIP_TREASURE:
	            return TreasureDef::$TREASURE_NO_ARMING;
	            break;
	        case HeroDef::EQUIP_SKILL_BOOK:
	            return array();
	            break;
	        case HeroDef::EQUIP_FIGHTSOUL:
	            return self::getValidFightSoulPos($uid);
	            break;
	        case HeroDef::EQUIP_GODWEAPON:
	            return self::getValidGodWeaponPos($uid);
	            break;
	        case HeroDef::EQUIP_POCKET:
	            return self::getValidPocketPos($uid);
	            break;
	        case HeroDef::EQUIP_TALLY:
	           	return self::getValidTallyPos($uid);
	           	break;
	        default:
	            throw new FakeException('invalid equiptype %s',$equipType);
	    }
	}
	
	public static function getInitTalentInfo()
	{
	    return array(
	            HeroDef::VA_SUBFIELD_TALENT_CONFIRMED => array(),
	            HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM => array(),
	            HeroDef::VA_SUBFIELD_TALENT_SEALED => array(),
	            );
	}
	
	public static function addNewHero($uid, $hid, $htid, $arrField = null)
	{
		if ($arrField == null)
		{
			$arrField = array();
		}

		$heroAttr = self::getInitData($uid, $hid, $htid);
	
		foreach( $arrField as $key => $value )
		{
			if( isset($heroAttr[$key]) )
			{
				$heroAttr[$key] = $value;
			} 
			else
			{
				throw new InterException('invalid field:%s', $key);
			}
		}
		
		$hid = HeroDao::insertNewHero($heroAttr);
		
		return $heroAttr;
	}

	/**
	 * 扒装备放到背包 函数内部已经update hero和背包
	 * @param OtherHeroObj $hid
	 */
	public static function unEquipeHero($hid,$equipType = HeroDef::EQUIP_ARMING,$arrPos = array())
	{
	    $user    = EnUser::getUserObj();
	    $heroMng = $user->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if($heroObj == NULL)
	    {
	        throw new FakeException('unEquipHero heroobj %s is null.or is not equiped',$hid);
	    }
	    if($heroObj->isEquiped() == FALSE)
	    {
	        return 'nochange';
	    }
	    $arrHeroEquip = array();
	    switch($equipType)
	    {
	        case HeroDef::EQUIP_ALL:
	            foreach(HeroDef::$ALL_EQUIP_TYPE as $type)
	            {
	                $arrHeroEquip[$type] = $heroObj->getEquipByType($type);
	            }
	            break;
	        default:
	            $arrHeroEquip[$equipType] = $heroObj->getEquipByType($equipType);
	    }
	    $bag        =    BagManager::getInstance()->getBag();
	    foreach($arrHeroEquip as $type => $heroArms)
	    {
	        $setFunc	= HeroUtil::getSetEquipFunc($type);
	        foreach($heroArms as $posId=>$armId)
	        {
	            if($armId == ItemDef::ITEM_ID_NO_ITEM)
	            {
	                continue;
	            }
	            if(!empty($arrPos)  &&   (in_array($posId, $arrPos) == FALSE))
	            {
	                continue;
	            }
	            call_user_func_array(array($heroObj, $setFunc), array($type, ItemDef::ITEM_ID_NO_ITEM,$posId) );
	            $bag->addItem($armId,true);
	        }
	    }
	    $heroMng->update();
	    HeroLogic::refershFmtOnHeroChange($hid);
	    $bag->update();
	    return 'ok';
	}
	
	public static function changeEquip($fromHid,$toHid,$equipTypes,$arrPos = array())
	{
	    Logger::trace('changeEquip %s %s %s',$fromHid,$toHid,$equipTypes);
	    if($fromHid == $toHid)
	    {
	        throw new FakeException('changeEquip fatal error.fromhtid %d equal to tohid %d',$fromHid,$toHid);
	    }
	    $user = EnUser::getUserObj();
	    $heroMng = $user->getHeroManager();
	    $fromHero = $heroMng->getHeroObj($fromHid);
	    $toHero = $heroMng->getHeroObj($toHid);
	    $preFromHeroInfo = $fromHero->getHeroVaInfo();
	    $preToHeroInfo = $toHero->getHeroVaInfo();
	    $fromHtid    =    $user->getUnusedHeroHtid($fromHid);
	    $toHtid      =    $user->getUnusedHeroHtid($toHid);
	    if(!$fromHero->isEquiped() && (!$toHero->isEquiped()))
        {
            Logger::warning('changeEquip Error!no equip.fromheroinfo %s,toHeroinfo %s.',$preFromHeroInfo,$preToHeroInfo);
            return;
        }
	    //将unused hero初始化
	    if(!empty($fromHtid) && ($toHero->isEquiped()))
	    {
	        $heroMng->initHero($fromHid);
	    }
	    if(!empty($toHtid) && ($fromHero->isEquiped()))
	    {
	        $heroMng->initHero($toHid);
	    }
	    $change = FALSE;
	    foreach($equipTypes as $equipType)
	    {
	        //两个武将都没有装备
	        if(!$fromHero->isEquipedByType($equipType) &&
	                (!$toHero->isEquipedByType($equipType)))
	        {
	            continue;
	        }
	        $equipValidPos = self::getValidEquipPos($user->getUid(), $equipType);
	        Logger::trace('equipValidPos %s.',$equipValidPos);
	        foreach($equipValidPos as $posId=>$arrItemType)
	        {
	            if(!empty($arrPos) && (in_array($posId, $arrPos) == FALSE))
	            {
	                continue;
	            }
	            $fromItemId    =    $fromHero->getEquipByPos($equipType, $posId);
	            $toItemId    =    $toHero->getEquipByPos($equipType, $posId);
	            if(empty($fromItemId) && (empty($toItemId)))
	            {
	                continue;
	            }
	            $change = TRUE;
	            $setFunc	= HeroUtil::getSetEquipFunc($equipType);
	            call_user_func_array(array($fromHero, $setFunc), array($equipType, $toItemId, $posId) );
	            call_user_func_array(array($toHero, $setFunc), array($equipType, $fromItemId, $posId) );
	        }
	    }
	    if(!$change)
	    {
	        Logger::warning('error!!changeEquip,but no change!current pos %s,equipType %s,fromheroinfo %s,toheroinfo %s.',
	                $arrPos,$equipType,$preFromHeroInfo,$preToHeroInfo);
	        return;
	    }
        //保存fromhero信息 到DB
        $batchData    =    new BatchData();
        $fromHeroInfo    =    $fromHero->getHeroVaInfo();
        if($fromHeroInfo != $preFromHeroInfo)
        {
            $fromHeroData    =    $batchData->newData();
            $fromHeroData->update(HeroDao::TBL_HERO)
                        ->set($fromHeroInfo)
                        ->where(array('hid','=',$fromHid))
                        ->query();
        }        
        //保存tohero信息 到DB
        $toHeroInfo    =    $toHero->getHeroVaInfo();
        if($toHeroInfo != $preToHeroInfo)
        {
            $toHeroData    =    $batchData->newData();
            $toHeroData->update(HeroDao::TBL_HERO)
                    ->set($toHeroInfo)
                    ->where(array('hid','=',$toHid))
                    ->query();
        }
        $batchData->query();
	    $heroMng->updateSession();
	    EnUser::getUserObj()->modifyBattleData();
	}
	
	public static function getValidFightSoulPos($uid)
	{
	    $userObj = Enuser::getUserObj($uid);
	    $userLv = $userObj->getLevel();
	    $arrOpenedPos = array();
	    $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_FIGHTSOUL_POSOPEN];
	    foreach($conf as $pos => $needLv)
	    {
	        if($userLv >= $needLv)
	        {
	            $arrOpenedPos[$pos] = array();
	        }
	    }
	    return $arrOpenedPos;
	}
	
	public static function getValidPocketPos($uid)
	{
	    $userObj = EnUser::getUserObj($uid);
	    $userLv = $userObj->getLevel();
	    $arrOpenedPos = array();
	    $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_POCKET_POS_OPENLV];
	    foreach($conf as $pos => $needLv)
	    {
	        if($userLv >= $needLv)
	        {
	            $arrOpenedPos[$pos] = array();
	        }
	    }
	    return $arrOpenedPos;
	}
	
	public static function getValidTallyPos($uid)
	{
		$userObj = EnUser::getUserObj($uid);
	    $userLv = $userObj->getLevel();
	    $arrOpenedPos = array();
	    $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TALLY_POS_OPENLV];
	    foreach($conf as $pos => $needLv)
	    {
	        if($userLv >= $needLv)
	        {
	            $arrOpenedPos[$pos] = array();
	        }
	    }
	    return $arrOpenedPos;
	}
	
	public static function getValidGodWeaponPos($uid)
	{
	    $userObj = Enuser::getUserObj($uid);
	    $userLv = $userObj->getLevel();
	    $arrOpenedPos = array();
	    $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GODWEAPON_POS_OPENLV];
	    foreach($conf as $pos => $needLv)
	    {
	        if($userLv >= $needLv)
	        {
	            $arrOpenedPos[$pos] = array();
	        }
	    }
	    return $arrOpenedPos;
	}
	
	public static function getHeroNumInBook($uid)
	{
	    $bookInfo = self::getHeroBookInfo($uid);
	    if(!empty($bookInfo))
	    {
	        return count($bookInfo[HeroBookDao::FIELD_VA_BOOK]['hero']);
	    }
	    return 0;
	}
	
	public static function getHeroBookInfo($uid)
	{
	    $bookInfo = array();
	    if($uid == RPCContext::getInstance()->getUid())
	    {
	        $bookInfo = RPCContext::getInstance()->getSession(HeroBookDef::SESSION_NAME);
	    }
	    Logger::trace('getHeroBookInfo %s.',$bookInfo);
	    if(empty($bookInfo))
	    {
	        $bookInfo = HeroBookDao::getHeroBook($uid);
	        if(!empty($bookInfo) && ($uid == RPCContext::getInstance()->getUid()))
	        {
	            RPCContext::getInstance()->setSession(HeroBookDef::SESSION_NAME, $bookInfo);
	        }
	    }
	    Logger::trace('getHeroBookInfo %s.',$bookInfo);
	    return $bookInfo;
	}
	
	public static function updateHeroBook($uid,$arrHtid)
	{
	    Logger::trace('updateHeroBook %s.',$arrHtid);
	    try {
    	    $bookInfo = self::getHeroBookInfo($uid);
    	    if(empty($bookInfo))
    	    {
    	        $bookInfo = array(
                    'uid'=>$uid,
                    HeroBookDao::FIELD_VA_BOOK=> array('hero'=>array())
                    );
    	    }
    	    $hasHtids = $bookInfo[HeroBookDao::FIELD_VA_BOOK]['hero'];
    	    $changed = FALSE;
    	    $bookHeroes = btstore_get()->SHOWS[ShowDef::HERO_SHOW]->toArray();
    	    foreach($arrHtid as $index => $htid)
    	    {
    	        if(!in_array($htid, $hasHtids) && (in_array($htid, $bookHeroes)))
    	        {
    	            $changed = TRUE;
    	            $hasHtids[] = $htid;
    	        }
    	    }
    	    if($changed)
    	    {
    	        $bookInfo[HeroBookDao::FIELD_VA_BOOK]['hero'] = $hasHtids;
    	        EnAchieve::updateHeroTypes($uid, count($hasHtids));
        	    HeroBookDao::updateHeroBook($uid, $bookInfo);
        	    RPCContext::getInstance()->setSession(HeroBookDef::SESSION_NAME, $bookInfo);
    	    }
	    }catch(Exception $e)
	    {
	        Logger::warning('initHeroBook fail.reason %s.',$e->getMessage());
	        throw $e;
	    }
	}
	
	public static function getEvLvByLevel($htid,$level)
	{
	    $newEvLv = ceil(($level - Creature::getHeroConf($htid, CreatureAttr::EVOLVE_INIT_LEVEL))
	            /Creature::getHeroConf($htid, CreatureAttr::EVOLVE_GAP_LEVEL));
	    $newEvLv = intval($newEvLv);
	    return $newEvLv;
	}
	
	
	/**
	 * 将背包的东西装备到武将身上
	 * @param int $type
	 * @param int $hid
	 * @param int $pos
	 * @param int $itemId
	 * @throws FakeException
	 * @throws InterException
	 */
	public static function addEquip ($type, $hid, $pos, $itemId)
	{
	    Logger::trace('addEquip type %d hid %d pos %d itemid %d',$type,$hid,$pos,$itemId);
	    $hid	= intval($hid);
	    $pos	= intval($pos);
	    $itemId	= intval($itemId);
	    $userObj = EnUser::getUserObj();
	    $uid	=	RPCContext::getInstance()->getUid();
	    if(EnFormation::isHidInFormation($hid, $uid) == FALSE)
	    {
	        throw new FakeException('can not equip hero which is not in formation.');
	    }
	    //1.判断此物品是否存在   是否在背包中
	    $item 	=	ItemManager::getInstance()->getItem($itemId);
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    $bag = BagManager::getInstance()->getBag();
	    if ($bag->getGidByItemId($itemId) == BagDef::INVALID_GRID_ID)
	    {
	        throw new FakeException('not found itemId:%d in bag', $itemId);
	    }
	    //2.调用相应类别物品的addEquip方法   判断是否能够添加此物品  如果可以将物品添加到hero的属性中
	    $heroObj	= $userObj->getHeroManager()->getHeroObj($hid);
	    $setFunc	= HeroUtil::getSetEquipFunc($type);
	    $oldItemId	= $heroObj->getEquipByPos($type, $pos);
	    if($oldItemId == $itemId)
	    {
	        Logger::warning('olditemid %d is equal to addeditem %d.',$oldItemId,$itemId);
	        return;
	    }
	    call_user_func_array(array($heroObj, $setFunc), array($type, $itemId,$pos) );
	    Logger::trace('after equip equipinfo %s.',$heroObj->getEquipByType($type));
	    //3.将物品在背包中删除	将原来的物品加入到背包中
	    if($bag->removeItem($itemId) == FALSE)
	    {
	        throw new FakeException('no such item %d in bag.remove failed.',$itemId);
	    }
	    if ($oldItemId != BagDef::ITEM_ID_NO_ITEM && !$bag->addItem($oldItemId,true) )
	    {
	        throw new InterException('add the oldItem:%d to bag failed', $oldItemId);
	    }
	    HeroLogic::refershFmtOnHeroChange($hid);
	    $bag->update();
	    $userObj->update();
	}
	
	public static function removeEquip($type, $hid, $pos)
	{
		//格式化输入
		$hid = intval($hid);
		$pos = intval($pos);
		$userObj = EnUser::getUserObj();
		$uid	=	RPCContext::getInstance()->getUid();
		if(EnFormation::isHidInFormation($hid, $uid) == FALSE)
		{
		    throw new FakeException('can not unequip hero which is not in formation.');
		}
		$bag = BagManager::getInstance()->getBag();
		if(!isset(HeroDef::$EQUIPTYPE_TO_BAGNAME[$type]))
		{
		    throw new FakeException('no such equiptype %s.',$type);
		}
		$bagName = HeroDef::$EQUIPTYPE_TO_BAGNAME[$type];
		if($bag->isFull($bagName))
		{
		    throw new FakeException('bag %s is full.can not remove equip.',$bagName);
		}
		//1.判断物品是否存在
		$hero = $userObj->getHeroManager()->getHeroObj($hid);	
		$itemId = $hero->getEquipByPos($type, $pos);
		if ( $itemId == BagDef::ITEM_ID_NO_ITEM )
		{
			throw new FakeException('not found item in pos:%d', $pos);
		}		
		//2.调用相应类别物品的addEquip方法  将ITEM_ID_NO_ITEM加入背包中    
		$setFunc = HeroUtil::getSetEquipFunc($type);
		call_user_func_array(array($hero, $setFunc), array($type, BagDef::ITEM_ID_NO_ITEM, $pos));
		//3.将移除的物品加入到背包中
		if ( $bag->addItem($itemId) == false )
		{
			throw new InterException('add the itemId:%d to bag failed', $itemId);
		}	
		HeroLogic::refershFmtOnHeroChange($hid);
		$userObj->update();
		$bag->update();		
	}
	
	public static function refershFmtOnHeroChange($hid)
	{
	    $uid	=	RPCContext::getInstance()->getUid();
	    if(EnFormation::isHidInFormation($hid, $uid)
			|| EnFormation::isHidInAttrExtra($hid, $uid))
	    {
	        EnUser::getUserObj()->modifyBattleData();
	    }
	}
	
	public static function getHeroQualityByHtid($htid)
	{
	    $quality = Creature::getHeroConf($htid, CreatureAttr::QUALITY);
	    return $quality;
	}
	
	public static function canBeEnforced($hid)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::HEROFORGE) == FALSE)
	    {
	        throw new FakeException('switch heroforge is not open!');
	    }
	    if(EnSwitch::isSwitchOpen(SwitchDef::HEROENFORCE) == FALSE)
	    {
	        throw new FakeException('switch heroenforce is not open!');
	    }
	    $heroObj = EnUser::getUserObj()->getHeroManager()->getHeroObj($hid);
	    if($heroObj->isMasterHero())
	    {
	        throw new FakeException('master hero: %d can not be enforced.', $heroObj->getHid());
	    }
	    if($heroObj->getLevel() >= $heroObj->getMaxEnforceLevel())
	    {
	        throw new FakeException('can not enforce hero %s.this hero has got to max level.',$hid);
	    }
	    return TRUE;
	}
	
	public static function canEvolve($hid)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::HEROFORGE) == FALSE)
	    {
	        throw new FakeException('switch heroforge is not open!');
	    }
	    if(EnSwitch::isSwitchOpen(SwitchDef::HEROEVOLVE) == FALSE)
	    {
	        throw new FakeException('switch herotransfer is not open!');
	    }
	    $heroObj = EnUser::getUserObj()->getHeroManager()->getHeroObj($hid);
	    $evolveTblId = HeroLogic::getEvolveTbl($heroObj->getHtid(), $heroObj->getEvolveLv());
	    if(empty($evolveTblId))
	    {
	        throw new FakeException('no evolve table ,can not evolve!evolvetbl %s.',$evolveTblId);
	    }
	    $needStar = 0;//主角卡牌进化需要名将星数
	    // 		if ($heroObj->isMasterHero())//不能被分解--主角英雄
	    // 		{
	    // 		    $needStar	=	intval(btstore_get()->HERO_CONVERT[$evolveTblId]['needBeauty']);
	    // 			$gotStar	=	EnStar::getAllStarFavor();//当前用户的所有名将的星数之和
	    // 			if ( $gotStar < $needStar )
	        // 			{
	    // 				throw new FakeException('user has not enough star.can not evolve master hero.');
	    // 			}
	    // 		}
	    //检查等级是否足够
	    $needLv = btstore_get()->HERO_CONVERT[$evolveTblId]['needLevel'];
	    if ($heroObj->getLevel() < $needLv)
	    {
	        throw new FakeException(' hero: %d level %s unsatisfied the evolve need level %s.',$hid,$heroObj->getLevel(),$needLv);
	    }
	    //检查是否进化到头了
	    $htid = $heroObj->getHtid();
	    if (empty( btstore_get()->HERO_CONVERT[$evolveTblId]['toHtid']))
	    {
	        throw new ConfigException('fail to evolve hero: %d,htid is %s, has reached holly status! ',$hid,$htid);
	    }
	    return $needStar;
	}
	
	
	public static function consumeHero($arrHero,$needHero)
	{
	    if(empty($needHero))
	    {
	        return array();
	    }
	    $needHero	=	Util::arrayIndex($needHero, 0);
	    $conHeroes	=	array();//实际消耗的武将
	    foreach($arrHero as $heroid)
	    {
	        $conHeroObj	=	EnUser::getUserObj()->getHeroManager()->getHeroObj($heroid);
	        if(!isset($needHero[$conHeroObj->getHtid()]))
	        {
	            continue;
	        }
	        $needLevel	=	$needHero[$conHeroObj->getHtid()][1];
	        $needNum	=	$needHero[$conHeroObj->getHtid()][2];
	        if($conHeroObj->getLevel() > $needLevel || ($needNum<1))
	        {
	            continue;
	        }
	        //武将满足要求  添加消耗武将
	        $conHeroes = array_merge($conHeroes , array($heroid));
	        $needHero[$conHeroObj->getHtid()][2]--;
	        if($needHero[$conHeroObj->getHtid()][2] < 1)
	        {
	            unset($needHero[$conHeroObj->getHtid()]);
	        }
	    }
	    if(!empty($needHero))
	    {
	        throw new FakeException('need %s.offer %s.not offer enough needed hero.',$needHero,$arrHero);
	    }
	    $heroMng = EnUser::getUserObj()->getHeroManager();
	    foreach($conHeroes as $hid)
	    {
	        $heroMng->delHeroByHid($hid);
	    }
	    return $conHeroes;
	}
	
	public static function consumeItem($arrItem,$needItems)
	{
	    $bag = BagManager::getInstance()->getBag();
	    $arrDeleteItem = array();
	    foreach($arrItem as $itemId => $num)
	    {
	        $itemObj = ItemManager::getInstance()->getItem($itemId);
	        if(empty($itemObj))
	        {
	            throw new FakeException('no such item %s.',$itemId);
	        }
	        $itemTmplId = $itemObj->getItemTemplateID();
	        if($itemObj->canStackable() == FALSE && ($num > 1))
	        {
	            throw new FakeException('item %s tmplid %d num %d is not stackable.',$itemId,$itemTmplId,$num);
	        }
	        if($itemObj->canStackable() == TRUE && (isset($needItems[$itemTmplId])))
	        {
	            if($bag->deleteItembyTemplateID($itemTmplId, $needItems[$itemTmplId]) == FALSE)
	            {
	                throw new FakeException('delete item %d item num %d failed.',$itemTmplId,$needItems[$itemTmplId]);
	            }
	            unset($needItems[$itemTmplId]);
	        }
	        if(isset($needItems[$itemTmplId]) && ($needItems[$itemTmplId] > 0))
	        {
	            $deleteNum = $num;
	            if($deleteNum > $needItems[$itemTmplId])
	            {
	                $deleteNum = $needItems[$itemTmplId];
	            }
	            $needItems[$itemTmplId] -= $deleteNum;
	            if($bag->decreaseItem($itemId, $deleteNum) == FALSE)
	            {
	                throw new FakeException('decrease item %d itemtmpid %d num %d failed.',$itemId,$itemTmplId,$deleteNum);
	            }
	            if(!isset($arrDeleteItem[$itemId]))
	            {
	                $arrDeleteItem[$itemId] = 0;
	            }
	            $arrDeleteItem[$itemId] += $deleteNum;
	            if($needItems[$itemTmplId] == 0)
	            {
	                unset($needItems[$itemTmplId]);
	            }
	        }
	    }
	    if(!empty($needItems))
	    {
	        throw new FakeException('provided arritem %s.need items %s.delete item %s',
	                $arrItem,$needItems,$arrDeleteItem);
	    }
	    return $arrDeleteItem;
	}
	
	
	/**
	 *
	 * @param int $hid              需要进化的武将hid
	 * @param array $arrHero        前端提供的hids
	 * @throws FakeException
	 */
	public static function consumeHeroOnHeroEvolve($hid,$arrHero)
	{
	    $heroObj	=	EnUser::getUserObj()->getHeroManager()->getHeroObj($hid);
	    $evolveTblIds    =    $heroObj->getCreatureConf($heroObj->getHtid(), CreatureAttr::EVOLVE_TBLID);
	    $evolveTblId    =    $evolveTblIds[$heroObj->getEvolveLv()];
	    $needHero	=	btstore_get()->HERO_CONVERT[$evolveTblId]['arrNeedHero']->toArray();
	    return self::consumeHero($arrHero, $needHero);
	}
	
	public static function consumeItemOnHeroEvolve($hid,$arrItem)
	{
	    $heroObj	=	EnUser::getUserObj()->getHeroManager()->getHeroObj($hid);
	    $evolveTblIds    =    $heroObj->getCreatureConf($heroObj->getHtid(), CreatureAttr::EVOLVE_TBLID);
	    $evolveTblId    =    $evolveTblIds[$heroObj->getEvolveLv()];
	    $needItems	=	btstore_get()->HERO_CONVERT[$evolveTblId]['arrNeedItem']->toArray();
	    return self::consumeItem($arrItem, $needItems);
	}
	
	/**
	 *
	 * @param array $removeNeed {itemTmpId=>num}
	 * @param array $provided	{itemid1,itemid2,......}
	 */
	public static function consumeItemOnRemoveSb($removeNeed,$provided)
	{
	    foreach($removeNeed as $itemTmpId => $itemNum)
	    {
	        $itemTplId	=	$itemTmpId;
	        $needNum	=	$itemNum;
	        break;
	    }
	    if(empty($itemTplId))
	    {
	        throw new FakeException('error!!!!no suply needed items.');
	    }
	    $providedNum	=	0;
	    $consumeItem	=	array();//实际消耗的物品id数组
	    foreach($provided as $itemId)
	    {
	        $itemObj	=	ItemManager::getInstance()->getItem($itemId);
	        if($itemObj->getItemTemplateID() == $itemTplId)
	        {
	            $consumeItem = $consumeItem + array($itemId);
	            $providedNum ++;
	        }
	        if($providedNum == $needNum)
	        {
	            break;
	        }
	    }
	    if($providedNum < $needNum)
	    {
	        throw new FakeException('provided item is not enough.');
	    }
	    $bag	=	BagManager::getInstance()->getBag();
	    foreach($consumeItem as $itemId)
	    {
	        $bag->deleteItem($itemId);
	    }
	    return $consumeItem;
	}
	/**
	 * 武将htid从进阶等级$evolveLv进阶到$evolveLv+1的进阶表
	 * @param int $htid
	 * @param int $evolveLv
	 */
	public static function getEvolveTbl($htid,$evolveLv)
	{
	    $evolveTbls = Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID);
	    if(!isset($evolveTbls[$evolveLv]))
	    {
	        return 0;
	    }
	    return $evolveTbls[$evolveLv];
	}
	
	public static function getMaxEvolveLv($htid)
	{
	    $evolveTbls = Creature::getHeroConf($htid, CreatureAttr::EVOLVE_TBLID);
	    return count($evolveTbls);
	}
	
	
	public static function equipBestEquip($hid,$equipType,$uid)
	{
	    Logger::trace('hero.equipBestArming start.params:%s.',$hid);
	    $ret = array();
	    if(EnFormation::isHidInFormation($hid, $uid) == FALSE)
	    {
	        throw new FakeException('can not equip hero which is not in formation.');
	    }
	    if($equipType == HeroDef::EQUIP_FIGHTSOUL)
	    {
	        return self::equipBestFightSoul($hid, $uid);
	    }
	    $heroObj = EnUser::getUserObj($uid)->getHeroManager()->getHeroObj($hid);
	    $bag = BagManager::getInstance()->getBag($uid);
	    $arrEquip = $heroObj->getEquipObjByType($equipType);
	    $bestEquip = $bag->getBestItems(HeroDef::$EQUIPTYPE_TO_ITEMTYPE[$equipType]);
	    $equipSubTypeOfPos = self::getEquipSubTypeOfPos($equipType,$arrEquip,$uid);
	    foreach($equipSubTypeOfPos as $posId => $arrItemType)
	    {
	        $oldItem = BagDef::ITEM_ID_NO_ITEM;
	        $quality = 0;
	        $level = -1;
	        if(!empty($arrEquip[$posId]))
	        {
	            $oldItem = $arrEquip[$posId]->getItemID();
	            $quality = $arrEquip[$posId]->getScore();
	            $level = $arrEquip[$posId]->getLevel();
	        }
	        $bagItem = NULL;
	        $tmpQuality = 0;
	        $tmpSubType = NULL;
	        $tmpLevel = -1;
	        foreach($bestEquip as $itemSubType => $bestItemId)
	        {
	            if(in_array($itemSubType, $arrItemType) == FALSE)
	            {
	                continue;
	            }
	            $bagItem = ItemManager::getInstance()->getItem($bestItemId);
	            if($bagItem->getScore() > $tmpQuality 
	                    || ($bagItem->getScore() == $tmpQuality 
	                            && ($bagItem->getLevel() > $tmpLevel)))
	            {
	                $tmpSubType = $itemSubType;
	                $tmpQuality = $bagItem->getScore();
	                $tmpLevel = $bagItem->getLevel();
	            }
	        } 
	        if($tmpSubType === NULL)
	        {
	            continue;
	        }
	        $bagItem = ItemManager::getInstance()->getItem($bestEquip[$tmpSubType]);
	        if($bagItem->getScore() > $quality || 
	                ($bagItem->getScore() == $quality 
	                        && ($bagItem->getLevel() > $level)))
	        {
	            unset($bestEquip[$tmpSubType]);
	            $newItem    =   $bagItem->getItemID();
	            $bag->removeItem($newItem);
	            $setFunc	= HeroUtil::getSetEquipFunc($equipType);
	            call_user_func_array(array($heroObj, $setFunc), array($equipType, $newItem,$posId) );
	            $ret[$equipType][$posId]    =    $bagItem->itemInfo();
	            if ($oldItem != BagDef::ITEM_ID_NO_ITEM && !$bag->addItem($oldItem,TRUE) )
	            {
	                throw new InterException('add the oldItem:%d to bag failed', $oldItem);
	            }
	        }
	    }
	    return $ret;
	}
	/**
	 * 
	 * @param int $equipType
	 * @param array $arrEquip
	 * [
	 *     posId=>itemObj/NULL
	 * ]
	 * @param int $uid
	 * @return array
	 * [
	 *     posId=>array
	 *     [
	 *         itemType:int
	 *     ]
	 * ]
	 */
	private static function getEquipSubTypeOfPos($equipType,$arrEquip,$uid)
	{
	    switch($equipType)
	    {
	        case HeroDef::EQUIP_ARMING:
	            return ArmDef::$ARM_VALID_POSITIONS;
	        case HeroDef::EQUIP_TREASURE:
	            return TreasureDef::$TREASURE_VALID_POSITIONS;
	        default:
	            throw new FakeException('invalid equiptype %s',$equipType);
	            break;
	    }
	}
	
	public static function equipBestFightSoul($hid, $uid)
	{
	    $ret = array();
	    if (EnFormation::isHidInFormation($hid, $uid) == FALSE)
	    {
	        throw new FakeException('can not equip hero which is not in formation.');
	    }
	    $heroObj = EnUser::getUserObj($uid)->getHeroManager()->getHeroObj($hid);
	    $bag = BagManager::getInstance()->getBag($uid);
	    $equipType = HeroDef::EQUIP_FIGHTSOUL;
	    $arrEquip = $heroObj->getEquipObjByType($equipType);
	    $bestEquip = $bag->getBestItems(HeroDef::$EQUIPTYPE_TO_ITEMTYPE[$equipType]);
	    Logger::trace('getBestFightSoul from bag is %s',$bestEquip);
	    $validFightSoulPos = self::getValidFightSoulPos($uid);
	    $setFunc = HeroUtil::getSetEquipFunc($equipType);
	    $allItemInfo = array();
	    //将背包中最好的战魂和武将身上的战魂合并
	    foreach ($arrEquip as $posId => $itemObj)
	    {
	        if(empty($itemObj))
	        {
	            continue;
	        }
	        $allItemInfo[] = array(
	                'item_id'=>$itemObj->getItemId(),
	                'sort'=>$itemObj->getSort(),
	                'score'=>$itemObj->getScore(),
	                'level'=>$itemObj->getLevel(),
	                'type'=>$itemObj->getType(),
	                'item_template_id'=>$itemObj->getItemTemplateID(),
	                'pos_id'=>$posId,
	                );
	    }
	    foreach ($bestEquip as $itemId)
	    {
	        $itemObj = ItemManager::getInstance()->getItem($itemId);
	        $allItemInfo[] = array(
	                'item_id'=>$itemObj->getItemId(),
	                'sort'=>$itemObj->getSort(),
	                'score'=>$itemObj->getScore(),
	                'level'=>$itemObj->getLevel(),
	                'type'=>$itemObj->getType(),
	                'item_template_id'=>$itemObj->getItemTemplateID(),
	                'pos_id'=>0,
	        );
	    }
	    Logger::trace('allItemInfo %s',$allItemInfo);
	    //将所有的战魂进行排序
	    $sortCmp = new SortByFieldFunc(
	            array('score' => SortByFieldFunc::DESC,
	                    'sort' => SortByFieldFunc::ASC,
	                    'level' => SortByFieldFunc::DESC,
	                    'item_id' => SortByFieldFunc::ASC));
	    usort($allItemInfo, array($sortCmp, 'cmp'));
	    //根据战魂的类型去除重复的战魂
	    foreach ($allItemInfo as $index => $itemInfo)
	    {
	        $itemType = $itemInfo['type'];
	        if (isset($itemInfo['del']))
	        {
	            continue;
	        }
	        for ($i=$index+1; $i<count($allItemInfo); $i++)
	        {
    	        $posId = $allItemInfo[$i]['pos_id'];
    	        if ($itemType == $allItemInfo[$i]['type'])
    	        {
        	        $allItemInfo[$i]['del'] = true;
        	        if(!empty($posId))
        	        {
        	            $allItemInfo[$index]['pos_id'] = $posId;
        	        }
        	        break;
    	        }
	        }
	    }
	    Logger::trace('allItemInfo %s',$allItemInfo);
	    $arrUniqItemInfo = $allItemInfo;
	    foreach ($allItemInfo as $index => $itemInfo)
	    {
	        if (isset($itemInfo['del']))
	        {
	            unset($arrUniqItemInfo[$index]);
	        }
	    }
	    Logger::trace('arrUniqItemInfo %s',$arrUniqItemInfo);
	    //给原来有装备的位置装备战魂
	    $arrUniqItemInfo = array_merge($arrUniqItemInfo);
	    $arrNeedEquipdItem = array_slice($arrUniqItemInfo, 0, count($validFightSoulPos));
	    $arrRestItem = $arrNeedEquipdItem;
	    $arrEquipBestPos = array();
	    foreach ($arrNeedEquipdItem as $index => $itemInfo)
	    {
	        $itemId = $itemInfo['item_id'];
	        $posId = $itemInfo['pos_id'];
	        if (empty($posId))
	        {
	            continue;
	        }
	        $oldItem = $arrEquip[$posId];
	        $arrEquipBestPos[$posId] = 1;
	        unset($arrRestItem[$index]);
	        if (!empty($oldItem)
	                && $oldItem->getItemId() == $itemId)
	        {
	            continue;
	        }
	        $bag->removeItem($itemId);
	        call_user_func_array(array($heroObj, $setFunc), array($equipType, $itemId,$posId) );
	        $ret[$equipType][$posId] = ItemManager::getInstance()->getItem($itemId)->itemInfo();
	        $oldItemId = $oldItem->getItemId();
	        if ($oldItemId != BagDef::ITEM_ID_NO_ITEM && !$bag->addItem($oldItemId,TRUE) )
	        {
	            throw new InterException('add the oldItem:%d to bag failed', $oldItemId);
	        }
	    }
	    //给没有战魂的位置装备战魂
	    foreach ($validFightSoulPos as $posId => $posInfo)
	    {
	        if (empty($arrRestItem))
	        {
	            break;
	        }
	        if (!isset($arrEquipBestPos[$posId]))
	        {
	            $arrRestItem = array_merge($arrRestItem);
	            $itemId = $arrRestItem[0]['item_id'];
	            unset($arrRestItem[0]);
	            $bag->removeItem($itemId);
	            call_user_func_array(array($heroObj, $setFunc), array($equipType,$itemId,$posId) );
	            $ret[$equipType][$posId] = ItemManager::getInstance()->getItem($itemId)->itemInfo();
	            if(isset($arrEquip[$posId]) && !empty($arrEquip[$posId]))
	            {
	                $oldItemId = $arrEquip[$posId]->getItemId();
	                if ($oldItemId != BagDef::ITEM_ID_NO_ITEM && !$bag->addItem($oldItemId,TRUE) )
	                {
	                    throw new InterException('add the oldItem:%d to bag failed', $oldItemId);
	                }
	            }
	        }
	    }
	    return $ret;
	}
	
	public static function activateTalent($hid,$uid,$talentIndex,$spendIndex,$batchOp,$num=1)
	{
	    $heroMng = Enuser::getUserObj($uid)->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    $htid = $heroObj->getHtid();
	    //判断此武将是否能激活天赋
	    $stid = Creature::getHeroConf($htid, CreatureAttr::STAR_ID);
	    if(self::canActivateTalent($uid, $hid, $talentIndex) == FALSE)
	    {
	        throw new FakeException('can not activate talent of hero %d htid %d.',
	                $hid,$htid);
	    }
	    if($heroObj->hasSealedTalent($talentIndex))
	    {
	        $talentInfo = $heroObj->getTalentInfo();
	        throw new FakeException('hero %d has unsealdindex in talentindex %d.talentinfo %s',$hid,$talentIndex,$talentInfo);
	    }
	    $arrActivateNeed = Creature::getHeroConf($htid, CreatureAttr::TALENT_ACTIVATE_NEED);
	    $activateNeed = $arrActivateNeed[$spendIndex];
	    $needJewel = $activateNeed[0];
	    $needGold = $activateNeed[1];
	    $needItem = $activateNeed[2];
	    $bag = BagManager::getInstance()->getBag($uid);
	    if(!empty($needItem)&&(!empty($activateNeed[3])))
	    {
	        if($bag->deleteItembyTemplateID($needItem, $activateNeed[3]*$num) == FALSE)
	        {
	            throw new FakeException('bag delete item %d num %d failed',$needItem,$activateNeed[3]);
	        }
	    }
	    $userObj = EnUser::getUserObj($uid);
	    if($userObj->subJewel($needJewel*$num) == FALSE)
	    {
	        throw new FakeException('sub jewel %d failed.user has jewel %d',$needJewel,$userObj->getJewel());
	    }
	    if($userObj->subGold($needGold*$num, StatisticsDef::ST_FUNCKEY_TALENT_ACTIVATE) == FALSE)
	    {
	        throw new FakeException('sub gold %d failed.user has gold %d',$needGold,$userObj->getGold());
	    }
	    $arrActivateTalent = array();
	    for($i=1;$i<=$num;$i++)
	    {
	        $arrGroupId = Util::noBackSample(btstore_get()->NORMAL_CONFIG[constant("NormalConfigDef::CONFIG_ID_TALENT_GROUP_WEIGHT_$talentIndex")]->toArray(), 1);
	        if(count($arrGroupId) != 1)
	        {
	            throw new FakeException('rand talent_group failed.rand result is %s',$arrGroupId);
	        }
	        $groupId = $arrGroupId[0];
	        Logger::trace('activateTalent group %d',$groupId);
	        $arrGroup = btstore_get()->NORMAL_CONFIG[constant("NormalConfigDef::CONFIG_ID_TALENT_GROUP_LIST_$talentIndex")];
	        $arrTalentId = $arrGroup[$groupId];
	        $arrTalentInfo = array();
	        foreach($arrTalentId as $talentId)
	        {
	            $arrTalentInfo[$talentId] = array(
	                    'weight' => btstore_get()->HEROTALENT[$talentId]['weight']
	            );
	        }
	        $curTalentIds = $heroObj->getCurTalent();
	        if(isset($curTalentIds[$talentIndex]) && isset($arrTalentInfo[$curTalentIds[$talentIndex]]))
	        {
	            unset($arrTalentInfo[$curTalentIds[$talentIndex]]);
	        }
	        $arrRandTalent = Util::noBackSample($arrTalentInfo, 1);
	        if(count($arrRandTalent) != 1)
	        {
	            throw new FakeException('rand talent_list from group %d failed.rand result is %s',$groupId,$arrRandTalent);
	        }
	        $talentId = $arrRandTalent[0];
	        $arrActivateTalent[] = $talentId;
	    }
	    Logger::trace('activateTalent talent %s',$arrActivateTalent);
	    $ret = NULL;
	    if($batchOp)
	    {
	        $heroObj->addToConfirmedTalent($arrActivateTalent,$talentIndex);
	        $ret = $arrActivateTalent;
	    }
	    else
	    {
	        $heroObj->addToConfirmedTalent($arrActivateTalent[0],$talentIndex);
	        $ret = $arrActivateTalent[0];
	    }
	    $bag->update();
	    $userObj->update();
	    Logger::info('activateTalent hid %d talentindex %d talent %s',$hid, $talentIndex, $arrActivateTalent);
	    return $ret;
	}
	
	public static function activateTalentConfirm($hid,$uid,$talentIndex,$talentId)
	{
	    $userObj = Enuser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if($heroObj->isMasterHero())
	    {
	        throw new FakeException('master hero can not activate talent');
	    }
	    if($heroObj->confirmTalent($talentIndex,$talentId) == FALSE)
	    {
	        throw new FakeException('no talent to confirm.heroinfo is %s',$heroObj->getTalentInfo());
	    }
	    $userObj->modifyBattleData();
	    $userObj->update();
	    Logger::info('activateTalentConfirm hid %d talentindex %d talent %d',$hid, $talentIndex, $talentId);
	    return 'ok';
	}
	
	public static function activateTalentUnDo($hid,$uid,$talentIndex)
	{
	    $userObj = Enuser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if($heroObj->isMasterHero())
	    {
	        throw new FakeException('master hero can not activate talent');
	    }
	    if($heroObj->undoTalent($talentIndex) == FALSE)
	    {
	        throw new FakeException('no talent to undo.heroinfo is %s',$heroObj->getTalentInfo());
	    }
	    $userObj->update();
	    Logger::info('activateTalentUnDo hid %d talentindex %d',$hid, $talentIndex);
	    return 'ok';
	}
	
	public static function getBestEquipQualityOnHero($uid,$type=HeroDef::EQUIP_FIGHTSOUL)
	{
	    $maxQuality = 0;
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObjInSquad();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        $heroInfo = $heroObj->getInfo();
	        if(!isset($heroInfo['equip'][$type]))
	        {
	            continue;
	        }
	        $equip = $heroInfo['equip'][$type];
	        foreach($equip as $pos => $itemInfo)
	        {
	            if(!isset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]))
	            {
	                continue;
	            }
	            $itemTmplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
	            $tmpQuality = ItemAttr::getItemAttr($itemTmplId, ItemDef::ITEM_ATTR_NAME_QUALITY);
	            if($tmpQuality > $maxQuality)
	            {
	                $maxQuality = $tmpQuality;
	            }
	        }
	    }
	    return $maxQuality;
	}
	
	public static function getAllEquipTmplIdOnHero($uid,$type,$hid=0)
	{
	    $arrEquipTmplId = array();
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObjInSquad();
	    foreach($arrHeroObj as $heroId => $heroObj)
	    {
	        if(!empty($hid) && ($heroId != $hid))
	        {
	            continue;
	        }
	        $heroInfo = $heroObj->getInfo();
	        if(!isset($heroInfo['equip'][$type]))
	        {
	            continue;
	        }
	        $equip = $heroInfo['equip'][$type];
	        foreach($equip as $pos => $itemInfo)
	        {
	            if(!isset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]))
	            {
	                continue;
	            }
	            $itemTmplId = $itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID];
	            $arrEquipTmplId[] = $itemTmplId;
	        }
	    }
	    return $arrEquipTmplId;
	}
	
	public static function getEquipNumOnHero($uid,$type = HeroDef::EQUIP_FIGHTSOUL)
	{
	    $equipNum = 0;
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObjInSquad();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        $heroInfo = $heroObj->getInfo();
	        if(!isset($heroInfo['equip'][$type]))
	        {
	            continue;
	        }
	        $equip = $heroInfo['equip'][$type];
	        foreach($equip as $pos => $itemInfo)
	        {
	            if(empty($itemInfo) || !isset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]))
	            {
	                continue;
	            }
	            $equipNum++;
	        }
	    }
	    return $equipNum;
	}
	
	public static function getMaxHeroEquipLevel($uid,$type=HeroDef::EQUIP_ARMING)
	{
	    $level = 0;
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObjInSquad();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        $heroInfo = $heroObj->getInfo();
	        if(!isset($heroInfo['equip'][$type]))
	        {
	            continue;
	        }
	        $equip = $heroInfo['equip'][$type];
	        foreach($equip as $pos => $itemInfo)
	        {
	            if(empty($itemInfo) || !isset($itemInfo[ItemDef::ITEM_SQL_ITEM_TEMPLATE_ID]))
	            {
	                continue;
	            }
	            $itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
	            $item = ItemManager::getInstance()->getItem($itemId);
	            $itemLevel = $item->getLevel();
	            if($itemLevel > $level)
	            {
	                $level = $itemLevel;
	            }
	        }
	    }
	    return $level;
	}
	
	
	
	public static function getBestHeroQuality($uid)
	{
	    $bestQuality = 0;
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObj();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        if(empty($heroObj) || ($heroObj->isMasterHero()))
	        {
	            continue;
	        }
	        $htid = $heroObj->getHtid();
	        $quality = Creature::getHeroConf($htid, CreatureAttr::QUALITY);
	        if($quality > $bestQuality)
	        {
	            $bestQuality = $quality;
	        }
	    }
	    return $bestQuality;
	}
	
	public static function getMaxHeroEvolveLv($uid)
	{
	    $maxEvolveLv = 0;
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObj();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        if(empty($heroObj) || ($heroObj->isMasterHero()))
	        {
	            continue;
	        }
	        $htid = $heroObj->getHtid();
	        $evolveLv = $heroObj->getEvolveLv();
	        if($evolveLv > $maxEvolveLv)
	        {
	            $maxEvolveLv = $evolveLv;
	        }
	    }
	    return $maxEvolveLv;
	}
	
	public static function getAllHeroTmplId($uid)
	{
	    $arrHtid = array();
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObj();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        if(empty($heroObj) || ($heroObj->isMasterHero()))
	        {
	            continue;
	        }
	        $htid = $heroObj->getHtid();
	        if(in_array($htid, $arrHtid) == FALSE)
	        {
	            $arrHtid[] = $htid;
	        }
	    }
	    return $arrHtid;
	}
	
	public static function getMaxHeroLevel($uid)
	{
	    $maxLevel = 0;
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $arrHeroObj = $heroMng->getAllHeroObj();
	    foreach($arrHeroObj as $hid => $heroObj)
	    {
	        if(empty($heroObj) || 
	                ($heroObj->isMasterHero()) ||
	                 $heroObj->getStarLv() < 5)
	        {
	            continue;
	        }
	        $htid = $heroObj->getHtid();
	        $level = $heroObj->getLevel();
	        if($level > $maxLevel)
	        {
	            $maxLevel = $level;
	        }
	    }
	    return $maxLevel;
	}
	
	public static function talentNeedEvolveLv($htid,$talentIndex)
	{
	    $arrTalentCopy = Creature::getHeroConf($htid, CreatureAttr::TALENT_ARR_COPY);
	    if(!isset($arrTalentCopy[$talentIndex]))
	    {
	        return PHP_INT_MAX;
	    }
	    $talentConf = $arrTalentCopy[$talentIndex];
	    if(count($talentConf) != 2)
	    {
	        throw new ConfigException('config error htid %d talentcopy %s',$htid,$arrTalentCopy->toArray());
	    }
	    $needEvLv = $talentConf[1];
	    return $needEvLv;
	}
	
	public static function isTalentHcopyPassed($uid,$hid,$talentIndex)
	{
	    $heroMng = EnUser::getUserObj($uid)->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    // 觉醒副本限制去除。这里就默认全过了。影响的地方只有重生和回收
	    /*
	    $arrTalentCopy = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::TALENT_ARR_COPY);
	    if(!isset($arrTalentCopy[$talentIndex]))
	    {
	        throw new InterException('no such talentindex %d ',$talentIndex);
	    }
	    $talentConf = $arrTalentCopy[$talentIndex];
	    if(count($talentConf) != 3)
	    {
	        throw new ConfigException('config error htid %d talentcopy %s',$heroObj->getHtid(),$arrTalentCopy->toArray());
	    }
	    $copyId = $talentConf[0];
	    $copyLv = $talentConf[1];
	    if(EnHCopy::getHCopyPassNum($uid, $copyId, $copyLv) < 1)
	    {
	        Logger::warning('not pass copy %d level %d',$copyId,$copyLv);
	        return FALSE;
	    }
	    */
	    if($heroObj->isMasterHero())
	    {
	        Logger::warning('hero %d htid %d is master hero.can not activate talent',$hid,$heroObj->getHtid());
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public static function canInheritTalent($uid, $hid, $talentIndex)
	{
	    $heroMng = EnUser::getUserObj($uid)->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    /*觉醒副本限制去除。这个限制取消
	    $arrTalentCopy = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::TALENT_ARR_COPY);
	    if(!isset($arrTalentCopy[$talentIndex]))
	    {
	        throw new InterException('no such talentindex %d ',$talentIndex);
	    }
	    $talentConf = $arrTalentCopy[$talentIndex];
	    if(count($talentConf) != 2)
	    {
	        throw new ConfigException('config error htid %d talentcopy %s',$heroObj->getHtid(),$arrTalentCopy->toArray());
	    }
	    $copyId = $talentConf[0];
	    $copyLv = $talentConf[1];
	    if(EnHCopy::getHCopyPassNum($uid, $copyId, $copyLv) < 1)
	    {
	        Logger::warning('not pass copy %d level %d',$copyId,$copyLv);
	        return FALSE;
	    }
	    */
	    $talentInfo = $heroObj->getTalentInfo();
	    if(empty($talentInfo[HeroDef::VA_FIELD_TALENT][HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM]) == FALSE)
	    {
	        Logger::warning('can not activate new talent,to confirm not null.talent info is %s',$talentInfo);
	        return FALSE;
	    }
	    if($heroObj->isMasterHero())
	    {
	        Logger::warning('hero %d htid %d is master hero.can not activate talent',$hid,$heroObj->getHtid());
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public static function canActivateTalent($uid,$hid,$talentIndex)
	{
	    $heroMng = EnUser::getUserObj($uid)->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    $arrTalentCopy = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::TALENT_ARR_COPY);
	    if(!isset($arrTalentCopy[$talentIndex]))
	    {
	        throw new InterException('no such talentindex %d ',$talentIndex);
	    }
	    $talentConf = $arrTalentCopy[$talentIndex];
	    if(count($talentConf) != 2)
	    {
	        throw new ConfigException('config error htid %d talentcopy %s',$heroObj->getHtid(),$arrTalentCopy->toArray());
	    }
	    
	    /*
	    $copyId = $talentConf[0];
	    $copyLv = $talentConf[1];
	    $needEvLv = $talentConf[2];
	    if(EnHCopy::getHCopyPassNum($uid, $copyId, $copyLv) < 1)
	    {
	        Logger::warning('not pass copy %d level %d',$copyId,$copyLv);
	        return FALSE;
	    }
	    */
	    
	    $heroQuality = self::getHeroQualityByHtid($heroObj->getHtid());
		$needQuality = $talentConf[0];
	    if($heroQuality < $needQuality)
	    {
	    	Logger::warning('quality less than config');
	    	return FALSE;
	    }
	   
	    $needEvLv = $talentConf[1];
	    if($heroQuality == $needQuality && $heroObj->getEvolveLv() < $needEvLv)
	    {
	        Logger::warning('activate talent need evolvelv %d now is %d',$needEvLv,$heroObj->getEvolveLv());
	        return FALSE;
	    }
	    $talentInfo = $heroObj->getTalentInfo();
	    if(empty($talentInfo[HeroDef::VA_FIELD_TALENT][HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM]) == FALSE)
	    {
	        Logger::warning('can not activate new talent,to confirm not null.talent info is %s',$talentInfo);
	        return FALSE;
	    }
	    if($heroObj->isMasterHero())
	    {
	        Logger::warning('hero %d htid %d is master hero.can not activate talent',$hid,$heroObj->getHtid());
	        return FALSE;
	    }
	    return TRUE;
	}
	
	public static function inheritTalent($uid,$fromHid,$toHid,$arrTalentIndex)
	{
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $fromHeroObj = $heroMng->getHeroObj($fromHid);
	    $toHeroObj = $heroMng->getHeroObj($toHid);
	    $arrFromTalent = $fromHeroObj->getCurTalent();
	    $arrToTalent = $toHeroObj->getCurTalent();
	    $inheritConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TALENT_INHERIT_NEEDGOLD];
	    $needGold = 0;
	    foreach ($arrTalentIndex as $index)
	    {
	    	if (!isset($inheritConf[$index - 1])) 
	    	{
	    		throw new ConfigException('inherit talentindex %s conf is %s',
	                $arrTalentIndex,$inheritConf);
	    	}
	    	$needGold += $inheritConf[$index - 1];
	    }
	    if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_HERO_INHERIT_TALENT) == FALSE)
	    {
	        throw new FakeException('sub gold to inherit talent failed.');
	    }
	    foreach($arrTalentIndex as $index)
	    {
	        if((!isset($arrFromTalent[$index]) || empty($arrFromTalent[$index]))
	                && (!isset($arrToTalent[$index]) || empty($arrToTalent[$index])))
	        {
	            throw new FakeException('inherit error.params %s fromtalent %s totalent %s',
	                    func_get_args(),$arrFromTalent,$arrToTalent);
	        }
	        //判断觉醒能力位置是否开启      与canActivateTalent的区别：策划后来把canInheritTalent中进阶等级条件去掉了
	        if(self::canInheritTalent($uid, $toHid, $index) == FALSE
	                || self::canInheritTalent($uid, $fromHid, $index) == FALSE)
	        {
	            throw new FakeException('can not inherit talentindex %d index is not open.fromhero %d tohero %d',$index,$fromHid,$toHid);
	        }
	        $fromTalent = 0;
	        if(isset($arrFromTalent[$index]))
	        {
	            $fromTalent = $arrFromTalent[$index];
	        }
	        $toTalent = 0;
	        if(isset($arrToTalent[$index]))
	        {
	            $toTalent = $arrToTalent[$index];
	        }
	        $fromHeroObj->addConfirmedTalent($index, $toTalent);
	        $toHeroObj->addConfirmedTalent($index, $fromTalent);
	        Logger::info('inherit talent %d from hero %d to hero %d.pre is %d',
	                $fromTalent,$fromHid,$toHid,$toTalent);
	    }
	    $userObj->update();
	    $userObj->modifyBattleData();
	    return 'ok';
	}

	public static function transfer($uid, $hid, $countryId, $htid) 
	{
		if (EnSwitch::isSwitchOpen(SwitchDef::HEROTRANSFER) == false)
		{
			throw new FakeException('user:%d does not open the hero transfer', $uid);
		}
		
		//检查武将在不在阵上,小伙伴,属性小伙伴上
		if (EnFormation::isHidInAll($hid, $uid)) 
		{
			throw new FakeException('hid:%d is in squad or extra or attr extra.', $hid);
		}
		
		//检查是否有其他武将没有确认变身的
		$userObj = EnUser::getUserObj($uid);
		$heroMng = $userObj->getHeroManager();
		$allHero = $heroMng->getAllHero();
		foreach ($allHero as $key => $heroInfo)
		{
			if ($key != $hid && $heroInfo['transfer'] != 0) 
			{
				throw new FakeException('hid:%d transfer is not confirmed', $hid);
			}
		}
		//检查用户金币
		$heroObj = $heroMng->getHeroObj($hid);
		$color = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::QUALITY);
		//橙卡需要用原型id的资质判断，原型id的资质+2为现在的资质
		if ($color == HERO_QUALITY::ORANGE_HERO_QUALITY) 
		{
			$quality = Creature::getHeroConf($heroObj->getBaseHtid(), CreatureAttr::QUALIFICATION) + 2;
		}
		else 
		{
			$quality = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::QUALIFICATION);
		}
		if ($quality < 12) 
		{
			throw new FakeException('hero quality:%d is too low to transfer', $quality);
		}
		//橙卡只能定向变身
		if (in_array($quality, array(13,14,15)) && $countryId != 5
		|| $quality == 12 && !in_array($countryId, array(1,2,3,4)))
		{
			throw new FakeException('hero quality is not consistent with country id');
		}
		
		//检查是否有相应国家
		$confs = btstore_get()->NORMAL_CONFIG->toArray();
		switch ($countryId)
		{
			case 1:$conf = $confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_WEI][$quality];break;
			case 2:$conf = $confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_SHU][$quality];break;
			case 3:$conf = $confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_WU][$quality];break;
			case 4:$conf = $confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_QUN][$quality];break;
			case 5:$conf = array_merge(
					$confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_WEI][$quality],
					$confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_SHU][$quality],
					$confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_WU][$quality],
					$confs[NormalConfigDef::CONFIG_ID_TRANSFER_COUNTRY_QUN][$quality]
				);
				break;
			default:
				throw new FakeException('invalid countryId:%d.', $countryId);
		}
		//随机出同品质的武将
		if (empty($htid)) 
		{
			$cost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TRANSFER_COST][$quality][$heroObj->getEvolveLv()];
			$arrHtid = array_diff($conf, array($heroObj->getHtid()));
			$index = array_rand($arrHtid);
			$htid = $arrHtid[$index];
			$heroObj->unsetDXTrans();
		}
		else 
		{
			$cost = 0;
			if (!in_array($htid, $conf))
			{
				throw new FakeException('htid:%d is not valid', $htid);
			}
			$heroObj->setDXTrans();
		}

		if($userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_HERO_TRANSFER) == FALSE)
		{
			throw new FakeException('sub gold to transfer failed.');
		}
		
		$color = Creature::getHeroConf($htid, CreatureAttr::QUALITY);
		if ($color == HERO_QUALITY::ORANGE_HERO_QUALITY)
		{
			$baseHtid = Creature::getHeroConf($htid, CreatureAttr::BASE_HTID);
			$newQuality = Creature::getHeroConf($baseHtid, CreatureAttr::QUALIFICATION) + 2;
		}
		else
		{
			$newQuality = Creature::getHeroConf($htid, CreatureAttr::QUALIFICATION);
		}
		if ($quality != $newQuality) 
		{
			throw new ConfigException('htid:%d quality is invalid.', $htid);
		}
		
		//保存随机出来的htid
		$heroObj->setTransfer($htid);
		$userObj->update();
		return $htid;
	}
	
	public static function transferConfirm($uid, $hid)
	{
		if (EnSwitch::isSwitchOpen(SwitchDef::HEROTRANSFER) == false)
		{
			throw new FakeException('user:%d does not open the hero transfer', $uid);
		}
		
		//检查武将在不在阵上,小伙伴,属性小伙伴上
		if (EnFormation::isHidInAll($hid, $uid))
		{
			throw new FakeException('hid:%d is in squad or extra or attr extra.', $hid);
		}
		//检查武将是否变身过
		$userObj = EnUser::getUserObj($uid);
		$heroMng = $userObj->getHeroManager();
		$heroObj = $heroMng->getHeroObj($hid);
		$htid = $heroObj->getTransfer();
		if (empty($htid)) 
		{
			throw new FakeException('hid:%d has no transfer.', $hid);
		}
		
		//定向变身要扣金币
		$color = Creature::getHeroConf($htid, CreatureAttr::QUALITY);
		if ($color == HERO_QUALITY::ORANGE_HERO_QUALITY)
		{
			$quality = Creature::getHeroConf($heroObj->getBaseHtid(), CreatureAttr::QUALIFICATION) + 2;
		}
		else 
		{
			$quality = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::QUALIFICATION);
		}
		$cost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_TRANSFER_ASSIGN_COST][$quality][$heroObj->getEvolveLv()];
		if ($heroObj->getDXTrans() && !$userObj->subGold($cost, StatisticsDef::ST_FUNCKEY_HERO_TRANSFER))
		{
			throw new FakeException('sub gold to transfer failed.');
		}
		
		//变身
		$soul = $heroObj->getSoul();
		$level = $heroObj->getLevel();
		$evolveLv = $heroObj->getEvolveLv();
		$curTalent = $heroObj->getCurTalent();
		$pillInfo = $heroObj->getPillInfo();
		$heroObj->setEvolveLevel(0);
		$heroObj->unsetTransfer();
		$heroObj->unsetDXTrans();
		$heroMng->delHeroByHid($hid);
		$oldHid = $hid;
		$hid = $heroMng->addNewHero($htid);
		$heroObj = $heroMng->getHeroObj($hid);
		$heroObj->addSoul($soul);
		$heroObj->setEvolveLevel($evolveLv);
		if (!empty($pillInfo)) 
		{
			$heroObj->setPillInfo($pillInfo);
		}
		//觉醒能力特殊处理
		foreach ($curTalent as $talentIndex => $talentId)
		{
			if (empty($talentId))
			{
				continue;
			}
			$heroObj->addConfirmedTalent($talentIndex, $talentId);
			if (self::isTalentHcopyPassed($uid, $hid, $talentIndex) == FALSE)
			{
				$heroObj->addSealedTalent($talentIndex);
			}
		}
		$userObj->update();
		Logger::trace('transfer confirm del hid:%d add hid:%d', $oldHid, $hid);
		return $hid;
	}
	
	public static function transferCancel($uid, $hid)
	{
		if (EnSwitch::isSwitchOpen(SwitchDef::HEROTRANSFER) == false)
		{
			throw new FakeException('user:%d does not open the hero transfer', $uid);
		}
		
		$userObj = EnUser::getUserObj($uid);
		$heroMng = $userObj->getHeroManager();
		//检查武将是否变身过
		$heroObj = $heroMng->getHeroObj($hid);
		$htid = $heroObj->getTransfer();
		if (empty($htid))
		{
			throw new FakeException('hid:%d has no transfer.', $hid);
		}
	
		//取消变身
		$heroObj->unsetTransfer();
		$heroObj->unsetDXTrans();
		$userObj->update();
		return 'ok';
	}

    public static function develop($uid,$hid,$arrHero,$arrItem)
	{
	    if(EnSwitch::isSwitchOpen(SwitchDef::HERODEVELOP,$uid) == FALSE)
	    {
	        throw new FakeException('switch herodevelop is not open.can not develop hero.');
	    }
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if($heroObj->getStarLv() < 5)
	    {
	        throw new FakeException('hero %d starlv is %ddevelop need startlv 5',$hid,$heroObj->getStarLv());
	    }
	    $transfer = $heroObj->getTransfer();
	    if(!empty($transfer))
	    {
	        Logger::info('hero %d has transfer %d',$hid,$transfer);
	        $heroObj->unsetTransfer();
	        $heroObj->unsetDXTrans();
	    }
	    $htid = $heroObj->getHtid();
	    $developTblId = Creature::getHeroConf($htid, CreatureAttr::DEVELOP_TBL_ID);
	    if(empty($developTblId) || !isset(btstore_get()->HERO_DEVELOP[$developTblId]))
	    {
	        throw new FakeException('hero %d htid %d can not develop',$hid,$htid);
	    }
	    //判断武将进化是否满足条件 1.进阶等级 2.等级 3.提供足够的消耗武将 4.提供足够的消耗物品 5.银币是否足够
	    $developTbl = btstore_get()->HERO_DEVELOP[$developTblId];
	    $needEvolveLv = $developTbl['needEvolveLv'];
	    $needLv = $developTbl['needLevel'];
	    if($heroObj->getEvolveLv() < $needEvolveLv)
	    {
	        throw new FakeException('hero %d evolvelv %d develop need %d can not develop',$hid,$heroObj->getEvolveLv(),$needEvolveLv);
	    }
	    if($heroObj->getLevel() < $needLv)
	    {
	        throw new FakeException('hero %d level %d develop need %d can not develop',$hid,$heroObj->getLevel(),$needLv);
	    }
	    $arrNeedHero = $developTbl['arrNeedHero']->toArray();
	    $arrNeedItem = $developTbl['arrNeedItem']->toArray();
	    $arrConsumeHero = self::consumeHero($arrHero, $arrNeedHero);
	    if($arrConsumeHero != $arrHero)
	    {
	        Logger::fatal('hero %d develop need %s provide %s consume %s',$hid,$arrNeedHero,$arrHero,$arrConsumeHero);
	    }
	    self::consumeItem($arrItem, $arrNeedItem);	
	    $needSilver = $developTbl['needSilver'];
	    if($userObj->subSilver($needSilver) == FALSE)
	    {
	        throw new FakeException('hero %d develop sub silver failed.',$hid);
	    }  
	    //武将满足各个条件之后   进化  
	    $toHtid = $developTbl['toHtid'];
	    $heroObj->develop($toHtid);
	    Logger::info('hero %d htid %d develop to htid %d',$hid,$htid,$toHtid);
	    self::updateHeroBook($uid, array($toHtid));
	    HeroLogic::refershFmtOnHeroChange($hid);
	    BagManager::getInstance()->getBag()->update();
	    $userObj->update();
	    EnAchieve::updateOrangeCard($uid, 1);
	    return 'ok';
	}
	
	/**
	 * 橙卡进化到红卡
	 * 
	 * @param number $uid			玩家uid
	 * @param number $hid			进化到红卡的橙卡hid
	 * @param array $arrHero		供消耗的hid数组
	 * @param array $arrItem		供消耗的物品item_id=>num数组
	 * @throws FakeException
	 * @return string				'ok'
	 */
	public static function develop2red($uid, $hid, $arrHero, $arrItem)
	{
		// 功能节点是否打开进化红卡
		if (!EnSwitch::isSwitchOpen(SwitchDef::HERODEVELOP_2_RED, $uid))
		{
			throw new FakeException('switch hero develop 2 red is not open');
		}
		
		$userObj = EnUser::getUserObj($uid);
		$heroMng = $userObj->getHeroManager();
		
		// 武将是否存在
		$heroObj = $heroMng->getHeroObj($hid);
		if ($heroObj == NULL) 
		{
			throw new FakeException('hero[%d] not exist', $hid);
		}
		
		// 是否为6星橙卡武将
		if ($heroObj->getStarLv() != 6)
		{
			throw new FakeException('hero[%d] starlv[%d], need 6',$hid, $heroObj->getStarLv());
		}
		
		// 如果有未确认武将变身信息，去掉
		$transfer = $heroObj->getTransfer();
		if (!empty($transfer))
		{
			Logger::info('hero[%d] has transfer[%d], remove', $hid, $transfer);
			$heroObj->unsetTransfer();
			$heroObj->unsetDXTrans();
		}
		
		// 进化的htid
		$htid = $heroObj->getHtid();
		$developTblId = Creature::getHeroConf($htid, CreatureAttr::DEVELOP_TBL_ID);
		if (empty($developTblId) || !isset(btstore_get()->HERO_DEVELOP[$developTblId]))
		{
			throw new FakeException('hero[%d] htid[%d] can not develop 2 red, no develop table id', $hid, $htid);
		}
		
		$developTbl = btstore_get()->HERO_DEVELOP[$developTblId];
		
		// 判断武将进化红卡是否满足条件 1.进阶等级
		$needEvolveLv = $developTbl['needEvolveLv'];
		if ($heroObj->getEvolveLv() < $needEvolveLv)
		{
			throw new FakeException('hero[%d] evolvelv[%d] develop 2 red need evolvelv[%d]', $hid, $heroObj->getEvolveLv(), $needEvolveLv);
		}
		
		// 判断武将进化红卡是否满足条件  2.等级
		$needLv = $developTbl['needLevel'];
		if ($heroObj->getLevel() < $needLv)
		{
			throw new FakeException('hero[%d] level[%d] develop 2 red need level[%d]', $hid, $heroObj->getLevel(), $needLv);
		}
		
		// 判断武将进化红卡是否满足条件  3.提供足够的消耗武将
		$arrNeedHero = $developTbl['arrNeedHero']->toArray();
		$arrConsumeHero = self::consumeHero($arrHero, $arrNeedHero);
		if ($arrConsumeHero != $arrHero)
		{
			Logger::fatal('hero[%d] develop 2 red need[%s] provide[%s] consume[%s]', $hid, $arrNeedHero, $arrHero, $arrConsumeHero);
		}
		
		// 判断武将进化红卡是否满足条件  4.提供足够的消耗物品
		$arrNeedItem = $developTbl['arrNeedItem']->toArray();
		self::consumeItem($arrItem, $arrNeedItem);
		
		// 判断武将进化红卡是否满足条件  5.银币是否足够
		$needSilver = $developTbl['needSilver'];
		if (!$userObj->subSilver($needSilver))
		{
			throw new FakeException('hero[%d] develop 2 red sub silver failed, need[%d], curr[%d]', $hid, $needSilver, $userObj->getSilver());
		}
		
		//武将满足各个条件之后   进化
		$toHtid = $developTbl['toHtid'];
		$heroObj->develop($toHtid);
		Logger::info('hero[%d] htid[%d] develop 2 red to htid[%d]', $hid, $htid, $toHtid);
		
		// 更新武将图鉴
		self::updateHeroBook($uid, array($toHtid));
		
		// 刷新武将战斗信息
		HeroLogic::refershFmtOnHeroChange($hid);
		
		// update
		BagManager::getInstance()->getBag()->update();
		$userObj->update();
		
		//TODO 成就这里，加一种红卡的类型
		//EnAchieve::updateOrangeCard($uid, 1);
		
		return 'ok';
	}
	
	public static function activateSealTalent($uid,$hid)
	{
	    $userObj = EnUser::getUserObj($uid);
	    $heroMng = $userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if(empty($heroObj))
	    {
	        throw new FakeException('hero %d not exist',$hid);
	    }
	    $sealedTalent = $heroObj->getSealedTalentInfo();
	    foreach($sealedTalent as $talentIndex => $status)
	    {
	        if(empty($status))
	        {
	            continue;
	        }
	        if(HeroLogic::canActivateTalent($uid, $hid, $talentIndex) == FALSE)
	        {
	            continue;
	        }
	        $heroObj->activeSealedTalent($talentIndex);
	    }
	    $userObj->update();
	    return $heroObj->getInfo();
	}
	
	public static function getHeroRage($uid, $hid)
	{
	    $heroMng = EnUser::getUserObj($uid)->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if(empty($heroObj))
	    {
	        throw new FakeException('no such hero %d',$hid);
	    }
	    $addAttrByAwakeAbility = $heroObj->getAddAttrByAwakeAbility();
	    $currRage = Creature::getHeroConf($heroObj->getHtid(), CreatureAttr::RAGE);
	    if(isset($addAttrByAwakeAbility[PropertyKey::CURR_RAGE]))
	    {
	        $currRage += $addAttrByAwakeAbility[PropertyKey::CURR_RAGE];
	    }
	    return $currRage;
	}

	public static function getBasicEquipInfoOfFmtHero($uid)
	{
	    $arrHid = EnFormation::getArrHidInFormation($uid);
	    $arrHeroInfo = HeroLogic::getArrHero($arrHid);
	    $arrHeroEquip = array();
	    $arrItemId = array();
	    foreach($arrHeroInfo as $hid => $heroInfo)
	    {
	        $heroObj = new OtherHeroObj($heroInfo);
	        $arrItemId = array_merge($arrItemId, $heroObj->getAllEquipId());
	    }
	    $arrItemInfo = ItemStore::getItems($arrItemId);
	    $arrInlayId = array();
	    foreach($arrHid as $hid )
	    {
	        $arrHeroEquip[$hid] = array();
	        if(!isset($arrHeroInfo[$hid]))
	        {
	            continue;
	        }
	        $heroInfo = $arrHeroInfo[$hid];
	        foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
	        {
	            if(!isset($heroInfo['va_hero'][$equipType]))
	            {
	                continue;
	            }
	            foreach($heroInfo['va_hero'][$equipType] as $pos => $itemId)
	            {
	                if($itemId == ItemDef::ITEM_ID_NO_ITEM)
	                {
	                    continue;
	                }
	                if(!isset($arrItemInfo[$itemId]))
	                {
	                    throw new FakeException('uid %d hid %d pos %d item %d not exist',$uid,$hid,$pos,$itemId);
	                }
	                $itemInfo = $arrItemInfo[$itemId];
	                $itemObj = ItemManager::__getItem($itemInfo);
	                $itemType = $itemObj->getItemType();
	                if($itemType == ItemDef::ITEM_TYPE_TREASURE)
	                {
	                    $arrInlayId = array_merge($arrInlayId,$itemObj->getInlay());
	                }
	                $arrHeroEquip[$hid][$equipType][$pos] = $itemInfo;
	            }
	        }
	    }
	    $arrInlayInfo = ItemStore::getItems($arrInlayId);
	    foreach($arrHeroEquip as $hid => $heroEquip)
	    {
	        foreach($heroEquip as $equipType => $equipInfo)
	        {
	            foreach($equipInfo as $posId => $itemInfo)
	            {
	                $itemObj = ItemManager::__getItem($itemInfo);
	                $itemType = $itemObj->getItemType();
	                if($itemType == ItemDef::ITEM_TYPE_TREASURE)
	                {
	                    $itemInfo = self::getTreasureItemInfo($itemInfo, $arrInlayInfo);
	                    $arrHeroEquip[$hid][$equipType][$posId] = $itemInfo;
	                }
	            }
	        }
	    }
	    return $arrHeroEquip;
	}
	
	private static function getTreasureItemInfo($treasureInfo,$arrInlayInfo)
	{
	    $treasureObj = ItemManager::__getItem($treasureInfo);
	    $arrInlayId = $treasureObj->getInlay();
	    $treasureInfo[ItemDef::ITEM_SQL_ITEM_TEXT][TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY] = array();
	    foreach($arrInlayId as $index => $inlayId)
	    {
	        if(!isset($arrInlayInfo[$inlayId]))
	        {
	            throw new FakeException('no such inlayid %s inlayinfo %s',$inlayId,$arrInlayInfo);
	        }
	        $treasureInfo[ItemDef::ITEM_SQL_ITEM_TEXT][TreasureDef::ITEM_ATTR_NAME_TREASURE_INLAY]
	                            [$index] = $arrInlayInfo[$inlayId];
	    }
	    return $treasureInfo;
	}

    public static function addPill($hid, $uid, $index, $itemId)
    {
        $item = ItemManager::getInstance()->getItem($itemId);
        //物品和类别参数是否合法
        if( empty($item) )
        {
            throw new FakeException('itemId:%d not exist', $itemId);
        }
        $itemTplId = $item->getItemTemplateID();
        $conf = btstore_get()->PILL[$index];
        if($conf[PillDef::PILL_ID] != $itemTplId)
        {
            throw new FakeException('itemTplId:%d not equal need:%d', $itemTplId, $conf[PillDef::PILL_ID]);
        }

        $heroMng = EnUser::getUserObj($uid)->getHeroManager();
        $heroObj = $heroMng->getHeroObj($hid);
        $pillNum = $heroObj->getPillNum($index, $itemTplId);
        if($pillNum >= $conf[PillDef::PILL_NUM])
        {
            throw new FakeException("the hero:%d have eat enough pill:%d, limit:%d", $hid, $pillNum, $conf[PillDef::PILL_NUM]);
        }

        if($item->getItemNum() < 1)
        {
            throw new FakeException("no enough item:%d", $item->getItemNum());
        }
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj = EnUser::getUserObj($uid);
        if($bag->decreaseItem($itemId, 1) == false)
        {
            throw new FakeException("decrease Item failed");
        }

        $heroObj->addPillNum($index, $itemTplId);
        HeroLogic::refershFmtOnHeroChange($hid);
        $bag->update();
        $userObj->update();
        return 'ok';
    }

    public static function addArrPills($uid, $hid, $pillType)
    {
    	$conf = btstore_get()->PILL;
    	$bag = BagManager::getInstance()->getBag();
    	foreach($conf as $index => $pillConf)
    	{
    		if($pillConf[PillDef::PILL_TYPE] != $pillType)
    		{
    			continue;
    		}
    		$itemTplId = $pillConf[PillDef::PILL_ID];
    		$pillNum = $bag->getItemNumByTemplateID($itemTplId);
    		if($pillNum > 0)
    		{
    			self::addPillNoUpdate($hid, $uid, $index, $itemTplId, $pillNum);
    		}
    	}
    	$modifyData = $bag->update();
        EnUser::getUserObj()->update();
        
        $HeroMgr = EnUser::getUserObj()->getHeroManager();
        $HeroObj = $HeroMgr->getHeroObj($hid);
        
        return array( 
        	'pill' => $HeroObj->getPillInfo(),
        	'bagModify' => $modifyData);
    } 
    
    public static function addPillNoUpdate($hid, $uid, $index, $itemTplId, $num)
    {
        $conf = btstore_get()->PILL[$index];
        if($conf[PillDef::PILL_ID] != $itemTplId)
        {
            throw new FakeException('itemTplId:%d not equal need:%d', $itemTplId, $conf[PillDef::PILL_ID]);
        }

        $heroMng = EnUser::getUserObj($uid)->getHeroManager();
        $heroObj = $heroMng->getHeroObj($hid);
        $pillNum = $heroObj->getPillNum($index, $itemTplId);
        if($pillNum + $num > $conf[PillDef::PILL_NUM])
        {
            $num = $conf[PillDef::PILL_NUM] - $pillNum;
        }
        
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj = EnUser::getUserObj($uid);
        if($num > 0 && $bag->deleteItembyTemplateID($itemTplId, $num) == false)
        {
            throw new FakeException("decrease Item failed");
        }

        $heroObj->addPillNum($index, $itemTplId, $num);
        
        return 'ok';
    }
    
	public static function removePill($hid, $uid, $index)
	{
		$bag = BagManager::getInstance()->getBag($uid);
		if($bag->isFull(BagDef::BAG_PROPS))
		{
			throw new FakeException('props bag is full');
		}
		$conf = btstore_get()->PILL[$index];
		$itemTplId = $conf[PillDef::PILL_ID];

		$heroMng = EnUser::getUserObj($uid)->getHeroManager();
		$heroObj = $heroMng->getHeroObj($hid);
		$pillNum = $heroObj->getPillNum($index, $itemTplId);
		if($pillNum <= 0)
		{
			throw new FakeException("have no pill on index:%d", $index);
		}

		$userObj = EnUser::getUserObj($uid);
		$removePillCostSilver = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_REMOVE_PILL_COST_SILVER];
		if($userObj->subSilver($removePillCostSilver) == false)
		{
			throw new FakeException("removeOnePill subSilver:%d failed", $removePillCostSilver);
		}

		$heroObj->decreasePillNum($index, $itemTplId);

		$bag->addItemByTemplateID($itemTplId, 1);

		HeroLogic::refershFmtOnHeroChange($hid);

		$userObj->update();
		$bag->update();
		Logger::info("HeroLogic::removePill ok. hid:%d index:%d add item:%d to bag", $hid, $index, $itemTplId);
		return 'ok';
	}

	public static function removePillByType($hid, $uid, $type)
	{
		$bag = BagManager::getInstance()->getBag($uid);
		if($bag->isFull(BagDef::BAG_PROPS))
		{
			throw new FakeException('props bag is full');
		}
		//1 2 3代表攻击防御生命3种属性
		if(in_array($type, array(1, 2, 3)) == false)
		{
			throw new FakeException("invalid param type:%d", $type);
		}

		$heroMng = EnUser::getUserObj($uid)->getHeroManager();
		$heroObj = $heroMng->getHeroObj($hid);
		$arrPill = $heroObj->removePillByType($type);

		$userObj = EnUser::getUserObj($uid);

		$removePillCostSilver = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_REMOVE_PILL_COST_SILVER];
		$totalPillNum = 0;
		foreach($arrPill as $itemTplId => $num)
		{
			$totalPillNum += $num;
		}
		if($totalPillNum == 0)
		{
			throw new FakeException("have no type:%d pill", $type);
		}

		if($userObj->subSilver($removePillCostSilver * $totalPillNum) == false)
		{
			throw new FakeException("removeOnePill subSilver:%d failed", $removePillCostSilver * $totalPillNum);
		}

		$bag->addItemsByTemplateID($arrPill, true);

		HeroLogic::refershFmtOnHeroChange($hid);

		$userObj->update();
		$bag->update();
		Logger::info("HeroLogic::removePillByType ok. hid:%d type:%d add Items:%s to bag", $hid, $type, $arrPill);
		return 'ok';
	}

    public static function activeMasterTalent($uid, $index, $talentId)
    {
        $userObj = EnUser::getUserObj($uid);
        $heroMgr = $userObj->getHeroManager();
        $masterHeroObj = $heroMgr->getMasterHeroObj();

        $arrMasterTalent = EnAthena::getArrMasterTalent($uid);
        if(in_array($talentId, $arrMasterTalent) == false)
        {
            throw new FakeException("talentId:%d not legal", $talentId);
        }

        $masterTalentInfo = $masterHeroObj->getMasterTalentInfo();
        if(in_array($talentId, $masterTalentInfo))
        {
            throw new FakeException("talent:%d already active, cant active twice", $talentId);
        }

        $masterHeroObj->confirmMasterTalent($index, $talentId);
        $userObj->update();
        HeroLogic::refershFmtOnHeroChange($masterHeroObj->getHid());
        return 'ok';
    }
    
    public static function activeDestiny($uid, $hid, $id)
    {
    	$userObj = EnUser::getUserObj($uid);
    	$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
    	$htid = $heroObj->getHtid();
    	$sum = Creature::getHeroConf($htid, CreatureAttr::DESTINY_SUM);
    	//检查id有效性
    	if ($id > $sum) 
    	{
    		throw new FakeException('user can not active destiny htid:%d id:%d sum:%d', $htid, $id, $sum);
    	}
    	//只能按顺序激活
    	if ($id != $heroObj->getDestiny() + 1) 
    	{
    		throw new FakeException('user can not active destiny hid:%d id:%d cur:%d', $hid, $id, $heroObj->getDestiny());
    	}
    	//消耗
    	if (empty(btstore_get()->HERO_DESTINY[$id])) 
    	{
    		throw new ConfigException('hero destiny id:%d is not exist', $id);
    	}
    	$cost = btstore_get()->HERO_DESTINY[$id]['cost']->toArray();
    	//额外消耗
    	$extraCost = Creature::getHeroConf($htid, CreatureAttr::DESTINY_COST)->toArray();
    	$cost = isset($extraCost[$id]) ? array_merge($cost, $extraCost[$id]) : $cost;
    	//扣东西
    	RewardUtil::delMaterial($uid, $cost, null, 1, array(), false);
    	//激活天命
    	$heroObj->setDestiny($id);
    	//更新数据
    	BagManager::getInstance()->getBag($uid)->update();
    	$userObj->update();
    	//刷新战斗数据
    	HeroLogic::refershFmtOnHeroChange($hid);
    	return 'ok';
    }
    
    public static function resetDestiny($uid, $hid)
    {
    	$userObj = EnUser::getUserObj($uid);
    	$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
    	$htid = $heroObj->getHtid();
    	//激活过才能重置
    	if ($heroObj->getDestiny() == 0) 
    	{
    		throw new FakeException('user can not reset destiny hid:%d cur:%d', $hid, $heroObj->getDestiny());
    	}
    	//重置花费
    	$gold = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_RESET_HERO_DESTINY];
    	if (!$userObj->subGold($gold, StatisticsDef::ST_FUNCKEY_HERO_DESTINY_RESET_COST)) 
    	{
    		throw new FakeException('user has no enough gold:%d to reset destiny', $gold);
    	}
    	//返还材料
    	$arrCost = self::getCostOfDestiny($uid, $hid);
    	//加东西
    	$rewardRet = RewardUtil::reward3DArr($uid, $arrCost, StatisticsDef::ST_FUNCKEY_HERO_DESTINY_RESET_REWARD);
    	//重置天命
    	$heroObj->setDestiny(0);
    	//更新
    	$userObj->update();
    	RewardUtil::updateReward($uid, $rewardRet);
    	//刷新战斗数据
    	HeroLogic::refershFmtOnHeroChange($hid);
    	return 'ok';
    }
    
    public static function getCostOfDestiny($uid, $hid)
    {
    	$userObj = EnUser::getUserObj($uid);
    	$heroObj = $userObj->getHeroManager()->getHeroObj($hid);
    	$htid = $heroObj->getHtid();
    	
    	$arrCost = array();
    	$extraCost = Creature::getHeroConf($htid, CreatureAttr::DESTINY_COST)->toArray();
    	for ($i = 1; $i <= $heroObj->getDestiny(); $i++)
    	{
    		$cost = btstore_get()->HERO_DESTINY[$i]['cost']->toArray();
    		$cost = isset($extraCost[$i]) ? array_merge($cost, $extraCost[$i]) : $cost;
    		$arrCost = array_merge($arrCost, $cost);
    	}
    	
    	Logger::trace('hero destiny:%d arrCost:%s', $heroObj->getDestiny(), $arrCost);
    	return $arrCost;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */