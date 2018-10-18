<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: HeroObj.class.php 251365 2016-07-13 05:36:38Z QingYao $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/HeroObj.class.php $
 * @author $Author: QingYao $(lanhongyu@babeltime.com)
 * @date $Date: 2016-07-13 05:36:38 +0000 (Wed, 13 Jul 2016) $
 * @version $Revision: 251365 $
 * @brief
 *
 **/
require_once (ROOT.'/def/Creature.def.php');

class HeroObj extends OtherHeroObj
{


	protected static $affectedBattleField = array('htid', 'level', 'va_hero');

	public function __construct ($attr)
	{
        parent::__construct($attr);

        $this->fixLevel();//FIXME: 上线前，删掉
	}

	public function isEquipedByType($type)
	{
	    if(!isset($this->heroModify['va_hero'][$type]))
	    {
	        Logger::warning('no such equiptype %s.',$type);
	        return FALSE;
	    }
	    foreach($this->heroModify['va_hero'][$type] as $posId => $itemId)
	    {
	        if($itemId!=ItemDef::ITEM_ID_NO_ITEM)
	        {
	            return TRUE;
	        }
	    }
	    return FALSE;
	}

	/**
	 * (non-PHPdoc)
	 * @see OtherHeroObj::canBeDel()
	 * 在阵型中或者装备或者转生过的武将不能被删除
	 * 主角武将不能被删除
	 *
	 * 当前看来：进阶等级大于等于1的武将时不能删除的      进阶过的武将只能重生 然后再分解或者出售或者强化其他武将
	 */
	public function canBeDel()
	{
		if(EnFormation::isHidInFormation($this->heroModify['hid'], $this->heroModify['uid']) ||
		        EnFormation::isHidInExtra($this->heroModify['hid'], $this->heroModify['uid']) ||
		        EnFormation::isHidInAttrExtra($this->heroModify['hid'], $this->heroModify['uid']) ||
				 $this->isEquiped()  ||  ($this->isMasterHero()))
		{
			return FALSE;
		}
		if($this->getEvolveLv() > 0)
		{
		    return FALSE;
		}
		if(EnPass::canArrHidBeDel($this->heroModify['uid'], array($this->getHid())) == FALSE)
		{
		    return FALSE;
		}
		return TRUE;
	}

	public function setLevel($level)
	{
		if ( ! $this->isMasterHero() )
		{
			throw new InterException('can only set level of master hero,%s.',$this->heroModify);
		}
		$this->heroModify['level'] = $level;
		$this->heroModify['upgrade_time'] = Util::getTime();
	}
	/**
	 * @author jinyang
	 * @param int $newHtid
	 * @abstract 变性使用
	 */
	public function setMasterHtid($newHtid)
	{
	    if (!$this->isMasterHero())
	        throw new FakeException('can only set htid of master hero.');
	    $this->heroModify['htid'] = $newHtid;
	}
	public function setEvolveLevel($lv)
	{
		/*
		if(!FrameworkConfig::DEBUG)
		{
			throw new FakeException('can only set evolve level when debug');
		}
		*/
		$preEvLv = $this->heroModify['evolve_level'];
		$this->heroModify['evolve_level'] = $lv;
		if($preEvLv < $lv)
		{
		    $this->checkActiveSealedTalent();
		}
	}

	public function setDestiny($id)
	{
		$this->heroModify['destiny'] = $id;
	}

	public function checkActiveSealedTalent()
	{
	    $sealedTalent = $this->getSealedTalentInfo();
	    foreach($sealedTalent as $talentIndex => $status)
	    {
	        if(empty($status))
	        {
	            continue;
	        }
	        if(HeroLogic::canActivateTalent($this->heroModify['uid'], $this->getHid(), $talentIndex)==FALSE)
	        {
	            continue;
	        }
	        if($this->activeSealedTalent($talentIndex) == FALSE)
	        {
	            throw new FakeException('activeSealedTalent index %d failed.talentinfo %s',$talentIndex,$this->getTalentInfo());
	        }
	    }
	}

	/**
	 * 根据经验值修复玩家的等级（原则：不修改经验值）
	 */
	protected function fixLevel()
	{
		if($this->isMasterHero())
		{
			return;
		}

		$soul = $this->heroModify['soul'];
		$level = $this->heroModify['level'];

		$expTblId = $this->getConf(CreatureAttr::EXP_ID);
		$expTable = btstore_get()->EXP_TBL[$expTblId];

		if( $soul < $expTable[$level] )
		{
			$lv = $level;
			while($lv > 1 && $soul < $expTable[$lv] )
			{
				$lv--;
			}
			$this->heroModify['level'] = $lv;

			Logger::fatal('fix hero level. exp < level. uid:%d, hid:%d, soul:%d, level:%d, newLevel:%d',
			$this->heroModify['uid'], $this->heroModify['hid'], $soul, $level, $this->heroModify['level']);
		}
		else if( isset( $expTable[$level + 1] ) && $soul >= $expTable[$level + 1] )
		{
			$lv = $level;
			$maxLv = $this->getMaxEnforceLevel();
			while( isset( $expTable[$lv+1] ) && $soul >= $expTable[$lv+1] && $lv < $maxLv)
			{
				$lv++;
			}

			if( $lv > $level)
			{
				$this->heroModify['level'] = $lv;

				Logger::fatal('fix hero level. exp > level. uid:%d, hid:%d, soul:%d, level:%d, newLevel:%d',
				$this->heroModify['uid'], $this->heroModify['hid'], $soul, $level, $this->heroModify['level']);
			}
		}
	}


	public function addSoul($num)
	{
	    $heroLv    =    $this->getLevel();
	    $maxLv    =    $this->getMaxEnforceLevel();
	    if($heroLv >= $maxLv)
	    {
	        Logger::info('hero %d.achieve maxLevel:%d',$this->getHid(),$heroLv);
	        return $heroLv;
	    }
		$this->heroModify['soul'] += $num;
		$curSoul  = $this->heroModify['soul'];
		$expTblId	= $this->getConf(CreatureAttr::EXP_ID);
		$expTbl		= btstore_get()->EXP_TBL[$expTblId];
		if(isset($expTbl[$maxLv+1]) && ($curSoul >= $expTbl[$maxLv+1]))
		{
		    Logger::warning('heroObj.addSoul,curSoul >= exptbl[maxenforcelv+1].');
		    $this->heroModify['soul']    =    $expTbl[$maxLv+1] - 1;  //此处修改了总将魂!!!
		    $newLv = $maxLv;
		}
		else
		{
		    if($curSoul < $expTbl[$heroLv])
		    {
		        Logger::fatal('invalid hero data.hero soul < level ,hero id:%s,level:%s,soul:%s.',$this->getHid(),$this->getLevel(),$this->getSoul());
		        return $this->getLevel();
		    }
		    $newLv = $heroLv;
		    while(isset($expTbl[$newLv+1]) && ($curSoul >= $expTbl[$newLv+1])
		            && ($newLv < $maxLv))
		    {
		        $newLv++;
		    }
		}
		if( $newLv > $heroLv )
		{
			$this->levelUp($newLv-$heroLv);
		}
		return $this->getLevel();
	}


	public function levelUp($num)
	{
	    if($num < 0  || ($this->isMasterHero()))
	    {
	        Logger::fatal('levelUp %s.or this hero %s is master',$num,$this->getHid());
	        return;
	    }
	    $this->heroModify['level'] += $num;
	    $this->heroModify['upgrade_time'] = Util::getTime();
	}

	//更新
	public function update ()
	{
		$arrField = array();

		foreach ($this->hero as $key=>$value)
		{
			if ($this->heroModify[$key] != $value)
			{
				$arrField[$key] = $this->heroModify[$key];
			}
		}

		Logger::debug('update hero. hid:%d, modify:%s', $this->heroModify['hid'], $arrField);
		if (!empty($arrField))
		{
			//如果这个武将还没有使用过，那就需要初始化它
			$userObj = EnUser::getUserObj($this->heroModify['uid']);
			$heroInfo = $userObj->getUnusedHero($this->heroModify['hid']);
			if( empty( $heroInfo ) )
			{
				HeroDao::update($this->heroModify['hid'], $arrField);
			}
			else
			{
				$userObj->getHeroManager()->initHero($this->heroModify['hid'], $this->heroModify);
			}
			if($this->isMasterHero() == FALSE &&
			        $this->hero['level'] != $this->heroModify['level'] &&
			        $this->getStarLv() >= 5)
			{
			    EnAchieve::updateHeroLevel($this->heroModify['uid'], $this->heroModify['level']);
			}
			$this->hero = $this->heroModify;

			$affectedField = array_intersect(array_keys($arrField), self::$affectedBattleField);
			//更新了影响战斗的字段
			if (!empty($affectedField)
					&& (EnFormation::isHidInFormation($this->heroModify['hid'], RPCContext::getInstance()->getUid())
						|| EnFormation::isHidInAttrExtra($this->heroModify['hid'], RPCContext::getInstance()->getUid())))
			{
				$userObj = EnUser::getUserObj();
				$userObj->modifyBattleData();
			}
			if(isset($arrField['htid']) && ($this->isMasterHero()))
			{
			    RPCContext::getInstance ()->setSession ( 'global.utid', $this->getHtid());
			}
		}
	}
	/**
	 * 转生之后，武将的等级和将魂都不变
	 */
	public function convertUp()
	{
	    $evolveTblId = HeroLogic::getEvolveTbl($this->getHtid(), $this->getEvolveLv());
	    if(empty($evolveTblId))
	    {
	        Logger::fatal('convertUp no corresponding evolve tableid.htid %d.evolve level %d.',$this->getHtid(),$this->getEvolveLv());
	        return;
	    }
	    $newHtid	=	intval(btstore_get()->HERO_CONVERT[$evolveTblId]['toHtid']);
		$preEvLv = $this->heroModify['evolve_level'];
	    $this->heroModify['htid'] = $newHtid;
		$this->heroModify['evolve_level']++;
		if($this->heroModify['evolve_level'] > $preEvLv)
		{
		    $this->checkActiveSealedTalent();
		}
	}

	public function setEquipByPos($equipType, $itemId, $pos)
	{
	    $this->heroModify['va_hero'][$equipType][$pos] = $itemId;
	    $item = NULL;
	    if($itemId != ItemDef::ITEM_ID_NO_ITEM)
	    {
	        $item = ItemManager::getInstance()->getItem($itemId);
	    }
	    switch($equipType)
	    {
	        case HeroDef::EQUIP_ARMING:
	            $this->arrArming[$pos] = $item;
	            break;
	        case HeroDef::EQUIP_DRESS:
	            $this->arrDress[$pos] = $item;
	            break;
	        case HeroDef::EQUIP_SKILL_BOOK:
	            $this->arrSkillBook[$pos] = $item;
	            break;
	        case HeroDef::EQUIP_TREASURE:
	            $this->arrTreasure[$pos] = $item;
	            break;
            case HeroDef::EQUIP_FIGHTSOUL:
                $this->arrFightSoul[$pos] = $item;
                if($item == NULL)
                {
                    unset($this->heroModify['va_hero'][$equipType][$pos]);
                    unset($this->arrFightSoul[$pos]);
                }
                break;
            case HeroDef::EQUIP_GODWEAPON:
                $this->arrGodWeapon[$pos] = $item;
                if($item == NULL)
                {
                    unset($this->heroModify['va_hero'][$equipType][$pos]);
                    unset($this->arrGodWeapon[$pos]);
                }
                break;
            case HeroDef::EQUIP_POCKET:
                $this->arrPocket[$pos] = $item;
                if($item == NULL)
                {
                    unset($this->heroModify['va_hero'][$equipType][$pos]);
                    unset($this->arrPocket[$pos]);
                }
                break;
            case HeroDef::EQUIP_TALLY:
                $this->arrTally[$pos] = $item;
                if($item == NULL)
                {
                	unset($this->heroModify['va_hero'][$equipType][$pos]);
                	unset($this->arrTally[$pos]);
                }
                break;
            case HeroDef::EQUIP_CHARIOT:
            	$this->arrChariot[$pos] = $item;
            	if($item == NULL)
            	{
            		unset($this->heroModify['va_hero'][$equipType][$pos]);
            		unset($this->arrChariot[$pos]);
            	}
            	break;
	        default:
	            throw new FakeException('no such equiptype %s',$equipType);
	    }
	}

	public function openSkillBookPos($posId)
	{
		if(!isset($this->heroModify['va_hero'][HeroDef::EQUIP_SKILL_BOOK][$posId]))
		{
			$this->heroModify['va_hero'][HeroDef::EQUIP_SKILL_BOOK][$posId]	=	ItemDef::ITEM_ID_NO_ITEM;
		}
	}

	public function getHeroVaInfo()
	{
	    $heroAttr = array(
				'va_hero' => array(
						HeroDef::EQUIP_ARMING => $this->getEquipByType(HeroDef::EQUIP_ARMING),
						HeroDef::EQUIP_SKILL_BOOK => $this->getEquipByType(HeroDef::EQUIP_SKILL_BOOK),
				        HeroDef::EQUIP_TREASURE => $this->getEquipByType(HeroDef::EQUIP_TREASURE),
				        HeroDef::EQUIP_DRESS => $this->getEquipByType(HeroDef::EQUIP_DRESS),
				        HeroDef::EQUIP_FIGHTSOUL => $this->getEquipByType(HeroDef::EQUIP_FIGHTSOUL),
				        HeroDef::EQUIP_GODWEAPON => $this->getEquipByType(HeroDef::EQUIP_GODWEAPON),
				        HeroDef::EQUIP_POCKET => $this->getEquipByType(HeroDef::EQUIP_POCKET),
						HeroDef::EQUIP_TALLY => $this->getEquipByType(HeroDef::EQUIP_TALLY),
						HeroDef::EQUIP_CHARIOT=> $this->getEquipByType(HeroDef::EQUIP_CHARIOT),
				        HeroDef::VA_FIELD_LOCK => $this->isLocked(),
				        HeroDef::VA_FIELD_TALENT => $this->getTalentInfo(),
						HeroDef::VA_FIELD_TRANSFER => $this->getTransfer(),
						HeroDef::VA_FIELD_DXTRANS => $this->getDXTrans(),
                        HeroDef::VA_FIELD_PILL => $this->getPillInfo(),
                        HeroDef::VA_FIELD_MATER_TALENT => $this->getMasterTalentInfo(),
				                    )
	                    );
	    foreach($this->heroModify['va_hero'] as $field => $value)
	    {
	        if(!isset($heroAttr['va_hero'][$field]))
	        {
	            throw new FakeException('getHeroVaInfo error.herodata %s heroattr %s,error field %s',
	                    $this->heroModify['va_hero'],$heroAttr['va_hero'],$field);
	        }
	    }
	    return $heroAttr;
	}

	/**
	 * 获取武将某个类型，某个位置上的装备ID
	 * @param int $type
	 * @param int $position
	 * @throws Exception
	 */
	public function getEquipByPos($type, $pos)
	{
	    Logger::trace('getEquipByPos heroinfo %s.',$this->heroModify);
	    if(empty($this->heroModify['va_hero'][$type]))
	    {
	        return ItemDef::ITEM_ID_NO_ITEM;
	    }
	    if (!isset($this->heroModify['va_hero'][$type][$pos]))
	    {
	        return ItemDef::ITEM_ID_NO_ITEM;
	    }
	    return $this->heroModify['va_hero'][$type][$pos];
	}

	public function canBeSell()
	{
	    $starLv    =    $this->getStarLv();
	    if($starLv >= 5 )
	    {
	        return FALSE;
	    }
	    return TRUE;
	}

	public function getStarLv()
	{
	    $starLv = Creature::getHeroConf($this->getHtid(), CreatureAttr::STAR_LEVEL);
	    return intval($starLv);
	}

	public function hasFiveStar()
	{
	    $starLv    =    $this->getStarLv();
	    if($starLv >= 5 )
	    {
	        return TRUE;
	    }
	    return FALSE;
	}

	public function resetHero()
	{
	    $this->heroModify['level'] = 1;
	    $this->heroModify['soul'] = 0;
	    $this->heroModify['evolve_level'] = 0;
        $this->heroModify['va_hero'][HeroDef::VA_FIELD_PILL] = array();
	    //TODO:技能书栏位是否恢复到初始状态   还有其他涉及到根据武将等级开启的东西都要改？
	}
	/**
	 * 此接口添加将魂，可能导致转生次数的增加
	 * addsoul接口是在当前转生等级下进行的，等级不能超过当前转生次数对应的最高强化等级
	 * @param int $soul
	 * @return int 返回加到最大等级之后剩余的将魂数目
	 */
	public function addSoulIgnoreEvLv($addSoul)
	{
	    if($addSoul <= 0)
	    {
	        Logger::warning('addSoulIgnoreEvLv invalid addsoul value is %d.',$addSoul);
	        return 0;
	    }
	    $htid = $this->getHtid();
	    $soul = $this->getSoul() + $addSoul;
	    $expTblId	= $this->getConf(CreatureAttr::EXP_ID);
	    $expTbl		= btstore_get()->EXP_TBL[$expTblId];
	    $newLv = $this->getLevel();
	    $reachMaxLv = TRUE;
	    $remainSoul = 0;
	    foreach ($expTbl as $level => $lvSoul)
	    {
	        if($soul < $lvSoul)
	        {
	            $reachMaxLv = FALSE;
	            break;
	        }
	        $newLv = $level;
	    }
	    $newSoul = $expTbl[$level];
	    //计算转生次数
	    $newEvLv = HeroLogic::getEvLvByLevel($htid, $newLv);
	    if($this->getSoul() > $newSoul || ($this->getLevel() > $newLv) ||
	            ($this->getEvolveLv() > $newEvLv))
	    {
	        throw new InterException('error please check.');
	    }
	    if($reachMaxLv && ($this->getSoul() >= $newSoul))
	    {
	        return $addSoul;
	    }
	    $remainSoul = $soul - $newSoul;
	    $deltEvLv = $newEvLv - $this->getEvolveLv();
	    $this->heroModify['soul'] = $newSoul;
	    $this->heroModify['level'] = $newLv;
	    $this->heroModify['evolve_level'] = $newEvLv;
	    return array(
	            'remainSoul'=>$remainSoul,
	            'deltEvLv'=>$deltEvLv
	            );
	}

	public function lock()
	{
	    $this->heroModify['va_hero'][HeroDef::VA_FIELD_LOCK] = 1;
	}

	public function unLock()
	{
	    unset($this->heroModify['va_hero'][HeroDef::VA_FIELD_LOCK]);
	}

	/**
	 * 突破（天命系统触发的）
	 * @param int $htid
	 */
	public function transformTo($htid)
	{
	    $this->heroModify['htid'] = $htid;
	}

	/**
	 * 进化以后 武将ID边了，等级不变，进阶等级变成“0阶”
	 * @param int $htid
	 */
	public function develop($toHtid)
	{
	    $this->heroModify['htid'] = $toHtid;
	    $this->heroModify['evolve_level'] = 0;
	}
	/**
	 * 橙卡重生
	 * @param int $toHtid
	 */
	public function unDevelop($toHtid, $evLv)
	{
	    $this->heroModify['htid'] = $toHtid;
	    $this->heroModify['evolve_level'] = $evLv;
	}

	public function addToConfirmedTalent($talentId,$talentIndex)
	{
	    Logger::trace('addTalentToConfirm %d',$talentId);
	    $talentInfo = $this->getTalentInfo();
	    $talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex] = $talentId;
	    $this->setTalentInfo($talentInfo);
	}

	public function addConfirmedTalent($talentIndex,$talentId)
	{
	    $talentInfo = $this->getTalentInfo();
	    if(empty($talentId))
	    {
	        unset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex]);
	    }
	    else
	    {
	        $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex] = $talentId;
	    }
	    $this->setTalentInfo($talentInfo);
	}

	public function addSealedTalent($talentIndex)
	{
	    $talentInfo = $this->getTalentInfo();
	    if(!isset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex]))
	    {
	        return FALSE;
	    }
	    $talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED][$talentIndex]  = 1;
	    $this->setTalentInfo($talentInfo);
	    return TRUE;
	}

	public function undoTalent($talentIndex)
	{
	    $talentInfo = $this->getTalentInfo();
	    if(empty($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM]) ||
	            empty($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex]))
	    {
	        return FALSE;
	    }
	    $talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex] = array();
	    $this->setTalentInfo($talentInfo);
	    return TRUE;
	}

	public function confirmTalent($talentIndex,$talentId)
	{
	    $talentInfo = $this->getTalentInfo();
	    if(empty($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM]) ||
	            empty($talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex]))
	    {
	        return FALSE;
	    }
	    $toConfirmed = $talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex];
	    if(is_int($toConfirmed))
	    {
	        if($toConfirmed != $talentId)
	        {
	            Logger::fatal('confirmTalent talentindex %d talentid %d',$talentIndex,$talentId);
	            return FALSE;
	        }
	        $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex] = $toConfirmed;
	    }
	    else
	    {
            if(in_array($talentId, $toConfirmed) == FALSE)
            {
                Logger::fatal('confirmTalent talentindex %d talentid %d',$talentIndex,$talentId);
                return FALSE;
            }
            $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex] = $talentId;
	    }
	    $talentInfo[HeroDef::VA_SUBFIELD_TALENT_TO_CONFIRM][$talentIndex] = array();
	    $this->setTalentInfo($talentInfo);
	    return TRUE;
	}

    /**
     * 主角天赋
     * @param $talentIndex int 天赋index
     * @param $talentId int 天赋id
     */
    public function confirmMasterTalent($talentIndex, $talentId)
    {
        $this->heroModify['va_hero'][HeroDef::VA_FIELD_MATER_TALENT][$talentIndex] = $talentId;
    }

	public function activeSealedTalent($talentIndex)
	{
	    $talentInfo = $this->getTalentInfo();
	    if(!isset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED][$talentIndex])
	            || empty( $talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED][$talentIndex]))
	    {
	        return FALSE;
	    }
// 	    if(!isset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex]) ||
// 	            (empty($talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED][$talentIndex])))
// 	    {
// 	        return FALSE;
// 	    }
	    unset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED][$talentIndex]);
	    $this->setTalentInfo($talentInfo);
	    return TRUE;
	}

	public function setTalentInfo($talentInfo)
	{
	    $this->heroModify['va_hero'][HeroDef::VA_FIELD_TALENT] = $talentInfo;
	}

	public function setTransfer($htid)
	{
		$this->heroModify['va_hero'][HeroDef::VA_FIELD_TRANSFER] = $htid;
	}

	public function unsetTransfer()
	{
		unset($this->heroModify['va_hero'][HeroDef::VA_FIELD_TRANSFER]);
	}

	public function setDXTrans()
	{
		$this->heroModify['va_hero'][HeroDef::VA_FIELD_DXTRANS] = 1;
	}

	public function unsetDXTrans()
	{
		unset($this->heroModify['va_hero'][HeroDef::VA_FIELD_DXTRANS]);
	}

    public function setPillInfo($pillInfo)
    {
        $this->heroModify['va_hero'][HeroDef::VA_FIELD_PILL] = $pillInfo;
    }

    public function getPillNum($index, $itemTplId)
    {
        $pillInfo = $this->getPillInfo();
        if(empty($pillInfo[$index][$itemTplId]))
        {
            return 0;
        }
        return $pillInfo[$index][$itemTplId];
    }

    public function addPillNum($index, $itemTplId, $num = 1)
    {
        $pillNum = $this->getPillNum($index, $itemTplId);
        $pillNum += $num;
		$this->heroModify['va_hero'][HeroDef::VA_FIELD_PILL][$index][$itemTplId] = $pillNum;
    }

	public function decreasePillNum($index, $itemTplId)
	{
		$pillNum = $this->getPillNum($index, $itemTplId);
		if($pillNum <= 0)
		{
			return;
		}
		$pillNum--;
		$this->heroModify['va_hero'][HeroDef::VA_FIELD_PILL][$index][$itemTplId] = $pillNum;
	}

	/**
	 * @param $type int 丹药类型
	 * @return array {itemTplId => $num} 卸掉的pill
	 */
	public function removePillByType($type)
	{
		$pillInfo = $this->getPillInfo();
		$ret = array();
		foreach($pillInfo as $index => $indexInfo)
		{
			$pillTypeInConf = btstore_get()->PILL[$index][PillDef::PILL_TYPE];

			if($pillTypeInConf == $type)
			{
				foreach($indexInfo as $itemTplId => $num)
				{
					if(isset($ret[$itemTplId]))
					{
						$ret[$itemTplId] += $num;
					}
					else
					{
						$ret[$itemTplId] = $num;
					}
				}
				unset($pillInfo[$index]);
			}
		}
		$this->setPillInfo($pillInfo);
		return $ret;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
