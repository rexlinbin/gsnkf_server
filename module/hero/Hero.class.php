<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Hero.class.php 253608 2016-07-28 09:36:00Z DuoLi $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/Hero.class.php $
 * @author $Author: DuoLi $(lanhongyu@babeltime.com)
 * @date $Date: 2016-07-28 09:36:00 +0000 (Thu, 28 Jul 2016) $
 * @version $Revision: 253608 $
 * @brief
 *
 **/
/**
 * 转生（进阶）evolve 进化develop 突破（天命系统触发）transform
 * 主角有进阶、突破  没有进化
 * 普通武将有进阶、进化
 * 主角进阶前三阶会改变HTID   以后进阶不会改变HTID   突破改变HTID
 * 普通武将进阶不改变HTID   进化改变武将的HTID
 * 
 * 转生之后的武将：不能卖、不能分解，不能强化其他武将、不能转生其他武将.
 * 五星及以上的武将：不能卖、不能分解、不能强化其他武将.
 * 
 * va_hero添加新的字段，需要改的地方：HeroLogic::getInitData和OtherHeroObj->getInfo, HeroObj->getHeroVaInfo
 *
 * @author dell
 *
 */

class Hero implements IHero
{
	/**
	 *
	 * @var UserObj
	 */
	private $userObj = null;

