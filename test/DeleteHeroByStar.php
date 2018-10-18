<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DeleteHeroByStar.php 81247 2013-12-17 04:04:44Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/DeleteHeroByStar.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-17 04:04:44 +0000 (Tue, 17 Dec 2013) $
 * @version $Revision: 81247 $
 * @brief 
 *  
 **/
class DeleteHeroByStar extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $uid = intval($arrOption[0]);
        $starLv = intval($arrOption[1]);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $userObj = EnUser::getUserObj();
        $heroMng = $userObj->getHeroManager();
        $allHeroObj = $heroMng->getAllHeroObj();
        foreach($allHeroObj as $hid => $heroObj)
        {
            if($heroObj->getConf(CreatureAttr::STAR_LEVEL) == $starLv && ($heroObj->canBeDel()))
            {
                $heroMng->delHeroByHid($hid);
                echo 'delete hero:'.$hid."\n";
            }
        }
        $userObj->update();
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */