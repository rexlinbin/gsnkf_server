<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Desact.def.php 203462 2015-10-20 11:40:35Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Desact.def.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-10-20 11:40:35 +0000 (Tue, 20 Oct 2015) $
 * @version $Revision: 203462 $
 * @brief 
 *  
 **/
class DesactDef
{
    const NCOPY_SUC = 1;         // 普通副本 ： 胜利
    const ARENA_SUC = 2;         // 竞技场     ： 胜利
    const FRAGSEIZE = 3;         // 夺宝         ： 无论胜负
    const OPEN_BOX = 4;          // 开箱子     ： 金银铜箱子
    const COMPETE = 5;           // 比武         ： 无论胜负
    
    public static $ARR_TASK_TYPE = array(
        self::NCOPY_SUC,
        self::ARENA_SUC,
        self::FRAGSEIZE,
        self::OPEN_BOX,
        self::COMPETE,
    );
    
    //解析配置字段
    const ID = 'id';              //任务id
    const LAST_DAY = 'days';      //持续天数
    const IS_OPEN = 'isopen';     //是否开启
    const REWARD = 'reward';      //奖励
    const DESCRIPTION = 'desc';   //描述
    const MISSION_NAME = 'name';  //任务名称
    const MISSION_TIPS = 'tip';   //任务说明
    
    const MISSION_CLOSE = 0;       //不开启任务
    const MISSION_OPEN = 1;        //开启任务
    
    //sql
    const SQL_UID = 'uid';
    const SQL_UPDATE_TIME = 'update_time';
    const SQL_VA_DATA = 'va_data';
    
    public static $ARR_INNER_DESACT_FIELDS = array(
        self::SQL_UID,
        self::SQL_UPDATE_TIME,
        self::SQL_VA_DATA,
    );
    
    //session中的key
    const SESS_KEY_DESACT_INFO = 'desact.userinfo';
    const SESS_KEY_DESACT_CROSS_CONFIG = 'desact.config';
}

class DesactCrossDef
{
    const SQL_SESS = 'sess';
    const SQL_UPDATE_TIME = 'update_time';
    const SQL_VERSION = 'version';
    const SQL_VA_CONFIG = 'va_config';
    
    const SESSION_VALID = 3600;
    const SESSION_KEY_TID = 'desact.tid';
    const SESSION_KEY_START_TIME = 'desact.start';
    const SESSION_KEY_END_TIME = 'desact.end';
    const SESSION_KEY_SET_TIME = 'desact.set';
    const SESSION_KEY_CONF_UPDATE_TIME = 'desact.update';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */