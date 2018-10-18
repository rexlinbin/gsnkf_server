<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: checkMasterHeroLevel.php 63761 2013-09-09 13:45:52Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/checkMasterHeroLevel.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-09 13:45:52 +0000 (Mon, 09 Sep 2013) $
 * @version $Revision: 63761 $
 * @brief 
 *  
 **/
class CheckMasterHeroLevel extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $usage    =    "usage::btscript game001 CheckMasterHeroLevel.php check|fix uid"."\n";
        // TODO Auto-generated method stub
        if(empty($arrOption) || ($arrOption[0] == 'help') || (count($arrOption) < 2))
        {
            echo 'invalid parameter :'.$usage;
            return;
        }
        $uid    =    intval($arrOption[1]);
        $operation    =    $arrOption[0];
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
        $fix    =    false;
        if($operation == 'fix')
        {
            $fix = true;
        }
        $proxy = new ServerProxy();
        $proxy->closeUser($uid);
        sleep(1);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $user    =    Enuser::getUserObj($uid);
        $heroMng    =    $user->getHeroManager();
        $oldLevel = $heroMng->getMasterHeroObj()->getLevel();
        $newLevel = $user->getLevel();
        echo 'oldlevel:'.$oldLevel.' newlevel:'.$newLevel."\n";
        if($fix == TRUE)
        {
            $heroMng->getMasterHeroObj()->setLevel($user->getLevel());
            $heroMng->update();
            $user->modifyBattleData();
        }
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */