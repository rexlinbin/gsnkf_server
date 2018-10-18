<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChargeRaffleDao.class.php 114446 2014-06-16 03:50:01Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/chargeraffle/ChargeRaffleDao.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-16 03:50:01 +0000 (Mon, 16 Jun 2014) $
 * @version $Revision: 114446 $
 * @brief 
 *  
 **/
class ChargeRaffleDao
{
    public static function getRaffleInfo($uid)
    {
        $data = new CData();
        $ret = $data->select(ChargeRaffleDef::$ALLFIELD)
                    ->from(ChargeRaffleDef::TBLNAME)
                    ->where(array(ChargeRaffleDef::TBLFIELD_UID,'=',$uid))
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function saveRaffleInfo($arrField)
    {
        $data = new CData();
        $data->insertOrUpdate(ChargeRaffleDef::TBLNAME)
             ->values($arrField)
             ->query();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */