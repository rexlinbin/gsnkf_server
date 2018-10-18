<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ADestiny.class.php 246738 2016-06-17 03:07:25Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/ADestiny.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-06-17 03:07:25 +0000 (Fri, 17 Jun 2016) $
 * @version $Revision: 246738 $
 * @brief 
 *  
 **/
class ADestiny extends ACopyObj
{
    public static function getPassReward($atkRet)
    {
        $baseId = AtkInfo::getInstance()->getBaseId();
	    $baseLv = AtkInfo::getInstance()->getBaseLv();
	    $reward = CopyUtil::getBasePassAward($baseId, $baseLv);
	    
	    return $reward;
    }
    
    public static function doneBattle($atkRet)
    {
        $copyId = AtkInfo::getInstance()->getCopyId();
        $actObj = MyACopy::getInstance()->getActivityCopyObj($copyId);
        Logger::trace('ExpTreasure doneBattle atkRet %s.',$atkRet);
        if($atkRet['pass'])
        {
            if( $actObj->subCanDefeatNum() == FALSE)
            {
                throw new FakeException('not enough defeatnum.now is %d',$actObj->getCanDefeatNum());
            }
            EnActive::addTask(ActiveDef::ACOPY);
            EnWeal::addKaPoints(KaDef::ACOPY);
            $uid = RPCContext::getInstance()->getUid();
            EnMission::doMission($uid, MissionType::ACOPY);
        }
        MyACopy::getInstance()->save();
        EnUser::getUserObj()->update();
        BagManager::getInstance()->getBag()->update();
        AtkInfo::getInstance()->saveAtkInfo();
        return array();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */