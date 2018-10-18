<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RobTomb.class.php 202953 2015-10-17 11:38:19Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/robtomb/RobTomb.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 11:38:19 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202953 $
 * @brief 
 *  
 **/
/**
 * 挖宝规则：
 * 1.挖宝有免费次数和金币次数限制，每天更新（在VIP表里配置）
 * 2.挖宝一次时优先使用免费次数，挖宝五次使用金币挖宝
 * 3.有三类掉落表，免费、金币、累积金币掉落表（累积金币掉落表是在特定金币挖宝累积次数时使用的掉落表）
 * 4.掉落表黑名单（三类掉落表中都有可能进入黑名单）
 * 5.等级不够不能进入挖宝界面，背包满了不能挖宝
 * @author dell
 *
 */
class RobTomb implements IRobTomb
{
    private $uid;
    
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
	/* (non-PHPdoc)
     * @see IRobTomb::getMyRobInfo()
     */
    public function getMyRobInfo ()
    {
        // TODO Auto-generated method stub
        $ret = RobTombLogic::getRobInfo($this->uid);
        return $ret;
    }

	/* (non-PHPdoc)
     * @see IRobTomb::rob()
     */
    public function rob ($num,$robType = RobTombDef::ROB_TYPE_PRI_FREE)
    {
        // TODO Auto-generated method stub
        Logger::trace('robTomb start.params num:%d.robType:%d.',$num,$robType);
        list($num) = Util::checkParam(__METHOD__, func_get_args());
        $ret = RobTombLogic::rob($num,$robType, $this->uid);
        Logger::trace('robTomb end.');
        return $ret;
    }

    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */