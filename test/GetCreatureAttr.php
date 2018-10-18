<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GetCreatureAttr.php 86909 2014-01-15 04:50:17Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/GetCreatureAttr.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-01-15 04:50:17 +0000 (Wed, 15 Jan 2014) $
 * @version $Revision: 86909 $
 * @brief 
 *  
 **/
class GetCreatureAttr extends BaseScript
{
	/* (non-PHPdoc)
     * @see BaseScript::executeScript()
     */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
        $monsterId = intval($arrOption[0]);
        $level = intval($arrOption[1]);
        $creature = new Creature($monsterId);
        $creature->setLevel($level);
        $btInfo = $creature->getBattleInfo();
        var_dump($btInfo[PropertyKey::HP_BASE]);
        var_dump($btInfo[PropertyKey::HP_RATIO]);
        var_dump($btInfo[PropertyKey::HP_FINAL]);
        var_dump($btInfo[PropertyKey::REIGN]);
        var_dump($btInfo[PropertyKey::MAX_HP]);
        
//         var_dump($btInfo);
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */