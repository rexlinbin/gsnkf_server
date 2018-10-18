<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: Arena.cfg.php 147585 2014-12-19 14:22:02Z BaoguoMeng $
 * 
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Arena.cfg.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2014-12-19 14:22:02 +0000 (Fri, 19 Dec 2014) $
 * @version $Revision: 147585 $
 * @brief 
 *  
 **/



class ArenaConf
{	
	/**
	 * memcahce 超时时间
	 */
	const MEM_EXPIRED_TIME = 900;
	
	/**
	 * 取用户名次前面几个做为对手
	 */
	const OPPONENT_BEFOR = 8;
	
	/**
	 * 取用户名次后面几个做为对手
	 */
	const OPPONENT_AFTER = 2;	
	
	/**
	 * 战报数量
	 */
	const FIGHT_MSG_NUM = 15;
	
	/**
	 * 排行榜数量
	 */
	const RANK_LIST_NUM = 10;
	
	/**
	 * 幸运排名取值
	 * 里层数组第一、二个为排名取值范围，第三个为奖励金币数量
	 * 如：从1到500中取一个随机数， 奖100金
	 */
	static $LUCKY_POSITION_CONFIG  = array(
        array(1,	500,	50),
        array(1,	500,	50),
        array(1,	500,	50),
        array(1,	20,		25),
        array(21,	50,		25),
        array(51,	100,	25),
        array(101,	200,	25),
        array(201,	300,	25),
        array(301,	400,	25),
        array(401,	500,	25),
        );
	
	/**
	 * 每次从数据库取多少数据,要比REWARD_BEFOR_POSITION小
	 * 不能超过100
	 */
	const NUM_OF_QUERY = 100;	
	
	/**
	 * 发奖用，一次连续发多少个用户, 只是修改数据库的一个字段，值可以适当大一点
	 */
	const NUM_OF_REWARD_PER = 10;
	
	/**
	 * 每次发 NUM_OF_REWARD_PER 后休眠多少毫秒
	 */
	const SLEEP_MTIME = 50; 
	
	//3600*13 是随意取的值。用来区分是否是上一轮发的奖。
	//最大可取值 每轮天数×24 - 发奖锁定时间	
	//别取太小了，不然出错重做的时候吧当前期的当上一期就悲剧了。
	const REWARD_REDO_LIMIT_HOURS = 13;
	
	//重发的时候保留多少个小时，用来区分上一次发奖
	// REWARD_REDO_LIMIT_HOURS必须小于ArenaDateConf::LAST_DAYS*24 - REWARD_REDO_LIMIT_HOURS_RETAIN
	const REWARD_REDO_LIMIT_HOURS_RETAIN = 6;
	
	const NPC_NUM = 5000;  //最大只能是5000，这个被uid分段限制的 @see UserDef::SPECIAL_UID
	
	//排名历史天数
	const POS_HIS = 7;
	
	//是否运行发奖脚本标志位,发奖脚本目前只生成竞技场玩家排名快照
	const NO_CRON = FALSE;
	
	//竞技场排名快照玩家个数
	const ARENA_RANK_SNAPSHOT_MAX_NUM = 5000;
}

/**
 * 广播优先级
 * 当都符合广播条件时， 选择优先级最高的广播，
 * 越小优先级越高
 * 
 * @author idyll
 *
 */
class ArenaBroadcast
{
	/**
	 * 第一名优先级
	 * @var unknown_type
	 */	
	const PRI_TOP1 = 0;
	
	/**
	 * 连胜被终止优先级
	 * @var unknown_type
	 */
	const PRI_CONTINUE_END = 1;
	
	/**
	 * 连胜次数优先级
	 * @var unknown_type
	 */
	const PRI_CONTINUE_SUC = 2;
	
	/**
	 * 连续上升名次优先级
	 * @var unknown_type
	 */
	const PRI_UPGRADE_CONTINUE = 3;
	
	/**
	 * 连胜
	 * @var unknown_type
	 */
	public static $ARR_CONTINUE_SUC = array(
		15 => 0,
		20 => 1,
		30 => 2,
		50 => 3,
	);
	
	/**
	 * 连续上升名次
	 * @var unknown_type
	 */
	public static $ARR_UPGRADE_CONTINUE = array(
		200 => 0,
		500 => 1,
		800 => 2,
		1000 => 3,
	);
	
	/**
	 * 终结连胜>=15时候，广播
	 * @var unknown_type
	 */
	const MIN_CONTINUE_END = 15;
};





/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */