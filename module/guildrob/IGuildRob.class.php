<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGuildRob.class.php 259118 2016-08-29 09:38:59Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/IGuildRob.class.php $
 * @author $Author: GuohaoZheng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-08-29 09:38:59 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259118 $
 * @brief 
 *  
 **/
 
/**********************************************************************************************************************
* Class       : IGuildRob
* Description : 军团抢粮战外部接口类
* Inherit     :
**********************************************************************************************************************/
interface IGuildRob
{
	/**
	 * create 创建抢粮战
	 *
	 * @param int $defendGuildId 		被抢夺的军团ID
	 *
	 * @return string/int				返回string代表异常原因，返回int代表抢粮战唯一ID
	 * 									
	 * 									异常原因如下
	 * 'attack_barn_not_open'			抢夺军团粮仓未开启
	 * 'defend_barn_not_open'			被抢军团粮仓未开启
	 * 'defend_in_shelter'				被抢军团刚被抢了一次粮，现在处于保护时间内，不能再被抢				
	 * 'defend_low_grain'				被抢夺军团粮草太少,无法抢夺
	 * 'defend_too_much'    			被抢的次数太多啦
	 * 'attack_too_much'    			抢夺的次数太多啦
	 * 'lack_fight_book'				缺少战书
	 * 'attacker_defending'				抢夺军团正在被另一个军团抢夺
	 * 'attacker_attacking'				抢夺军团正在抢夺另一个军团
	 * 'defender_defending'				防守军团正在被另一个军团抢夺
	 * 'defender_attacking'				防守军团正在抢夺另一个军团
	 * 			
	 */
	public function create($defendGuildId);
	
	/**
	 * enter 							进入抢粮战
	 *
	 * @return string
	 * 'over'							不在抢粮的时间段内
	 * 'not_found'						没发现这个玩家所在的军团在任何一场抢粮战内
	 * 'full'							战场有参战人数限制，表示已满
	 * 'ok'								进入正常，返回抢粮战id
	 */
	public function enter($robId);
	
	/**
	 * getEnterInfo 					获得初始信息
	 *
	 * @return array
	 * {
	 * 		ret = ok
	 * 		res = array
	 * 		{
	 * 			refreshMs:						刷新周期,单位 微秒
	 * 			readyDuration:                  准备时间,单位秒
	 * 
	 * 			attacker        				抢粮军团信息
	 * 			{
	 * 				guildId   	 				抢粮军团id   
	 * 				guildName					军团名字
	 * 				totalMemberCount            军团成员总数
	 * 				robGrain	   				已经抢夺的粮草
	 * 				morale						当前士气值
	 * 				memberCount					抢粮军团在战场上的人数
	 * 			}
	 * 
	 * 			defender     					被抢粮军团信息
	 * 			{
	 * 				guildId	    				被抢粮军团id
	 * 				guildName					军团名字
	 * 				totalMemberCount            军团成员总数
	 * 				robLimit					最多可以被抢多少粮草
	 * 				memberCount					被抢军团在战场上的人数
	 * 			}
	 *
	 * 			user	                		玩家信息
	 * 			{
	 * 				guildId						玩家所属的军团
	 * 				canJoinTime	    			能够参战的时间 leaveBattleTime + JoinCd  
	 * 				readyTime					能够进入战场的时间 quitBattleTime + JoinReady
	 * 				winStreak	      			连续击杀个数
	 *				extra           			其他信息   
	 *				{
	 *					info
	 *					{
	 *						removeCdNum			本次战斗中玩家消除cd的次数
	 *						speedUpNum         	本次战斗中玩家加速次数
	 *						killNum				本次战斗中玩家击杀个数
	 *						meritNum  			本次战斗中玩家获得功勋
	 *						userGrainNum		本次战斗中玩家为自己抢夺的粮草
	 *						guildGrainNum 		本次战斗中玩家为公会抢夺的粮草
	 *					}
	 *				}
	 * 			}
	 *
	 * 			field							战场信息
	 * 			{
	 * 				pastTime:					战场已经持续的时间，包括准备时间和正式开战时间，单位秒			
	 * 				endTime:					抢粮战结束时间，单位秒
	 * 				roadState					通道状态，1 代表目前属于较少通道 2 代表目前属于较多通道
	 * 				roadAddLimit				人数达到多少后，需要开启中间的通道
	 * 				roadLength:[]               数组，表示通道的长度
	 * 				transfer:[1,3,0,4,6,1]  	传送阵信息，每个传送阵上的人数，传送阵标号按照从从上向下，从攻方到守方的顺序，从0开始 
	 * 				road						包含所有在通道上的单位的信息
	 *				[
	 *					array					每个战斗单位数据如下
	 *					{
	 *						id					玩家id
	 *						type 				如果没有这个字段，就认为是玩家，非NPC之类
	 *						
	 *											以下数据在两种情况下会有：[1]需要的信息的用户刚刚进入战场[2]当前单位刚刚进入通道
	 *						name        		战斗单位名称
	 *						tid					形象id
	 *						transferId  		传送阵id
	 *						maxHp      			最大血量
	 *						
	 *											以下数据在三种情况下会有：[1]需要的信息的用户刚刚进入战场[2]当前单位刚刚进入通道[3]速度发生变化
	 *						speed				速度
	 *							
	 *											以下数据在三种情况下会有：[1]需要的信息的用户刚刚进入战场；[2]当前单位刚刚进入通道；[3]当前单位血量发生改变
	 *						curHp				当前血量
	 *						winStreak			连杀次数
	 *							
	 *											以下数据在四种情况下会有：[1]需要的信息的用户刚刚进入战场；[2]当前单位刚刚进入通道；[3]当前单位发生移动 [4] 速度发生变化
	 *						roadX				在通道上的位置
	 *						stopX				预测单位可能会停止的位置
	 *					}
	 *				]
	 *				spec						蹲点粮仓信息，数组有两个元素，分别代表蹲点粮仓1和蹲点粮仓2
	 *				[
	 *					userInfo => array
	 *					[
	 *						id=>array
	 *						{
	 *							name				玩家名称
	 *							tid					形象id
	 *							guildId				所在军团ID
	 *							specId				所在蹲点粮仓，取0和1
	 *							maxHp				最大血量
	 *							currHp				当前血量
	 *							winStreak			连胜次数
	 *							endTime:			蹲点到期时间
	 *						}
	 *					]
	 *				]
	 * 			}
	 * 		}
	 * }
	 */
	public function getEnterInfo();
	
	/**
	 * join 							参加抢粮战
	 *
	 * @param int $transferId 			传送阵id
	 *
	 * @return string/array
	 * 
	 * 如果返回string,string取值如下其中之一
	 * 
	 * battling							玩家正在战斗中
	 * full								传送阵或者通道上的人数已满
	 * 
	 * 如果返回array,array取值如下其中之一
	 * 
	 * {
	 * 		ret = 'in_spec_barn'		在蹲点粮仓中，无法加入传送阵
	 * 		spec_pos => int				所在蹲点粮仓的编号，从0开始
	 * }
	 * 
	 * {
	 * 		ret = 'waitTime'			处于等待时间，无法加入传送阵
	 * 		waitTime => int				还需要等待的时间
	 * }
	 * 
	 * {
	 * 		ret = 'cdtime'				处于参战冷却时间，无法加入传送阵
	 * 		cdtime => int				还有多长的冷却时间
	 * }
	 * 
	 * {
	 * 		ret = 'ok'
	 * 		outTime = int				出阵时间戳，单位秒
	 * 		reward						参战奖励
	 * 		{	
	 * 			merit => int      
	 * 		}
	 * }
	 *
	 */
	public function join($transferId);
	
	/**
	 * leave							退出战场
	 *
	 * @return string
	 * ret = ok 						成功
	 */
	public function leave();
	
	/**
	 * removeJoinCd 					秒除参战冷却时间
	 *
	 * @return string/array
	 * 
	 * 如果返回string,取值如下
	 * lack_cost						扣除金币失败
	 * nocd								表示没有cd
	 * 
	 * 如果返回array,取值如下
	 * {
	 * 		ret = 'ok' 					成功
	 * 		res = int 					实际花费的金币数
	 * }
	 */
	public function removeJoinCd();
	
	/**
	 * speedUp 							加速
	 * 
	 * @param int $multiple 			加速的倍数
	 *
	 * @return string/array
	 * 
	 * 如果返回string，取值如下
	 * not_in_transfer					不在传送阵中，无法加速
	 * lack_cost						扣除金币失败
	 * 
	 * 如果返回array,取值如下
	 * 
	 * {
	 * 		ret = 'limit' 				加速次数收到限制
	 * 		res = int 					最多加速的次数
	 * }
	 * 
	 * {
	 * 		ret = 'ok' 					成功
	 * 		res = int 					实际花费的金币数
	 * }
	 */
	public function speedUp($multiple);
	
	/**
	 * enterSpecBarn 					攻击牛掰粮仓
	 *
	 * @return 'ok'						为了同步，将所有蹲点粮仓的操作抛到SPECIAL_UID::GUILD_ROB系统用户线程去执行
	 * 									返回值通过push.guildrob.enterSpecRet推送
	 * 
	 */
	public function enterSpecBarn($pos);
	
	/**
	 * getRankByKill 					获取击杀排行榜
	 * 
	 * @param bool $onlyMysql 			是否只获取自己的排名
	 *
	 * @return array
	 * 	topN							击杀排行榜
	 *	[
	 *		id => array
	 *		{
	 *			rank					战斗单位排名
	 *			uname					战斗单位名称
	 *			killNum					战斗单位击杀数量
	 *		}
	 *	]
	 */
	public function getRankByKill($onlyMysql = FALSE);
	
	/**
	 * getGuildRobList 					在军团抢粮战中，拉取不同抢粮区域内的军团列表信息
	 *
	 * @param int $areaId               军团抢粮战中，抢粮区域id，从1开始
	 * 
	 * @return array                    区域信息
	 * 	{
	 * 		inRob                       是否在抢粮有效时间内，0 不在抢粮时间内 1 在抢粮时间内
	 * 		areaNum                  	抢粮区域总数						
	 * 		guildInfo => array
	 *      [
	 *			guildId => array		一个军团抢粮信息
	 *			{
	 *				name                军团名称
	 *				grain               可抢粮草
	 *				barn_level	                         军团粮仓等级		
	 *				robId				抢粮战唯一ID，如果为0表示军团不在任何抢粮战中，如果不为0，表示抢粮战唯一ID
	 *			}
	 *		]
	 *	}
	 */
	public function getGuildRobAreaInfo($areaId, $pattern = '');
	
	/**
	 * getGuildRobInfo 					玩家获取自己的抢粮战相关信息
	 *
	 * @return array                    区域信息
	 * 	[
	 *		guildId						军团ID
	 *		name                		军团名称
	 *		fight_book              	战书数量
	 *		grain               		可抢粮草
	 *		barn_level	                                                军团粮仓等级
	 *		robId						抢粮战唯一ID，如果为0表示军团不在任何抢粮战中，如果不为0，表示抢粮战唯一ID
	 *		shelterTime					抢粮战结束后的被抢军团CD时间，如果为0表示不处于该CD时间内，不为0代表这个CD结束的时间点
	 *		cdTime						抢粮战结束后的抢夺军团CD时间，如果为0表示不处于该CD时间内，不为0代表这个CD结束的时间点
	 *	]
	 */
	public function getGuildRobInfo();
	
	/**
	 * leaveGuildRobArea				当玩家离开军团抢粮列表区域时候调用
	 *
	 * @param            
	 * @return                          空
	 */
	public function leaveGuildRobArea();
	
	/**
	 * 获取玩家信息
	 * @return int 返回玩家勾选离线的时间（若为0，则表示其没有勾选）
	 */
	public function getInfo();
	
