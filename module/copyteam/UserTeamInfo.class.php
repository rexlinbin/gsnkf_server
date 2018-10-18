<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserTeamInfo.class.php 92268 2014-03-05 07:36:42Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/UserTeamInfo.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-03-05 07:36:42 +0000 (Wed, 05 Mar 2014) $
 * @version $Revision: 92268 $
 * @brief 
 *  
 **/
class UserTeamInfo
{
    protected $userTeamInfo = NULL;
    protected $buffer = NULL;
    protected $uid = NULL;
    
    public function __construct($uid)
    {
        if(empty($uid))
        {
            throw new FakeException('empty uid %d.',$uid);
        }
        $this->uid = $uid;
    }
    
    public function doneTeamBattle($copyId, $atkRet, $isLeader)
    {
        
    }
    
    public function canAtk($copyId)
    {
        
    }
    
    public function getUserTeamInfo()
    {
        return $this->userTeamInfo;
    }
    
    public function saveUserTeamInfo()
    {
        if($this->userTeamInfo != $this->buffer)
        {
            CopyTeamDao::updateCopyTeamInfo($this->uid, $this->userTeamInfo);
        }
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */