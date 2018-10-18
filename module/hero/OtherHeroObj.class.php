<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: OtherHeroObj.class.php 251365 2016-07-13 05:36:38Z QingYao $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/OtherHeroObj.class.php $
 * @author $Author: QingYao $(lanhongyu@babeltime.com)
 * @date $Date: 2016-07-13 05:36:38 +0000 (Wed, 13 Jul 2016) $
 * @version $Revision: 251365 $
 * @brief 
 * 
 **/
class OtherHeroObj extends Creature
{
    protected $hero;
    protected $heroModify;
    
    /**
     * 装备item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrArming = NULL;
    /**
     * 技能书item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrSkillBook = NULL;
    /**
     * 宝物item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrTreasure = NULL;
    /**
     * 时装item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrDress = NULL;
    /**
     * 战魂item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrFightSoul = NULL;
    /**
     * 神兵item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrGodWeapon = NULL;
    
    /**
     * 锦囊item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrPocket = NULL;
    
    /**
     * 兵符item对象数组
     * pos => item
     * @var Item[]
     */
    protected $arrTally = NULL;
    
    /**
     * 战车item对象数组
     * pos=>item
     * @var unknown
     */
    protected  $arrChariot = NULL;

    /**
     * 所有武将都有而且相同的属性加成
     * @var array
     */
    protected static $addAttr = array(
            
            );

    /**
     * 这个地方不用hid或者htid初始化hero对象。是因为在HeroManager中可以批量拉取所有在阵容中的英雄数据。这样少些db操作
     * @param array $attr
     */
    public function __construct ($attr)
    {
        $this->hero = $attr;
        $this->heroModify = $this->hero;

        //主角武将的等级  = 用户等级
        if( $this->isMasterHero() )
        {
            $userObj = EnUser::getUserObj($attr['uid']);
            $this->heroModify['level'] = $userObj->getLevel();
        }

        parent::__construct($this->hero['htid']);
    }

    public function getAllAttr ()
    {
        return $this->heroModify;
    }

    public function getHid ()
    {
        return $this->heroModify['hid'];
    }

    public function getHtid ()
    {
        return $this->heroModify['htid'];
    }

    public function getLevel ()
    {
        return $this->heroModify['level'];
    }

    public function getEvolveLv()
    {
        return $this->heroModify['evolve_level'];
    }

    public function getSoul()
    {
        return $this->heroModify['soul'];
    }
    
    public function getDestiny()
    {
    	return $this->heroModify['destiny'];
    }

    public function getBaseHtid()
    {
        return HeroUtil::getBaseHtid($this->heroModify['htid']);
    }

    public function getVocation ()
    {
        return Creature::getHeroConf($this->heroModify['htid'], CreatureAttr::VOCATION);
    }

    public function isMasterHero()
    {
        return HeroUtil::isMasterHtid( $this->heroModify['htid'] );
    }

    public function getAllEquipId()
    {
        $allId = array();
        foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
        {
            $allId = array_merge($allId,$this->getEquipByType($equipType));
        }
        return $allId;
    }
    
    public function removeArmPos($posId)
    {
        unset($this->heroModify['va_hero'][HeroDef::EQUIP_ARMING][$posId]);
    }
    
    public function canBeDel()
    {
        //不能删除别人的武将
        Logger::fatal('try to del hero by other');
        return false;
    }

    public function getInfo()
    {
        $equipInfo = $this->getEquipInfo();
        $heroInfo =  array(
                'hid' => $this->heroModify['hid'],
                'htid' => $this->heroModify['htid'],
                'level' => $this->heroModify['level'],
        		'destiny' => $this->heroModify['destiny'],
                'soul'=>$this->getSoul(),
                'evolve_level'=>$this->heroModify['evolve_level'],
                'equip' => $equipInfo,
                'talent'=> $this->getTalentInfo(),
        		'transfer'=> $this->getTransfer(),
        		'dxtrans' => $this->getDXTrans(),
                'pill' => $this->getPillInfo(),
                'masterTalent' => $this->getMasterTalentInfo(),
        );
        if($this->isLocked())
        {
            $heroInfo[HeroDef::VA_FIELD_LOCK] = 1;
        }
        return $heroInfo;
    }
    
    public function getHeroInfo()
    {
        return $this->heroModify;
    }
    
    public function getTalentInfo()
    {
        if(!isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_TALENT]) || 
                (!isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_TALENT][HeroDef::VA_SUBFIELD_TALENT_CONFIRMED])) ||               
                (is_array($this->heroModify['va_hero'][HeroDef::VA_FIELD_TALENT][HeroDef::VA_SUBFIELD_TALENT_CONFIRMED])==FALSE))
        {
            $this->heroModify['va_hero'][HeroDef::VA_FIELD_TALENT] = 
                    HeroLogic::getInitTalentInfo();
        }
        return $this->heroModify['va_hero'][HeroDef::VA_FIELD_TALENT];
    }

    public function getPillInfo()
    {
        if(!isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_PILL]))
        {
            return array();
        }
        return $this->heroModify['va_hero'][HeroDef::VA_FIELD_PILL];
    }

    public function getMasterTalentInfo()
    {
        if(!isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_MATER_TALENT]))
        {
            return array();
        }
        return $this->heroModify['va_hero'][HeroDef::VA_FIELD_MATER_TALENT];
    }
    
    public function hasPill()
    {
    	$arrPillInfo = $this->getPillInfo();
    	
    	foreach ($arrPillInfo as $inex => $itemInfo)
    	{
    		foreach ($itemInfo as $tmpId => $num)
    		{
    			if ($num > 0) 
    			{
    				return TRUE;
    			}
    		}
    	}
    	
    	return FALSE;
    }
    
    public function hasSealedTalent($talentIndex)
    {
        $talentInfo = $this->getTalentInfo();
        if(!isset( $talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED][$talentIndex])
                || empty( $talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED][$talentIndex]))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    public function getSealedTalentInfo()
    {
        $talentInfo = $this->getTalentInfo();
        if(!isset($talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED]))
        {
            return array();
        }
        return $talentInfo[HeroDef::VA_SUBFIELD_TALENT_SEALED];
    }
    
    public function getCurTalent()
    {
        $talentInfo = $this->getTalentInfo();
        return $talentInfo[HeroDef::VA_SUBFIELD_TALENT_CONFIRMED];
    }
    
    public function hasTalent()
    {
        $arrTalentId = $this->getCurTalent();
        foreach($arrTalentId as $talentIndex => $talentId)
        {
            if(!empty($talentId))
            {
                return TRUE;
            }
        }
        return FALSE;
    }
    
    public function getTransfer()
    {
    	if (!isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_TRANSFER]))
    	{
    		return 0;
    	}
    	return $this->heroModify['va_hero'][HeroDef::VA_FIELD_TRANSFER];
    }
    
    public function getDXTrans()
    {
    	if (!isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_DXTRANS]))
    	{
    		return 0;
    	}
    	return $this->heroModify['va_hero'][HeroDef::VA_FIELD_DXTRANS];
    }
    
    public function getBattleInfo()
    {
        $this->creatureInfo[PropertyKey::HID] = $this->heroModify['hid'];
        parent::getBattleInfo();
        if($this->isMasterHero())
        {
            $this->creatureInfo[PropertyKey::DRESS_INFO] =
                EnUser::getUserObj($this->heroModify['uid'])->getDressInfo();
        }
        $this->creatureInfo[PropertyKey::PILL_INFO] = $this->getPillInfo();
        $this->creatureInfo[PropertyKey::DESTINY] = $this->getDestiny();
        $this->replaceMasterSkill();
        return $this->creatureInfo;
    }
    
    public function replaceMasterSkill()
    {
        if($this->isMasterHero() == FALSE)
        {
            return;
        }
        $arrSkillInfo = EnUser::getUserObj($this->heroModify['uid'])->getMasterSkill();
        Logger::trace('replaceMasterSkill with %s', $arrSkillInfo);
        foreach($arrSkillInfo as $skillType => $skillInfo)
        {
            if(!empty($skillInfo[0]))
            {
                $this->creatureInfo[$skillType] = $skillInfo[0];
            }
        }
    }

    public function getAddAttr()
    {
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_EQUIP] = $this->getAddAttrByEquip();//装备(武器、技能书)去加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_AWAIK] = $this->getAddAttrByAwakeAbility();//觉醒能力附加属性
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_STAR] = $this->getAddAttrByStar();//名将系统的属性加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_PET] = $this->getAddAttrByPet();//宠物模块的属性加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_ACHIEVE] = $this->getAddAttrByAchieve();//成就系统加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_TALENT] = $this->getAddAttrByTalent();
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_DRESSROOM] = $this->getAddAttrByDressRoom();//时装屋加成
        $this->arrAddAttr[HeroDef::ADD_ATTR_BY_GODWEAPONBOOK] = $this->getAddAttrByGodWeaponBook();//神兵录加成
        $this->arrAddAttr[HeroDef::ADD_ATTR_BY_TALLYBOOK] = $this->getAddAttrByTallyBook();//兵符录加成
        $this->arrAddAttr[HeroDef::ADD_ATTR_BY_GUILDSKILL] = $this->getAddAttrByGuildSkill();//军团科技加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_PILL] = $this->getAddAttrByPill();//丹药加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_UNIONEXTRA] = $this->getAddAttrByUnionExtra();
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_STYLISH] = $this->getAddAttrByStylish();
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_HERO_DESTINY] = $this->getAddAttrByHeroDestiny();//武将天命属性加成
    	$this->arrAddAttr[HeroDef::ADD_ATTR_BY_CHARIOT]=$this->getAddAttrByChariot();//战车属性
        if($this->isMasterHero())
    	{
    	    $this->arrAddAttr[HeroDef::ADD_ATTR_BY_DESTINY] = $this->getAddAttrByDestiny();//天命系统属性加成
    	    $this->arrAddAttr[HeroDef::ADD_ATTR_BY_ATHENA] = $this->getAddAttrByAthena();
    	}
        return $this->arrAddAttr;
    }
    
    /**
     * 获取装备的属性加成(武器、技能书、套装、时装的属性加成)
     */
    protected function getAddAttrByEquip()
    {
        $allItems = array();
        foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
        {
            $allItems[$equipType] = $this->getEquipObjByType($equipType);
        }
        $addAttr = array();
        //套装提供的属性加成
        $arrArmInfo = array();
        foreach($allItems[HeroDef::EQUIP_ARMING] as $pos => $itemObj)                 
        {
        	if(empty($itemObj))                                                       
            {
            	continue;                                                             
             }
             $itemId = $itemObj->getItemID();
             $arrArmInfo[$itemId] = $itemObj->itemInfo();
        }
        $addAttr = ArmItem::getExtraAttr(array(),$arrArmInfo);
        Logger::trace('hero %s arm suit addAttr is %s.',$this->getHtid(),$addAttr);
        //兵符提供的连锁属性加成
        $arrTallyInfo = array();
        foreach($allItems[HeroDef::EQUIP_TALLY] as $pos => $itemObj)
        {
        	if(empty($itemObj))
        	{
        		continue;
        	}
        	$itemId = $itemObj->getItemID();
        	$arrTallyInfo[$itemId] = $itemObj->itemInfo();
        }
        $tallyAddAttr = TallyItem::getExtraAttr(array(), $arrTallyInfo);
        Logger::trace('hero %s tally suit addAttr is %s.',$this->getHtid(),$tallyAddAttr);
        $addAttr = Util::arrayAdd2V(array($addAttr, $tallyAddAttr));
        //单个装备提供的属性加成
        foreach ($allItems as $equipType => $posItem)
        {
            foreach ($posItem as $item)
            {
                if ($item == null)
                {
                    continue;
                }
    
                $itemInfo = $item->info();
                Logger::trace('hero %s addAttrByItem %s equiptype %s addattr is %s.',$this->getHtid(),$item->getItemTemplateID(),$equipType,$itemInfo);
                foreach ($itemInfo as $key => $value)
                {
                    if (isset($addAttr[$key]))
                    {
                        $addAttr[$key] += $value;
                    }
                    else
                    {
                        $addAttr[$key] = $value;
                    }
                }
            }
        }
        return $addAttr;
    }
    
    protected function getAddAttrByStylish()
    {
    	$addAttr = EnStylish::getAddAttr($this->heroModify['uid'], $this->getHtid());
    	return $addAttr;
    }
    
    protected function getAddAttrByStar()
    {
        $uid    =    $this->heroModify['uid'];
        $addAttr = EnStar::getStarAttr($this->heroModify['uid'], $this->getHtid());
        return $addAttr;
    }
    
    protected function getAddAttrByDestiny()
    {
        $uid    =    $this->heroModify['uid'];
        $addAttr = EnDestiny::getAddAttr($uid);
        return $addAttr;
    }
    
    protected function getAddAttrByAthena()
    {
        $uid = $this->heroModify['uid'];
        $addAttr = EnAthena::getAddAddrByAthena($uid);
        return $addAttr;
    }
    
    protected function getAddAttrByDressRoom()
    {
        $uid = $this->heroModify['uid'];
        if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_DRESSROOM]))
        {
            self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_DRESSROOM]    =
                EnDressRoom::getAddAttrByDress($uid);
        }
        Logger::trace('getAddAttrByDressRoom is %s',self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_DRESSROOM]);
        return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_DRESSROOM];
    }
    
    protected function getAddAttrByPet()
    {
        $uid    =    $this->heroModify['uid'];
        if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_PET]))
        {
            self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_PET]    =
                    EnPet::petAdditionForHero($uid);
        }
        Logger::trace('addAttrByPet is %s.',self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_PET]);
        return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_PET];
    }
    
    protected function getAddAttrByAchieve()
    {
    	$uid    =    $this->heroModify['uid'];
    	if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_ACHIEVE]))
    	{
    		self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_ACHIEVE] = EnAchieve::getAddAttrByAchieve($uid);
    	}
    	Logger::trace('addAttrByAchieve is %s.',self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_ACHIEVE]);
    	return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_ACHIEVE];
    }

    protected function getAddAttrByGodWeaponBook()
    {
        $uid    =    $this->heroModify['uid'];
        if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GODWEAPONBOOK]))
        {
            self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GODWEAPONBOOK] = GodWeaponBook::getAddAttrByGodWeaponBook($uid);
        }
        Logger::trace('addAttrByPet is %s.',self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GODWEAPONBOOK]);
        return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GODWEAPONBOOK];
    }
    
    protected function getAddAttrByTallyBook()
    {
    	$uid    =    $this->heroModify['uid'];
    	if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_TALLYBOOK]))
    	{
    		self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_TALLYBOOK] = TallyItem::getAddAttrByTallyBook($uid);
    	}
    	Logger::trace('addAttrByPet is %s.',self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_TALLYBOOK]);
    	return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_TALLYBOOK];
    }
    
    protected function getAddAttrByGuildSkill()
    {
    	$uid    =    $this->heroModify['uid'];
    	if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GUILDSKILL]))
    	{
    		self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GUILDSKILL] = EnGuild::getAddAttr($uid);
    	}
    	Logger::trace('addAttrByGuildSkill is %s.',self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GUILDSKILL]);
    	return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_GUILDSKILL];
    }
    
    public function getAddAttrByTalent()
    {
        $arrTalentId = $this->getCurTalent();
        Logger::trace('getCurTalent %s  htid %d',$arrTalentId,$this->getHtid());
        if(empty($arrTalentId))
        {
            return array();
        }
        $addAttr = array();
        foreach($arrTalentId as $talentIndex => $talentId)
        {
            if(empty($talentId))
            {
                continue;
            }

            if(! HeroLogic::canActivateTalent($this->heroModify['uid'], $this->getHid(), $talentIndex))
            {
                continue;
            }

            if ($this->hasSealedTalent($talentIndex))
            {
            	continue;
            }
            $addAttrTmp = btstore_get()->HEROTALENT[$talentId]['addAttr']->toArray();
            foreach($addAttrTmp as $attr => $value)
            {
                if(!isset($addAttr[$attr]))
                {
                    $addAttr[$attr] = 0;
                }
                $addAttr[$attr] += $value;
            }
        }
        
        Logger::trace('getAddAttrByTalent htid %d is %s.',$addAttr,$this->getHtid());
        return $addAttr;
    }

    public function getAddAttrByUnionExtra()
    {
        $uid = $this->heroModify['uid'];
        if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_UNIONEXTRA]))
        {
            self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_UNIONEXTRA] = EnUnion::getAddAttrByUnion($uid);
        }
        return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_UNIONEXTRA];
    }
    
    public function getAddAttrByChariot()
    {
    	$uid = $this->heroModify['uid'];
    	if(!isset(self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_CHARIOT]))
    	{
    		self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_CHARIOT] = EnChariot::getAddAttrByChariot($uid);
    	}
    	return self::$addAttr[$uid][HeroDef::ADD_ATTR_BY_CHARIOT];
    }
    
    public function getAddAttrByPill()
    {
        $pillInfo = $this->getPillInfo();
        if(empty($pillInfo))
        {
            return array();
        }
        $addAttr = array();
        foreach($pillInfo as $index => $indexInfo)
        {
            foreach($indexInfo as $itemTplId => $num)
            {
                $addAttrByPillNum = HeroUtil::calAddAttrByPillNum($index, $num);
                foreach($addAttrByPillNum as $attrId => $attrValue)
                {
                    if(!isset($addAttr[$attrId]))
                    {
                        $addAttr[$attrId] = $attrValue;
                    }
                    else
                    {
                        $addAttr[$attrId] += $attrValue;
                    }
                }
            }
        }

        $arrRet = HeroUtil::adaptAttr( $addAttr );
        Logger::trace('getAddAttrByPill. arr:%s', $arrRet);
        return $arrRet;
    }
    
    public function getAddAttrByHeroDestiny()
    {
    	$addAttr = array();
    	for ($i = 1; $i <= $this->getDestiny(); $i++)
    	{
    		$addAttr[] = btstore_get()->HERO_DESTINY[$i]['attr']->toArray();
    	}
    	$addAttr = Util::arrayAdd2V($addAttr);
    	$addAttr = HeroUtil::adaptAttr($addAttr);
    	Logger::trace('getAddAttrByHeroDestiny. arr:%s', $addAttr);
    	return $addAttr;
    }

    public function getEquipObjByType($equipType)
    {
        switch($equipType)
        {
            case HeroDef::EQUIP_ARMING:
                if ($this->arrArming === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrArming;
                break;
            case HeroDef::EQUIP_TREASURE:
                if ($this->arrTreasure === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrTreasure;
                break;
            case HeroDef::EQUIP_DRESS:
                if ($this->arrDress === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrDress;
                break;
            case HeroDef::EQUIP_SKILL_BOOK:
                if ($this->arrSkillBook === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrSkillBook;
                break;
            case HeroDef::EQUIP_FIGHTSOUL:
                if ($this->arrFightSoul === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrFightSoul;
                break;
            case HeroDef::EQUIP_GODWEAPON:
                if ($this->arrGodWeapon === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrGodWeapon;
            case HeroDef::EQUIP_POCKET:
                if( $this->arrPocket === NULL)
                {
                    $this->initEquip();
                }
                return $this->arrPocket;
            case HeroDef::EQUIP_TALLY:
               	if( $this->arrTally === NULL)
                {
                	$this->initEquip();
                }
                return $this->arrTally;
            case HeroDef::EQUIP_CHARIOT:
            	if ($this->arrChariot===NULL)
            	{
            		$this->initEquip();
            	}
            	return $this->arrChariot;
            default:
                throw new FakeException('no such equiptype %s.',$equipType);
        }
    }
    
    /**
     * 获取武将某个类型的所有装备ID
     * 返回 pos->itemId
     * @param string $type
     * @return int[]
     */
    public function getEquipByType ($type)
    {
        $arrItemId = array();
        if(!isset($this->heroModify['va_hero'][$type]) || 
                (empty($this->heroModify['va_hero'][$type])))
        {
            if($this->heroModify['uid'] != RPCContext::getInstance()->getUid())
            {
                return $arrItemId;
            }
            $this->heroModify['va_hero'][$type] = HeroLogic::getInitEquipInfo($type);
        }
        foreach ($this->heroModify['va_hero'][$type] as $pos => $itemId)
        {
            if ($itemId != BagDef::ITEM_ID_NO_ITEM)
            {
                $arrItemId[$pos] = $itemId;
            }
            else
            {
                $arrItemId[$pos] = 0;
            }
        }
        return $arrItemId;
    }
    
    public function getFightSoulPos($itemId)
    {
        if(empty($itemId))
        {
            return HeroDef::INVALID_EQUIP_POSITION;
        }
        $arrFightSoul = $this->getEquipByType(HeroDef::EQUIP_FIGHTSOUL);
        foreach($arrFightSoul as $pos => $fightSoul)
        {
            if($itemId == $fightSoul)
            {
                return $pos;
            }
        }
        return HeroDef::INVALID_EQUIP_POSITION;
    }
    
    public function getPocketPos($itemId)
    {
        if(empty($itemId))
        {
            return HeroDef::INVALID_EQUIP_POSITION;
        }
        $arrPocket = $this->getEquipByType(HeroDef::EQUIP_POCKET);
        foreach($arrPocket as $pos => $pocket)
        {
            if($itemId == $pocket)
            {
                return $pos;
            }
        }
        return HeroDef::INVALID_EQUIP_POSITION;
    }
    
    public function getTallyPos($itemId)
    {
    	if(empty($itemId))
    	{
    		return HeroDef::INVALID_EQUIP_POSITION;
    	}
    	$arrTally = $this->getEquipByType(HeroDef::EQUIP_TALLY);
    	foreach($arrTally as $pos => $tally)
    	{
    		if($itemId == $tally)
    		{
    			return $pos;
    		}
    	}
    	return HeroDef::INVALID_EQUIP_POSITION;
    }
    
    public function getGodWeaponPos($itemId)
    {
        if(empty($itemId))
        {
            return HeroDef::INVALID_EQUIP_POSITION;
        }
        $arrGodWeapon = $this->getEquipByType(HeroDef::EQUIP_GODWEAPON);
        foreach($arrGodWeapon as $pos => $godWeapon)
        {
            if($itemId == $godWeapon)
            {
                return $pos;
            }
        }
        return HeroDef::INVALID_EQUIP_POSITION;
    }

    protected function getEquipInfo()
    {
        $equipInfo = array();
        foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
        {
            $equipInfo[$equipType] = $this->getItemsInfo($this->getEquipObjByType($equipType));
        }
        return $equipInfo;
    }
    
    public function getItemsInfo ($arrItem)
    {
        $arrRet = array();
        foreach ($arrItem as $posId=>$item)
        {
            if ($item == NULL)
            {
                $arrRet[$posId]    =    0;
            }
            else
            {
                $arrRet[$posId] = $item->itemInfo();
            }
        }
        return $arrRet;
    }

    protected function initEquip()
    {
        $arrArmingItemId = $this->getEquipByType( HeroDef::EQUIP_ARMING );
        $arrSkillBookItemId = $this->getEquipByType( HeroDef::EQUIP_SKILL_BOOK );
        $arrTreasureItemId = $this->getEquipByType(HeroDef::EQUIP_TREASURE);
        $arrDressId = $this->getEquipByType(HeroDef::EQUIP_DRESS);
        $arrFightSoul = $this->getEquipByType(HeroDef::EQUIP_FIGHTSOUL);
        $arrGodWeapon = $this->getEquipByType(HeroDef::EQUIP_GODWEAPON);
        $arrPocket = $this->getEquipByType(HeroDef::EQUIP_POCKET);
        $arrTally = $this->getEquipByType(HeroDef::EQUIP_TALLY);
        $arrChariot=$this->getEquipByType(HeroDef::EQUIP_CHARIOT);
        $allId = array_merge($arrArmingItemId, $arrSkillBookItemId,
                $arrTreasureItemId,$arrDressId,$arrFightSoul,$arrGodWeapon,
                $arrPocket, $arrTally,$arrChariot);
        $allItems = ItemManager::getInstance()->getItems($allId);
        
        //修复神兵栏位数据
        $arrGodWeapon = $this->fixGodWeapon($arrGodWeapon, $allItems);

        $this->arrArming = $this->getArrItem($arrArmingItemId, $allItems);
        $this->arrSkillBook = $this->getArrItem($arrSkillBookItemId, $allItems);
        $this->arrTreasure = $this->getArrItem($arrTreasureItemId, $allItems);
        $this->arrDress = $this->getArrItem($arrDressId, $allItems);
        $this->arrFightSoul = $this->getArrItem($arrFightSoul, $allItems);
        $this->arrGodWeapon = $this->getArrItem($arrGodWeapon, $allItems);
        $this->arrPocket = $this->getArrItem($arrPocket, $allItems);
        $this->arrTally = $this->getArrItem($arrTally, $allItems);
        $this->arrChariot = $this->getArrItem($arrChariot, $allItems);
    }
    
    protected function fixGodWeapon($arrGodWeapon, $allItems)
    {
    	$ret = array();
    	foreach ($arrGodWeapon as $pos => $id)
    	{
    		if (isset($allItems[$id])) 
    		{
    			$ret[$allItems[$id]->getType()] = $id;
    		}
    		else
    		{
    			if($id != 0)
    			{
    				Logger::fatal('hid:%d miss itemId:%d', $this->heroModify['hid'], $id);
    			}
    		}
    	}
    	
    	if ($arrGodWeapon != $ret) 
    	{
    		$this->heroModify['va_hero'][HeroDef::EQUIP_GODWEAPON] = $ret;
    		Logger::info('fix hid:%d godWeapon, old:%s, new:%s', $this->heroModify['hid'], $arrGodWeapon, $ret);
    	}
    	
    	return $ret;
    }

    /**
     * 返回 pos->itemObj
     * @param array $arrPosId
     * @param array $arrItem
     * @return array
     */
    protected function getArrItem ($arrPosId, $arrItem)
    {
        $ret = array();
        foreach ($arrPosId as $pos=>$id)
        {
            if (isset($arrItem[$id]))
            {
                $ret[$pos] = $arrItem[$id];
            }
            else
            {
            	if($id != 0)
            	{
            		Logger::fatal('hid:%d miss itemId:%d', $this->heroModify['hid'], $id);
            	}
                $ret[$pos] = NULL;
            }
        }
        return $ret;
    }
    
    public function rollback()
    {	
    	$this->heroModify = $this->hero;
    	
    	$this->arrArming = NULL;
    	$this->arrSkillBook = NULL;
    	$this->arrTreasure = NULL;
    	$this->arrDress = NULL;
    	$this->arrFightSoul = NULL;
    	$this->arrGodWeapon = NULL;
    	$this->arrPocket = NULL;
    	$this->arrTally = NULL;
    	
    	$uid = $this->heroModify['uid'];
    	if( !isset(self::$addAttr[$uid] ) )
    	{
    		unset( self::$addAttr[$uid] );
    	}
    }

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
        
        if (!empty($arrField))
        {
            //目前来看，在otherHeroObj中不应该有update操作
            throw new InterException('cant update in otherHeroObj,updateinfo %s.',$arrField);
        }
    }
    /**
     * 强化等级上限 = 主角等级
     */
    public function getMaxEnforceLevel()
    {
        $userObj = EnUser::getUserObj($this->heroModify['uid']);
        $maxLv    =    $userObj->getLevel();
        return $maxLv;
    }
    
    public function isHero()
    {
        return TRUE;
    }
    
/**
	 *
	 * 属性初始值 * (1 + 进阶基础值系数/1000 * 进阶次数) +
	 * (武将等级 - 1 ) * (属性成长值/100) +
	 * 进阶次数  * (进阶初始等级 * 2 + 进阶间隔等级 * (进阶次数 - 1) - 2) * 属性成长值 / 2 /100
	 *
	 * @param string $field
	 * @param string $incField
	 * @return int
	 */
	protected function getByEvolveLv($field,$incField)
	{
		$ev = $this->getEvolveLv();	//进化等级
		$inc = $this->getConf($incField);	//等级成长
		$evBaseRatio = $this->getConf(CreatureAttr::EVOLVE_BASE_RATIO); //进阶基础值系数  基础值随进化的成长百分比
		$ev0 = $this->getConf(CreatureAttr::EVOLVE_INIT_LEVEL); //进阶初始等级（第一次进阶时的等级）
		$ev1 = $this->getConf(CreatureAttr::EVOLVE_GAP_LEVEL); //进阶间隔等级（第一次进阶后，每隔多少等级进阶一次）
		//初始值
		$valInit = $this->getConf( $field);
		//等级增长            在进化等级上的等级增长值
		$valLevel = ( $this->getLevel()-1 ) * ($inc/HeroConf::INC_RATIO);
		//如ev0是40 ev1是10   进化路线：40（进化0次） 50（进化1次） 60（进化2次） 
		//在进化第一次时等级变成1，要将0阶的等级增长加上即39*inc
		//在进化第二次事等级变成1，要将0阶，1阶的等级增长加上即40*inc+49*inc
		//......
		//进阶产生的数据。 每次进阶后等级变成0， 进阶等级+1，下面的公式是为了保证进阶后，整体数值不变
		$valEv = $ev * ( $ev0 * 2 +  $ev1 * ($ev-1) - 2) * $inc / 2 / HeroConf::INC_RATIO;
		$val = $valInit * (1 + $evBaseRatio/UNIT_BASE*$ev)  + $valLevel +  $valEv;
	    return intval($val);
	}
	
	public function isLocked()
	{
	    if(isset($this->heroModify['va_hero'][HeroDef::VA_FIELD_LOCK])
	            && ($this->heroModify['va_hero'][HeroDef::VA_FIELD_LOCK] == 1))
	    {
	        return TRUE;
	    }
	    return FALSE;
	}
	
	protected function initAwakeAbility()
	{
	    parent::initAwakeAbility();
	    //觉醒能力的附加优先级:天赋4觉醒能力>天赋3觉醒能力>天赋2觉醒能力>天赋1觉醒能力>成长觉醒能力>初始觉醒能力
	    $arrTalentId = $this->getCurTalent();
	    $talentAwakeAbility = array();
		ksort($arrTalentId);
	    foreach($arrTalentId as $talentIndex => $talentId)
	    {
	        if(! HeroLogic::canActivateTalent($this->heroModify['uid'], $this->getHid(), $talentIndex))
	        {
	            continue;
	        }
	        
	        if ($this->hasSealedTalent($talentIndex)) 
	        {
	        	continue;
	        }
	        if(!empty($talentId))
	        {
	            $talentAwakeAbilityTmp = btstore_get()->HEROTALENT[$talentId]['addAwakeAbility']->toArray();
	            $talentAwakeAbility = array_merge($talentAwakeAbility,$talentAwakeAbilityTmp);
	        }
	    }
	    $pocketAwakeAbility = $this->getAwakeAbilityByPocket();
	    $tallyAwakeAbility = $this->getAwakeAbilityByTally();
        $masterTalentAwakeAbility = $this->getMasterTalentInfo();
        $heroDestinyAwakeAbility = $this->getAwakeAbilityByHeroDestiny();
	    Logger::trace('arrAwakeAbility by talent is %s',$talentAwakeAbility);
	    Logger::trace('arrAwakeAbility by pocker is %s',$pocketAwakeAbility);
	    Logger::trace('arrAwakeAbility by tally is %s',$tallyAwakeAbility);
        Logger::trace('arrAwakeAbility by masterTalent is %s', $masterTalentAwakeAbility);
        Logger::trace('arrAwakeAbility by hero destiny is %s', $heroDestinyAwakeAbility);
	    $this->arrAwakeAbility = array_merge($this->arrAwakeAbility,$talentAwakeAbility,$pocketAwakeAbility,$tallyAwakeAbility,$heroDestinyAwakeAbility);
        if($this->isMasterHero())
        {
            $this->arrAwakeAbility = array_merge($this->arrAwakeAbility, $masterTalentAwakeAbility);
        }
	    Logger::trace('arrAwakeAbility final is %s',$this->arrAwakeAbility);
	}
	
	private function getAwakeAbilityByPocket()
	{
	    $arrItem = $this->getEquipObjByType(HeroDef::EQUIP_POCKET);
	    $arrAwakeAbility = array();
	    foreach($arrItem as $pocketItem)
	    {
	        if(empty($pocketItem))
	        {
	            continue;
	        }
	        $awakeAbility = $pocketItem->getAwakeAbility();
	        if(!empty($awakeAbility))
	        {
	            $arrAwakeAbility[] = $awakeAbility;
	        }
	    }
	    return $arrAwakeAbility;
	}
	
	private function getAwakeAbilityByTally()
	{
		$arrItem = $this->getEquipObjByType(HeroDef::EQUIP_TALLY);
	    $arrAwakeAbility = array();
	    foreach($arrItem as $tallyItem)
	    {
	        if(empty($tallyItem))
	        {
	            continue;
	        }
	        $awakeAbility = $tallyItem->getEvolveAwakeAbility();
	        if(!empty($awakeAbility))
	        {
	            $arrAwakeAbility[] = $awakeAbility;
	        }
	    }
	    return $arrAwakeAbility;
	}
	
	private function getAwakeAbilityByHeroDestiny()
	{
		$arrAwakeAbility = array();
		$destinyAwake = Creature::getHeroConf($this->getHtid(), CreatureAttr::DESTINY_AWAKE)->toArray();
		foreach ($destinyAwake as $key => $value)
		{
			if ($key <= $this->getDestiny()) 
			{
				$arrAwakeAbility = array_merge($arrAwakeAbility, $value);
			}
		}
		Logger::trace('getAwakeAbilityByHeroDestiny. arr:%s', $arrAwakeAbility);
    	return $arrAwakeAbility;
	}
	
	public function isEquiped()
	{
	    foreach($this->heroModify['va_hero'] as $equipType=>$equips)
	    {
	        if(in_array($equipType,HeroDef::$ALL_EQUIP_TYPE) == FALSE)
	        {
	            continue;
	        }
	        foreach($equips as $posId => $itemId)
	        {
	            if($itemId!=ItemDef::ITEM_ID_NO_ITEM)
	            {
	                return TRUE;
	            }
	        }
	    }
	    return FALSE;
	}
	
	public function getNakedBattleInfo()
	{
	    $this->init();
	    $arrAddAttr[HeroDef::ADD_ATTR_BY_AWAIK] = $this->getAddAttrByAwakeAbility();
	    $arrAddAttr[HeroDef::ADD_ATTR_BY_TALENT] = $this->getAddAttrByTalent();
	    $arrAddAttr[HeroDef::ADD_ATTR_BY_STAR] = $this->getAddAttrByStar();
        //丹药的助战军属性
        $arrAddAttr[HeroDef::ADD_ATTR_BY_PILL] = $this->getAddAttrByPill();
	    if(isset($this->arrAddAttr[HeroDef::ADD_ATTR_BY_UNIONPROFIT]))
	    {
	        $arrAddAttr[HeroDef::ADD_ATTR_BY_UNIONPROFIT] = $this->arrAddAttr[HeroDef::ADD_ATTR_BY_UNIONPROFIT];
	    }
	    $this->addAttr($arrAddAttr);
	    $this->getSanwei();
	    $this->getMaxHp();
	    $this->creatureInfo['equipInfo'] = $this->getEquipInfo();
	    $this->addAwakeAbilitySkillBuff();
	    $this->creatureInfo[PropertyKey::FIGHT_FORCE] = $this->getFightForce();
	    return $this->creatureInfo;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