	/**
	 * 离线入场按钮
	 * @param $type int 1勾选离线 2取消离线
	 * @return int 新的勾选离线的时间(为0表示操作是取消离线)
	 */
	public function offline($type=1);
	
	
	
	/*******************以下是后端推送给前端的数据****************************/
	/*
	 [1]push.guildrob.refresh			一个刷新周期内发送的信息
	 {
		attacker						攻击军团的信息，如果有人达阵，才会有这个字段的信息
		{
			robGrain					抢夺的粮草数量
			morale						当前士气值
			memberCount					抢粮军团在战场上的人数
		}
		
		defender						防守军团的信息，暂为空，如果有人达阵，才会有这个字段的信息
		{
			memberCount					被抢军团在战场上的人数
		}
		
		field							战场信息
		{
			endTime						抢粮战结束时间，单位秒，如果结束时间发生变化，会传这个字段
			roadState					通道状态，1 代表目前属于较少通道 2 代表目前属于较多通道，如果通道的状态没有发生变化，则没有这个字段，这个字段值会在getEnterInfo时获取一次
			transfer					每个传送阵上的人数，传送阵标号按照从从左向右，从攻方到守方的顺序，从0开始
			[
				1						第0个传送阵上的战斗单位数量，以下类似
				2
				0
				3
				2
				1
			]
			road						包含所有在通道上的单位的信息
			[
				array					每个战斗单位数据如下
				{
					id 					玩家id
					type 				如果没有这个字段，就认为是玩家，非NPC之类
							
										以下数据在两种情况下会有：[1]需要的信息的用户刚刚进入战场[2]当前单位刚刚进入通道
					name        		战斗单位名称
					tid					形象id
					transferId  		传送阵id
					maxHp      			最大血量
							
										以下数据在三种情况下会有：[1]需要的信息的用户刚刚进入战场[2]当前单位刚刚进入通道[3]速度发生变化
					speed				速度
								
										以下数据在三种情况下会有：[1]需要的信息的用户刚刚进入战场；[2]当前单位刚刚进入通道；[3]当前单位血量发生改变
					curHp				当前血量
					winStreak			连杀次数
								
										以下数据在四种情况下会有：[1]需要的信息的用户刚刚进入战场；[2]当前单位刚刚进入通道；[3]当前单位发生移动 [4] 速度发生变化
					roadX				在通道上的位置
					stopX				预测单位可能会停止的位置
				}
			]
			touchdown					这个周期内达阵的战斗单位id数组
			[
				array
				{
					id					达阵的id
					type 				如果没有这个字段，就认为是玩家，非NPC之类
				}				
			]
			leave						这个周期内掉线或者主动离开战场的战斗单位id数组
			[
				array
				{
					id					离开的id
					type 				如果没有这个字段，就认为是玩家，非NPC之类
				}		
			]
		}
	}
	
	[2]push.guildrob.fightResult 		任何一场战斗结束都需要向战场所有玩家广播战斗结果
	{
		winnerId						胜者id
		loserId							败者id 
		winnerName						胜者名字
		loserName						败者名字
		winStreak						胜利者连胜次数
		terminalStreak					失败者在此次失败之前的连胜次数
		brid							战报id
		winnerType						如果没有这个字段，就认为是玩家，非NPC之类
        loserType						如果没有这个字段，就认为是玩家，非NPC之类
		winnerOut						默认赢家是不会被移出战场的，但是出现同归于尽的情况，虽然判一方胜，但是该胜者也需要移出战场
	}
	
	[3]push.guildrob.fightWin			给胜者单独发送的信息
	{			
		reward							胜者奖励信息
		{
			userGrain				   	用户获得的粮草
			guildGrain  				公会获得的粮草
			merit       				 用户获得的功勋
			contr                       用户获得的个人贡献
		}
		extra							扩展信息
		{
			adversaryName				对手名称
			winnerOut					默认赢家是不会被移出战场的，但是出现同归于尽的情况，虽然判一方胜，但是该胜者也需要移出战场
			joinCd						如果赢家也要被移出战场，这个是重新参战的时间
		}
	}
		
	[4]push.guildrob.fightLose			给败者单独发送的信息
	{
		reward							败者奖励信息，值为负代表需要扣除
		{
			userGrain				   	用户获得的粮草
			guildGrain  				公会获得的粮草
			merit       				 用户获得的功勋
			contr                       用户获得的个人贡献
		}
		extra							扩展信息
		{
			adversaryName				对手名称
			joinCd                      从新参战的CD时间
		}
	}
	
	[5]push.guildrob.touchDown			给达阵者单独发送的信息
	{
		reward							达阵者奖励信息
		{
			userGrain				   	用户获得的粮草
			guildGrain  				公会获得的粮草
			merit       				 用户获得的功勋
			contr                       用户获得的个人贡献
		}
		extra							扩展信息
		{
			joinCd                      从新参战的CD时间
		}
	}
	
	[6]push.guildrob.battleEnd          一整场战斗结束后发送的信息
	{
		ret = 'ok'                            
	}
	
	[7]push.guildrob.reckon				战斗结束后的玩家结算数据
	{
		rank
		kill
		merit
		contr
		userGrain
		guildGrain
		duration                                               
	}
		
	[8]push.guildrob.topN				击杀排行榜
	{
		[
			id => array
			{
				rank					战斗单位排名                							
				uname					战斗单位名称
				killNum					击杀数量
			}
		]
	}
	
	[9]push.guildrob.info				军团抢粮区域推送信息
	{
		guildId							军团ID
		name 							军团名称
		fight_book						战书数量
		grain							可抢粮草
		barn_level						军团粮仓等级
		robId                           抢粮战唯一ID，如果为0表示军团不在任何抢粮战中，如果不为0，表示抢粮战唯一ID
		shelterTime						抢粮战结束后的CD时间，如果为0表示不处于该CD时间内，不为0代表这个CD结束的时间点
	}
	
	[10]push.guildrob.spec    			蹲点粮仓推送信息
	[
		userInfo => array
		[
			id => array
			{
											以下字段只有在蹲点粮仓占有者发生变化的时候才发送
				name						玩家名称
				tid							形象id
				guildId						所在军团ID
				specId						所在蹲点粮仓，取0和1
				maxHp						最大血量
				endTime:					蹲点到期时间
				
											以下字段只有在血量发生变化的时候
				curHp						当前血量
				
											以下字段只有在连胜次数发生变化时候才发送
				winStreak					连胜次数
			}
		]
											这两个字段，只有在蹲点粮仓的时间达到上限以后，才会传递
		outSpecId							如果传递了该字段，字段的值是蹲点粮仓编号，表示该蹲点粮仓需要置空
		joinCd								原来在outSpecId的粮仓上的玩家的cd
		reward								到期奖励信息
		{
			userGrain				   		用户获得的粮草
			guildGrain  					公会获得的粮草
			merit       					 用户获得的功勋
			contr                       	用户获得的个人贡献
		}
	]
	
	[11]push.guildrob.enterSpecRet
	[
		ret => string/array
		
		如果返回string,取值如下:
		in_road							            在传送阵或者通道中，无法进入蹲点粮仓	
	 	in_spec_barn						已经在蹲点粮仓上啦，不能再次进入
	 	waitTime							处于等待时间
	 	cdtime							            处于冷却时间
	 	same_guild						           当前蹲点粮仓上的成员是自己军团的，不能抢占
	 	fail								抢占蹲点粮仓失败				
	  
	  	如果返回array,取值如下
	  	array								表示占领蹲点粮仓成功
	  	{
		 	userInfo => array
			[
				id => array
				{
					name					玩家名称
					tid						形象id
					guildId					所在军团ID
					specId					所在蹲点粮仓，取0和1
					maxHp					最大血量
					endTime:				蹲点到期时间
					curHp					当前血量
					winStreak				连胜次数
				}
			]
	  	}
	]
	
	*/
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */