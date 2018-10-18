<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: RobTomb.def.php 202940 2015-10-17 10:48:19Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/RobTomb.def.php $
 * @author $Author: wuqilin $(zhangtiantian@babeltime.com)
 * @date $Date: 2015-10-17 10:48:19 +0000 (Sat, 17 Oct 2015) $
 * @version $Revision: 202940 $
 * @brief 
 *  
 **/
class RobTombDef
{
    const BTSTORE_ROB_NEED_GOLD = 'rob_need_gold';
    const BTSTORE_ROB_NEED_LEVEL = 'rob_need_level';
    const BTSTORE_FREE_DROP_ID = 'free_drop_id';
    const BTSTORE_GOLD_DROP_ID = 'gold_drop_id';
    const BTSTORE_ACCUM_NUM = 'accum_num';
    const BTSTORE_ACCUM_DROP_ID = 'accum_drop_id';
    const BTSTORE_DROPID_LIMIT = 'drop_id_limit';
    const BTSTORE_LAST_ACCUMNUM = 'last_accum_num';
    const BTSTORE_LAST_INC_ACCUMNUM = 'last_inc_accumnum';
    
    
    
    
    const SQL_FIELD_UID = 'uid';
    const SQL_TODAY_FREE_NUM = 'today_free_num';
    const SQL_TODAY_GOLD_NUM = 'today_gold_num';
    const SQL_ACCUM_FREE_NUM = 'accum_free_num';
    const SQL_ACCUM_GOLD_NUM = 'accum_gold_num';
    const SQL_LAST_RFR_TIME = 'last_refresh_time';
    const SQL_VA_ROB_TOMB = 'va_rob_tomb';
    const SQL_VA_ROB_BLACKLIST = 'black_list';
    
    static $ALL_TBL_FIELD = array(
            self::SQL_FIELD_UID,
            self::SQL_TODAY_FREE_NUM,
            self::SQL_TODAY_GOLD_NUM,
            self::SQL_LAST_RFR_TIME,
            self::SQL_ACCUM_FREE_NUM,
            self::SQL_ACCUM_GOLD_NUM,
            self::SQL_VA_ROB_TOMB
            );
    
    
    const ROB_TYPE_FREE = 1;
    const ROB_TYPE_GOLD = 2;
    const ROB_TYPE_PRI_FREE = 3;//优先使用免费次数
    
    const ROB_MAX_NUM = 20;
    
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */