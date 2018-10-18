<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnChargeDart.class.php 239689 2016-04-21 13:52:11Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chargedart/EnChargeDart.class.php $
 * @author $Author: GuohaoZheng $(hoping@babeltime.com)
 * @date $Date: 2016-04-21 13:52:11 +0000 (Thu, 21 Apr 2016) $
 * @version $Revision: 239689 $
 * @brief 
 *  
 **/

/**
 * 木牛流马对外的接口，暂时还没啥可写的。。。留坑
 */

class EnChargeDart
{
    public static function getTopActivityInfo($uid)
    {
        $status = 'ok';
        $num = 0;
        //功能节点是否开启
        $need_level = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_LEVEL];
        if(!EnSwitch::isSwitchOpen(SwitchDef::CHARGEDART) && EnUser::getUserObj($uid)->getLevel() < $need_level)
        {
            $status = 'invalid';
        }
        if( 'ok' == $status )
        {
            $userChargeDartObj = MyChargeDart::getInstance($uid);
            $userInfo = $userChargeDartObj->getAllInfo();
            $freeNum = btstore_get()->CHARGEDART_RULE[ChargeDartDef::CSV_FREE_SHIP_NUM];
            
            $num = $userInfo[ChargeDartDef::SQL_BUY_SHIPPING_NUM] + $freeNum - $userInfo[ChargeDartDef::SQL_SHIPPING_NUM];
        }
        
        return array(
            'status' => $status,
            'extra' => array(
                'num' => $num,
            ),
        );
    }
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */