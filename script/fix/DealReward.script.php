<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DealReward.script.php 248225 2016-06-27 02:40:31Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/fix/DealReward.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-06-27 02:40:31 +0000 (Mon, 27 Jun 2016) $
 * @version $Revision: 248225 $
 * @brief 
 *  
 **/
class DealReward extends BaseScript
{
    protected function executeScript($arrOption)
    {
        if ( count( $arrOption ) < 7 )
        {
            echo "btscript gameXXX DealReward.script.php uid fightSoulExp bead tally pocketExp starExp attrNum";
            exit();
        }
        
        $uid = intval( $arrOption[0] );
        $fightSoulExp = intval( $arrOption[1] );
        $bead = intval( $arrOption[2] );
        $tally = intval( $arrOption[3] );
        $pocketExp = intval( $arrOption[4] );
        $starExp = intval( $arrOption[5] );
        $attrNum = intval( $arrOption[6] );
        
        $do = 'check';
        if ( isset( $arrOption[7] ) && 'do' == $arrOption[7] )
        {
            $do = $arrOption[7];
        }
        
        $userInfo = UserDao::getUserByUid($uid, array('uid', 'pid', 'uname'));
        
        if ( empty( $userInfo ) )
        {
            echo "not found userInfo. uid:".$uid;
            Logger::fatal("not found userInfo. uid:%d", $uid);
            exit();
        }
        
        printf("found user. uid:%d, pid:%d, uname:%s.\n", $userInfo['uid'], $userInfo['pid'], $userInfo['uname']);
        Logger::info("found user. uid:%d, pid:%d, uname:%s.", $userInfo['uid'], $userInfo['pid'], $userInfo['uname']);
        
        $beadItemTplId = 60033;
        $tallyItemTplId = 60034;
        $attrItemTplId = 60040;
        
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        
        $bag = BagManager::getInstance()->getBag($uid);
        
        $hasBeadNum = $bag->getItemNumByTemplateID($beadItemTplId);
        $hasTallyNum = $bag->getItemNumByTemplateID($tallyItemTplId);
        
        $subBeadNum = $hasBeadNum >= $bead ? $bead : $hasBeadNum;
        //$subTallyNum = $hasTallyNum >= $tally ? $tally : $hasTallyNum;
        
        printf("sub bead. has:%d needDel:%d del:%d lack:%d.\n", $hasBeadNum, $bead, $subBeadNum, $bead - $subBeadNum);
        Logger::info("sub bead. has:%d needDel:%d del:%d lack:%d.", $hasBeadNum, $bead, $subBeadNum, $bead - $subBeadNum);
        
        //printf("sub tally. has:%d needDel:%d del:%d lack:%d.\n", $hasTallyNum, $tally, $subTallyNum, $tally - $subTallyNum);
        //Logger::info("sub tally. has:%d needDel:%d del:%d lack:%d.", $hasTallyNum, $tally, $subTallyNum, $tally - $subTallyNum);
        
	    $user = EnUser::getUserObj($uid);
	    $arrHero = $user->getHeroManager()->getAllHeroObjInSquad();
	    
	    $arrPocket = array();
	    $arrFightSoul = array();
	    
	    foreach ( $arrHero as $hero )
	    {
	        $arrTmpPocketInfo = $hero->getEquipByType(HeroDef::EQUIP_POCKET);
	        $arrTmpFightSoulInfo = $hero->getEquipByType(HeroDef::EQUIP_FIGHTSOUL);
	        
	        $arrPocket = array_merge($arrPocket, $arrTmpPocketInfo);
	        $arrFightSoul = array_merge($arrFightSoul, $arrTmpFightSoulInfo);
	    }
        
	    foreach ( $arrPocket as $key => $itemId )
	    {
	        if ( empty( $itemId ) )
	        {
	            unset( $arrPocket[$key] );
	        }
	    }
	    
	    foreach ( $arrFightSoul as $key => $itemId )
	    {
	        if ( empty( $itemId ) )
	        {
	            unset( $arrFightSoul[$key] );
	        }
	    }
	    
	    $arrPocketItem = ItemManager::getInstance()->getItems($arrPocket);
	    $arrFightSoulItem = ItemManager::getInstance()->getItems($arrFightSoul);
	    
	    $arrPocketInfo = array();
	    $arrFightSoulInfo = array();
	    
	    foreach ( $arrPocketItem as $itemId => $item )
	    {
	        $arrPocketInfo[] = array(
	            'item_id' => $item->getItemID(),
	            'item_template_id' => $item->getItemTemplateID(),
	            'exp' => $item->getExp(),
	        );
	    }
	    
	    foreach ( $arrFightSoulItem as $itemId => $item )
	    {
	        $arrFightSoulInfo[] = array(
	            'item_id' => $item->getItemID(),
	            'item_template_id' => $item->getItemTemplateID(),
	            'exp' => $item->getExp(),
	            'ev' => $item->getDevelopLevel(),
	        );
	    }
	    
	    $arrPocketExp = array();
	    foreach ( $arrPocketInfo as $key => $pocketInfo )
	    {
	        $arrPocketExp[$key] = $pocketInfo['exp'];
	    }
	    
	    $arrFightSoulExp = array();
	    foreach ( $arrFightSoulInfo as $key => $fightSoulInfo )
	    {
	        $arrFightSoulExp[$key] = $fightSoulInfo['exp'];
	    }
	    
	    array_multisort($arrPocketExp, SORT_DESC, $arrPocketInfo);
	    array_multisort($arrFightSoulExp, SORT_DESC, $arrFightSoulInfo);
	    
	    $arrSubPocketInfo = array();
	    foreach ( $arrPocketInfo as $key => $pocketInfo )
	    {
	        if ( $pocketExp <= 0 )
	        {
	            break;
	        }
	        
	        $subExp = $pocketInfo['exp'] > $pocketExp ? $pocketExp : $pocketInfo['exp'];
	        
	        $pocketExp -= $subExp;
	        
	        $arrSubPocketInfo[$pocketInfo['item_id']] = $subExp;
	        
	        printf("sub pocketExp. item_id:%d exp:%d sub exp:%d\n", $pocketInfo['item_id'], $pocketInfo['exp'], $subExp);
	        Logger::info("sub pocketExp. item_id:%d exp:%d sub exp:%d", $pocketInfo['item_id'], $pocketInfo['exp'], $subExp);
	    }
	    
	    if ( $pocketExp > 0 )
	    {
	        printf("sub pocketExp remain:%d\n", $pocketExp);
	        Logger::warning("sub pocketExp remain:%d", $pocketExp);
	    }
	    
	    $arrSubFightSoulInfo = array();
	    foreach ( $arrFightSoulInfo as $key => $fightSoulInfo )
	    {
	        if ( $fightSoulExp <= 0 )
	        {
	            break;
	        }
	        
	        $subExp = $fightSoulInfo['exp'] > $fightSoulExp ? $fightSoulExp : $fightSoulInfo['exp'];
	        
	        $fightSoulExp -= $subExp;
	        
	        $arrSubFightSoulInfo[$fightSoulInfo['item_id']] = $subExp;
	        
	        printf("sub fightSoulExp. item_id:%d exp:%d sub exp:%d\n", $fightSoulInfo['item_id'], $fightSoulInfo['exp'], $subExp);
	        Logger::info("sub fightSoulExp. item_id:%d exp:%d sub exp:%d", $fightSoulInfo['item_id'], $fightSoulInfo['exp'], $subExp);
	    }
	    
	    if ( $fightSoulExp > 0 )
	    {
	        printf("sub fightSoulExp remain:%d\n", $fightSoulExp);
	        Logger::warning("sub fightSoulExp remain:%d", $fightSoulExp);
	    }
	    
	    $arrFightSoulEv = array();
	    foreach ( $arrFightSoulInfo as $key => $fightSoulInfo )
	    {
	        $arrFightSoulEv[$key] = $fightSoulInfo['ev'];
	    }
	    
	    array_multisort($arrFightSoulEv, SORT_DESC, $arrFightSoulInfo);
	    
	    $arrDecEvLvInfo = array();
	    foreach ( $arrFightSoulInfo as $key => $fightSoulInfo )
	    {
	        if ( $tally <= 0 )
	        {
	            break;
	        }
	        
	        $fightSoulTplId = $fightSoulInfo['item_template_id'];
	        $ev = $fightSoulInfo['ev'];
	        
	        if ( $ev >= 1 )
	        {
	            $arrCost = ItemAttr::getItemAttr($fightSoulTplId, FightSoulDef::ITEM_ATTR_NAME_FIGHTSOUL_EVOLVECOST);
	             
	            $sum = 0;
	            for ( $i = $ev; $i > 0; $i-- )
	            {
	                $arrEvolveCost = $arrCost[$i];
	                 
	                $num = 0;
	                foreach ( $arrEvolveCost as $key => $need )
	                {
	                    if ( 7 == $need[0] && $tallyItemTplId == $need[1] )
	                    {
	                        $num = $need[2];
	                    }
	                }
	                 
	                if ( !empty( $num ) )
	                {
	                    if ( $tally < $num )
	                    {
	                        break;
	                    }
	                    else
	                    {
	                        $tally -= $num;
	                        $sum += $num;
	                    }
	                }
	            }
	            
	            $arrDecEvLvInfo[$fightSoulInfo['item_id']] = $i;
	            printf("decrease fightSoulEv. item_id:%d ori ev:%d delNum:%d set ev:%d\n", $fightSoulInfo['item_id'], $ev, $sum, $i);
	            Logger::info("decrease fightSoulEv. item_id:%d ori ev:%d delNum:%d set ev:%d\n:%d", $fightSoulInfo['item_id'], $ev, $sum, $i);
	        }
	    }
	    
	    $subTallyNum = 0;
	    if ( $tally > 0 )
	    {
	        $subTallyNum = $hasTallyNum >= $tally ? $tally : $hasTallyNum;
	        printf("sub tally. has:%d needDel:%d del:%d lack:%d.\n", $hasTallyNum, $tally, $subTallyNum, $tally - $subTallyNum);
	        Logger::info("sub tally. has:%d needDel:%d del:%d lack:%d.", $hasTallyNum, $tally, $subTallyNum, $tally - $subTallyNum);
	    }
	    
	    $arrSubStarInfo = array();
	    if ( FALSE == EnSwitch::isSwitchOpen(SwitchDef::STAR) )
	    {
	        printf("star switch is not open.\n");
	    }
	    else 
	    {
	        $myStar = MyStar::getInstance($uid);
	        $arrStarInfo = $myStar->getAllInfo();
	        $arrStarList = $arrStarInfo['star_list'];
	        
	        $arrStarExp = array();
	        foreach ( $arrStarList as $key => $starInfo )
	        {
	            $arrStarExp[$key] = $starInfo[StarDef::STAR_TOTAL_EXP];
	        }
	        
	        array_multisort($arrStarExp, SORT_DESC, $arrStarList);
	        
	        foreach ( $arrStarList as $key => $starInfo )
	        {
	            if ( $starExp <= 0 )
	            {
	                break;
	            }
	            
	            $subExp = $starInfo[StarDef::STAR_TOTAL_EXP] >= $starExp ? $starExp : $starInfo[StarDef::STAR_TOTAL_EXP];
	            
	            $starExp -= $subExp;
	            
	            $arrSubStarInfo[$starInfo[StarDef::STAR_ID]] = $subExp;
	            
	            printf("sub star. starId:%d tplId:%d sub exp:%d\n", $starInfo[StarDef::STAR_ID], $starInfo[StarDef::STAR_TID], $subExp);
	            Logger::info("sub star. starId:%d tplId:%d sub exp:%d", $starInfo[StarDef::STAR_ID], $starInfo[StarDef::STAR_TID], $subExp);
	        }
	        
	        if ( $starExp > 0 )
	        {
	            printf("sub starExp remain:%d\n", $starExp);
	            Logger::warning("sub starExp remain:%d", $starExp);
	        }
	    }
	    
	    $arrDecAttrExtraLv = array();
	    if(FALSE == EnSwitch::isSwitchOpen(SwitchDef::ATTREXTRA))
	    {
	        printf("attrextra switch is not open.\n");
	    }
	    else 
	    {
	        $myFormation = EnFormation::getFormationObj($uid);
	        $arrAttrExtra = $myFormation->getAttrExtraLevel();
	        
	        $arrAdjustAttrExtra = array();
	        foreach ( $arrAttrExtra as $index => $lv )
	        {
	            $arrAdjustAttrExtra[] = array(
	                'index' => $index,
	                'lv' => $lv,
	            );
	        }
	        
	        $arrAttrExtraLv = array();
	        foreach ( $arrAdjustAttrExtra as $key => $attrInfo )
	        {
	            $arrAttrExtraLv[$key] = $attrInfo['lv'];
	        }
	        
	        array_multisort($arrAttrExtraLv, SORT_DESC, $arrAdjustAttrExtra);
	        
	        $attrExtraConf = btstore_get()->SECOND_FRIENDS_LVUP;
	        
	        foreach ( $arrAdjustAttrExtra as $key => $attrInfo )
	        {
	            $lv = $attrInfo['lv'];
	            $index = $attrInfo['index'];
	            
	            $sum = 0;
	            for ( $i = $lv; $i > 0; $i-- )
	            {
	                $num = 0;
	                
	                $costItem = $attrExtraConf[$i]["costItem"];
	                foreach ( $costItem as $itemTplNum => $itemNum )
	                {
	                    if ( $itemTplNum == $attrItemTplId )
	                    {
	                        $num = $itemNum;
	                    }
	                }
	                
	                if ( !empty( $num ) )
	                {
	                    if ( $attrNum < $num )
	                    {
	                        break;
	                    }
	                    else
	                    {
	                        $attrNum -= $num;
	                        $sum += $num;
	                    }
	                }
	            }
	            
	            $arrDecAttrExtraLv[$index] = $i;
	            
	            printf("decrease attrExtraLv. index:%d ori lv:%d delNum:%d set lv:%d\n", $index, $lv, $sum, $i);
	            Logger::info("decrease attrExtraLv. index:%d ori lv:%d delNum:%d set lv:%d\n", $index, $lv, $sum, $i);
	        }
	    }
	    
	    $subAttrNum = 0;
	    if ( $attrNum > 0 )
	    {
	        $hasAttrNum = $bag->getItemNumByTemplateID($attrItemTplId);
	        $subAttrNum = $hasAttrNum >= $attrNum ? $attrNum : $hasAttrNum;
	        printf("sub attr. has:%d needDel:%d del:%d lack:%d.\n", $hasAttrNum, $attrNum, $subAttrNum, $attrNum - $subAttrNum);
	        Logger::info("sub attr. has:%d needDel:%d del:%d lack:%d.", $hasAttrNum, $attrNum, $subAttrNum, $attrNum - $subAttrNum);
	    }
	    
	    if ( 'do' == $do )
	    {
	        Util::kickOffUser($uid);
	        
	        if ( FALSE == $bag->deleteItembyTemplateID($beadItemTplId, $subBeadNum) )
	        {
	            printf("sub bead failed. num:%d.\n", $subBeadNum);
	            Logger::fatal("sub bead failed. num:%d.", $subBeadNum);
	        }
	        
	        if ( FALSE == $bag->deleteItembyTemplateID($tallyItemTplId, $subTallyNum) )
	        {
	            printf("sub tally failed. num:%d.\n", $subTallyNum);
	            Logger::fatal("sub tally failed. num:%d.", $subTallyNum);
	        }
	        
	        if ( FALSE == $bag->deleteItembyTemplateID($attrItemTplId, $subAttrNum) )
	        {
	            printf("sub attr failed. num:%d.\n", $subAttrNum);
	            Logger::fatal("sub attr failed. num:%d.", $subAttrNum);
	        }
	        
	        foreach ( $arrSubPocketInfo as $itemId => $needSubExp )
	        {
	            $item = $arrPocketItem[$itemId];
	            $exp = $item->getExp();
	            $item->reset();
	            $item->addExp($exp-$needSubExp);
	        }
	        
	        foreach ( $arrSubFightSoulInfo as $itemId => $needSubExp )
	        {
	            $item = $arrFightSoulItem[$itemId];
	            $exp = $item->getExp();
	            $item->setExp(0);
	            $item->setLevel(0);
	            $item->addExp($exp-$needSubExp);
	        }
	        
	        foreach ( $arrDecEvLvInfo as $item => $ev )
	        {
	            $item = $arrFightSoulItem[$itemId];
	            $exp = $item->getExp();
	            $item->setEvolve($ev);
	        }
	        
	        foreach ( $arrSubStarInfo as $sid => $needSubExp )
	        {
	            $myStar = MyStar::getInstance($uid);
	            $exp = $myStar->getStarExp($sid);
	            $myStar->setStarLevel($sid, 0);
	            $myStar->setStarExp($sid, 0);
	            StarLogic::addFavor($uid, $sid, $exp - $needSubExp);
	        }
	        
	        foreach ( $arrDecAttrExtraLv as $index => $lv )
	        {
	            $myFormation->setAttrExtraLvOfIndex($index, $lv);
	        }
	        
	        $user->modifyBattleData();
	        $user->update();
	        $bag->update();
	        EnFormation::getFormationObj($uid)->update();
	        MyStar::getInstance($uid)->update();
	        
	        if ( !empty( $arrSubStarInfo ) )
	        {
	            $myStar->update();
	        }
	        
	    }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */