<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CityWar.def.php 138005 2014-10-30 04:15:36Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/CityWar.def.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2014-10-30 04:15:36 +0000 (Thu, 30 Oct 2014) $
 * @version $Revision: 138005 $
 * @brief 
 *  
 **/
class CityWarDef
{
	//session 城池占领列表array('city'=>$id, 'time'=>$time)
	const SESSION_INFO = 'citywar.info';
	
	//城池占领效果
	//军团组队银币奖励
	const COPYTEAM = 1;
	//试练塔银币奖励
	const TOWER = 2;
	//摇钱树银币奖励
	const GOLDTREE = 3;
	//普通副本银币奖励
	const NCOPY = 4;
	//精英副本银币奖励
	const ECOPY = 5;
	//资源矿银币奖励
	const MINERAL = 6;
	
	//clearcd类型
	public static $CLEARCD_VALID_TYPES = array(
			0 => self::MEND_TIME,
			1 => self::RUIN_TIME,		
	);
	
	public static $CITY_VALID_TYPES = array(
			self::COPYTEAM,
			self::TOWER,
			self::GOLDTREE,
			self::NCOPY,
			self::ECOPY,
			self::MINERAL,
	);
	
	//城池配置表
	const CITY_LEVEL = 'city_level';
	const GUILD_LEVEL = 'guild_level';
	const CITY_REWARD = 'city_reward';
	const CITY_EFFECT = 'city_effect';
	const CITY_GUARD = 'city_guard';
	const DEFENCE_DEFAULT = 'defence_default';
	const DEFENCE_DECREASE = 'defence_decrease';
	const RUIN_GUARD = 'ruin_guard';
	const MEND_GUARD = 'mend_guard';
	
	//城池战配置表
	const SIGNUP_LIMIT = 'signup_limit';
	const ATTACK_LIMIT = 'attack_limit';
	const DEFENCE_PARAM = 'defence_param';
	const JOIN_LIMIT = 'join_limit';
	const INSPIRE_ATTACK = 'inspire_attack';
	const INSPIRE_DEFEND = 'inspire_defend';
	const INSPIRE_LIMIT = 'inspire_limit';
	const INSPIRE_BASERATE = 'inspire_baserate';
	const INSPIRE_SUCPARAM = 'inspire_sucparam';
	const INSPIRE_SILVER = 'inspire_silver';
	const INSPIRE_GOLD = 'inspire_gold';
	const INSPIRE_CD = 'inspire_cd';
	const WIN_DEFAULT = 'win_default';
	const WIN_GOLD = 'win_gold';
	const REWARD_PARAM = 'reward_param';
	const DEFENCE_MIN = 'defence_min';
	const DEFENCE_MAX = 'defence_max';
	const CONTRI_WIN = 'contri_win';
	const CONTRI_FAIL = 'contri_fail';
	const CONTRI_ADD = 'contri_add';
	const USER_LEVEL = 'user_level';
	const CD_CLEAR = 'cd_clear';
	const CD_TIME = 'cd_time';
	
	//sql
	const TABLE_CITY_WAR = 't_city_war';
	
	const CITY_ID = 'city_id';
	const CITY_DEFENCE = 'city_defence';
	const DEFENCE_TIME = 'defence_time';
	const LAST_GID = 'last_gid';
	const CURR_GID = 'curr_gid';
	const OCCUPY_TIME = 'occupy_time';
	const SIGNUP_END_TIMER = 'signup_end_timer';
	const BATTLE_END_TIMER = 'battle_end_timer';
	const VA_CITY_WAR = 'va_city_war';
	const VA_REWARD = 'va_reward';
	
	public static $CITY_WAR_FIELDS = array(
			self::CITY_ID,
			self::CITY_DEFENCE,
			self::DEFENCE_TIME,
			self::LAST_GID,
			self::CURR_GID,
			self::OCCUPY_TIME,
			self::SIGNUP_END_TIMER,
			self::BATTLE_END_TIMER,
			self::VA_CITY_WAR,
			self::VA_REWARD,
	);
	
	const TABLE_CITY_WAR_ATTACK = 't_city_war_attack';
	
	const SIGNUP_ID = 'signup_id';
	const SIGNUP_TIME = 'signup_time';
	const ATTACK_GID = 'attack_gid';
	const DEFEND_GID = 'defend_gid';
	const ATTACK_TIMER = 'attack_timer';
	const ATTACK_REPLAY = 'attack_replay';
	const ATTACK_RESULT = 'attack_result';
	const ATTACK_CONTRI = 'attack_contri';
	
	public static $CITY_WAR_ATTACK_FIELDS = array(
			self::SIGNUP_ID,
			self::SIGNUP_TIME,
			self::CITY_ID,
			self::ATTACK_GID,
			self::DEFEND_GID,
			self::ATTACK_TIMER,
			self::ATTACK_REPLAY,
			self::ATTACK_RESULT,
			self::ATTACK_CONTRI,
	);
	
	const TABLE_CITY_WAR_USER = 't_city_war_user';
	
	const USER_ID = 'uid';
	const CUR_CITY = 'cur_city_id';
	const ENTER_TIME = 'enter_time';
	const REWARD_TIME = 'reward_time';
	const MEND_TIME = 'mend_time';
	const RUIN_TIME = 'ruin_time';
	const VA_CITY_WAR_USER = 'va_city_war_user';
	
	public static $CITY_WAR_USER_FIELDS = array(
			self::USER_ID,
			self::CUR_CITY,
			self::ENTER_TIME,
			self::REWARD_TIME,
			self::MEND_TIME,
			self::RUIN_TIME,
			self::VA_CITY_WAR_USER,
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */