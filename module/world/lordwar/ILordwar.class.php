<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ILordwar.class.php 241173 2016-05-05 13:23:46Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/lordwar/ILordwar.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-05-05 13:23:46 +0000 (Thu, 05 May 2016) $
 * @version $Revision: 241173 $
 * @brief 
 *  
 **/
interface  ILordwar
{

	/**
	 * 进入跨服场景，为推送
	*/
	function enterLordwar();
	
	/**
	 * 离开跨服场景
	*/
	function leaveLordwar();
	
	/**
	 * 报名
	*/
	function register();
	
	/**
	 * array(
	 * 		serverId =>serverName,
	 * 		serverId =>serverName,
	 * 		...
	 * )
	*/
	function getMyTeamInfo();
	
	/**
	 * @return array(
	 * 		round => array( subRound1 => array(@see below), subRound2 => array(),... )
	 * 		round => array( subRound1 => array(),subRound2 => array(), )
	 * 		...
	 * )
	 * 
	 * below:
	 * atk => array
	 * ()//若果自己胜利的话，atk为空
	 * def => 
	 * (
	 * 		uname => '',
	 * 		serverId => ,
	 * 		serverName=>,
	 *		uid => ,
	 * )
	 * replyId => ,
	 * res => 0/1 ,
	 * )
	 */
	function getMyRecord();
	
	/**
	 * 更新战斗信息
	 */
	function updateFightInfo();
	
	/**
	 * 清更新战斗信息的cd
	*/
	function clearFmtCd();
	
	
	
	/**
	 * array(
	 * 		0 => array(
	 * 			uname => ,
	 * 			serverId => ,
	 * 			serverName=>,
	 * 			pid => ,
	 * 			htid => ,
				dress => ,
				title => ,
	 * 			...
	 * 			
	 * 		),
	 * 		1 => array(),
	 * 		2 => array(),
	 * )
	 * 
	 */
	function getTempleInfo();
	
	
	/**
	 * 
	 * @param int $type 膜拜类型
	 * 
	 */
	function worship($pos, $type);


	/**
	 * 
	 * @param int $serverId
	 * @param int $uid
	 */
	function support($pos, $teamType);
	
	/**
	 * array(
	 * 		round => array(serverId,serverName,teamType,uid,uname,win),
	 * 		round => array(serverId,serverName,teamType,uid,uname,win),
	 * 		...
	 * 		...
	 * )
	 */
	function getMySupport();
	
	/**
	 * 获取用户信息
	 * @return array							
	 *	( 
	 *		ret:ok/no no表示本服务器不在分组内
	 *		round:
	 *		status:
	 * 		team_type:						组别, 初始为0, 胜者组为1, 负者组为 2
	 * 		worship_num:					膜拜次数
	 * 		update_fmt_time:				更新战斗力时刻
	 * 		sign_time:						报名时间
	 * 		server_id:						服务器id
	 *  )	
	 */
	function getLordInfo();
	
	/**
	 * //拉取战斗进度、结果等信息，其实主要就是拉取晋级赛的信息
	 * array(                         
	 * 		round => int 
	 *		status => int,
	 *		winLord => array(
	 *				//32个人的信息，32条数据，每条中包含当前名次，每次战斗对手信息
	 *				0=> array(
	 *					uid => ,
	 *					uname => ,
	 *					htid => ,
	 *					vip => ,
	 *					dress =>,
	 *					serverId => ,
	 *					rank => ,
	 *					fightForce =>,
	 *				 	)
	 *				), 
	 * 			),
	 * 		loseLord=>array()//同winLord
	 * 
	 */
	function getPromotionInfo();
	
	/**
	 * 获取某个阶段，晋级赛某两个人的战报id
	 * @return
	 * array(
	 * subround=> 
	 * array(
	 * atk => array
	 * (
	 * 		serverId => ,
	 *		uid => ,
	 * )
	 * def => 
	 * (
	 * 		serverId => ,
	 *		uid => ,
	 * )
	 * replyId => ,
	 * res => ,				
	 * 			)
	 * )
	 */
	function getPromotionBtl( $round,$teamType, $serverId1, $uid1, $serverId2, $uid2 );
	
	
	function getPromotionHistory( $round );
	
	
} 
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