	public function __construct()
	{
		if (RPCContext::getInstance()->getUid()!=null)
		{
			$this->userObj = EnUser::getUserObj();
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHero::getAllHeros()
	 */
	public function getAllHeroes()
	{
		$heroMgr = $this->userObj->getHeroManager();
		//getAllHero会修复武将数据    如果hid在hero表和user表的unused字段都有   在unused字段删除
		$arrHeroInfo = $heroMgr->getAllHero();
		$arrHeroInfo = $this->fixEquip($arrHeroInfo);
		$this->userObj->update();
		$arrHeroInfo = $this->simplifyHeroInfo($arrHeroInfo);
		return $arrHeroInfo;
	}
	
	/**
	 * 1.删除武将身上丢失的装备
	 * 2.check武将身上是否有重复装备
	 * @param array $arrHeroInfo
	 */
	private function fixEquip($arrHeroInfo)
	{
	    $arrItemStatus = array();
	    $heroMgr = $this->userObj->getHeroManager();
	    foreach($arrHeroInfo as $heroInfo)
		{
			$hid = $heroInfo['hid'];
			$heroObj = $heroMgr->getHeroObj($hid);
			foreach(HeroDef::$ALL_EQUIP_TYPE as $type)
			{
			    $setFunc = HeroUtil::getSetEquipFunc($type);
				$arrItem = $heroObj->getEquipByType($type);
				foreach($arrItem as $pos => $itemId)
				{
				    if(empty($itemId))
				    {
				        continue;
				    }
				    //检查此装备是否还存在
					if(  $itemId > 0 && empty($heroInfo['equip'][$type][$pos]) )
					{
	               		call_user_func_array(array($heroObj, $setFunc), array($type, ItemDef::ITEM_ID_NO_ITEM, $pos) );
	               		Logger::fatal('fix hero missed equip. hid:%d, type:%s, pos:%d, itemId:%d', 
	               				$hid, $type, $pos, $itemId);
	               		continue;
					}
					//检查此装备是否在其他武将身上
					if(!isset($arrItemStatus[$itemId]))
					{
					    $arrItemStatus[$itemId] = $hid;
					}
					else
					{
					    call_user_func_array(array($heroObj, $setFunc), array($type, ItemDef::ITEM_ID_NO_ITEM, $pos) );
					    Logger::fatal('fix hero duplicated equip. hid:%d, type:%s, pos:%d, itemId:%d, it belong to hid %d',
					            $hid, $type, $pos, $itemId, $arrItemStatus[$itemId]);
					}
				}
			}
			$arrHeroInfo[$hid] = $heroObj->getInfo();
		}
		return $arrHeroInfo;
	}
	
	private function fixDressInfo($heroInfo)
	{
	    $fix = FALSE;
	    $htid = $heroInfo['htid'];
	    if(!HeroUtil::isMasterHtid($htid))
	    {
	        return $fix;
	    }
	    $equip = $heroInfo['equip'];
	    if(isset($equip[HeroDef::EQUIP_DRESS]) 
	            && (!empty($equip[HeroDef::EQUIP_DRESS])))
	    {
	        foreach($equip[HeroDef::EQUIP_DRESS] as $posId => $itemInfo)
	        {
	            if(empty($itemInfo))
	            {
	                continue;
	            }
	            if($this->userObj->setDressInfo($itemInfo['item_template_id'], $posId))
	            {
	                $fix = TRUE;
	            }
	        }
	    }
	    return $fix;
	}
	
    public function simplifyHeroInfo($arrHeroInfo)
	{
	    $heroMgr = $this->userObj->getHeroManager();
	    foreach($arrHeroInfo as $hid => $heroInfo)
	    {
	        if(EnFormation::isHidInFormation($hid, $this->userObj->getUid()))
	        {
	            continue;
	        }
	        unset($arrHeroInfo[$hid]['hid']);
	        $heroObj = $heroMgr->getHeroObj($hid);
	        if($heroObj->isEquiped() == FALSE)
	        {
	            //如果以后加上技能书或者其他装备   需要根据武将的等级或者进阶等级、花费金币等开启栏位   
	            //不能直接unset所有的equip
	            unset($arrHeroInfo[$hid]['equip']);
	        }
	        else if(EnFormation::isHidInFormation($hid, $this->userObj->getUid()) == FALSE)
	        {
	            Logger::warning('the hero %d is not in formation.but has equip.hero info is %d.',$hid,$heroInfo);
	        }
	        if($heroObj->getSoul() == 0)
	        {
	            unset($arrHeroInfo[$hid]['soul']);
	        }
	        if($heroObj->getLevel() == 1)
	        {
	            unset($arrHeroInfo[$hid]['level']);
	        }
	        if($heroObj->getEvolveLv() == 0)
	        {
	            unset($arrHeroInfo[$hid]['evolve_level']);
	        }
	        $talentInfo = $arrHeroInfo[$hid]['talent'];
	        foreach($talentInfo as $key => $info)
	        {
	            if(empty($info))
	            {
	                unset($arrHeroInfo[$hid]['talent'][$key]);
	            }
	        }
	        if(empty($arrHeroInfo[$hid]['talent']))
	        {
	            unset($arrHeroInfo[$hid]['talent']);
	        }
	        if(empty($arrHeroInfo[$hid]['transfer']))
	        {
	            unset($arrHeroInfo[$hid]['transfer']);
	        }
	        if(empty($arrHeroInfo[$hid]['dxtrans']))
	        {
	        	unset($arrHeroInfo[$hid]['dxtrans']);
	        }
            $pillInfo = $arrHeroInfo[$hid]['pill'];
            if(empty($pillInfo))
            {
                unset($arrHeroInfo[$hid]['pill']);
            }
            $masterTalentInfo = $arrHeroInfo[$hid]['masterTalent'];
            if(empty($masterTalentInfo))
            {
                unset($arrHeroInfo[$hid]['masterTalent']);
            }
	    }
	    return $arrHeroInfo;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHero::getAllFragments()
	 */
	public function getAllFragments()
	{
		throw new FakeException( 'not implement' );
	}
	
	
	/**
	 * 消耗卡牌将魂（经验）强化另一个卡牌
	 * @param int $hid
	 * @param array $consumeHids
	 * @return array
	 */
	public function enforceByHero($hid,$consumeHids)
	{
	    Logger::info('enforceByHero start.params:%s.%s.',$hid,$consumeHids);
	    list($hid,$consumeHids) = Util::checkParam(__METHOD__, func_get_args());
	    HeroLogic::canBeEnforced($hid);
	    $heroMng = $this->userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    $addSoul = 0;
	    foreach($consumeHids as $consumeHid)
	    {
	        $conHeroObj = $heroMng->getHeroObj($consumeHid);
	        if($conHeroObj->hasFiveStar() == TRUE)
	        {
	             throw new FakeException('can not consume this hero with hid %s.it has five star',$consumeHid);   
	        }
	        if($conHeroObj->canBeDel() == FALSE)
	        {
	            throw new FakeException('can not consume this hero with hid %s.',$consumeHid);
	        }
	        $addSoul    +=    ($conHeroObj->getSoul()+$conHeroObj->getConf(CreatureAttr::SOUL));
	        $heroMng->delHeroByHid($consumeHid);
	    }
	    $heroLv    =    $heroObj->addSoul($addSoul);
	    $needSilver = intval($addSoul*intval(Creature::getCreatureConf($heroObj->getHtid(),CreatureAttr::LVLUP_RATIIO))/100);
	    if($this->userObj->subSilver($needSilver) == FALSE)
	    {
	        throw new FakeException('sub silver failed.');
	    }
	    HeroLogic::refershFmtOnHeroChange($hid);
	    $this->userObj->update();
	    $ret    =    array(
	            'err'=>'ok',
	            'soul'=>$addSoul, 
	            'silver'=>$needSilver, 
	            'level'=>$heroLv
	            );
	    Logger::trace('enforceByHero end.return:%s.',$ret);
	    return $ret;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see IHero::enforce()
	 */
	public function enforce($hid,$enforceNum)
	{
		Logger::trace('hero.resolve start.params:%s,%s.',$hid,$enforceNum);
		list($hid,$enforceNum) = Util::checkParam(__METHOD__, func_get_args());
		HeroLogic::canBeEnforced($hid);
		$heroObj = $this->userObj->getHeroManager()->getHeroObj($hid);
		$heroLv = $heroObj->getLevel();
		$maxEnforceLv = $heroObj->getMaxEnforceLevel();
		if ($heroLv + $enforceNum > intval($maxEnforceLv))
		{
		    $enforceNum = $maxEnforceLv-$heroLv;
		}
		$expTblId	= Creature::getCreatureConf($heroObj->getHtid(),CreatureAttr::EXP_ID);
		$expTbl		= btstore_get()->EXP_TBL[$expTblId];
		while($enforceNum>0)
		{
		    $needSoul   = $expTbl[$heroLv+$enforceNum] - $heroObj->getSoul();
		    if($needSoul < 0)
		    {
		        $needSoul = 0;
		    }
		    //消耗银币    =    获得将魂*LVLUP_RATIIO/100
		    $needSilver = intval($needSoul*$heroObj->getConf(CreatureAttr::LVLUP_RATIIO)/100);
		    if($this->userObj->getSoul() > $needSoul && ($this->userObj->getSilver() > $needSilver))
		    {
		        break;
		    }
		    $enforceNum = $enforceNum-1;
		}
		if ((!$this->userObj->subSoul($needSoul)) || (!$this->userObj->subSilver($needSilver)))
		{
			throw new FakeException('lack soul: %d or lack silver: %d' , $needSoul , $needSilver);
		}
		$heroObj->addSoul($needSoul);
		HeroLogic::refershFmtOnHeroChange($hid);
		$this->userObj->update();
		$ret    =    array(
		        'soul'=>$needSoul,'silver'=>$needSilver,
		        'level'=>$heroObj->getLevel(),'hero_soul'=>$heroObj->getSoul()
		        );
		Logger::trace('hero.resolve end.return: %s.',$ret);
		return $ret;
	}
	
	public function sell($hids)
	{
	    $silverGot    =    0;
	    Logger::trace('hero.sell start.params:%s.',$hids);
	    list($hids) = Util::checkParam(__METHOD__, func_get_args());
	    foreach($hids as $hid)
	    {
	        $heroObj	=	$this->userObj->getHeroManager()->getHeroObj($hid);
	        if($heroObj->canBeSell() == FALSE)
	        {
	            throw new FakeException('the hero %s can not be sell,because its starlevel is bigger than 5.',$hid);
	        }
	        if($heroObj->canBeDel() == FALSE)
	        {
	            throw new FakeException('the hero %s can not be selled.heroinfo is %s.',$hid,$heroObj->getAllAttr());
	        }
	        $silverGot += Creature::getCreatureConf($heroObj->getHtid(),CreatureAttr::PRICE);
	        $this->userObj->getHeroManager()->delHeroByHid($hid);
	    }
	    $this->userObj->addSilver($silverGot);
		$this->userObj->update();
		Logger::trace('hero.sell end.return:%s.',$silverGot);
		return $silverGot;
	}
	
	
	/**
	 * 武将转生
	 * 普通武将进化之后等级变成1，经验为0；而主角武将进化之后经验和等级都不变
	 * @see IHero::evolve()
	 * @param $hid int
	 * @param $hidsConsume array (hid)
	 * @param $arrItem array
	 *         [
	 *             item_id=>item_num
	 *         ]
	 */
	public function evolve($hid,$hidsConsume,$arrItem)
	{
		Logger::trace('Hero.evolve satrt.params:%s,%s.',$hid,$hidsConsume);
		list($hid,$hidsConsume) = Util::checkParam(__METHOD__, func_get_args());
		$needStar =	HeroLogic::canEvolve($hid);
		$heroObj = $this->userObj->getHeroManager()->getHeroObj($hid);
		$evolveTblId = HeroLogic::getEvolveTbl($heroObj->getHtid(), $heroObj->getEvolveLv());
		if(intval(btstore_get()->HERO_CONVERT[$evolveTblId]['fromHtid']) != $heroObj->getHtid())
		{
		    throw new ConfigException('hero or hero_transfer table %d error.fromHtid %d in hero transfer is not equal to the htid %d of hero.',
		            $evolveTblId,
		            intval(btstore_get()->HERO_CONVERT[$evolveTblId]['fromHtid']),
		            $heroObj->getHtid());
		}
		//消耗银币
		$needSilver	=	intval(btstore_get()->HERO_CONVERT[$evolveTblId]['needSilver']);
		if($this->userObj->subSilver($needSilver) == FALSE)
		{
			throw new FakeException('user has not enough silver to evolve hero %s.',$hid);
		}
		//消耗材料
		$arrDeleteItem = HeroLogic::consumeItemOnHeroEvolve($hid, $arrItem);
		//消耗武将
		$conHeroes	=	HeroLogic::consumeHeroOnHeroEvolve($hid, $hidsConsume);
		if($hidsConsume != $conHeroes)
		{
		    Logger::fatal('hero.evolve param consumerHeroes from fang is %s,consume actually is %s.',$hidsConsume,$conHeroes);
		}
		$newHtid = intval(btstore_get()->HERO_CONVERT[$evolveTblId]['toHtid']);
		$oldHtid = $heroObj->getHtid();
		$transfer = $heroObj->getTransfer();
		if(!empty($transfer))
		{
		    Logger::info('hero %d has transfer %d',$hid,$transfer);
		    $heroObj->unsetTransfer();
		    $heroObj->unsetDXTrans();
		}
		$newStar = Creature::getHeroConf($newHtid, CreatureAttr::STAR_LEVEL);
		$oldStar = Creature::getHeroConf($oldHtid, CreatureAttr::STAR_LEVEL);
		$evolveLv = $heroObj->getEvolveLv();
		$heroObj->convertUp();
		HeroLogic::refershFmtOnHeroChange($hid);
		BagManager::getInstance()->getBag()->update();
		$this->userObj->update();
		if($newStar > $oldStar && ($oldStar >= 3) && ($heroObj->isMasterHero()))
		{
		    ChatTemplate::sendMasterHeroEnforce($this->userObj->getTemplateUserInfo(), $newStar);
		}
		ChatTemplate::sendHeroEvolve($this->userObj->getTemplateUserInfo(), $oldHtid, $heroObj->getEvolveLv());
		$consume	=	array(
		        'star'=>$needStar,
		        'silver'=>$needSilver,
		        'hero'=>$conHeroes,
		        'evolve_level'=>$heroObj->getEvolveLv());
		if($hid == $this->userObj->getMasterHid())
		{
		    EnAchieve::updateMHeroEvolve($this->userObj->getUid(), $heroObj->getEvolveLv());
		}
		else
		{
		    EnAchieve::updateHeroEvolve($this->userObj->getUid(), $heroObj->getEvolveLv());
		}
		Logger::trace('Hero.evolve end.Consume is %s.',$consume);
		return $consume;
	}

	/* (non-PHPdoc)
	 * @see IHero::addArming()
	*/
	public function addArming($hid, $pos, $itemId, $fromHid = 0)
	{	
	    Logger::trace('hero.addArming start.params:%s.%s.%s.%s',$hid,$pos,$itemId,$fromHid);
	    list($hid,$pos,$itemId) = Util::checkParam(__METHOD__, func_get_args());
	    $fromHid = intval($fromHid);
	    if ( !isset(ArmDef::$ARM_VALID_POSITIONS[$pos]) )
	    {
	        throw new FakeException('invalid pos:%d', $pos);
	    }
	    $item = ItemManager::getInstance()->getItem($itemId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    $itemType	= $item->getItemType();
	    $armType	= $item->getType();
	    if ( $itemType != ItemDef::ITEM_TYPE_ARM
	            || !in_array($armType, ArmDef::$ARM_VALID_POSITIONS[$pos]))
	    {
	        throw new FakeException('invalid itemType:%d, or armType:%d',$itemType, $armType);
	    }
	    //等级要求
	    $heroLevelReq = $item->getReqLevel();
	    $heroLevel = $this->userObj->getHeroManager()->getHeroObj($hid)->getLevel();
	    if (  $heroLevel < $heroLevelReq )
	    {
	        throw new FakeException('hero level:%d < req:%d!', $heroLevel, $heroLevelReq);
	    }
	    if(empty($fromHid))
	    {
	        HeroLogic::addEquip(HeroDef::EQUIP_ARMING, $hid, $pos, $itemId);
	    }
	    else  
	    {
	        if($hid == $fromHid)
	        {
	            throw new FakeException('same hid.error param %s',func_get_args());
	        }
	        HeroLogic::unEquipeHero($hid,HeroDef::EQUIP_ARMING,array($pos));
	        HeroLogic::changeEquip($fromHid,  $hid, array(HeroDef::EQUIP_ARMING), array($pos));
	    }
	    
	    $uid = RPCContext::getInstance()->getUid();
	    ForgeLogic::activateArmAchieve($uid);
		return 'ok';
	}
	/**
	 * 每次加战魂时，判断玩家开启了哪些格子  将数据库中没有开启的格子开启
	 * 前端保证格子自动开启（显示）
	 * (non-PHPdoc)
	 * @see IHero::addFashion()
	 */
	public function addFashion($pos,$fashionId)
	{
	    Logger::trace('hero.addArming start.params:%s.%s.',$pos,$fashionId);
	    list($pos,$fashionId) = Util::checkParam(__METHOD__, func_get_args());
	    if(EnSwitch::isSwitchOpen(SwitchDef::DRESS) == FALSE)
	    {
	        throw new FakeException('switch dress is not open');
	    }
	    $item = ItemManager::getInstance()->getItem($fashionId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $fashionId);
	    }
	    if(!in_array($pos, HeroDef::$VALID_DRESS_POS))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.',$pos,HeroDef::$VALID_DRESS_POS);
	    }
	    $itemType	= $item->getItemType();
	    if ( $itemType != ItemDef::ITEM_TYPE_DRESS)
	    {
	        throw new FakeException('invalid itemType:%d',$itemType);
	    }
	    $hid = $this->userObj->getMasterHid();
	    $this->userObj->setDressInfo($item->getItemTemplateID(), $pos);
	    HeroLogic::addEquip(HeroDef::EQUIP_DRESS, $hid, $pos, $fashionId);
	    EnDressRoom::setCurDress($this->userObj->getUid(), $item->getItemTemplateID());
	    return 'ok';
	}
	
	public function addFightSoul($hid,$pos,$itemId,$fromHid=0)
	{
	    Logger::trace('hero.addFightSoul start.params:%d.%d.%d.',$hid,$pos,$itemId);
	    list($hid,$pos,$itemId) = Util::checkParam(__METHOD__, func_get_args());
	    $fromHid = intval($fromHid);
	    if(EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == FALSE)
	    {
	        throw new FakeException('switch fightsoul is not open');
	    }
	    $item = ItemManager::getInstance()->getItem($itemId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    $itemTmplId = $item->getItemTemplateID();
	    $itemType = $item->getType();
	    $heroObj = $this->userObj->getHeroManager()->getHeroObj($hid);
	    $fightSoul = $heroObj->getEquipByType(HeroDef::EQUIP_FIGHTSOUL);
	    foreach($fightSoul as $tmpPos => $equipItemId)
	    {
	        if($tmpPos == $pos)
	        {
	            continue;
	        }
	        if($equipItemId == ItemDef::ITEM_ID_NO_ITEM)
	        {
	            continue;
	        }
	        $equipItem = ItemManager::getInstance()->getItem($equipItemId);
	        if(empty($equipItem))
	        {
	            Logger::warning('equip fightsoul %d is not exist.equipinfo %s',$equipItemId,$fightSoul);
	            continue;
	        }
	        if($equipItem->getType() == $itemType)
	        {
	            throw new FakeException('this hero %d has equip this type %d in pos %d,equipinfo %s',
	                    $hid,$itemType,$tmpPos,$fightSoul);
	        }
	    }
	    $validPos = HeroLogic::getValidFightSoulPos($this->userObj->getUid());
	    if(!isset($validPos[$pos]))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.or is not open.user level is %d',$pos,$validPos,$this->userObj->getLevel());
	    }
	    $itemType	= $item->getItemType();
	    if ( $itemType != ItemDef::ITEM_TYPE_FIGHTSOUL)
	    {
	        throw new FakeException('invalid itemType:%d',$itemType);
	    }
	    if(empty($fromHid))
	    {
	        HeroLogic::addEquip(HeroDef::EQUIP_FIGHTSOUL, $hid, $pos, $itemId);
	    }
	    else  
	    {
	        $heroMng = $this->userObj->getHeroManager();
	        $fromHero = $heroMng->getHeroObj($fromHid);
	        $toHero = $heroMng->getHeroObj($hid);
	        $fromPos = $fromHero->getFightSoulPos($itemId);
	        if($fromPos == HeroDef::INVALID_EQUIP_POSITION)
	        {
	            throw new FakeException('fightsoul %d is not in hero %d',$itemId,$fromHid);
	        }
	        $oldItem = $toHero->getEquipByPos(HeroDef::EQUIP_FIGHTSOUL, $pos);
	        if($oldItem == $itemId)
	        {
	            throw new FakeException('change same item %d',$itemId);
	        }
	        $bag = BagManager::getInstance()->getBag($this->userObj->getUid());
	        if($oldItem != ItemDef::ITEM_ID_NO_ITEM)
	        {
	            $bag->addItem($oldItem,TRUE);
	        }
	        $fromHero->setEquipByPos(HeroDef::EQUIP_FIGHTSOUL, ItemDef::ITEM_ID_NO_ITEM, $fromPos);
	        $toHero->setEquipByPos(HeroDef::EQUIP_FIGHTSOUL,$itemId, $pos);
	        $this->userObj->update();
	        HeroLogic::refershFmtOnHeroChange($hid);
	        $bag->update();
	    }
	    return 'ok';
	}
	
	public function addTreasure($hid, $pos, $itemId, $fromHid = 0)
	{
	    Logger::trace('hero.addTreasure start.params:%s.%s.%s.%s',$hid,$pos,$itemId,$fromHid);
	    list($hid,$pos,$itemId) = Util::checkParam(__METHOD__, func_get_args());
	    if ( !isset(TreasureDef::$TREASURE_VALID_POSITIONS[$pos]) )
	    {
	        throw new FakeException('invalid treasure pos:%d', $pos);
	    }
	    $item = ItemManager::getInstance()->getItem($itemId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    if($item->getStackable() > 1)
	    {
	        throw new FakeException('item %d itemtmplid %d is stackable,cant equip.',$itemId,$item->getItemTemplateID());
	    }
	    $itemType	= $item->getItemType();
	    $treasureType	= $item->getType();
	    if ( $itemType != ItemDef::ITEM_TYPE_TREASURE
	            || !in_array($treasureType, TreasureDef::$TREASURE_VALID_POSITIONS[$pos]))
	    {
	        throw new FakeException('invalid itemType:%d, or treasureType:%d.%s',$itemType, $treasureType,TreasureDef::$TREASURE_VALID_POSITIONS[$pos]);
	    }
	    //暂时没有装备要求
	    if(empty($fromHid))
	    {
	        HeroLogic::addEquip(HeroDef::EQUIP_TREASURE, $hid, $pos, $itemId);
	    }
	    else
	    {
	        if($hid == $fromHid)
	        {
	            throw new FakeException('same hid.error param %s',func_get_args());
	        }
	        HeroLogic::unEquipeHero($hid,HeroDef::EQUIP_TREASURE,array($pos));
	        HeroLogic::changeEquip($fromHid,  $hid, array(HeroDef::EQUIP_TREASURE), array($pos));
	    }
	    
	    $uid = RPCContext::getInstance()->getUid();
	    ForgeLogic::activateTreasAchieve($uid);
	    Logger::trace('addTreasure end');
	    return 'ok';
	}
	
	public function addGodWeapon($hid, $pos, $itemId, $fromHid = 0)
	{
	    Logger::trace('hero.addGodWeapon start.params:%d.%d.%d.',$hid,$pos,$itemId);
	    list($hid,$pos,$itemId) = Util::checkParam(__METHOD__, func_get_args());
	    $fromHid = intval($fromHid);
	    $item = ItemManager::getInstance()->getItem($itemId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    $godWeaponType = $item->getType();
	    //金木水火土属性神兵对应1，2，3，4，5号栏位
	    if ($godWeaponType != $pos)
	    {
	    	throw new FakeException('invalid godWeapon type:%d, pos:%d', $godWeaponType, $pos);
	    }
	    $heroObj = $this->userObj->getHeroManager()->getHeroObj($hid);
	    $arrGodWeapon = $heroObj->getEquipByType(HeroDef::EQUIP_GODWEAPON);
	    foreach($arrGodWeapon as $tmpPos => $equipItemId)
	    {
	        if($tmpPos == $pos)
	        {
	            continue;
	        }
	        if($equipItemId == ItemDef::ITEM_ID_NO_ITEM)
	        {
	            continue;
	        }
	        $equipItem = ItemManager::getInstance()->getItem($equipItemId);
	        if(empty($equipItem))
	        {
	            Logger::warning('equip fightsoul %d is not exist.equipinfo %s',$equipItemId,$arrGodWeapon);
	            continue;
	        }
	        if($equipItem->getType() == $godWeaponType)
	        {
	            throw new FakeException('this hero %d has equip this type %d in pos %d,equipinfo %s',
	                    $hid,$godWeaponType,$tmpPos,$arrGodWeapon);
	        }
	    }
	    $validPos = HeroLogic::getValidGodWeaponPos($this->userObj->getUid());
	    if(!isset($validPos[$pos]))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.or is not open.user level is %d',$pos,$validPos,$this->userObj->getLevel());
	    }
	    $itemType	= $item->getItemType();
	    if ( $itemType != ItemDef::ITEM_TYPE_GODWEAPON)
	    {
	        throw new FakeException('invalid itemType:%d',$itemType);
	    }
	    if(empty($fromHid))
	    {
	        HeroLogic::addEquip(HeroDef::EQUIP_GODWEAPON, $hid, $pos, $itemId);
	    }
	    else  
	    {
	        $heroMng = $this->userObj->getHeroManager();
	        $fromHero = $heroMng->getHeroObj($fromHid);
	        $toHero = $heroMng->getHeroObj($hid);
	        $fromPos = $fromHero->getGodWeaponPos($itemId);
	        if($fromPos == HeroDef::INVALID_EQUIP_POSITION)
	        {
	            throw new FakeException('godweapon %d is not in hero %d',$itemId,$fromHid);
	        }
	        $oldItem = $toHero->getEquipByPos(HeroDef::EQUIP_GODWEAPON, $pos);
	        if($oldItem == $itemId)
	        {
	            throw new FakeException('change same item %d',$itemId);
	        }
	        $bag = BagManager::getInstance()->getBag($this->userObj->getUid());
	        if($oldItem != ItemDef::ITEM_ID_NO_ITEM)
	        {
	            $bag->addItem($oldItem,TRUE);
	        }
	        $fromHero->setEquipByPos(HeroDef::EQUIP_GODWEAPON, ItemDef::ITEM_ID_NO_ITEM, $fromPos);
	        $toHero->setEquipByPos(HeroDef::EQUIP_GODWEAPON, $itemId, $pos);
	        $this->userObj->update();
	        HeroLogic::refershFmtOnHeroChange($hid);
	        $bag->update();
	    }
	    return 'ok';
	}
	
	public function addPocket($hid, $pos, $itemId, $fromHid = 0)
	{
	    list($hid, $pos, $itemId) = Util::checkParam(__METHOD__, func_get_args());
	    $fromHid = intval($fromHid);
	    $uid = RPCContext::getInstance()->getUid();
	    $arrValidPos = HeroLogic::getValidPocketPos($uid);
	    if(FALSE == isset($arrValidPos[$pos]))
	    {
	        throw new FakeException('hid %d addpocket pos %d is not open.',$hid,$pos);
	    }
	    if(FALSE == EnSwitch::isSwitchOpen(SwitchDef::POCKET))
	    {
	        throw new FakeException('switch pocket is not open');
	    }
	    $item = ItemManager::getInstance()->getItem($itemId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    if($item->isExp())
	    {
	        throw new FakeException('exp pocket can not be equiped.params %s',func_get_args());
	    }
	    $itemType = $item->getItemType();
	    if ( $itemType != ItemDef::ITEM_TYPE_POCKET)
	    {
	        throw new FakeException('invalid itemType:%d not pocket',$itemType);
	    }
	    $pocketType = $item->getType();
	    $heroObj = $this->userObj->getHeroManager()->getHeroObj($hid);
	    $pocket = $heroObj->getEquipByType(HeroDef::EQUIP_POCKET);
	    foreach($pocket as $tmpPos => $equipItemId)
	    {
	        if($tmpPos == $pos)
	        {
	            continue;
	        }
	        if($equipItemId == ItemDef::ITEM_ID_NO_ITEM)
	        {
	            continue;
	        }
	        $equipItem = ItemManager::getInstance()->getItem($equipItemId);
	        if(empty($equipItem))
	        {
	            Logger::warning('equip pocket %d is not exist.equipinfo %s',$equipItemId,$pocket);
	            continue;
	        }
	        if($equipItem->getType() == $pocketType)
	        {
	            throw new FakeException('this hero %d has equip this type %d in pos %d,equipinfo %s',
	                    $hid,$pocketType,$tmpPos,$pocket);
	        }
	    }
	    if(empty($fromHid))
	    {
	        HeroLogic::addEquip(HeroDef::EQUIP_POCKET, $hid, $pos, $itemId);
	    }
	    else
	    { 
	        $heroMng = $this->userObj->getHeroManager();
	        $fromHero = $heroMng->getHeroObj($fromHid);
	        $toHero = $heroMng->getHeroObj($hid);
	        $fromPos = $fromHero->getPocketPos($itemId);
	        if($fromPos == HeroDef::INVALID_EQUIP_POSITION)
	        {
	            throw new FakeException('pocket %d is not in hero %d',$itemId,$fromHid);
	        }
	        $oldItem = $toHero->getEquipByPos(HeroDef::EQUIP_POCKET, $pos);
	        if($oldItem == $itemId)
	        {
	            throw new FakeException('change same item %d',$itemId);
	        }
	        $bag = BagManager::getInstance()->getBag($this->userObj->getUid());
	        if($oldItem != ItemDef::ITEM_ID_NO_ITEM)
	        {
	            $bag->addItem($oldItem,TRUE);
	        }
	        $fromHero->setEquipByPos(HeroDef::EQUIP_POCKET, ItemDef::ITEM_ID_NO_ITEM, $fromPos);
	        $toHero->setEquipByPos(HeroDef::EQUIP_POCKET, $itemId, $pos);
	        $this->userObj->update();
	        HeroLogic::refershFmtOnHeroChange($hid);
	        HeroLogic::refershFmtOnHeroChange($fromHid);
	        $bag->update();
	    }
	    return 'ok';
	}
	
	public function addTally($hid, $pos, $itemId, $fromHid = 0)
	{
		list($hid, $pos, $itemId) = Util::checkParam(__METHOD__, func_get_args());
	    $fromHid = intval($fromHid);
	    $uid = RPCContext::getInstance()->getUid();
	    $arrValidPos = HeroLogic::getValidTallyPos($uid);
	    if(FALSE == isset($arrValidPos[$pos]))
	    {
	        throw new FakeException('hid %d addtally pos %d is not open.',$hid,$pos);
	    }
	    if(FALSE == EnSwitch::isSwitchOpen(SwitchDef::TALLY))
	    {
	        throw new FakeException('switch tally is not open');
	    }
	    $item = ItemManager::getInstance()->getItem($itemId);
	    //物品和类别参数是否合法
	    if( empty($item) )
	    {
	        throw new FakeException('itemId:%d not exist', $itemId);
	    }
	    $itemType = $item->getItemType();
	    if ( $itemType != ItemDef::ITEM_TYPE_TALLY)
	    {
	        throw new FakeException('invalid itemType:%d not tally',$itemType);
	    }
	    $tallyType = $item->getType();
	    $heroObj = $this->userObj->getHeroManager()->getHeroObj($hid);
	    $tally = $heroObj->getEquipByType(HeroDef::EQUIP_TALLY);
	    foreach($tally as $tmpPos => $equipItemId)
	    {
	        if($tmpPos == $pos)
	        {
	            continue;
	        }
	        if($equipItemId == ItemDef::ITEM_ID_NO_ITEM)
	        {
	            continue;
	        }
	        $equipItem = ItemManager::getInstance()->getItem($equipItemId);
	        if(empty($equipItem))
	        {
	            Logger::warning('equip tally %d is not exist.equipinfo %s',$equipItemId,$tally);
	            continue;
	        }
	        if($equipItem->getType() == $tallyType)
	        {
	            throw new FakeException('this hero %d has equip this type %d in pos %d,equipinfo %s',
	                    $hid,$tallyType,$tmpPos,$tally);
	        }
	    }
	    if(empty($fromHid))
	    {
	        HeroLogic::addEquip(HeroDef::EQUIP_TALLY, $hid, $pos, $itemId);
	    }
	    else
	    { 
	        $heroMng = $this->userObj->getHeroManager();
	        $fromHero = $heroMng->getHeroObj($fromHid);
	        $toHero = $heroMng->getHeroObj($hid);
	        $fromPos = $fromHero->getTallyPos($itemId);
	        if($fromPos == HeroDef::INVALID_EQUIP_POSITION)
	        {
	            throw new FakeException('tally %d is not in hero %d',$itemId,$fromHid);
	        }
	        $oldItem = $toHero->getEquipByPos(HeroDef::EQUIP_TALLY, $pos);
	        if($oldItem == $itemId)
	        {
	            throw new FakeException('change same item %d',$itemId);
	        }
	        $bag = BagManager::getInstance()->getBag($this->userObj->getUid());
	        if($oldItem != ItemDef::ITEM_ID_NO_ITEM)
	        {
	            $bag->addItem($oldItem,TRUE);
	        }
	        $fromHero->setEquipByPos(HeroDef::EQUIP_TALLY, ItemDef::ITEM_ID_NO_ITEM, $fromPos);
	        $toHero->setEquipByPos(HeroDef::EQUIP_TALLY, $itemId, $pos);
	        $this->userObj->update();
	        HeroLogic::refershFmtOnHeroChange($hid);
	        HeroLogic::refershFmtOnHeroChange($fromHid);
	        $bag->update();
	    }
	    return 'ok';
	}
	
	/* (non-PHPdoc)
	 * @see IHero::removeArming()
	*/
	public function removeArming ($hid, $pos)
	{
	    Logger::info('hero.removeArming start.params:%s.%s.',$hid,$pos);
	    list($hid,$pos) = Util::checkParam(__METHOD__, func_get_args());
	    if ( !isset(ArmDef::$ARM_VALID_POSITIONS[$pos]) )
	    {
	        throw new FakeException('invalid pos:%d', $pos);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_ARMING, $hid, $pos);	
		Logger::trace('hero.removeArming end.');
		return 'ok';
	}
	
	public function removeFightSoul($hid,$pos)
	{
	    Logger::info('hero.removeFightSoul start.params:%s.%s.',$hid,$pos);
	    list($hid,$pos) = Util::checkParam(__METHOD__, func_get_args());
	    if(EnSwitch::isSwitchOpen(SwitchDef::FIGHTSOUL) == FALSE)
	    {
	        throw new FakeException('switch fightsoul is not open');
	    }
	    $validPos = HeroLogic::getValidFightSoulPos($this->userObj->getUid());
	    if(!isset($validPos[$pos]))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.or is not open',$pos,$validPos);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_FIGHTSOUL, $hid, $pos);
	    Logger::trace('hero.removeFightSoul end.');
	    return 'ok';
	}
	
	public function removeFashion($pos)
	{
	    $hid = $this->userObj->getMasterHid();
	    Logger::info('hero.removeFashion start.params:%s.%s.',$hid,$pos);
	    list($pos) = Util::checkParam(__METHOD__, func_get_args());
	    if(EnSwitch::isSwitchOpen(SwitchDef::DRESS) == FALSE)
	    {
	        throw new FakeException('switch dress is not open');
	    }
	    if(!in_array($pos, HeroDef::$VALID_DRESS_POS))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.',$pos,HeroDef::$VALID_DRESS_POS);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_DRESS, $hid, $pos);
	    Logger::trace('hero.removeFashion end.');
	    return 'ok';
	}
	
	public function removeTreasure($hid=0,$pos=-1)
	{
	    Logger::trace('hero.removeTreasure start.params:%s.%s.',$hid,$pos);
	    if(empty($hid) || $pos == -1)
	    {
	        Logger::warning('hero.removeTreasure param error.%d %d',$hid,$pos);
	        return 'err';
	    }
	    list($hid,$pos) = Util::checkParam(__METHOD__, func_get_args());
	    if ( !isset(TreasureDef::$TREASURE_VALID_POSITIONS[$pos]) )
	    {
	        throw new FakeException('invalid treasure pos:%d', $pos);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_TREASURE, $hid, $pos);
	    Logger::trace('hero.removeTreasure end.');
	    return 'ok';
	}
	
	public function removeGodWeapon($hid, $weaponPos)
	{
	    Logger::info('hero.removeGodWeapon start.params:%s.%s.',$hid,$weaponPos);
	    list($hid,$weaponPos) = Util::checkParam(__METHOD__, func_get_args());
	    $validPos = HeroLogic::getValidGodWeaponPos($this->userObj->getUid());
	    if(!isset($validPos[$weaponPos]))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.or is not open',$weaponPos,$validPos);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_GODWEAPON, $hid, $weaponPos);
	    Logger::trace('hero.removeGodWeapon end.');
	    return 'ok';
	}
	
	public function removePocket($hid, $pos)
	{
	    Logger::trace('hero.removePocket start.params:%s.%s.',$hid,$pos);
	    list($hid,$pos) = Util::checkParam(__METHOD__, func_get_args());
	    if(FALSE == EnSwitch::isSwitchOpen(SwitchDef::POCKET))
	    {
	        throw new FakeException('switch pocket is not open');
	    }
	    $validPos = HeroLogic::getValidPocketPos($this->userObj->getUid());
	    if(!isset($validPos[$pos]))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.or is not open',$pos,$validPos);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_POCKET, $hid, $pos);
	    Logger::trace('hero.removePocket end.');
	    return 'ok';
	}
	
	public function removeTally($hid, $pos)
	{
		Logger::trace('hero.removeTally start.params:%s.%s.',$hid,$pos);
	    list($hid,$pos) = Util::checkParam(__METHOD__, func_get_args());
	    if(FALSE == EnSwitch::isSwitchOpen(SwitchDef::TALLY))
	    {
	        throw new FakeException('switch tally is not open');
	    }
	    $validPos = HeroLogic::getValidTallyPos($this->userObj->getUid());
	    if(!isset($validPos[$pos]))
	    {
	        throw new FakeException('pos %d is not in valid poses %s.or is not open',$pos,$validPos);
	    }
	    HeroLogic::removeEquip(HeroDef::EQUIP_TALLY, $hid, $pos);
	    Logger::trace('hero.removePocket end.');
	    return 'ok';
	}
	
	/**
	 * 从背包里换装   装备最好的武器和宝物（arming和treasure）
	 * @param int $hid
	 * @throws InterException
	 */
	public function equipBestArming($hid)
	{
	    Logger::trace('hero.equipBestArming start.params:%s.',$hid);
	    list($hid) = Util::checkParam(__METHOD__, func_get_args());
	    $uid = $this->userObj->getUid();
        $retArming = HeroLogic::equipBestEquip($hid, HeroDef::EQUIP_ARMING, $uid);
        $retTreasure = HeroLogic::equipBestEquip($hid, HeroDef::EQUIP_TREASURE, $uid);
	    BagManager::getInstance()->getBag($uid)->update();
	    HeroLogic::refershFmtOnHeroChange($hid);
	    $this->userObj->update();
	    
	    $uid = RPCContext::getInstance()->getUid();
	    ForgeLogic::activateArmAchieve($uid);
	    ForgeLogic::activateTreasAchieve($uid);
	    Logger::trace('hero.equipBestArming end.ret:%s.%s',$retArming,$retTreasure);
	    return $retArming+$retTreasure;
	}
	
	/**
	 * 装备战魂的的规则：
	 * 1.从背包中选择战魂每个类型中最好的物品，然后根据品质高低进行排序，如果品质相同按下面的规则进行排序
	 *     第1优先级：类型1和类型2
	 *     第2优先级：类型6、类型4和类型5
	 *     第3优先级：类型7
	 *     第4优先级：类型3
	 *     第5优先级：类型8
	 *     优先级相同的时候随机选一个
	 * 2.若当前有装备同类型的战魂，检查背包有无同类型且星级更高的，若有就直接替换，若无，就弹提示
	 * (non-PHPdoc)
	 * @see IHero::equipBestFightSoul
	 */
	public function equipBestFightSoul($hid)
	{
	    Logger::trace('hero.equipBestArming start.params:%s.',$hid);
	    list($hid) = Util::checkParam(__METHOD__, func_get_args());
	    $uid = $this->userObj->getUid();
	    $retFightSoul = HeroLogic::equipBestEquip($hid, HeroDef::EQUIP_FIGHTSOUL, $uid);
	    BagManager::getInstance()->getBag($uid)->update();
	    HeroLogic::refershFmtOnHeroChange($hid);
	    $this->userObj->update();
	    Logger::trace('hero.equipBestArming end.ret:%s.',$retFightSoul);
	    return $retFightSoul;
	}
	
	public function getHeroBook($uid=0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    $bookInfo = HeroLogic::getHeroBookInfo($uid);
	    if(!empty($bookInfo))
	    {
	        return $bookInfo[HeroBookDao::FIELD_VA_BOOK]['hero'];
	    }
	    return $bookInfo;
	}
	
	public function lockHero($hid)
	{
	    Logger::trace('hero.lockHero start.hid %d.',$hid);
	    list($hid) = Util::checkParam(__METHOD__, func_get_args());
	    $heroMng = $this->userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if(empty($heroObj))
	    {
	        throw new FakeException('no this hero %d.',$hid);
	    }
	    if($heroObj->getStarLv() < 5)
	    {
	        throw new FakeException('can only lock five star hero.hero %d star lv is %d',$hid,$heroObj->getStarLv());
	    }
	    if($heroObj->isLocked())
	    {
	        Logger::warning('hero %d is locked.can not be locked again.',$hid);
	        return 'ok';
	    }
	    $heroObj->lock();
	    $heroMng->update();
	    return 'ok';
	}
	
	public function unlockHero($hid)
	{
	    Logger::trace('hero.lockHero start.hid %d.',$hid);
	    list($hid) = Util::checkParam(__METHOD__, func_get_args());
	    $heroMng = $this->userObj->getHeroManager();
	    $heroObj = $heroMng->getHeroObj($hid);
	    if(empty($heroObj))
	    {
	        throw new FakeException('no this hero %d.',$hid);
	    }
	    if($heroObj->isLocked() == FALSE)
	    {
	        Logger::warning('hero %d is not locked.can not be unlocked.',$hid);
	        return 'ok';
	    }
	    $heroObj->unLock();
	    $heroMng->update();
	    return 'ok';
	}
	
	public function activateTalent($hid,$talentIndex,$spendIndex,$batchOp,$num=1)
	{
	    Logger::trace('hero.activateTalent start.hid %d.',$hid);
	    list($hid,$talentIndex,$spendIndex,$batchOp) = Util::checkParam(__METHOD__, func_get_args());
	    $num = intval($num);
	    if($num <= 0 || $num > 10)
	    {
	        throw new FakeException('activateTalent num %d invalid',$num);
	    }
	    if(FALSE == $batchOp && ($num != 1))
	    {
	        throw new FakeException('error params %s',func_get_args());
	    }
	    $ret = HeroLogic::activateTalent($hid, $this->userObj->getUid(),$talentIndex,$spendIndex,$batchOp,$num);
	    return $ret;
	}
	
	public function activateTalentConfirm($hid,$talentIndex,$talentId)
	{
	    Logger::trace('hero.activateTalentConfirm start.hid %d.',$hid);
	    list($hid,$talentIndex) = Util::checkParam(__METHOD__, func_get_args());
	    $ret = HeroLogic::activateTalentConfirm($hid, $this->userObj->getUid(),$talentIndex,$talentId);
	    return $ret;
	}
	
	public function activateTalentUnDo($hid,$talentIndex)
	{
	    Logger::trace('hero.activateTalentUnDo start.hid %d.',$hid);
	    list($hid,$talentIndex) = Util::checkParam(__METHOD__, func_get_args());
	    $ret = HeroLogic::activateTalentUnDo($hid, $this->userObj->getUid(),$talentIndex);
	    return $ret;
	}
	
	public function inheritTalent($fromHid,$toHid,$arrTalentIndex)
	{
	    Logger::trace('hero.inheritTalent start.params fromhid %d tohid %d arrtalentindex %s',$fromHid,$toHid,$arrTalentIndex);
	    list($fromHid,$toHid,$arrTalentIndex) = Util::checkParam(__METHOD__,func_get_args());
	    $ret = HeroLogic::inheritTalent($this->userObj->getUid(), $fromHid, $toHid, $arrTalentIndex);
	    return $ret;
	}

	public function transfer($hid, $countryId, $htid = 0)
	{
		Logger::trace('hero.transfer start.params hid %d countryId %d htid %d',$hid,$countryId,$htid);
		list($hid, $countryId) = Util::checkParam(__METHOD__,func_get_args());
		$htid = intval($htid);
		$ret = HeroLogic::transfer($this->userObj->getUid(), $hid, $countryId, $htid);
		return $ret;
	}
	
	public function transferConfirm($hid)
	{
		Logger::trace('hero.transferConfirm start.params hid %d',$hid);
		list($hid) = Util::checkParam(__METHOD__,func_get_args());
		$ret = HeroLogic::transferConfirm($this->userObj->getUid(), $hid);
		return $ret;
	}
	
	public function transferCancel($hid)
	{
		Logger::trace('hero.transferCancel start.params hid %d',$hid);
		list($hid) = Util::checkParam(__METHOD__,func_get_args());
		$ret = HeroLogic::transferCancel($this->userObj->getUid(), $hid);
		return $ret;
	}

	public function develop($hid,$arrHero,$arrItem)
	{
	    Logger::trace('hero.develop start.hid %d.arrhero %s,arritem %s',$hid,$arrHero,$arrItem);
	    list($hid,$arrHero,$arrItem) = Util::checkParam(__METHOD__, func_get_args());
	    $ret = HeroLogic::develop($this->userObj->getUid(), $hid,$arrHero,$arrItem);
	    return $ret;
	}
	
	public function develop2red($hid, $arrHero, $arrItem)
	{
		Logger::trace('hero.develop2red start.hid %d.arrhero %s,arritem %s',$hid, $arrHero, $arrItem);
		list($hid, $arrHero, $arrItem) = Util::checkParam(__METHOD__, func_get_args());
		$ret = HeroLogic::develop2red($this->userObj->getUid(), $hid, $arrHero, $arrItem);
		return $ret;
	}
	
	public function activateSealTalent($hid)
	{
	    Logger::trace('hero.activateSealTalent start.hid %d',$hid);
	    list($hid) = Util::checkParam(__METHOD__, func_get_args());
	    $ret = HeroLogic::activateSealTalent($this->userObj->getUid(), $hid);
	    return $ret;
	}

    public function addPill($hid, $index, $itemId)
    {
        Logger::trace('hero::addElixir start. hid:%d, index:%d, itemId:%d', $hid, $index, $itemId);
        list($hid, $index, $itemId) = Util::checkParam(__METHOD__, func_get_args());
        $ret = HeroLogic::addPill($hid, $this->userObj->getUid(), $index, $itemId);
        return $ret;
    }

	public function removePill($hid, $index)
	{
		Logger::trace('hero::removePill start. hid:%d, index:%d', $hid, $index);
		list($hid, $index) = Util::checkParam(__METHOD__, func_get_args());
		$ret = HeroLogic::removePill($hid, $this->userObj->getUid(), $index);
		return $ret;
	}

	public function removePillByType($hid, $type)
	{
		Logger::trace("hero::removePillByAttr start. hid:%d, type:%d", $hid, $type);
		list($hid, $type) = Util::checkParam(__METHOD__, func_get_args());
		$ret = HeroLogic::removePillByType($hid, $this->userObj->getUid(), $type);
		return $ret;
	}

    public function activeMasterTalent($index, $talentId)
    {
        Logger::trace("hero::activeMasterTalent start. index:%d, talentId:%d", $index, $talentId);
        list($index, $talentId) = Util::checkParam(__METHOD__, func_get_args());
        if($index > 3 || $index <= 0)
        {
            throw new FakeException("error param index:%d", $index);
        }

        return HeroLogic::activeMasterTalent($this->userObj->getUid(), $index, $talentId);
    }
    
    public function addArrPills($hid, $pillType)
    {
    	$uid = RPCContext::getInstance()->getUid();
    	
    	return HeroLogic::addArrPills($uid, $hid, $pillType);
    }
    
    public function activeDestiny($hid, $id)
    {
    	Logger::trace("hero::activeDestiny start. hid:%d, id:%d", $hid, $id);
    	if($hid <= 0 || $id <= 0)
    	{
    		throw new FakeException("error param hid:%d, id:%d", $hid, $id);
    	}
    	
    	return HeroLogic::activeDestiny($this->userObj->getUid(), $hid, $id);
    }
    
    public function resetDestiny($hid)
    {
    	Logger::trace("hero::resetDestiny start. hid:%d", $hid);
    	if($hid <= 0)
    	{
    		throw new FakeException("error param hid:%d", $hid);
    	}
    	 
    	return HeroLogic::resetDestiny($this->userObj->getUid(), $hid);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */