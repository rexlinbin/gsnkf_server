<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GoldPitObj.class.php 118905 2014-07-07 06:59:28Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mineral/GoldPitObj.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-07 06:59:28 +0000 (Mon, 07 Jul 2014) $
 * @version $Revision: 118905 $
 * @brief 
 *  
 **/
class GoldPitObj extends PitObj
{
    public function __construct($pitInfo, $arrGuardInfo=array())
    {
        parent::__construct($pitInfo, $arrGuardInfo);
        if(!empty($arrGuardInfo) || ($this->domainType != MineralType::GOLD))
        {
            throw new FakeException('construct GoldPitObj failed.');
        }
    }
    
    public function giveUpGuard($uid)
    {
        Logger::fatal('gold pit can not be gived up temporarily');
    }
    
    public function dueGuard($uid)
    {
        Logger::fatal('gold pit can not be guarded temporarily,so no due guard.');
    }
    
    public function robGuardByOther($uid)
    {
        Logger::fatal('gold pit can not be robbed guard by other temporarily');
    }
    
    public function addGuard($uid)
    {
        Logger::fatal('gold pit can not be guarded temporarily');
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */