<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CopyTeamDao.class.php 102988 2014-04-23 03:49:30Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/CopyTeamDao.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-04-23 03:49:30 +0000 (Wed, 23 Apr 2014) $
 * @version $Revision: 102988 $
 * @brief 
 *  
 **/
class CopyTeamDao
{
    private static $tblCopyTeam = 't_copy_team';
    
    public static function getCopyTeamInfo($uid,$arrField)
    {
        $data = new CData();
        $ret = $data->select($arrField)
                    ->from(self::$tblCopyTeam)
                    ->where(array(CopyTeamDef::COPYTEAM_SQLFIELD_UID,'=',$uid)) 
                    ->query();
        if(empty($ret))
        {
            return array();
        }   
        return $ret[0];
    }
    
    public static function getArrUserCopyTeamInfo($arrUid,$arrField)
    {
        if(empty($arrUid))
        {
            return array();
        }
        $data = new CData();
        $ret = $data->select($arrField)
                    ->from(self::$tblCopyTeam)
                    ->where(array(CopyTeamDef::COPYTEAM_SQLFIELD_UID,'IN',$arrUid))
                    ->query();
        return $ret;
    }
    
    public static function getArrCopyTeamInfo($arrField,$arrWhere,$num)
    {
        if(empty($arrField) || ($num < 1))
        {
            return array();
        }
        $ret = array();
        $curRet = array();
        $data = new CData();
        $offset = 0;
        do 
        {
            $data->select($arrField)
                 ->from(self::$tblCopyTeam);
            foreach($arrWhere as $where)
            {
                $data->where($where);
            }
            if($num < CData::MAX_FETCH_SIZE)
            {
                $data->limit($offset, $num);
            }
            else
            {
                $data->limit($offset, CData::MAX_FETCH_SIZE);
            }
            $data->orderBy(CopyTeamDef::COPYTEAM_SQLFIELD_CURGUILDCOPY, FALSE);
            $curRet = $data->query();
            $num -= CData::MAX_FETCH_SIZE;
            $offset += CData::MAX_FETCH_SIZE;
            $ret = array_merge($ret,$curRet);
        }
        while(!empty($curRet) && ($num > 0));
        return $ret;    
    }
    
    public static function initCopyTeamInfo($allField)
    {
        $data = new CData();
        $data->insertInto(self::$tblCopyTeam)
             ->values($allField)
             ->query();
    }
    
    public static function updateCopyTeamInfo($uid,$arrField)
    {
        $data = new CData();
        $data->update(self::$tblCopyTeam)
             ->set($arrField)
             ->where(array(CopyTeamDef::COPYTEAM_SQLFIELD_UID,'=',$uid))
             ->query();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */