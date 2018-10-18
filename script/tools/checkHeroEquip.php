<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkHeroEquip.php 95176 2014-03-24 08:54:41Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkHeroEquip.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-03-24 08:54:41 +0000 (Mon, 24 Mar 2014) $
 * @version $Revision: 95176 $
 * @brief 
 *  
 **/
class CheckHeroEquip extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $usage = "usage::btscript game001 CheckHeroEquip check|fix uid"."\n";
        // TODO Auto-generated method stub
        if(empty($arrOption) || ($arrOption[0] == 'help') || (count($arrOption) < 2))
        {
             echo 'invalid parameter :'.$usage;
             return;              
        }
        $uid = intval($arrOption[1]);
        $operation = $arrOption[0];
        if(empty($uid))
        {
            echo 'invalid uid :'.$usage;
            return;
        }
        if($operation != 'fix' && ($operation != 'check'))
        {
            echo 'invalid operation :'.$usage;
            return;
        }
        $fix = false;
        if($operation == 'fix')
        {
            $fix = true;
            Util::kickOffUser($uid);
           
        }  
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        
        $user = Enuser::getUserObj($uid);
        if(empty($user))
        {
            echo 'empty user :'.$usage;
            return;
        }
        $userLevel = $user->getLevel();
        
        $heroMng = $user->getHeroManager();
        $hids = EnFormation::getArrHidInFormation($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $fixItems = array();
        $bagItem = array();
        $invalidItem = array();
        foreach($hids as $pos=>$hid)
        {
            $heroObj = $heroMng->getHeroObj($hid);
            $allArm = $heroObj->getEquipByType(HeroDef::EQUIP_ARMING);
            foreach($allArm as $pos => $armId)
            {
                if(!in_array($pos, ArmDef::$ARM_VALID_TYPES))
                {
                    $heroObj->removeArmPos($pos);
                    $fixItems[$hid][] = $armId;
                    Logger::warning('hid:%d, armId:%d, pos:%d invalid pos', $hid, $armId, $pos);
                    continue;
                }
                if(empty($armId))
                {
                    continue;
                }
                $itemObj =  ItemManager::getInstance()->getItem($armId);
               
                if(empty($itemObj))
                {
                    $fixItems[$hid][] = $armId;
                    $heroObj->setArmingByPos(ItemDef::ITEM_ID_NO_ITEM, $pos);
                    Logger::warning('hid:%d, armId:%d not exist', $hid, $armId);
                }
                else
                {          
                    if(!isset(btstore_get()->ITEMS[$itemObj->getItemTemplateID()]))
                    {
                        $fixItems[$hid][] = $armId;
                        $heroObj->setArmingByPos(ItemDef::ITEM_ID_NO_ITEM, $pos);
                        Logger::warning('hid:%d, armId:%d, pos:%d invalid itemTpl:%d', $hid, $armId, $pos, $itemObj->getItemTemplateID());
                    }
                }
                $maxReinLevel = $userLevel* $itemObj->getReinforceRate();
                if( $itemObj->getLevel() >   $maxReinLevel)
                {
                	Logger::warning('uid:%d, hid:%d, armId:%d, reinLevel:%d > max:%d', 
                	$uid, $hid, $armId, $itemObj->getLevel(), $maxReinLevel);
                	//TODO
                }
                          
                if($bag->getGidByItemId($armId) !=BagDef::INVALID_GRID_ID)
                {
                    $bagItem[$hid][] = $armId;
                    $ret  = $bag->deleteItem($armId);
                    Logger::warning('hid:%d, armId:%d, pos:%d in bag too', $hid, $armId, $pos, $itemObj->getItemTemplateID());
                }
            }
            $allTreasure = $heroObj->getEquipByType(HeroDef::EQUIP_TREASURE);
            foreach($allTreasure as $pos => $treasureId)
            {
                if(empty($treasureId))
                {
                    continue;
                }
                $itemObj = ItemManager::getInstance()->getItem($treasureId);
            
                if(empty($itemObj))
                {
                    $fixItems[$hid][] = $treasureId;
                    $heroObj->setTreasureByPos(ItemDef::ITEM_ID_NO_ITEM, $pos);
                    Logger::warning('hid:%d, treasureId:%d not exist', $hid, $treasureId);
                }
                if(!isset(btstore_get()->ITEMS[$itemObj->getItemTemplateID()]))
                {
                    $fixItems[$hid][] = $treasureId;
                    $heroObj->setTreasureByPos(ItemDef::ITEM_ID_NO_ITEM, $pos);
                    Logger::warning('hid:%d, armId:%d, pos:%d invalid itemTpl:%d', $hid, $armId, $pos, $itemObj->getItemTemplateID());
                }
                if($bag->getGidByItemId($treasureId) !=BagDef::INVALID_GRID_ID)
                {
                    $bagItem[$hid][] = $treasureId;
                    $ret  = $bag->deleteItem($treasureId);
                    Logger::warning('hid:%d, treasureId:%d, pos:%d in bag too', $hid, $treasureId, $pos, $itemObj->getItemTemplateID());
                }
            }
                        
        }
        
        $allItemInfoInBag = $bag->bagInfo();
        $allArmInBag = $allItemInfoInBag[BagDef::BAG_ARM];
        foreach($allArmInBag as $itemInfo)
        {
        	$itemId = $itemInfo[ItemDef::ITEM_SQL_ITEM_ID];
        	$itemObj = ItemManager::getInstance()->getItem($itemId);
        	$itemType = $itemObj->getItemType();
        	if(  $itemType != ItemDef::ITEM_TYPE_ARM )
        	{
        		continue;
        	}
        	
        	$maxReinLevel = $userLevel* $itemObj->getReinforceRate();
        	if( $itemObj->getLevel() > $maxReinLevel)
        	{
        		Logger::warning('uid:%d, hid:%d, armId:%d, reinLevel:%d > max:%d', 
        		$uid, $hid, $itemId, $itemObj->getLevel(), $maxReinLevel);
        		//TODO
        	}
        	
        	Logger::debug('uid:%d, arm:%s', $uid, $itemInfo);
        }
        
        
        printf("error items those not in itemManager but in heroEquip:\n%s\n", var_export($fixItems, true));
                
        printf("error items those are both in bag and heroEquip:\n%s\n", var_export($bagItem, true));
        
        if($fix == TRUE)
        {
            printf("fix hero data\n");
            $user->update();
            $bag->update();
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */