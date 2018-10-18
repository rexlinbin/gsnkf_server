<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Desact.class.php 202029 2015-10-14 03:23:20Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/desact/Desact.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-14 03:23:20 +0000 (Wed, 14 Oct 2015) $
 * @version $Revision: 202029 $
 * @brief 
 *  
 **/
class Desact implements IDesact
{
    public function getDesactInfo()
    {
        $uid = RPCContext::getInstance()->getUid();
        
        $ret = DesactLogic::getInfo($uid);
        
        return $ret;
    }
    
    public function gainReward($id)
    {
        $id = intval($id);
        
        $uid = RPCContext::getInstance()->getUid();
        
        $ret = DesactLogic::gainReward($uid, $id);
        
        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */