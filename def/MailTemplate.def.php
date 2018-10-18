<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: MailTemplate.def.php 239466 2016-04-21 06:31:35Z ShuoLiu $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/MailTemplate.def.php $
 * @author $Author: ShuoLiu $(jhd@babeltime.com)
 * @date $Date: 2016-04-21 06:31:35 +0000 (Thu, 21 Apr 2016) $
 * @version $Revision: 239466 $
 * @brief
 *
 **/

class MailTemplateID
{
	//矿
	const MINERAL_DUE								=1;
	const MINERAL_ATK_SUCCESS						=4;
	const MINERAL_ATK_FAIL							=5;
	const MINERAL_DFD_SUCCESS						=2;
	const MINERAL_DFD_FAIL							=3;
	const MINERAL_FORCE_DFD_SUCCESS					=7;
	const MINERAL_FORCE_DFD_FAIL					=6;
	const MINERAL_FORCE_ATK_SUCCESS					=8;
	const MINERAL_FORCE_ATK_FAIL					=9;
	//竞技场
	const ARENA_LUCKY_AWARD							=17;
	const ARENA_AWARD								=18;
	const ARENA_DFD_SUCCESS							=16;
	const ARENA_DFD_FAIL							=15;
	//好友
	const FRIEND_APPLY								=10;
	const FRIEND_REJECT								=12;
	const FRIEND_ADD								=11;
	const FRIEND_DEL								=13;
	const FRIEND_MSG								=14;
	//充值
	const CHARGE									=22;
	//夺宝
	const FRAG_SEIZE								=23;
	
	//掠夺
	const ROB_ARENA									=24;
	const ROB_FRAGSEIZE								=25;
	const ROB_COMPETE_SILVER						=26;
	const ROB_COMPETE_INTEGREL						=27;
	
	const COMPETE_RANK								=28;
	const ROB_FRAGSEIZE_SILVER						=29;
	
	const VIP_UP									=30;
	
	const ARENA_RANK_NOTCHANGE						=31;
	const ARENA_RANK_NOTCHANGEBUTROB				=32;

	//工会
	const GUILD_REJECT								=20;
	const GUILD_ACCEPT								=19;
	const GUILD_KICK								=21;
	
	//军团切磋
	const GUILD_COMPETE_FAIL						=33;
	const GUILD_COMPETE_SUCCESS						=34;
	
	//城池战
	const CITY_WAR_REWARD						=35;
	
	//资源矿协助邮件
	//占领者占领时间到了
	const MINERAL_HELPER_OCCUPY_TIMEUP				=36;
	//放弃协助
	const MINERAL_HELPER_GIVEUP						=37;
	//资源矿被别的玩家占领 需要别的玩家的uid
	const MINERAL_HELPER_BEOCCUPIED					=38;
	//资源况的占领者主动放弃 需要uid
	const MINERAL_GIVEUP_BYOWNER					=39;
	//协助者被抓走了 需要抓走协助者的玩家的uid
	const MINERAL_HELPER_BESEIZED					=40;
	//协助时间到期
	const MINERAL_HELPER_TIMEUP						=41;
	//协助军被抢了
	const MINERAL_HELPER_ANNOUNCE_OWNER				=42;
	
	//擂台赛除冠亚军外
	const OLYMP_NORMAL_RANK							=43;
	//擂台赛亚军
	const OLYMP_SECOND								=44;
	//擂台赛冠军
	const OLYMP_FIRST								=45;
	//助威奖
	const OLYMP_CHEER								=46;
	//幸运奖
	const OLYMP_LUCKY								=47;
	//超级幸运奖
	const OLYMP_SUPER_LUCKY							=48;
	//奖池， 连战被终结
	const OLYMP_POOL_BECUT							=49;
	//奖池， 终结别人的连战
	const OLYMP_POOL_CUT							=50;
	//奖池， 有人连战被终结，感谢参与
	const OLYMP_POOL_PATICIPATE						=51;
	
	//跨服战
	const LORDWAR_INNER_WIN_4_32					=52;
	const LORDWAR_INNER_LOSE_4_32					=53;
	const LORDWAR_INNER_WIN_2						=54;
	const LORDWAR_INNER_LOSE_2						=55;
	const LORDWAR_INNER_WIN_1						=56;
	const LORDWAR_INNER_LOSE_1						=57;
	
	const LORDWAR_CROSS_WIN_4_32					=58;
	const LORDWAR_CROSS_LOSE_4_32					=59;
	const LORDWAR_CROSS_WIN_2						=60;
	const LORDWAR_CROSS_LOSE_2						=61;
	const LORDWAR_CROSS_WIN_1						=62;
	const LORDWAR_CROSS_LOSE_1						=63; 
	
	const LORDWAR_SUPPORT_INNER						=64;
	const LORDWAR_SUPPORT_CROSS						=65;
	
	//这里空了一个，想知道为什么？ 可以来问爷
	const GUILD_ROB_NOTICE							=67;
	const GUILD_DISTRIBUTE_GRAIN					=68;
	const GUILD_ROB_GAIN							=69;
	const GUILD_ROB_LOSE							=70;
	
	const MINERAL_ONE_HOUR							=71;
	
	const PASS_RANK_REWARD							=72;
	
	//跨服军团战
	const GUILD_WAR_LOSE_NORMAL						=73;
	const GUILD_WAR_WIN_NORMAL						=74;
	const GUILD_WAR_WIN_FIRST						=75;
	const GUILD_WAR_LOSE_FIRST						=76;
	const GUILD_WAR_REWARD_NORMAL					=77;
	const GUILD_WAR_REWARD_FIRST					=78;
	const GUILD_WAR_REWARD_SECOND					=79;
	const GUILD_WAR_REWARD_SUPPORT					=80;
	
	//木牛流马
	const CHARGE_DART_FINISH_USER                   =81;
	const CHARGE_DART_FINISH_ASSIST                 =82;
	const CHARGE_DART_ROB_ROBBER                    =83;
	const CHARGE_DART_BE_ROBBED                     =84;
}
/* vim: set ts=>4 sw=>4 sts=>4 tw=>100 noet: */