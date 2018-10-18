<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Mission.def.php 205690 2015-10-29 01:47:47Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Mission.def.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-10-29 01:47:47 +0000 (Thu, 29 Oct 2015) $
 * @version $Revision: 205690 $
 * @brief 
 *  
 **/
class MissionDef
{
	const FIELD_CROSS = 'cross';
	const FIELD_INNER = 'inner';

	const OLDER = 'older';
	const NEWER = 'newer';
	
	const MISS_USER_SESSKEY = 'mission.userinfo';
	const MISS_RANK_SESSKEY = 'mission.ranklist';
	const RANK_SESS_SET_TIME = 'setTime';
	const RANK_SESS_LIST = 'list';
	const MISS_ADD_KEY = 'mission.addkey';
	
	const CROSS_DB_PRE = 'pirate_mission_';
	const CROSS_USR_TBL_PRE = 't_mission_cross_user_';
	
	static $missionTimeArr = array(
		SECONDS_OF_DAY,3600,60,1
	);
	
	const MISS_STT = 'missStt';
	const MISS_EDT = 'missEdt';
	const MISS_NTT = 'missNtt';
	
	const MISS_TD = 'missTeamId';
	const MISS_TDST= 'missSetTime';
	
	const MISSIONTIME_SESSION_KEY = 'mission.time';
	const MISSIONTEAMID_SESSION_KEY = 'mission.teamid';
}
class MissionBts
{
	const MISSION_DETAIL = 'MISSION_DETAIL';
	const MISSION_REWARD = 'MISSION_REWARD';
	
}

class MissionDBField
{
	const SESS = 'sess';
	const VA_MISSCONFIG = 'va_missconfig';
	const CONF_UPDATE_TIME = 'update_time';
	static $innerConfField = array(
		self::SESS, 
		self::VA_MISSCONFIG,
		self::CONF_UPDATE_TIME,
	);
	

	const UID = 'uid';
	const FAME = 'fame' ;
	const DONATE_ITEM_NUM = 'donate_item_num' ;
	const SPEC_MISS_FAME = 'spec_mission_fame' ;
	const UPDATE_TIME = 'update_time' ;
	const RANK_REWARD_TIME = 'rankreward_time' ;
	const DAY_REWATD_TIME = 'dayreward_time' ;
	const VA_MISSION_USER = 'va_mission' ;
	
	
	static $innerUserField = array(
			self::UID,
			self::FAME,
			self::DONATE_ITEM_NUM,
			self::SPEC_MISS_FAME,
			self::UPDATE_TIME,
			self::RANK_REWARD_TIME,
			self::DAY_REWATD_TIME,
			self::VA_MISSION_USER,
	);
	
	const CROSS_PID= 'pid';
	const CROSS_SERVERID= 'server_id';
	const CROSS_UNAME= 'uname';
	const CROSS_FAME= 'fame';
	const CROSS_UPDATE_TIME= 'update_time';
	const CROSS_SERVER_NAME = 'server_name';
	const CROSS_HTID = 'htid';
	const CROSS_LEVEL = 'level';
	const CROSS_VIP = 'vip';
	const CROSS_VA_USER = 'va_cross_user';
	
	static $crossUserField = array(
			self::CROSS_PID,
			self::CROSS_SERVERID,
			self::CROSS_UNAME,
			self::CROSS_FAME,
			self::CROSS_UPDATE_TIME,
			self::CROSS_HTID,
			self::CROSS_LEVEL,
			self::CROSS_VIP,
			self::CROSS_VA_USER,
	);
	
	const SERVER_ID = 'server_id';
	const LAST_TEAMID = 'last_team_id';
	const TEAMID = 'team_id';
	const TEAM_UPDATE_TIME = 'update_time';
	
	static $teamInfoField = array(
			self::SERVER_ID,
			//self::LAST_TEAMID,
			self::TEAMID,
			self::UPDATE_TIME,
	);
	
}

class MissionFrontField
{
	const FAME = 'fame' ;
	const DONATE_ITEM_NUM = 'donate_item_num' ;
	const SPEC_MISS_FAME = 'spec_mission_fame' ;
	const MISSION_INFO = 'missionInfo';
	const DAY_REWATD_TIME = 'dayreward_time';
	const TEAMID = 'teamId';
	
	const RANK_REWARDARR = 'rankRewardArr';
	const DAY_REWARDARR = 'dayRewardArr';
	const MISSION_BACKGROUNDARR = 'missionBackground';
	
	const RANK_LIST = 'list';
	const MYRANK = 'mine';
	const RANK_FAME = 'fame';
	const RANK = 'rank';
	
	const CONFIG_INFO = 'configInfo';
}

class MissionCsvField
{
	const ID = 'id';
	const SESS = 'sess';
	const NEEDLV = 'needLevel';
	const RANK_REWARDARR = 'rankRewardArr';
	const DAY_REWARDARR = 'dayRewardArr';
	const DONATE_ITEM_LIMIT = 'donateItemLimit';
	const FAME_PERGOLD = 'famePerGold';
	const DONATE_GOLD_RANGEARR = 'donateGoldRangeArr';
	const MISSION_IDARR = 'missionIdArr';
	const MISSION_LASTTIME = 'missionLastTime';
	const MISSION_BACKGROUNDARR = 'missionBackground';
	const MISSION_SHOWRANKARR = 'showRankArr';
	
	const MISID = 'missId';
	const MAX_NUM = 'maxNum';
	const FAME_RECEIVE = 'fame';
	
	const GODID = 'goodId';
	const ITEMARR = 'itemArr';
	const PRICE = 'price';
	const GODTYPE = 'goodType';
	const MAX_BUY_NUM = 'maxBuyNum';
	
	
	const REWARDID = 'rewardId';
	const REWARDARR = 'rewardArr';
}

class MissionVAField
{
	//const CONF_DETAIL = 'confDetail';
	const MISSION_INFO = 'missionInfo';
	const MISSION_DRESS = 'dress';
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */