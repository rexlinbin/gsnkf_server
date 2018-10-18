<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWar.def.php 241170 2016-05-05 13:15:35Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/CountryWar.def.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-05 13:15:35 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241170 $
 * @brief 
 *  
 **/

class CountryWarDef
{
	static $RETARR = array(
			 CountryWarFrontField::RETCODE => 'fail',
	);
	
	const CROSS_DB_PRE 					= 'pirate_countrywar_';		//跨服db前綴
	const RESOURCE_PRE 					= 'resource_';				//資源方前綴
	const RANK_MEM_SET_TIME 			= 'setTime';				//排名的mem的設置時間
	const RANK_MEM_LIST 				= 'list';					//排名列表的key
}

class CountryWarTeamField
{
	const SERVER_ID 					= 'server_id';
	const TEAM_ID 						= 'team_id';
	const UPDATE_TIME 					= 'update_time';
	
	static $ALL_FIELDS = array(
			self::SERVER_ID,
			self::TEAM_ID,
			self::UPDATE_TIME,
	);
}

class CountryWarInnerUserField
{
	const PID 							= 'pid';
	const SERVER_ID 					= 'server_id';
	const SUPPORT_PID 					= 'support_pid';
	const SUPPORT_SERVER_ID 			= 'support_server_id';
	const SUPPORT_SIDE 					= 'support_side';
	const WORSHIP_TIME 					= 'worship_time';
	const AUDITION_REWARD_TIME 			= 'audition_reward_time';
	const SUPPORT_REWARD_TIME 			= 'support_reward_time';
	const FINAL_REWARD_TIME 			= 'final_reward_time';
	const UPDATE_TIME 					= 'update_time';
	
	static $ALL_FIELDS = array(
			self::PID,
			self::SERVER_ID,
			self::SUPPORT_PID,
			self::SUPPORT_SERVER_ID,
			self::SUPPORT_SIDE,
			self::WORSHIP_TIME,
			self::AUDITION_REWARD_TIME,
			self::SUPPORT_REWARD_TIME,
			self::FINAL_REWARD_TIME,
			self::UPDATE_TIME,
	);
}

class CountryWarCrossUserField
{
	const PID 							= 'pid';
	const SERVER_ID 					= 'server_id';
	const UUID 							= 'uuid';
	const SIGN_TIME 					= 'sign_time';
	const TEAM_ROOM_ID 					= 'team_room_id';
	const COUNTRY_ID 					= 'country_id';
	const SIDE 							= 'side';
	const FINAL_QUALIFY 				= 'final_qualify';
	const UNAME 						= 'uname';
	const HTID 							= 'htid';
	const FIGNT_FORCE 					= 'fight_force';
	const VIP 							= 'vip';
	const LEVEL 						= 'level';
	const FANS_NUM 						= 'fans_num';
	const COCOIN_NUM 					= 'cocoin_num';
	const COPOINT_NUM 					= 'copoint_num';
	const RECOVER_PERCENT 				= 'recover_percent';
	const AUDITION_POINT 				= 'audition_point';
	const AUDITION_POINT_TIME 			= 'audition_point_time';
	const FINAL_POINT 					= 'final_point';
	const FINAL_POINT_TIME 				= 'final_point_time';
	const AUDITION_INSPIRE_NUM 		= 'audition_inspire_num';
	const FINALTION_INSPIRE_NUM 	= 'finaltion_inspire_num';
	const UPDATE_TIME 					= 'update_time';
	const VA_EXTRA 						= 'va_extra';
	//上面像不像兩個半扇飛機
	static $ALL_FIELDS = array(
			self::PID,
			self::SERVER_ID,
			self::UUID,
			self::SIGN_TIME,
			self::TEAM_ROOM_ID,
			self::COUNTRY_ID,
			self::SIDE,
			self::FINAL_QUALIFY,
			self::UNAME,
			self::HTID,
			self::FIGNT_FORCE,
			self::VIP,
			self::LEVEL,
			self::FANS_NUM,
			self::COCOIN_NUM,
			self::COPOINT_NUM,
			self::RECOVER_PERCENT,
			self::AUDITION_POINT,
			self::AUDITION_POINT_TIME,
			self::FINAL_POINT,
			self::FINAL_POINT_TIME,
			self::AUDITION_INSPIRE_NUM,
			self::FINALTION_INSPIRE_NUM,
			self::UPDATE_TIME,
			self::VA_EXTRA,		
	);
	
	static $POINT_LIST_FIELDS = array(
			self::PID,
			self::SERVER_ID,
			self::UUID,
			self::TEAM_ROOM_ID,
			self::COUNTRY_ID,
			self::UNAME,
			self::HTID,
			self::FIGNT_FORCE,
			self::VIP,
			self::LEVEL,
			self::AUDITION_POINT,
			self::FINAL_POINT,
			self::FANS_NUM,
			self::VA_EXTRA,		
	);
	
/* 	static $SUPPORT_LIST_FIELD = array(
			self::PID,
			self::SERVER_ID,
			self::UUID,
			self::COUNTRY_ID,
			self::UNAME,
			self::HTID,
			self::FIGNT_FORCE,
			self::VIP,
			self::LEVEL,
			self::AUDITION_POINT,
			self::FINAL_POINT,
			self::FANS_NUM,
			self::VA_EXTRA,		
	); */
	
	static $BASEINFO_LIST = array(
		
			self::COUNTRY_ID,
			self::UNAME,
			self::HTID,
			self::FIGNT_FORCE,
			self::VIP,
			self::LEVEL,
	);
}

/* class CountryWarCrossRoomField
{
	const WAR_ID = 'war_id';
	const TEAM_ROOM_ID = 'team_room_id';
	const RESOURCE_A = 'resource_a';
	const RESOURCE_B = 'resource_b';
	const VA_EXTRA = 'va_extra';

	static $ALL_FIELDS = array(
			self::WAR_ID,
			self::TEAM_ROOM_ID,
			self::RESOURCE_A,
			self::RESOURCE_B,
			self::VA_EXTRA,
	);
	
} */

class CountryWarCrossTeamContentField
{
	const WAR_ID 						= 'war_id';
	const TEAM_ID 						= 'team_id';
	const NUM_COUNTRY_1 				= 'num_country_1';
	const NUM_COUNTRY_2 				= 'num_country_2';
	const NUM_COUNTRY_3 				= 'num_country_3';
	const NUM_COUNTRY_4 				= 'num_country_4';
	const RESOURCE_A 					= 'resource_a';
	const RESOURCE_B 					= 'resource_b';
	const ROOM_NUM 						= 'room_num';
	const VA_EXTRA 						= 'va_extra';
	
	static $ALL_FIELDS = array(
			self::WAR_ID,
			self::TEAM_ID,
			self::RESOURCE_A,
			self::RESOURCE_B,
			self::NUM_COUNTRY_1,
			self::NUM_COUNTRY_2,
			self::NUM_COUNTRY_3,
			self::NUM_COUNTRY_4,
			self::ROOM_NUM,
			self::VA_EXTRA,
	);

}

class CountryWarInnerWorshipField
{
	const WAR_ID 						= 'war_id';
	const PID 							= 'pid';
	const SERVER_ID 					= 'server_id';
	const UNAME 						= 'uname';
	const HTID 							= 'htid';
	const FIGHT_FORCE 					= 'fight_force';
	const VIP 							= 'vip';
	const TITLE							= 'title';
	const LEVEL 						= 'level';
	const VA_EXTRA 						= 'va_extra';

	static $ALL_FIELDS = array(
			self::WAR_ID,
			self::PID,
			self::SERVER_ID,
			self::UNAME,
			self::HTID,
			self::FIGHT_FORCE,
			self::VIP,
			self::TITLE,
			self::LEVEL,
			self::VA_EXTRA,
	);

}

class CountryWarFrontField
{
	const RETCODE 						= 'ret';
	const SERVER_IP 					= 'serverIp';
	const PORT 							= 'port';
	const TOKEN 						= 'token';
	const UUID 							= 'uuid';
	const TEAM_BEGIN 					= 'teamBegin';
	const SIGN_BEGIN 					= 'signupBegin';
	const RANGE_BEGIN 					= 'rangeRoomBegin';
	const AUDITION_BEGIN 				= 'auditonBegin';
	const SUPPORt_BEGIN 				= 'supportBegin';
	const FINALTION_BEGIN 				= 'finaltionBegin';
	const WORSHIP_BEGIN 				= 'worshipBegin';
	const COUNTRY_ID 					= 'countryId';
	const SIGN_TIME 					= 'signup_time';
	const COUNTRY_SIGN_NUM 				= 'country_sign_num';
	const FORCE_INFO 					= 'forceInfo';
	const MEMBER_INFO 					= 'memberInfo';
	const MY_SUPPORT			 		= 'mySupport';
	const TEAM_ID 						= 'teamId';
	const STAGE 						= 'stage';
	const TIME_CONFIG 					= 'timeConfig';
	const DETAIL 						= 'detail';
	const SIDE 							= 'side';
	const USER 							= 'user';
	const SERVER_ID 					= 'server_id';
	const PID 							= 'pid';
	const NAME 							= 'name';
	const HTID 							= 'htid';
	const DRESS 						= 'dress';
	const WORSHIP_TIME 					= 'worship_time';
	const COCOIN 						= 'cocoin';
	const COPOINT 						= 'copoint';
	const QUALIFY 						= 'qualify';
	const SERVER_NAME 					= 'server_name';
}


class CountryWarSessionKey
{
	const MY_INNER_DB 					= 'global.inner_db';			//跨服php放到session中的原服的db
	const MY_INNER_SERVERID 			= 'global.inner_server_id';		//跨服php放到session中的原服的serverId
	const MY_INNER_PID 					= 'global.inner_pid';			//跨服php放到session中的原服的pid
	const UUID 							= 'global.uid';					//跨服php放到session中的id，充當uid
	const HP_RAGE_INFO 					= 'country.hpRageInfo';			//跨服php放到session中的血量信息
	const AUTO_RECOVER 					= 'country.autoRecover';		//跨服php放到session中的是否自動回血的開關
	const BATTLEID 						= 'global.countryBattleId';		//跨服lc放到session中的battleid
	const QUIT_BATTLE_TIME 				= 'countryBattle.quitBattleTime';		//跨服lc放到session中的離開時間
	const LEAVE_BATTLE_TIME 			= 'countryBattle.leaveBattleTime';	//跨服lc放到session中的上次戰鬥失敗時間
		
}

class CountryWarLockKey
{
	const ENTER 						= 'cw_enter_';					//沒報名的人enter的時候產生創建房間的請求，鎖一把
}

class CountryWarMemKey
{
	const SERIAL 						= 'cw_serial_';					//用來串化登錄的memkey的前綴
	const TOKEN 						= 'cw_token_';					//用來校驗登錄的memkey的前綴
	const RANK_ADD 						= 'cw_rank_add_';				//mcclient add的key的前綴
	const RANK_SET 						= 'cw_rank_set_';				//mcclient set的key的前綴
	const MARK_USER_ADD					= 'cw_mark_user_';				//mark的补救的add的key的前缀
}

class CountryWarCsvField
{
	/*
	ID
	赛前准备
	参与等级
	开服天数
	战场人数最大值
	国家属性加成
	报名奖励
	鼓舞国战币
	鼓舞提升百分比
	鼓舞上限等级
	势力助威奖
	个人助威奖
	随机分势力概率
	按国家分概率
	国家进决赛名额
	决赛势力初始资源
	达阵掠夺资源
	出阵国战积分
	击杀国战积分
	达阵国战积分
	终结对手获得积分
	连杀名字品质
	回满血怒国战币
	开放四条赛道需要人数
	参战冷却
	清除冷却国战币
	设置自动回血范围
	膜拜奖励
	1金币兑国战币
	*/
	const ID 							= 'id';
	const BATTLE_PREPARE_SECONDS 		= 'battlePrepareSeconds';
	const REQ_LEVEL 					= 'reqLevel';
	const REQ_OPEN_DAYS 				= 'openDays';
	const BATTLE_MAX_NUM 				= 'battleMaxNum';
	const COUNTRY_ADDITION_ARR 			= 'countryAdditionArr';
	const SIGN_REWARD_ARR 				= 'signRewardArr';
	const INSPIRE_REQ_COCOIN 			= 'inspireReqCocoin';
	const INSPIRE_ADDITION_ARR 			= 'inspireAdditionArr';
	const INSPIRE_LIMIT 				= 'inspireLimit';
	const COUNTRY_SUPPORT_REWARD 		= 'countrySupportReward';
	const MEMBER_SUPPORT_REWARD 		= 'memberSupportReward';
	const RANDOM_COUNTRY_RATIO 			= 'randomCountryRatio';
	const MANUAL_COUNTRY_RATIO_ARR 		= 'manualCountryRatioArr';
	const COUNTRY_FINAL_MEMBERNUM 		= 'countryFinalMemberNum';
	const FINAL_INIT_RESOURCE 			= 'finalInitResouece';
	const TOUCHDOWN_ROB_RESOURCE 		= 'touchDownRobResource';
	const JOIN_POINT 					= 'joinCocoin';
	const KILL_POINT_ARR 				= 'killCocionArr';
	const TOUCH_DOWN_POINT 				= 'touchDownPoint';
	const TERMINAL_KILL_POINT_ARR 		= 'terminalKillCocoinArr';
	const RECOVER_REQ_COCOIN 			= 'recoverReqPoint';
	const OPEN_TRANSFER_REQ_NUM 		= 'openTransferReqNum';
	const JOIN_CD 						= 'joinCd';
	const CLEAR_JOIN_CD_REQ_COCOIN 		= 'clearJoinCdReqCocoin';
	const RECOVER_RANGE_ARR 			= 'recoverRangeArr';
	const WORSHIP_REWARD_ARR 			= 'worshipRewardArr';
	const EXCHANGE_RATIO 				= 'exchangeRatio';
	const ROAD_ARR 						= 'roadArr';
	const COCOIN_MAX 					= 'cocoinMax';
	const WINSIDE_REWARD_ARR 			= 'winSideRewardArr';
	
	const REWARD_ID 					= 'id';
	const RANK_MIN 						= 'rankMin';
	const RANK_MAX 						= 'rankMax';
	const REWARD_ARR 					= 'rewardArr';
	const STAGE 						= 'stage';
	
	const GODID 						= 'id';
	const ITEMARR 						= 'item';
	const PRICE 						= 'extra';
	const GODTYPE 						= 'type';
	const MAX_BUY_NUM 					= 'num';
	const NEED_LEVEL 					= 'level';
}
class CW_BTSTORE_NAME
{
	const COUNTRY_WAR 					= 'COUNTRY_WAR';
	const COUNTRY_WAR_REWARD		 	= 'COUNTRY_WAR_REWARD';
}
class CW_ROOM_VA_KEY
{
	const FINAL_MEMBER 					= 'finalMember';
}

class CountryWarShopDef
{
	const COPOINT 						= 'copoint';
	const GODLIST 						= 'good_list';
	const REQ 							= 'req';
	const ACQ 							= 'acq';
	const MAX_BUY_NUM 					= 'num';
}


/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */