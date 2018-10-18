<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Envelope.def.php 223335 2016-01-19 03:02:47Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Envelope.def.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-01-19 03:02:47 +0000 (Tue, 19 Jan 2016) $
 * @version $Revision: 223335 $
 * @brief 
 *  
 **/
class EnvelopeDef
{
    const ID = 'id'; //id
    const NEED_LEVEL = 'level'; //开启等级
    const MAX_NUM_LIMIT = 'num'; //发红包单次个数上限
    const MIN_GOLD_LIMIT = 'gold'; //发红包单次金币数下限
    const DAY_MAX_GOLD_LIMIT = 'day_max'; //单日红包总金额上限
    const RECLAIM_TIME = 'time'; //回收时间
    const MAX_MSG_NUM = 'msg_num'; //显示的最大消息条数
    
    const SQL_ENVELOPE_EID = 'eid'; //红包id
    const SQL_ENVELOPE_SENDER_UID = 'uid'; //发红包者的uid
    const SQL_ENVELOPE_SCALE = 'scale'; //红包范围
    const SQL_ENVELOPE_SEND_TIME = 'send_time'; //发送时间
    const SQL_ENVELOPE_SUM_GOLD_NUM = 'gold_num'; //发送总金币数
    const SQL_ENVELOPE_LEFT_NUM = 'left_num'; //剩余的份数
    const SQL_ENVELOPE_BACK_TIME = 'back_time'; //到期返还的时间
    const SQL_ENVELOPE_VA_DATA = 'va_data'; //随机好的序列
    
    const SQL_ENVELOPE_USER_RECV_UID = 'uid'; //领红包者的uid
    const SQL_ENVELOPE_USER_EID = 'eid'; //所领红包的id
    const SQL_ENVELOPE_USER_RECV_TIME = 'recv_time'; //领取时间
    const SQL_ENVELOPE_USER_RECV_INDEX = 'recv_index'; //领取的第几份
    const SQL_ENVELOPE_USER_RECV_GOLD = 'recv_gold'; //领到的金币数
    
    public static $ALL_ENVELOPE_FIELD = array(
        self::SQL_ENVELOPE_EID,
        self::SQL_ENVELOPE_SENDER_UID,
        self::SQL_ENVELOPE_SCALE,
        self::SQL_ENVELOPE_SEND_TIME,
        self::SQL_ENVELOPE_SUM_GOLD_NUM,
        self::SQL_ENVELOPE_LEFT_NUM,
        self::SQL_ENVELOPE_BACK_TIME,
        self::SQL_ENVELOPE_VA_DATA,
    );
    
    public static $ALL_ENVELOPE_USER_FIELD = array(
        self::SQL_ENVELOPE_USER_RECV_UID,
        self::SQL_ENVELOPE_USER_EID,
        self::SQL_ENVELOPE_USER_RECV_TIME,
        self::SQL_ENVELOPE_USER_RECV_INDEX,
        self::SQL_ENVELOPE_USER_RECV_GOLD,
    );
    
    const ENVELOPE_SCALE_WHOLE_GROUP = 1; //红包范围是全服
    const ENVELOPE_SCALE_GUILD = 2; //红包范围是军团
    
    public static $ENVELOPE_SCALE = array(
        self::ENVELOPE_SCALE_WHOLE_GROUP,
        self::ENVELOPE_SCALE_GUILD,
    );
    
    const ENVELOPE_LIST_TYPE_ALL = 1; //拉取所有红包信息（包括全服、军团、个人）
    const ENVELOPE_LIST_TYPE_GUILD = 2; //拉取军团红包信息
    const ENVELOPE_LIST_TYPE_USER = 3; //拉取个人红包信息
    
    public static $ENVELOPE_LIST_TYPE = array(
        self::ENVELOPE_LIST_TYPE_ALL,
        self::ENVELOPE_LIST_TYPE_GUILD,
        self::ENVELOPE_LIST_TYPE_USER,
    );
    
    const MIN_GOLD_EVERYONE_ARGS = 10; // 计算每人所拿最小金币时的参数
    const MIN_GOLD_EVERYONE_GOLD = 1;  // 领红包金币最低值
    
    const LOCK_ENVELOPE = 'lock_envelope_'; // 锁红包
    const MC_RECV_OVER = 'mc_over_'; // 标志红包已领完的前缀
    
    const MAX_ENVELOPE_DIV_NUM_BACKEND = 100; // 约定的每个红包最多分的份数（即使改配置也不能超过这个值）
    
    const MAX_ENVELOPE_NUM_EACH_ACT = 1000000; // 单次活动中最多的红包数
    
    const MAX_MSG_LENGTH = 14; // 红包留言的最大字符长度
    
    const TIMER_OFFSET = 5; // timer执行的偏移时间
    
    public static $ENVELOPE_RANGE = array(0, 100000); // 红包随机分布范围
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */