<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ICountryWarCross.class.php 216701 2015-12-21 10:30:59Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/ICountryWarCross.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-21 10:30:59 +0000 (Mon, 21 Dec 2015) $
 * @version $Revision: 216701 $
 * @brief 
 *  
 *  跨服接口类
 *  
 *  loginCross 返回值的结构修改
 *  添加排行榜接口getRankList
 *  添加自动回血相关接口两个（设置回血点，开启关闭自动回血）
 *  getEnterInfo 返回值添加回血点，自动回血是否开启字段
 *  getLoginInfo 返回值添加uuid
 *  
 **/
interface ICountryWarCross
{
	/**
	 * 登录跨服机器，参数含义@seegetLoginInfo
	 *
 	 * @param int $serverId 				原服务器id		
	 * @param int $pid						玩家pid
	 * @param string $token					登录校验串
	 *
	 * @return
	 * 
	 * <code>
	 * 
	 * {
	 * 		ret => string,				'ok'|'fail'
	 * }
	 * 
	 * </code>
	 * 
	 * 	TODO修改超时时间配置lcserver
	 */
	public function loginCross( $serverId,$pid, $token );
	
	/**
	 * 进入战场
	 * 轻量级，初始化及标识场景的功能
	 * @return string
	 * 
	 * <code>
	 * 
	 * 		'over'											结束了
	 * 		'not_found'										没发现战场
	 * 		'full'											战场有参战人数限制，表示已满
	 * 		'ok'											进入正常
	 * 		'expired'										时间不对
	 * 		'noone'											没人参加
	 * 
	 * </code>
	*/
	public function enter( $countryId = NULL );
	
	/**
	 * 标识场景的功能
	 * @return string
	 * 
	 * <code>
	 * 
	 * 	string 						'ok'|'fail'								
	 * 
	 * </code>
	 * 
	*/
	public function leave();
	
	/**
	 * 进入场景后获取的场景信息
	 * getEnterInfo
	 *
	 * @return
	 * 
	 * <code>
	 * 
	 * {
	 * 		ret = ok
	 * 		res = array
	 * 		{
	 * 			refreshMs=>int						刷新周期,单位 微秒
	 * 			readyDuration=>int                  准备时间,单位秒
	 *
	 * 			user	                		玩家信息
	 * 			{
	 * 				canJoinTime	    			能够参战的时间 leaveBattleTime + JoinCd 可清的
	 * 				readyTime					能够进入战场的时间 quitBattleTime + JoinReady
	 * 				canInspreTime				能够鼓舞的时间lastInspireTime + cdTime
	 *				extra           			其他信息
	 *				{
	 *					info
	 *					{
	 *						attackLevel			攻击鼓舞等级					
	 * 						defendLevel			防守鼓舞等级
	 *						auto_recover 		自动回血的状态 1|2 开启|关闭
	 *						recover_percent		自动回血的点 触发回血的百分比 是一个以10000做基数的值
	 *					}
	 *				}
	 * 			}
	 * 			field							战场信息
	 * 			{
	 * 				pastTime:					战场已经持续的时间，包括准备时间和正式开战时间，单位秒
	 * 				endTime:					抢粮战结束时间，单位秒
	 * 				roadState					通道状态，1 代表目前属于较少通道 2 代表目前属于较多通道
	 * 				roadAddLimit				人数达到多少后，需要2变4
	 * 				roadLength:[]               数组，表示通道的长度
	 * 				transfer:[1,3,0,4,6,1]  	传送阵信息，每个传送阵上的人数，传送阵标号按照从从上向下，从攻方到守方的顺序，从0开始
	 * 				road						包含所有在通道上的单位的信息
	 *				[
	 *					array					每个战斗单位数据如下    TODO	跨服的东西需要server_id，pid
	 *					{
	 *						id					玩家id
	 *						type 				如果没有这个字段，就认为是玩家，非NPC之类
	 *						serverName			服务器名字
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
	 * 			}
	 * 		}
	 * }
	 * 
	 * </code>
	 * 
	*/
	public function getEnterInfo();
	
	/**
	 * join 						zhandou 
	 *
	 * @param int  			传送阵id
	 *
	 *@return
	 *
	 *<code>
	 *
	 *{
	 *	ret:状态如下 
	 *	battling正在战斗, 
	 *	full传送阵已满, 
	 *	waitTime等冷却, 
	 *	cdtime达阵失败后的再次出阵cd,
	 *	ok 出阵成功
	 *
	 *	waitTime:int 			ret为waitTime|cdtime时有效,什么时间可以参战
	 *	outtime:int				ret为ok时有效
	 *}
	 *
	 *</code>
	 *
	*/
	public function joinTransfer( $transferId );
	
	/**
	 * 鼓舞
	 * @param 鼓舞类型 $type					1|2|3|4|5|6,金攻击|银攻击|金防御|银防御|金血|银血
	 * 
	 * <code>
	 * 
	 * @return
	 * {
	 * 		ret=>string						'ok'
	 * 		res=>array
	 * 		{
	 * 			attackLevel					
	 * 			defendLevel
	 * 			cost
	 * 		}			
	 * }
	 * 
	 * </code>
	 * 
	*/
	public function inspire();
	
	/**
	 * 清除达阵后的cd
	 * @return
	 * 
	 * <code>
	 * 
	 * {
	 * 		ret:string						ok|fail|poor|limit|cooled,成功|失败|数值不足|已达上限|已经过了冷却时间 
	 * }
	 * 
	 * </code>
	 * 
	*/
	public function clearJoinCd();
	
	/**
	 * 手动回血
	 *
	 *<code>
	 *
	 * @return
	 * {
	 * 		hpRecover
	 *		{
	 *			cost
	 *		}
	 *
	 *
	 *		'fail'
	 * }
	 * 
	 *</code>
	 * 
	*/
	public function recoverByUser();
	
	/**
	 * 手动设置恢复参数
	 * @param int $percent				3000 表示30%
	 * 
	 * @return
	 * 
	 * <code>
	 * 
	 * {
	 * 		ret:string						ok|fail|poor,成功|失败|数值不足
	 * }
	 * 
	 * </code>
	 * 
	 */
	public function setRecoverPara( $percent );
	
	/**
	 * 自动回血开关
	 * @return int 1 开 2关
	 * @return
	 *
	 * <code>
	 *
	 * {
	 * 		ret:string						ok|fail|poor,成功|失败|数值不足
	 * }
	 *
	 * </code>
	 *
	 */
	public function turnAutoRecover( $onOrOff );
	
	/**
	 * @return
	 * 	 			[
	 * 						{
	 * 							uname
	 * 							htid
	 * 							vip
	 * 							level
	 * 							fight_force
	 * 							dress
	 * 						}
	 * 				 ]
	 */
	public function getRankList();
	

	/*******************后端推送给前端的数据****************************/
	/*
	 [1]push.countrywarcross.refresh			一个刷新周期内发送的信息
	{
	attacker						攻方信息，如果有人达阵，才会有这个字段的信息
	{
	resource					当前资源数
	memberCount					战场上的人数
	}
	
	defender						守方信息，如果有人达阵，才会有这个字段的信息
	{
	resource					当前资源数
	memberCount					战场上的人数
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
	
	[2]push.countrywarcross.fightResult 		任何一场战斗结束都需要向战场所有玩家广播战斗结果
	{
	winnerId						胜者id
	loserId							败者id
	winnerName						胜者名字//TODO 服的名字
	loserName						败者名字
	winStreak						胜利者连胜次数
	terminalStreak					失败者在此次失败之前的连胜次数
	brid							战报id
	winnerOut						默认赢家是不会被移出战场的，但是出现同归于尽的情况，虽然判一方胜，但是该胜者也需要移出战场
	}
	
	[3]push.countrywarcross.fightWin			给胜者单独发送的信息
	{
	reward							胜者奖励信息
	{
	point					   	用户获得的积分
	}
	extra							扩展信息
	{
		adversaryName				对手名称
		winnerOut					默认赢家是不会被移出战场的，但是出现同归于尽的情况，虽然判一方胜，但是该胜者也需要移出战场
		joinCd						如果赢家也要被移出战场，这个是重新参战的时间
	}
	hpRecover
	{
		cost
	}
	hpRecover
	{
		curHp
		cost
	}
	}
	
	[4]push.countrywarcross.fightLose		给败者单独发送的信息
	{
	reward							败者奖励信息，值为负代表需要扣除
	{
	point					   	用户获得的积分
	}
	extra							扩展信息
	{
	adversaryName				对手名称
	joinCd                      从新参战的CD时间
	}
	}
	
	[5]push.countrywarcross.touchDown		给达阵者单独发送的信息
	{
	reward							达阵者奖励信息
	{
	point					   	用户获得的积分
	}
	extra							扩展信息
	{
	joinCd                      从新参战的CD时间
	}
	}
	
	[6]push.countrywarcross.battleEnd        一整场战斗结束后发送的信息
	{
	ret = 'ok'
	}
	
	[7]push.countrywarcross.reckon			战斗结束后的玩家结算数据
	{
	rank							排名
	point							积分
	}
	
	[8]push.countrywarcross.topN				排行榜
	{
	[
	id => array
	{
	rank					战斗单位排名
	uname					战斗单位名称
	point					积分
	}
	]
	}
	
	[9]push.countrywarcross.reset			场上玩家回血
	[
	id
	]
	事故预案和测试方案TODO
	*/
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */