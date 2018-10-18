<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ClearActivityCache.script.php 239946 2016-04-25 03:54:57Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/script/ClearActivityCache.script.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-04-25 03:54:57 +0000 (Mon, 25 Apr 2016) $
 * @version $Revision: 239946 $
 * @brief 
 *  
 **/
class ClearActivityCache extends BaseScript
{
    protected function executeScript($arrOption)
    {
        $frontKey = ActivityConfLogic::genMcKey4Front();
        $conf = McClient::get($frontKey);

        if ( !empty($conf) )
        {
            McClient::del($frontKey);
        }

        $arrConfName = ActivityConfLogic::getAllConfName();

        foreach ($arrConfName as $name)
        {
            $backKey = ActivityConfLogic::genMcKey($name);
            $confInMem = McClient::get($backKey);

            if ( !empty($confInMem) )
            {
                McClient::del($backKey);
            }
        }

        echo "done\n";
        return ;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */