<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnMonthlyCard.class.php 120565 2014-07-15 12:39:07Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/discountcard/monthlycard/EnMonthlyCard.class.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-07-15 12:39:07 +0000 (Tue, 15 Jul 2014) $
 * @version $Revision: 120565 $
 * @brief 
 *  
 **/
class EnMonthlyCard
{
    public static function loginToGetReward()
    {
        $uid = RPCContext::getInstance()->getUid();
        RPCContext::getInstance()->executeTask($uid, 
                'monthlycard.sendRewardToCenter', array($uid));
    }
    
    public static function readMonthlyCardCSV()
    {
        return 'dummy';
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */