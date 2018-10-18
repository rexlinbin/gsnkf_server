<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserExtraDAO.class.php 75661 2013-11-19 10:46:28Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/UserExtraDAO.class.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-19 10:46:28 +0000 (Tue, 19 Nov 2013) $
 * @version $Revision: 75661 $
 * @brief 
 *  
 **/


class UserExtraDao
{
    const tblUser = 't_user_extra';
    
    public static function getUserExtra($uid,$arrFiled)
    {
        $data    =    new CData();
        $ret    =    $data->select($arrFiled)
                         ->from(self::tblUser)
                         ->where(array('uid','=',$uid))
                         ->query();
        if(empty($ret)||(empty($ret[0])))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function initUserExtra($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->insertInto(self::tblUser)
             ->values($arrField)
             ->query();
        return $ret;
    }
    
    public static function updateUserExtra($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->update(self::tblUser)->set($arrField)
        		->where(array('uid','=',$uid))->query();

        return $ret;
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */