<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: DiscountCardDao.class.php 114287 2014-06-13 13:22:56Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/DiscountCardDao.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-06-13 13:22:56 +0000 (Fri, 13 Jun 2014) $
 * @version $Revision: 114287 $
 * @brief 
 *  
 **/
class DiscountCardDao
{
    public static function getCardInfo($uid,$cardId)
    {
        $data = new CData();
        $ret = $data->select(DiscountCardDef::$TBL_ALLSQLFIELD)
                    ->from(DiscountCardDef::DISCOUNTCARD_TBLNAME)
                    ->where(DiscountCardDef::TBL_SQLFIELD_UID,'=',$uid)
                    ->where(DiscountCardDef::TBL_SQLFIELD_CARDID,'=',$cardId)
                    ->query();
        if(empty($ret))
        {
            return array();
        }
        return $ret[0];
    }
    
    public static function saveCardInfo($cardInfo)
    {
        $data = new CData();
        $data->insertOrUpdate(DiscountCardDef::DISCOUNTCARD_TBLNAME)
             ->values($cardInfo)
             ->query();    
    }
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */