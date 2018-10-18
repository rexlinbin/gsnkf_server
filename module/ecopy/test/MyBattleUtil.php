<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: MyBattleUtil.php 74868 2013-11-14 10:18:08Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/test/MyBattleUtil.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-14 10:18:08 +0000 (Thu, 14 Nov 2013) $
 * @version $Revision: 74868 $
 * @brief 
 *  
 **/
/**
 * 为当前用户（session中有uid）准备战斗  以及  战斗
 * @author dell
 *
 */
class MyBattleUtil
{
    
    public static function upFightForce()
    {
        $uid = RPCContext::getInstance()->getUid();
        $userObj = EnUser::getUserObj();
        $fmtObj = EnFormation::getFormationObj($uid);
        $heroMng = $userObj->getHeroManager();
        $squad = $fmtObj->getSquad();//index=>hid(没有武将的位置，没有index)
        $arrHtidInSquad = array();
        foreach($squad as $index => $hid)
        {
            $heroObj = $heroMng->getHeroObj($hid);
            $arrHtidInSquad[$index] = $heroObj->getHtid();
        }
        //添加武将  填充阵型
        self::fullSquadWithFiveStarHero($arrHtidInSquad);
        //武将升级
        self::levelUpHeroInFmt();
        //武将装备高级武器
        self::equipBestArming();
    }
    
    
    /**
     * 通关指定copyId的副本以及之前的副本
     * @param int $copyId
     */
    public static function doNCopyBattle($copyId,$baseLv=BaseLevel::SIMPLE)
    {
        $ncopy = new NCopy();
        $copyList = $ncopy->getCopyList();
        $atkCopies = array($copyId);
        $preCopy = self::getPreCopy($copyId);
        while (! empty($preCopy))
        {
            if (self::isCopyPassed($copyList, $preCopy) == TRUE)
            {
                break;
            }
            $copyId = $preCopy;
            $preCopy = self::getPreCopy($copyId);
            $atkCopies[] = $copyId;
        }
        $atkCopies = array_reverse($atkCopies);
        foreach ($atkCopies as $atkCopy)
        {
            self::attackCopy($atkCopy, $baseLv);
        }
    }
    
    public static function doECopyBattle($atkCopy)
    {
        $ecopy    =    new ECopy();
        $ecopy->enterCopy($atkCopy);
        $baseId    =    btstore_get()->ELITECOPY[$atkCopy]['base_id'];
        $armies    =    btstore_get()->BASE[$baseId]['simple']['simple_army_arrays']->toArray();
        foreach($armies as $army)
        {
            $atkRet    =    $ecopy->doBattle($atkCopy, $army, array());
            echo "attack ecopy:copyid ".$atkCopy.",army ".$army."result:".$atkRet['appraisal']."\n";
        }
        return $atkRet;
    }
    
    public static function fullSquadWithFiveStarHero($arrHtidInSquad)
    {
        $arrOpenNeedLevel = btstore_get()->FORMATION['arrOpenNeedLevel']->toArray();
        $level = $arrOpenNeedLevel[count($arrOpenNeedLevel)-1];
        $console = new Console();
        $console->level($level);
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $formation = new Formation();
        for($i=0;$i<FormationDef::FORMATION_SIZD;$i++)
        {
            if(isset($arrHtidInSquad[$i]))
            {
                continue;
            }
            while(true)
            {
                $newHtid = self::randFiveStarHero();
                if(HeroUtil::isMasterHtid($newHtid))
                {
                    continue;
                }
                if(!in_array($newHtid, $arrHtidInSquad))
                {
                    $arrHtidInSquad[$i] = $newHtid;
                    $hid = $heroMng->addNewHero($newHtid);
                    $formation->addHero($hid, $i);
                    break;
                }
            }
        }
    }
    
    private static function randFiveStarHero()
    {
        $arrHtid = btstore_get()->FIVESTARHERO->toArray();
        $index = array_rand($arrHtid);
        return $arrHtid[$index];
    }
    
    
    public static function levelUpHeroInFmt()
    {
        $switchObj = EnSwitch::getSwitchObj();
        $switchObj->addNewSwitch(SwitchDef::HEROFORGE);
        $switchObj->addNewSwitch(SwitchDef::HEROENFORCE);
        $switchObj->save();
        $uid = RPCContext::getInstance()->getUid();
        $fmtObj = EnFormation::getFormationObj($uid);
        $fmt = $fmtObj->getFormation();
        $hero = new Hero();
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        foreach($fmt as $pos => $hid)
        {
            if($heroMng->getHeroObj($hid)->isMasterHero())
            {
                continue;
            }
            $hero->enforce($hid, 30);
        }
    }
    
    public static function equipBestArming()
    {
        $bag = BagManager::getInstance()->getBag();
        $uid = RPCContext::getInstance()->getUid();
        //rand武器
        $arrItemCnf = btstore_get()->ITEMS;
        //arming的itemType是1  treasure的itemType是11
        $arrItem = array(
                HeroDef::EQUIP_ARMING=>array(),
                HeroDef::EQUIP_TREASURE=>array()
                );
        foreach($arrItemCnf as $itemId => $itemConf)
        {
            $itemType = $itemConf['type'];
            if($itemType ==ItemDef::ITEM_TYPE_ARM)
            {
                $armType = $itemConf['armType'];
                if(!isset($arrItem[HeroDef::EQUIP_ARMING][$armType]))
                {
                    $arrItem[HeroDef::EQUIP_ARMING][$armType] = $itemId;
                }
                else
                {
                    continue;
                }
            }
            else if ($itemType == ItemDef::ITEM_TYPE_TREASURE)
            {
                $treasureType = $itemConf['treasureType'];
                if(isset($arrItem[HeroDef::EQUIP_TREASURE][$treasureType]))
                {
                    continue;
                }
                $arrItem[HeroDef::EQUIP_TREASURE][$treasureType] = $itemId;
            }
            else
            {
                continue;
            }
        }
        foreach($arrItem as $equipType => $arrEquip)
        {
            foreach($arrEquip as $equipType =>$itemId)
            {
                $bag->addItemByTemplateID($itemId, 6);
            }
        }
        $bag->update();
        $fmtObj = EnFormation::getFormationObj($uid);
        $formation = $fmtObj->getFormation();
        $hero = new Hero();
        foreach($formation as $pos => $hid)
        {
            $hero->equipBestArming($hid);
        }
    }
    
    
    private static function isCopyPassed ($copyList, $copyId)
    {
        $uid = RPCContext::getInstance()->getUid();
        if (! isset($copyList[$copyId]))
        {
            return FALSE;
        }
        $copyInfo = $copyList[$copyId];
        $copyObj = new NCopyObj($uid, $copyId, $copyInfo);
        if ($copyObj->isCopyPassed())
        {
            return TRUE;
        }
        return FALSE;
    }
    
    private static function getPreCopy ($copyId)
    {
        if ($copyId == CopyConf::$FIRST_NORMAL_COPY_ID)
        {
            return FALSE;
        }
        $preBaseId = btstore_get()->COPY[$copyId]['base_open'];
        $preCopyId = btstore_get()->BASE[$preBaseId]['copyid'];
        return $preCopyId;
    }
    
    
    private static function isAtkBaseLv($copyInfo,$baseId,$baseLv)
    {
        if(!isset($copyInfo['va_copy_info']['progress'][$baseId]))
        {
            $copyInfo['va_copy_info']['progress'][$baseId]	=	1;
        }
        //已经通关
        if($copyInfo['va_copy_info']['progress'][$baseId] >= ($baseLv +2 ) && ($baseLv == BaseLevel::NPC))
        {
            return FALSE;
        }
        //没有此难度
        if(!isset(btstore_get()->BASE[$baseId][CopyConf::$BASE_LEVEL_INDEX[$baseLv]]))
        {
            return FALSE;
        }
        return TRUE;
    }
    
    private static function attackCopy ($copyId, $baseLv)
    {
        $uid    =    RPCContext::getInstance()->getUid();
        $formation    =  EnFormation::getFormationObj($uid)->getFormation();
        $ncopy = new NCopy();
        $copyList = $ncopy->getCopyList();
        if (! isset($copyList[$copyId]))
        {
            $uid = RPCContext::getInstance()->getUid();
            $copyInfo = array('uid' => $uid, 'copy_id' => $copyId, 'score' => 0,
                    'prized_num' => 0, 'va_copy_info' => array('progress' => array()));
        } 
        else
        {
            $copyInfo = $copyList[$copyId];
        }
        $baseIds = btstore_get()->COPY[$copyId]['base'];
        foreach ($baseIds as $baseId)
        {
            if ($baseId < 1)
            {
                break;
            }
            for ($j = 0; $j <= $baseLv; $j ++)
            {
                if (self::isAtkBaseLv($copyInfo, $baseId, $j) == FALSE)
                {
                    echo "can not atk baselevel copyid " . $copyId . " baseid " . $baseId . " baseLevel " . $j . "\n";
                    continue;
                }
                $lvName = CopyConf::$BASE_LEVEL_INDEX[$j];
                $armies = btstore_get()->BASE[$baseId][$lvName][$lvName.'_army_arrays'];
                $ret = $ncopy->enterBaseLevel($copyId, $baseId, $j);
                if ($ret != 'ok')
                {
                    echo "can not enter baselevel copyid " . $copyId ." baseid " . $baseId . " baseLevel " . $j . "\n";
                    return;
                }
                foreach ($armies as $index => $army)
                {
                    //NPC部队
                    if ($j == 0)
                    {
                        $fmt = self::getFmtOnAtkNpc($army, $formation);
                        $atkRet = $ncopy->doBattle($copyId, $baseId, $j,$army, array(), $fmt);
                        $atkRetStr    =    var_export($atkRet,true);
                        echo "attack ncopy ".$copyId." baseId ".$baseId." baselevel ".$j." armyid ".$army." result:".$atkRet['appraisal']."\n";
                    }
                    else
                    {
                        $atkRet = $ncopy->doBattle($copyId, $baseId, $j,$army, array());
                        $atkRetStr    =    var_export($atkRet,true);
                        echo "attack ncopy ".$copyId." baseId ".$baseId." baselevel ".$j." armyid ".$army." result:".$atkRet['appraisal']."\n";
                    }
                }
            }
        }
    }
    private static function getFmtOnAtkNpc($army,$formation)
    {
        $fmt    =    array();
        $teamId = intval(btstore_get()->ARMY[$army]['npc_team_id']);
        $mstFmt = btstore_get()->TEAM[$teamId]['fmt'];
        $hidInFmt    =    array();
        foreach($mstFmt as $pos => $mstId)
        {
            if(empty($mstId))
            {
                $fmt[$pos]    =    0;
                continue;
            }
            if(intval($mstId) == 1)
            {
                foreach($formation as $positon => $hid)
                {
                    if(in_array($hid, $hidInFmt) == TRUE)
                    {
                        continue;
                    }
                    $fmt[$pos]    =    $hid;
                    $hidInFmt[] = $hid;
                    break;
                }
            }
            else
            {
                $fmt[$pos]    =    0;
            }
        }
        return $fmt;
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */