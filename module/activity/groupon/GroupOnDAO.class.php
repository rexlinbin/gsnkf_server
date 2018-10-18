<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GroupOnDAO.class.php 151979 2015-01-12 12:58:39Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/GroupOnDAO.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-01-12 12:58:39 +0000 (Mon, 12 Jan 2015) $$
 * @version $$Revision: 151979 $$
 * @brief 
 *  
 **/
class GroupOnDao
{
    public static function selectGroupOn($aid)
    {
        $data = new CData();
        $arrRet = $data->select( array( GroupOnDef::VADATA) )
                    ->from(GroupOnDef::TGROUPON)
                    ->where(array(GroupOnDef::AID, '=', $aid))
                    ->query();
        if(!empty($arrRet[0]))
        {
            return $arrRet[0];
        }
        return array();
    }

    public static function selectGroupOnUser($uid)
    {
        $data = new CData();
        $arrRet = $data->select(array(GroupOnDef::BUYTIME ,GroupOnDef::USERVADATA))
                    ->from(GroupOnDef::TGROUPONUSER)
                    ->where(array(GroupOnDef::UID, '=', $uid))
                    ->query();
        if(!empty($arrRet[0]))
        {
            return $arrRet[0];
        }
        return array();
    }

    public static function iOrUGroupOn($arrField)
    {
        $data = new CData();
        $arrRet = $data->insertOrUpdate(GroupOnDef::TGROUPON)
                    ->values($arrField)
                    ->query();
    }

    public static function iOrUUsrData($arrField)
    {
        $data = new CData();
        $arrRet = $data->insertOrUpdate(GroupOnDef::TGROUPONUSER)
                    ->values($arrField)
                    ->query();
    }
    
    public static function updateUserData($uid, $arrField)
    {
    	$data = new CData();
    	$arrRet = $data->update(GroupOnDef::TGROUPONUSER)
		    			->set($arrField)
		    			->where(GroupOnDef::UID, '=', $uid)
		    			->query();
  		return $arrRet;
    }

    public static function selectAllUserData($beginTime, $endTime)
    {
        $data = new CData();
        $arrRet = $data->select(array(GroupOnDef::UID, GroupOnDef::BUYTIME, GroupOnDef::USERVADATA))
                    ->from(GroupOnDef::TGROUPONUSER)
                    ->where(array(GroupOnDef::BUYTIME, 'between', array($beginTime, $endTime)))
                    ->query();
        return $arrRet;
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */