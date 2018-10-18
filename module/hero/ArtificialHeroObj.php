<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ArtificialHeroObj.php 251659 2016-07-15 04:14:40Z QingYao $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/hero/ArtificialHeroObj.php $
 * @author $Author: QingYao $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-07-15 04:14:40 +0000 (Fri, 15 Jul 2016) $
 * @version $Revision: 251659 $
 * @brief 
 *  
 **/
class ArtificialHeroObj extends OtherHeroObj
{
	private $mReplaceInitAttr = array();
	 
    public function __construct($attr, $replaceInitAttr = array())
    {
        $attr = $this->initEquipInfo($attr);
        parent::__construct($attr);
        $this->mReplaceInitAttr = $replaceInitAttr;
    }
    
    /**
     * (non-PHPdoc)
     * @see Creature::replaceInitAttr()
     */
    public function replaceInitAttr()
    {
    	foreach ($this->mReplaceInitAttr as $attr => $value)
    	{
    		$this->setAttr($attr, $value);
    	}
    }
    
    private function initEquipInfo($heroInfo)
    {
        $heroAttr = $heroInfo;
        $this->arrArming = array();
        $this->arrDress = array();
        $this->arrFightSoul = array();
        $this->arrGodWeapon = array();
        $this->arrSkillBook = array();
        $this->arrTreasure = array();
        $this->arrPocket = array();
        $this->arrTally = array();
        $this->arrChariot=array();
        foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
        {
            if(isset($heroInfo['va_hero'][$equipType]))
            {
                foreach($heroInfo['va_hero'][$equipType] as $pos => $itemInfo)
                {
                    switch($equipType)
                    {
                        case HeroDef::EQUIP_ARMING:
                            $this->arrArming[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_DRESS:
                            $this->arrDress[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_FIGHTSOUL:
                            $this->arrFightSoul[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_GODWEAPON:
                            $this->arrGodWeapon[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_TREASURE:
                            $this->arrTreasure[$pos] = ItemManager::__getItem($itemInfo,TRUE);
                            break;
                        case HeroDef::EQUIP_SKILL_BOOK:
                            $this->arrSkillBook[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_POCKET:
                            $this->arrPocket[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_TALLY:
                            $this->arrTally[$pos] = ItemManager::__getItem($itemInfo);
                            break;
                        case HeroDef::EQUIP_CHARIOT:
                        	$this->arrChariot[$pos] = ItemManager::__getItem($itemInfo);
                        	break;
                        default:
                            throw new FakeException('no such equiptype %s',$equipType);
                    }
                    $itemId = 0;
                    if(!empty($itemInfo))
                    {
                        $itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
                    }  
                    $heroAttr['va_hero'][$equipType][$pos] = $itemId;    
                }
            }
        }
        return $heroAttr;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */