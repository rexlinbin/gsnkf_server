<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActPayBack.def.php 231147 2016-03-05 10:54:09Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/ActPayBack.def.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-03-05 10:54:09 +0000 (Sat, 05 Mar 2016) $
 * @version $Revision: 231147 $
 * @brief 
 *  
 **/
class ActPayBackDef
{   
    const REWARD_ID_BASE = 90000;    //补偿活动奖励id基础值
    
    const SQL_UID = 'uid';
    const SQL_REFRESH_TIME = 'rfr_time';
    const SQL_VA_DATA = 'va_data';
    
    public static $ALL_SQL_FIELD = array(
        self::SQL_UID,
        self::SQL_REFRESH_TIME,
        self::SQL_VA_DATA,
    );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */