<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DestinyLogic.class.php 163324 2015-03-24 08:04:40Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/destiny/DestinyLogic.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-03-24 08:04:40 +0000 (Tue, 24 Mar 2015) $
 * @version $Revision: 163324 $
 * @brief 
 *  
 **/
class DestinyLogic
{
    public static function getDestinyInfo()
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::DESTINY) == FALSE)
        {
            throw new FakeException('switch destiny is not open.');
        }
        $destinyInst = EnDestiny::getDestiny();
        $destinyInfo = $destinyInst->getDestinyInfo(); 
        $curDestinyId = $destinyInst->getCurDestinyId();
        $copyScore = MyNCopy::getInstance()->getScore();
        $costScore = 0;
        if(!empty($curDestinyId))
        {
            $costScore = btstore_get()->DESTINY[$curDestinyId]['needCopyScore'];
        }
        $destinyInfo['has_score'] = $copyScore - $costScore;
        $destinyInfo['all_score'] = $copyScore;
        return $destinyInfo;    
    }
    
    /**
     * 激活天命
     * 武将tranform
     * @param int $astrolabeId
     */
    public static function activateDestiny($destinyId)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::DESTINY) == FALSE)
        {
            throw new FakeException('switch destiny is not open.');
        }
        if(!isset(btstore_get()->DESTINY[$destinyId]))
        {
            throw new FakeException('no such destiny id %s.',$destinyId);
        }
        $destinyInst = EnDestiny::getDestiny();
        $curDestinyId = $destinyInst->getCurDestinyId();
        if(btstore_get()->DESTINY[$destinyId]['preId'] != $curDestinyId)
        {
            throw new FakeException('pre destiny id %s is not be activated.or this destiny %d has been activated.',btstore_get()->DESTINY[$destinyId]['preId'],$destinyId);
        }
        $preNeedScore = 0;
        if(!empty($curDestinyId))
        {
            $preNeedScore = btstore_get()->DESTINY[$curDestinyId]['needCopyScore'];
        }
        $copyScore = MyNCopy::getInstance()->getScore();
        $needScore = btstore_get()->DESTINY[$destinyId]['needCopyScore'];
        if($copyScore < $needScore)
        {
            throw new FakeException('activate destiny %d need score %d has %d.',$destinyId,$needScore,$copyScore);
        }
        $userObj = EnUser::getUserObj();
        $needSilver = btstore_get()->DESTINY[$destinyId]['spendSilver'];
        if($userObj->subSilver($needSilver) == FALSE)
        {
            throw new FakeException('activate destiny;sub silver failed.');
        }
        $breakTblId = btstore_get()->DESTINY[$destinyId]['breakId'];
        if(!empty($breakTblId))
        {
            self::heroTransform($breakTblId);
        }
        $destinyInst->activateDestiny($destinyId);
        $uid = RPCContext::getInstance()->getUid();
        EnAchieve::updateDestiny($uid, EnDestiny::getActiveDestinyNum($uid));
        $destinyInst->save();
        $userObj->update();
        $userObj->modifyBattleData();
        return $needScore-$preNeedScore;
    }
    
    
    /**
     * 突破
     * @param int $destinyId
     */
    private static function heroTransform($transformId)
    {
        Logger::trace('heroTransform id is %d.',$transformId);
        if(!isset(btstore_get()->BREAKTBL[$transformId]))
        {
            throw  new FakeException('no such breaktbl id %d.',$transformId);
        }
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $heroObj = $heroMng->getMasterHeroObj();
        $htid = $heroObj->getBaseHtid();
        $toHtid = btstore_get()->BREAKTBL[$transformId]['transformToHtid'][$htid];
        $needEvLv = btstore_get()->BREAKTBL[$transformId]['minEvolveLv'];
        if($heroObj->getEvolveLv() < $needEvLv)
        {
            throw new FakeException('can not break %d.need evolve lv %d now is %d.',
                    $transformId,$needEvLv,$heroObj->getEvolveLv());
        }
        if(btstore_get()->BREAKTBL[$transformId]['isDevelop'])
        {
            Logger::info('masterhero develop to %d breakid %d',$toHtid,$transformId);
            $heroObj->develop($toHtid);
        }
        else
        {
            Logger::info('masterhero transform to %d breakid %d',$toHtid,$transformId);
            $heroObj->transformTo($toHtid);
        }
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */