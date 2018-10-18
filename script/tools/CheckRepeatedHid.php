<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CheckRepeatedHid.php 92844 2014-03-11 07:35:34Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/CheckRepeatedHid.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-03-11 07:35:34 +0000 (Tue, 11 Mar 2014) $
 * @version $Revision: 92844 $
 * @brief 
 *  
 **/
class CheckRepeatedHid extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        if (empty($arrOption[0]) || $arrOption[0] == 'help' || (count($arrOption) < 2))
        {
            $this->usage();
            return;
        }        
        $option = $arrOption[0];
        if ($option == 'check')
        {
            $fix = false;
        }
        elseif ($option == 'fix')
        {
            $fix = true;
        }
        else
        {
            echo "invalid operation!\n";
            $this->usage();
            return;
        }
        
        $uid = intval($arrOption[1]);
        if(empty($uid))
        {
            $this->usage(); 
        }
        $usedHero = HeroDao::getArrHeroeByUid($uid, array('hid'));
        $unusedHero = array();
        $userInfo = UserDao::getUserByUid($uid, array('va_hero','uid','pid','vip','level','uname'));
        if(!empty($userInfo))
        {
            $unusedHero = $userInfo['va_hero']['unused'];
            unset($userInfo['va_hero']);
            echo "user info is \n";
            var_dump($userInfo);
        }
        else
        {
            echo "no such user\n";
        }
        $errData = array();
        foreach($usedHero as $hid => $heroInfo)
        {
            if(isset($unusedHero[$hid]))
            {
                $errData[$hid] = $heroInfo;
            }
        }
        echo "the heroes these are both in hero table and user table\n";
        var_dump($errData);
        if($fix && (!empty($errData)))
        {
            Util::kickOffUser($uid);
            RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
            $userObj = EnUser::getUserObj($uid);
            $allUnusedHero = $userObj->getAllUnusedHero();
            foreach($errData as $hid => $heroInfo)
            {
                $userObj->delUnusedHero($hid);
                Logger::info('CheckRepeatedHid fix.del unused hero %d.htid %d. level %d.uid %d',
                        $hid,$allUnusedHero[$hid]['htid'],$allUnusedHero[$hid]['level'],$userObj->getUid());
                echo "fix ".$hid." done\n";
            }
            $userObj->update();
            echo "FIX DONE\n";
        }
    }
    private function usage()
    {
        echo "usage: btscript game001 checkFormation.php check|fix uid\n";
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */