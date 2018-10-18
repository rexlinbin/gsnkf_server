<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnRetrieve.class.php 257926 2016-08-23 09:15:28Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/retrieve/EnRetrieve.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-08-23 09:15:28 +0000 (Tue, 23 Aug 2016) $
 * @version $Revision: 257926 $
 * @brief 
 *  
 **/
class EnRetrieve
{
    public static function refreshData($uid=0)
    {
        try
        {
            if ( empty( $uid ) )
            {
                $uid = RPCContext::getInstance()->getUid();
            }
        
            RetrieveLogic::refreshInfo($uid);
        }
        catch(Exception $e)
        {
            Logger::fatal("EnRetrieve::rfrInfo failed. msg:%s.", $e->getMessage());
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */