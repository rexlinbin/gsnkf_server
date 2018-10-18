<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkHeroHtid.php 83430 2013-12-27 08:50:39Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkHeroHtid.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-27 08:50:39 +0000 (Fri, 27 Dec 2013) $
 * @version $Revision: 83430 $
 * @brief 
 *  
 **/
class CheckHeroHtid extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        $usage    =    "usage::btscript game001 checkHeroHtid.php check|fix [uid]"."\n";
        if(empty($arrOption) || ($arrOption[0] == 'help') || (count($arrOption) < 2))
        {
            echo 'invalid parameter :'.$usage;
            return;
        }
        $uid    =    intval($arrOption[1]);
        $operation    =    $arrOption[0];
        if(empty($uid) || ($operation != 'fix' && ($operation != 'check')))
        {
            var_dump($arrOption);
            echo 'invalid parameter :'.$usage;
            return;
        }
        $fix    =    false;
        if($operation === 'fix')
        {
            $fix = true;
        }
        $proxy = new ServerProxy();
        $proxy->closeUser($uid);
        sleep(1);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $formation     =    FormationDao::getByUid($uid);
        $heroes    =    HeroDao::getArrHeroeByUid($uid, array('hid','htid','evolve_level'));
        $fixHero    =    array();
        //检查used_hero
        foreach($heroes as $hid => $hero)
        {
            $arrField = array();
            if(!isset(btstore_get()->HEROES[$hero['htid']]))
            {
                if($fix == TRUE)
                {
                    $arrField    =    array(
                            'uid'=>$uid,'hid'=>$hid,'delete_time'=>time(),
                    );
                    Logger::info('delete hero %d with htid %d.',$hid,$hero['htid']);
                }
                $fixHero[$hid]['htid'] = $hero['htid'];
                $fixHero[$hid]['reason'] = "no such hero";
                if(isset($formation['va_formation'][$hid]))
                {
                    unset($formation['va_formation'][$hid]);
                    Logger::info('delete hero %d from formation.',$hid);
                }
            }
            else//根据进阶等级和突破次数修复卡牌的htid
            {
                $htid = $hero['htid'];
                $actualHtid = $htid;
                $preEvLv = $hero['evolve_level'];
                $evLv = $hero['evolve_level'];
                $utid = EnUser::getUserObj()->getUtid();
                if($evLv > HeroLogic::getMaxEvolveLv($htid))
                {
                    $evLv = HeroLogic::getMaxEvolveLv($htid);
                }
                $lastBreakId = EnDestiny::getLastBreakId();
                if(!empty($lastBreakId) && (HeroUtil::isMasterHtid($htid)))
                {
                    $actualHtid = btstore_get()->BREAKTBL[$lastBreakId]['transformToHtid'][$baseHtid];
                }
                else
                {
                    $evlTbl = HeroLogic::getEvolveTbl($htid, $evLv-1);
                    if(!empty($evlTbl))
                    {
                        $actualHtid = btstore_get()->HERO_CONVERT[$evlTbl]['toHtid'];
                    }
                }
                if($actualHtid != $htid || ($preEvLv != $evLv))
                {
                    $fixHero[$hid]['htid'] = $htid;
                    $fixHero[$hid]['reason'] = 'fix evolve level pre is '.$preEvLv.'.after '.$evLv.'; prehtid '.$htid.' after '.$actualHtid;
                    Logger::info('fix hero htid or evolve level.preinfo %s after %s',$hero,$arrField);
                    if($fix == TRUE)
                    {
                        $arrField    =    array(
                        'uid'=>$uid,'hid'=>$hid,'htid'=>$actualHtid,'evolve_level'=>$evLv,
                        );
                    }
                }
            }
            if(!empty($arrField))
            {
                HeroDao::update($hid, $arrField);
            }
        }
        //检查unused_hero
        $userInfo    =    UserDao::getUserByUid($uid, array('va_hero'));
        foreach($userInfo['va_hero']['unused'] as $hid => $hero)
        {
            if(!isset(btstore_get()->HEROES[$hero[0]]))
            {
                $fixHero[$hid]['htid'] = $hero[0];
                $fixHero[$hid]['reason'] = 'no such hero';
                Logger::info('delete unused hero %d with no existed htid %d.',$hid,$hero[0]);
                unset($userInfo['va_hero']['unused'][$hid]);
                if(isset($formation['va_formation']['formation'][$hid]))
                {
                    Logger::info('delete hero %d from formation ',$hid);
                    unset($formation['va_formation']['formation'][$hid]);
                }
                $key = array_search($hid, $formation['va_formation']['extra'][$hid]);
                if(!empty($key))
                {
                    Logger::info('delete hero %d from extra formation.',$hid);
                    unset($formation['va_formation']['extra'][$key]);
                }
            }
        }
        if($fix == TRUE)
        {
            UserDao::updateUser($uid, $userInfo);
        }
        echo "the error data is :\n";
        var_dump($fixHero);
        if($fix == TRUE)
        {
            FormationDao::update($uid, $formation);
            echo 'fix done'."\n";
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */