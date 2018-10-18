<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Guild.cfg.php 230581 2016-03-02 10:11:43Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Guild.cfg.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-03-02 10:11:43 +0000 (Wed, 02 Mar 2016) $
 * @version $Revision: 230581 $
 * @brief 
 *  
 **/
class GuildConf
{
	//默认等级
	const GUILD_DEFAULT_LEVEL = 1;
	//默认经验值
	const GUILD_DEFAULT_EXP = 0;
	
	//默认解散军团的等级上限
	const GUILD_DISMISS_LEVEL = 5;
	
	//名称的最大字符数
	const MAX_NAME_LENGTH = 6;
	//宣言的最大字符数
	const MAX_SLOGAN_LENGTH = 20;
	//公告的最大字符数
	const MAX_POST_LENGTH = 40;
	//留言的最大字符数
	const MAX_MSG_LENGTH = 80;
	
	//最大创建次数
	const MAX_CREATE_NUM = 10;
	//最大申请数量
	const MAX_APPLY_NUM = 3;
	//最大记录数
	const MAX_RECORD_NUM = 5;
	//最大动态数
	const MAX_DYNAMIC_NUM = 50;	
	//最大留言次数
	const MAX_MSG_NUM = 3;
	//粮田最大分粮数
	const MAX_SHARE_NUM = 1500000;
	
	//军团排行数
	const GUILD_RANK_LIST = 50;
	
	//弹劾的时间底线
	const MAX_IMPEACHMENT_TIME = 259200;
	//留言的时间底线
	const MAX_KEEPMSG_TIME = 604800;
	
	//珍品类商品的重置偏移时间
	const SPECIAL_GOODS_OFFSET = 43200;
	//珍品类商品的刷新时间点
	const SPECIAL_REFRESH_TIME = "12:00:00";
	
	//默认宣言
	const DEFAULT_SLOGAN = I18nDef::GUILD_DEFAULT_SLOGAN;//"欢迎大家来到放开那三国！";
	//默认公告
	const DEFAULT_POST = I18nDef::GUILD_DEFAULT_POST;//"大家快来一起建设军团吧！";
	//默认密码
	const DEFAULT_PASSWD = '123456';
	
	//1忠义堂2关公殿3商城4副本5任务6粮仓对应默认等级
	static $GUILD_BUILD_DEFAULT = array(
			GuildDef::GUILD => array(GuildDef::LEVEL => self::GUILD_DEFAULT_LEVEL, GuildDef::ALLEXP => 0),
			GuildDef::TEMPLE => array(GuildDef::LEVEL => 0, GuildDef::ALLEXP => 0),
			GuildDef::STORE => array(GuildDef::LEVEL => 0, GuildDef::ALLEXP => 0),
			GuildDef::COPY => array(GuildDef::LEVEL => 0, GuildDef::ALLEXP => 0),
			GuildDef::TASK => array(GuildDef::LEVEL => 0, GuildDef::ALLEXP => 0),
			GuildDef::BARN => array(GuildDef::LEVEL => 0, GuildDef::ALLEXP => 0),
			GuildDef::TECH => array(GuildDef::LEVEL => 0, GuildDef::ALLEXP => 0),//不可升级
	);
	
	//用户粮田对应默认信息(采集次数和刷新时间)
	static $MEMBER_FIELD_DEFAULT = array(
			1 => array(0,0),
			2 => array(0,0),
			3 => array(0,0),
			4 => array(0,0),
			5 => array(0,0),
	);
	
	//军团粮田对应默认信息(等级和经验)
	static $GUILD_FIELD_DEFAULT = array(
			1 => array(0,0),
			2 => array(0,0),
			3 => array(0,0),
			4 => array(0,0),
			5 => array(0,0),
	);
	
	/**
	 * 用户权限表
	 * @var array
	 */
	static $ARR_PRIV = array (
			GuildPrivType::MEMBER_MANAGE => array(
					GuildMemberType::PRESIDENT,
					GuildMemberType::VICE_PRESIDENT 
			),
			GuildPrivType::SLOGAN_MODIFY => array(
					GuildMemberType::PRESIDENT,
					GuildMemberType::VICE_PRESIDENT
			),
			GuildPrivType::POST_MODIFY => array(
					GuildMemberType::PRESIDENT,
					GuildMemberType::VICE_PRESIDENT
			),
			GuildPrivType::PASSWD_MODIFY => array(
					GuildMemberType::PRESIDENT
			),
			GuildPrivType::LEVEL_UP => array(
					GuildMemberType::PRESIDENT,
					GuildMemberType::VICE_PRESIDENT
			),
			GuildPrivType::SET_VP => array(
					GuildMemberType::PRESIDENT
			),
			GuildPrivType::ROLE_TRANS => array(
					GuildMemberType::PRESIDENT
			),
			GuildPrivType::DISMISS => array(
					GuildMemberType::PRESIDENT
			),
			GuildPrivType::IMPEACH => array(
					GuildMemberType::VICE_PRESIDENT
			),
	        GuildPrivType::SHARE => array(
	                GuildMemberType::PRESIDENT,
	                GuildMemberType::VICE_PRESIDENT
	        ),
	        GuildPrivType::REFRESH_BYEXP => array(
	                GuildMemberType::PRESIDENT,
	                GuildMemberType::VICE_PRESIDENT    
	        ),
	        GuildPrivType::BUY_FIGHTBOOK => array(
	                GuildMemberType::PRESIDENT,
	                GuildMemberType::VICE_PRESIDENT
	        ),
	        GuildPrivType::ICON_MODIFY => array(
	        		GuildMemberType::PRESIDENT,
	        		GuildMemberType::VICE_PRESIDENT
	        ),
	        GuildPrivType::PROMOTE_SKILL => array(
	        		GuildMemberType::PRESIDENT,
	        		GuildMemberType::VICE_PRESIDENT
	        ),
	        GuildPrivType::NAME_MODIFY => array(
	        		GuildMemberType::PRESIDENT,	
	        ),
	);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */