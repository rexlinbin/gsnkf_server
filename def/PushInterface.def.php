<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: PushInterface.def.php 259097 2016-08-29 08:40:55Z YangJin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/PushInterface.def.php $
 * @author $Author: YangJin $(wuqilin@babeltime.com)
 * @date $Date: 2016-08-29 08:40:55 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259097 $
 * @brief 
 *  
 **/



class PushInterfaceDef
{
	
	const USER_UPDATE_USER_INFO    =    'push.user.updateUser';
	
	const CHARGE_GOLD_UPDATE_USER = 'push.user.chargegold';//VIP 充值金额  当前的金币数目  返还的金币（平台返还金币+配置返还金币）
	
	const HERO_ADD_NEW_HERO    =    'push.hero.addhero';

	const NCOPY_ADD_NEW_COPY    =    'push.copy.newcopy';
	
	const SWITCH_ADD_NEW_SWITCH    =    'push.switch.newSwitch';
	
	const ARENA_USER_DATA_REFRESH = 're.arena.dataRefresh';
	
	const STAR_ADD_NEW_NOTICE =	're.star.addNewNotice';
	
	const MESSAGE_CALLBACK 			= 're.chat.getMsg';
	
	const MAIL_CALLBACK				= 're.mail.newMail';
	
	const FRIEND_NEW				= 're.friend.Newfriend';
	
	const REWARD_NEW				= 're.reward.newReward';
	
	const MINERAL_PIT_UPDATE        =    'push.mineral.updatepit';
	
	const GM_ANNOUNCE				= 're.chat.getAnnounce';
	
	const GM_RESPONSE				= 're.gm.newMsg';
	
	const FRIEND_LOGIN 				= 'push.friend.login';
	
	const FRIEND_LOGOFF 			= 'push.friend.logoff';
	
	const COMPETE_REFRESH			= 'push.compete.refresh';
	
	const COMPETE_REWARD			= 'push.compete.reward';
	
	const FRAGSEIZE_SEIZE			= 'push.fragseize.seize';
	
	const FRIEND_MUCHLOVE			= 'push.friend.newLove';
	
	const FRIEND_DEL				= 'push.friend.del';
	
	const HERO_SHOP_RFR_RANK        = 'push.heroshop.rfrrank';
	
	const REFRESH_MEMBER			= 'push.guild.refreshMember';
	
	const HEROSHOP_ACT_END          = 'push.heroshop.endact';
	
	const BOSS_UPDATE				= 'push.boss.update';
	
	const BOSS_KILL					= 'push.boss.kill';
	
	const REFRESH_GUILD				= 'push.refreshGuild';
	
	const REFRESH_GOODS				= 'push.refreshGoods';
	
	const COPY_TEAM_ATK_RESULT           = 'push.copyteam.battleResult';
	
	const COPY_TEAM_INVITE_GUILD_MEM  = 'push.copyteam.inviteGuildMem';
	
	const WEAL_KA_POINTS			= 'push.weal.kapoint';
	
	const CITYWAR_REFRESH			= 'push.citywar.refresh';
	
	const CITYWAR_USER_LOGIN		= 'push.citywar.userLogin';
	
	const CITYWAR_USER_LOGOFF		= 'push.citywar.userLogoff';
	
	const CITYWAR_ATK_RESULT		= 'push.citywar.battleResult';
	
	const CITYWAR_ATK_END			= 'push.citywar.battleEnd';
	
	const CITYWAR_SIGN_REFRESH		= 'push.citywar.signRefresh';

    const GROUPON_BUY_GOOD          = 'push.groupon.buygood';
    
    const ACHIEVE_NEW_FINISH 		= 'push.achieve.newFinish';
    
    const MONTHLY_CARD_UPDATE       = 'push.monthlycard.update';

    const OLYMPIC_BATTLE_RECORD     = 'push.olympic.battlerecord';
    
    const OLYMPIC_SIGNUP_UPDATE     = 'push.olympic.signup';
    
    const OLYMPIC_STAGE_END       = 'push.olympic.stagechange';

    const OLYMPIC_CHEER           = 'push.olympic.cheer';

    const OLYMPIC_CHALLENGE       = 'push.olympic.challenge';

    const OLYMPIC_SILVER_POOL     = 'push.olympic.silverpool';
    
    const LORDWAR_UPDATE			= 'push.lordwar.update';
    
    const GUILDWAR_UPDATE			= 'push.guildwar.update';
    
    const MINERAL_ROB_BROADCAST     = 'push.mineral.rob';
    
    const GUILD_REFRESH_ALL			= 'push.guild.refreshAll';
    
    const GUILD_BARN_LEVEL			= 'push.guild.barnLevel';
    
    const GUILD_FIELD_HARVEST		= 'push.guild.fieldHarvest';
    
    const GUILD_SHARE_CD			= 'push.guild.shareCd';

    const GODWEAPON_NEW_DICT        = 'push.godweapon.newDict';
    
    // 军团抢粮战前端回调函数
    const GUILD_ROB_REFRESH    		    = 'push.guildrob.refresh';
    const GUILD_ROB_FIGHT_WIN         	= 'push.guildrob.fightWin';
    const GUILD_ROB_FIGHT_LOSE         	= 'push.guildrob.fightLose';
    const GUILD_ROB_TOUCH_DOWN         	= 'push.guildrob.touchDown';
    const GUILD_ROB_FIGHT_RESULT        = 'push.guildrob.fightResult';
    const GUILD_ROB_BATTLE_END         	= 'push.guildrob.battleEnd';
    const GUILD_ROB_TOP_N         		= 'push.guildrob.topN';
    const GUILD_ROB_RECKON         		= 'push.guildrob.reckon';
    const GUILD_ROB_INFO				= 'push.guildrob.info';
    const GUILD_ROB_SPEC				= 'push.guildrob.spec';
    const GUILD_ROB_ENTER_SPEC_RET		= 'push.guildrob.enterSpecRet';
    
    // 军团副本前端回调函数
    const GUILD_COPY_UPDATE_REFRESH_NUM = 'push.guildcopy.update_refresh_num';
    const GUILD_COPY_CURR_COPY_PASS = 'push.guildcopy.curr_copy_pass';
    
    //战斗力更新推送
    const USER_FIGHTFORCE_UPDATE = 'push.user.fightForceUpdate';
    
    // 跨服嘉年华推送
    const WORLD_CARNIVAL_PUSH_STATUS = 'push.worldcarnival.update';
	
    const MISSION_FAME_CHANGE = 'push.mission.fame';
    
    // 国战前端回调函数
    const COUNTRY_WAR_REFRESH    		    = 'push.countrywarcross.refresh';
    const COUNTRY_WAR_FIGHT_WIN         	= 'push.countrywarcross.fightWin';
    const COUNTRY_WAR_FIGHT_LOSE         	= 'push.countrywarcross.fightLose';
    const COUNTRY_WAR_TOUCH_DOWN         	= 'push.countrywarcross.touchDown';
    const COUNTRY_WAR_FIGHT_RESULT       	= 'push.countrywarcross.fightResult';
    const COUNTRY_WAR_BATTLE_END         	= 'push.countrywarcross.battleEnd';
    const COUNTRY_WAR_RECKON         		= 'push.countrywarcross.reckon';
    
    // 红包系统推送
    const ENVELOPE_SEND_WHOLE_GROUP = 'push.envelope.all';
    const ENVELOPE_SEND_GUILD = 'push.envelope.guild';
    
    //木牛流马推送
    const CHARGEDART_SEND_SHIP              = 'push.chargedart.newship';
    const CHARGEDART_SEND_BEROBBED          = 'push.chargedart.berobbed';
    const CHARGEDART_FINISH_BYGOLD          = 'push.chargedart.finishbygold';
    const CHARGEDART_INVITE_FRIENDY         = 'push.chargedart.invitefriendy';
    const CHARGEDART_ACCEPT_INVITE          = 'push.chargedart.acceptinvite';
    
    //新服活动"开服7天乐"推送
    const NEWSERVERACTIVITY_NEW_FINISH		=  'push.newserveractivity.newfinishtask';	// 把完成的任务过滤出来，推送给起前端

    //夏日狂欢任务活动推送
    const FESTIVALACT_NEW_FINISH            =  'push.festivalact.newfinishtask';
    
    const MINERALELVES_UPDATE_ELVES  = 'push.mineralelves.update';  //矿精灵出现或消失
    const MINERALELVES_ROB               =  'push.mineralelves.rob';         //矿精灵被抢
    
    //老玩家回归任务完成推送
    const WELCOMEBACK_TASK_FINISH = 'push.welcomeback.taskFinish';
	/**
	 * [1]背包更新通知: re.bag.bagInfo
	 * <code>
	 * 			gid:
	 * 				{
	 * 					item_id:int						物品ID
	 * 					item_template_id:int			物品模板ID
	 * 					item_num:int					物品数量
	 * 					item_time:int					物品产生时间
	 * 					va_item_text:					物品扩展信息
	 * 					{
	 * 						mixed
	 * 					}
	 * 				}
	 * </code>
	 * 
	 *[2]玩家的信息被其他玩家更改  包括耐力变化、体力变化（耐力、体力更新时间）、金币、银币、经验、将魂、战斗力的变化
	 * USER_UPDATE_USER_INFO
	 * <code>
	 * [
	 *     ban_chat_time:int
	 *     fight_force:int
	 *	   gold_num:int
	 *	   silver_num:int
	 *	   exp_num:int
	 *	   soul_num:int
	 *	   execution:int
	 *	   execution_time:int
	 *	   stamina:int
	 *	   stamina_time:int		
	 *	   vip:int
	 *	   charge_gold:int
	 * ]
	 * </code>
	 *
	 *[4]添加新的武将
	 * HERO_ADD_NEW_HERO
	 * <code>
	 * [
	 *      hid:int,
     *      htid:int,
     *      level:int,
     *      soul:int,
     *      evolve_level:int,
     *      equip: array
     *          {
     *             arming:array
     *             skillbook:array 
     *          },
	 * ]
	 * </code>
	 * 
	 *[5]开启新的副本
	 *NCOPY_ADD_NEW_COPY
	 *<code>
	 *[
	 *    uid:int
     *     copy_id:int
     *     score:int
     *     prized_num:int
     *     va_copy_info:array
     *         {
     *           progress:array
     *               {
     *                   baseid:status
     *               }
     *         }
	 *]
	 *</code>
	 *
	 *[6]开启新的功能节点
	 *SWITCH_ADD_NEW_SWITCH
	 *<code>
	 *[
	 *    switchId:int
	 *]
	 *</code>
	 *
	 *[7]更新竞技场用户数据:
	 *ARENA_USER_DATA_REFRESH
	 * <code>
	 * 		{
	 * 			uid:int						用户id	
	 * 			position:int				排名
	 * 			cur_suc:int					当前连胜场次
	 * 			max_suc:int					历史最大连胜场次
	 * 			opponents:array				对手信息
	 * 				{
	 * 					{
	 * 						'uid':int			用户id
	 *  					'utid':int			用户模板id
	 *  					'uname':string		用户名
	 *  					'level':int 		用户等级 
	 *  	 				'position':int		用户排名
	 *  				    'squad':array		阵容
	 *  				    {
	 *  					   index => htid	阵容的位置对应武将模板id
	 *  				    }
	 *  			    }
	 * 				}
	 * 		}	
	 * </code>		
	 *
	 *[8]加名将通知: 
	 *STAR_ADD_NEW_NOTICE
	 * <code>
	 * 		sid:
	 * 			{
	 * 				star_id:int				名将ID
	 * 				star_tid:int			名将模板ID
	 * 				level:int				好感度等级
	 * 				total_exp:int			好感度总值
	 * 			}
	 * </code>
	 * 
	 * [9]用户得到新的消息
	 * MESSAGE_CALLBACK
	 * <code>
	 * array 
	 * {
	 *		'message_text' => $message,				信息
	 *		'sender_uid' => $sender_uid,			发送者uid 系统为0
	 *		'sender_uname' => $sender_uname,		发送者姓名
	 *		//'sender_utid' => $sender_utid,			发送者模板
	 *		'sender_utype' => $sender_utype,		发送者类型（是否是指导员）
	 *		'sender_level' => $sender_level,		发送者等级
			'sender_fight' => $sender_fight,		发送者战力
	 *		'send_time' => Util::getTime(),			发送时间
	 *		'channel' => $channel,					发送频道 @see ChatChannel
	 *		'showFace' => $isShowFace,				是否使用表情	
	 *		'sender_gender' => $sender_gender,		发送者性别
	 *		'$sender_tmpl' => $sender_tmpl,			发送者模板id
	 *	}
	 * </code>
	 * 
	 * [10]用户的到新的邮件
	 * MAIL_CALLBACK
	 * <code>
	 * {
	 * 		啥也没有 只是通知
	 * }
	 * </code>
	 * 
	 * [11]有新的好友
	 * FRIEND_NEW
	 * <code>
	 * {
	 * 		啥也没有 只是通知
	 * }
	 * </code>
	 * 
	 * [12]奖励中心有新的奖励
	 * REWARD_NEW
	 * <code>
	 * {
	 * 		啥也没有 只是通知
	 * }
	 *  </code>
	 *  
	 * [13]通知
	 * GM_ANNOUNCE
	 * <code>
	 * {
	 * 		啥也没有 只是通知
	 * }
	 * </code>
	 * 
	 * [14]通知用户gm给回复了
	 * GM_RESPONSE
	 * <code>
	 * {
	 * 		啥也没有 只是通知
	 * }
	 * </code>
	 * 
	 * [15]通知用户好友上线
	 * FRIEND_LOGIN
	 * <code>
	 * 		uid	上线的好友uid
	 * </code>
	 * 
	 * [16]通知用户好友下线
	 * FRIEND_LOGOFF
	 * <code>
	 * 		uid	下线好友的uid
	 * </code>
	 * 
	 * [17]通知用户比武信息更新
	 * COMPETE_REFRESH
	 * <code>
	 * 		'point':int 当前积分
	 * 		'rank':int	当前排名
	 * 		'addFoeInfo':array
	 * 		{
	 * 			uid:array					用户id
	 * 			{
	 * 				'uid':int				用户id
	 * 				'utid':int				用户模板id
	 * 				'uname':int        		用户名称
	 *    			'level':int             用户等级
	 *    			'fight_force':int 		用户战斗力
	 *    			'squad':array			阵容，3个
	 *  			{
	 *  				index => htid		阵容的位置对应武将模板id
	 *  			}
	 *    			'point':int				积分
	 *    		}
	 * 		}
	 * 
	 * </code>
	 * [18] 充值推消息
	 * CHARGE_GOLD_UPDATE_USER array 
	 * <code>
	 * [
	 *     gold_num:int    玩家当前的金币数目
	 *     vip:int     玩家当前的VIP等级
	 *     charge_gold_sum:int        玩家当前的充值金额总值
	 *     charge_gold:int    玩家此次充值金额
	 *     pay_back:int        充值返还（平台返还+配置返还）
	 *     first_pay:boolean    是否是首充        
	 *     charge_type:int    充值类型  1是金币充值 2是人民币购买月卡
	 * ]
	 * </code>
	 * 
	 * [19]通知用户有最新的赠送体力（只有当有新的被顶掉的才推）
	 * FRIEND_MUCHLOVE
	 * <code>
	 * 		无
	 * </code>
	 * 
	 * [20]通知用户某好友关系被对方解除
	 * FRIEND_DEL
	 * <code>
	 * 		uid	下线好友的uid
	 * </code>
	 * 
	 * [21]刷新军团用户信息
	 * REFRESH_MEMBER
	 * <code>
	 * {
	 * 		'uid':				用户id
	 * 		'guild_id':			军团id, 0是没有在任何军团里
	 * 		'member_type':		成员类型：0团员，1团长，2副团
	 * 		'contri_point':		贡献值
	 * 		'contri_num':		当天剩余贡献次数
	 * 		'contri_time':		贡献时间
	 * 		'reward_num':		当天剩余领奖次数
	 * 		'reward_time':		领奖时间
	 * 		'lottery_num':		当天摇奖次数
	 * 		'lottery_time':		摇奖时间
	 * 		'rejoin_cd':		冷却时间
	 * }
	 * </code>
	 * 
	 * [22]推送boss信息
	 * BOSS_UPDATE
	 * <code>
	 * {
	 * 		hp： boss当前血量
	 *		uname： 攻击者信息
	 *		bossAtkHp： 攻击者攻击的血量
	 *
	 * }
	 * </code>
	 * 
	 * [23]刷新军团信息
	 * REFRESH_GUILD
	 * <code>
	 * {
	 * 		'guild_id':			军团id
	 * 		'guild_name':		名称
	 * 		'guild_level':		等级
	 * 		'create_uid':		创建者id
	 * 		'create_time':		创建时间
	 * 		'join_num':			当天加入人数
	 * 		'join_time':		上次加入时间
	 * 		'contri_num':		当天贡献次数
	 * 		'contri_time':		上次贡献时间
	 * 		'reward_num':		当天领奖次数
	 * 		'reward_time':		上次领奖时间
	 * 		'curr_exp':			当前贡献值
	 * 		'status':			状态
	 * 		'va_info':
	 * 		{	
	 * 			0 =>
	 * 			{	
	 * 				'slogan':	宣言
	 * 				'post':		公告
	 * 			}
	 * 			1 =>			忠义堂
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 			2 =>			关公殿
	 * 			{
	 * 				'level':	等级
	 * 				'allExp':	贡献总值
	 * 			}
	 * 		}
	 * 		'leader_uid':		团长id
	 * 		'leader_name':		团长名字
	 * 		'member_num':		成员数量
	 * 		'member_limit':		成员上限
	 * 		'vp_num':			副团长数量
	 * }
	 * </code>
	 * 
	 * [24]推送boss被击杀
	 * BOSS_KILL
	 * <code>
	 * {
	 * 		int boss的id
	 *
	 * }
	 * </code>
	 * 
	 * [25]商品的军团购买次数
	 * REFRESH_GOODS
	 * <code>
	 * {
	 * 		$goodsId
	 * 		{
	 * 			'sum':int		军团购买次数
	 * 		}
	 * }
	 * </code>
	 * 
	 * [26]副本组队战斗完成之后推给前端的战斗信息、奖励信息、组队副本信息
	 * COPY_TEAM_ATK_RESULT:array
	 * <code>
	 *     reward:array
	 *     [
	 *         silver:int
	 *         exp:int
	 *         soul:int
	 *         item:array
	 *         [
	 *             item_tempate_id=>item_num
	 *         ]
	 *     ]
	 *     copyTeamInfo:array    组队副本信息      根据不同的组队类型  此数据会不同
	 *     [
	 *         //组队类型是公会组队时
	 *         uid:int
     *         cur_guild_copy:int
     *         guild_atk_num:int
     *         guild_rfr_time:int
	 *     ]
	 *     server:array
	 *     [
	 *         result:bool        组队是否打赢了
	 *         brid:int            整体战报id
	 *         team1:array
	 *         [
	 *             memberCount:int            队伍成员数
	 *             name:string                队伍名字
	 *             level:int                    队伍等级
	 *             memberList:array            队伍成员列表
	 *             [
	 *                 array
	 *                 [
	 *                     uid:int            队伍成员id  如果id<2000(说明此成员是army)  需要从name中取得真实的id
	 *                     name:string        队伍成员名字    如果此成员是玩家  name表示玩家名字   如果此成员是army  name表示真实的armyid
	 *                     htid:int            如果此队伍成员是玩家，htid表示此玩家的形象
	 *                     dress:array
	 *                     maxHp:int            此队伍成员的最大血量
	 *                 ]
	 *             ]
	 *         ]
	 *         team2:array            同team1
	 *         [
	 *         ]
	 *         arrProcess:array
	 *         [
	 *             array:每一轮的战斗数据
	 *             [
	 *                 array:一轮中每个战斗的信息
	 *                 [
	 *                     brid:int
	 *                     attacker:int
	 *                     defender:int
	 *                     appraise:string
	 *                     simpleRecord:array
	 *                     [
	 *                         array
	 *                         [
	 *                             int:本回合攻击者attacker损血
	 *                             int:本回合防守者defender损血
	 *                         ]
	 *                     ]
	 *                 ]
	 *             ]
	 *         ]
	 *     ]
	 *     
	 * </code>
	 * [27]要求其他人参加工会组队战
	 * COPY_TEAM_INVITE_GUILD_MEM:array
	 * <code>
	 *     inviteUid:int        邀请的玩家id
	 *     teamCopyId:int        副本id
	 *     teamId:int            队伍id
	 * </code>
	 * 
	 * [28]推给前段福利活动的积分
	 * WEAL_KA_POINTS
	 * 
	 * <code>
	 * 		point_today => int   当前的积分
	 * </code>
	 * 
	 * [29]推送前端当前参战人员信息
	 * CITYWAR_REFRESH
	 * <code>
	 * {
	 * 		'attacker':array
	 * 		{
	 * 			'guild_id':int
	 * 			'guild_name':string
	 * 			'enter':array
	 * 			{
	 * 				{
	 * 					'uid':int
	 * 					'utid':int
	 * 					'uname':string
	 * 					'htid':int
	 *					'dress':array
	 *				}
	 * 			}
	 * 			'leave':array
	 * 			{
	 * 				$uid => N/0 N表示勾选离线入场的位置，0表示没有勾选离线入场
	 * 			}
	 * 		}
	 * 		'defender':array
	 * 		{
	 * 			'guild_id':int
	 * 			'guild_name':string
	 * 			'enter':array
	 * 			{
	 * 				{
	 * 					'uid':int
	 * 					'utid':int
	 * 					'uname':string
	 * 					'htid':int
	 *					'dress':array
	 *				}
	 * 			}
	 * 			'leave':array
	 * 			{
	 * 				$uid => N/0 N表示勾选离线入场的位置，0表示没有勾选离线入场
	 * 			}
	 * 		}
	 * }
	 * </code>
	 * 
	 * [30]城池战，已参战的玩家登陆时通知相关人
	 * CITYWAR_USER_LOGIN
	 * <code>
	 * {
	 * 		uid:int
	 * }
	 * </code>
	 * 
	 * [31]城池战，已参战的玩家离线时通知相关人
	 * CITYWAR_USER_LOGOFF
	 * <code>
	 * {
	 * 		uid:int
	 * 		time:int
	 * }
	 * </code>
	 * 
	 * [32]城池战，战斗结束推送给前端战报
	 * CITYWAR_ATK_RESULT
	 * <code>
	 * {
	 * }
	 * </code>
	 * 
	 * [33]城池战，战斗结束广播
	 * CITYWAR_ATK_END
	 * <code>
	 * {
	 * 		'cityId':int
	 * }
	 * </code>
	 * 
	 * [34]军团报名列表刷新
	 * CITYWAR_SIGN_REFRESH
	 * <code>
	 * {
	 * 		$cityId
	 * }
	 * </code>
	 * [35]擂台赛报名或者挑战 报名位置玩家信息更新
	 * OLYMPIC_SIGNUP_UPDATE     = 'push.olympic.signup';
	 * <code>
	 * [
	 *     sing_up_index:int
	 *     user_info:array
	 *     [
	 *         uid:int
	 *         uname:string
	 *         dress:array
	 *         htid:int
	 *     ]
	 * ]
	 * </code>
	 * [36]擂台赛战报推送
	 * const OLYMPIC_BATTLE_RECORD     = 'push.olympic.battlerecord';
	 * <code>
     * [
     *    attacker:int
     *    defender:int
     *    brid:int
     *    result:string
     *    stage:int
     * ]
     * </code>            
	 * [37]擂台赛比赛阶段结束推送  推送新的阶段  比如16进8结束了，推送的阶段id是8进4
     * const OLYMPIC_STAGE_END       = 'push.olympic.stagechange';
     * <code>
     * int
     * </code>
	 * 
	 * [38]跨服战信息更新推送
	 * const LORDWAR_UPDATE			= 'push.lordwar.update';
	 * <code>
     * [
     *    round:int
     *    status:int
     * ]
     * </code>
     * 
     * [39] 资源矿抢夺信息推送
     * const MINERAL_ROB_BROADCAST     = 'push.mineral.rob';
     * <code>
     * [
     *     domain_id:int        资源矿区id
     *     pit_id:int            资源矿id
     *     pre_capture:int        原占有者的名字
     *     now_capture:int        现占有者的名字
     * ]
     * </code> 
     * 
     * [40] 军团粮田全体刷新推送
     * const GUILD_REFRESH_ALL			= 'push.guild.refreshAll';
     * <code>
     * [
     * 		0 => $uname
     * 		1 => array
     * 		{
     *    		$id 粮田id
	 * 	  		{
	 * 		 		0 => $num	剩余次数
	 * 		 		1 => $exp	经验
	 * 				2 => $level 等级 
	 * 			}
	 * 		}
     * ]
     * </code> 
     * 
     * [41] 军团粮仓等级推送
     * const GUILD_BARN_LEVEL			= 'push.guild.barnLevel';
     * <code>
     * [
     * 		$level
     * ]
     * </code> 
     * 
     * [42] 军团粮仓粮田等级经验信息推送
     * const GUILD_FIELD_HARVEST		= 'push.guild.fieldHarvest';
     * <code>
     * [
     * 		0 => $level
     * 		1 => $exp
     * ]
     * </code>
	 * 
	 * [43] 军团粮仓分粮冷却时间推送
	 * const GUILD_SHARE_CD			= 'push.guild.shareCd';
	 * <code>
     * [
     * 		$time
     * ]
     * </code>
     * 
     * [44]玩家战斗力的更新
     * push.user.fightForceUpdate
     * <code>
     * [
     *     fight_force=>fight_force
     * ]
     * </code>
     * 
     * 木牛流马有新车
     * push.chargedart.newship
     * <code>
     * [
     *     'stage_id',
     *     'page_id',
     *     'road_id',
     *     'uid',
     *     'uname',
     *     'guild_name',
     *     'be_robbed_num',
     *     'begin_time',
     * ]
     * </code>
     * 
     * 木牛流马被掠夺
     * push.chargedart.berobbed
     * <code>
     * [
     *     'stage_id',
     *     'page_id',
     *     'uid', 被掠夺者的uid
     *     'uname', 被掠夺者的uname
     *     'rob_uid', 掠夺者的uid
     *     'rob_uname', 掠夺者的uname
     *     'be_robbed_num',
     * ]
     * </code>
     * 
     * 木牛流马邀请好友
     * push.chargedart.invitefriendy
     * <code>
     * [
     *      'uid'
     *      'uname',
     *      'utid',
     *      'level',
     *      'fight_force',
     *      'guild_id'
     *      'stage_id'
     *      'master_hid'
     *      'guild_name'
     *      'flag'
     * ]
     * </code>
     * 
     * [45]矿精灵出现和矿精灵状态发生改变的推送
     * push.mineralelves.rob
     * <code>
     * [
     * 		'domain_id'
     * 		'pre_capture'
     * 		'now_capture'
     * 		'rob_time'
     * ]
     * </code>
     * push.mineralelves.update
     * <code>
     * [
     * 		'msg'=>'begin'
     * ]
     * </code>
     * 
     * 老玩家回归任务完成
     * WELCOMEBACK_TASK_FINISH
     * <code>
     * [
     * 		102001	任务id
     * ]
	 */
	public function zzDummyFuncPush()
	{
	    
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */