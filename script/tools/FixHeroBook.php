<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixHeroBook.php 89194 2014-02-10 03:52:14Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/tools/FixHeroBook.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-02-10 03:52:14 +0000 (Mon, 10 Feb 2014) $
 * @version $Revision: 89194 $
 * @brief 
 *  
 **/
class FixHeroBook extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        if(!isset($arrOption[0]))
        {
            return;
        }
        $arrHtid = array();
        $uid = intval($arrOption[0]);
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
        $heroMng = EnUser::getUserObj()->getHeroManager();
        $arrHeroObj = $heroMng->getAllHeroObj();
        $bookHeroes = btstore_get()->SHOWS[ShowDef::HERO_SHOW]->toArray();
        foreach($arrHeroObj as $hid => $heroObj)
        {
            $htid = $heroObj->getHtid();
            if(HeroUtil::isMasterHtid($htid))
            {
                continue;
            }
            if(!isset($arrHtid[$htid]) && (in_array($htid, $bookHeroes)))
            {
                $arrHtid[$htid] = 1;
            }
        }
        HeroLogic::updateHeroBook($uid, array_keys($arrHtid));
        $heroInfo = HeroLogic::getHeroBookInfo($uid);
        var_dump($heroInfo);
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */