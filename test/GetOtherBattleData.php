<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GetOtherBattleData.php 81543 2013-12-18 08:35:59Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/GetOtherBattleData.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-12-18 08:35:59 +0000 (Wed, 18 Dec 2013) $
 * @version $Revision: 81543 $
 * @brief 
 *  
 **/
class GetOtherBattleData extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $uid = intval($arrOption[0]);
        $userObj = EnUser::getUserObj($uid);
        $userObj->modifyBattleData();
        $ret = $userObj->getBattleDataAndSquad();
        var_dump($ret);
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */