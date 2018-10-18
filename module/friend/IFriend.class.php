<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IFriend.class.php 236988 2016-04-07 08:59:12Z JiexinLin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/IFriend.class.php $
 * @author $Author: JiexinLin $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-04-07 08:59:12 +0000 (Thu, 07 Apr 2016) $
 * @version $Revision: 236988 $
 * @brief 
 *  
 **/
interface IFriend
{
	/**
	 * 申请好友
	 * @param int $fuid 对方uid
	 * @param int $content 申请内容
	 * @return 'ok' 'applied' 'reach_maxnum'
	 * 
	 */
	public function applyFriend( $fuid , $content );
	
	/**
	 * 添加好友
	 * @param int $fuid 对方uid( 申请者的uid )
	 * @return 'ok' 'applicant_reach_maxnum' 'accepter_reach_maxnum' 'isfriend'
	 */
	public function addFriend( $fuid );
	
	/**
	 * 拒绝好友
	 * @param int $fuid 对方uid
	 * @return 'ok' 'isfriend'
	 */
	public function rejectFriend( $fuid );
	
	/**
	 * 删除好友
	 * @param int $fuid 对方uid
	 * @return 'ok' 'notfriend'
	 */
	public function delFriend( $fuid );
	
	/**
	 * 获取单个好友信息
	 * @param int $fuid 要获取信息的好友的uid
	 * @return 
	 * array (
	 *			'uid' => int, 好友uid
	 *			'uname' => string, 好友uname
	 *			'utid' => int, 好友模板id
	 *			'status' => int, 好友 用户状态（ 在线、离线、删除、封号 ）
	 *			'level' => int 用户等级
	 *	);
	 */
	public function getFriendInfo( $fuid );
	
	/**
	 * 获取所有好友信息
	 * @return
	 * array(
	 * int => array
	 * 			(
	 * 			'uid' => int, 好友uid
	 *			'uname' => string, 好友uname
	 *			'utid' => int, 好友模板id
	 *			'status' => int, 好友 用户状态（ 在线、离线、删除、封号 ）
	 *			'level' => int 用户等级
	 *			'lovedTime' => int 上次赠送时间
	 *			'guild_name' => string 公会名
	 *			'fight_force' => int 战斗力
	 *			'htid'	=> int 好友主角的英雄模板id
	 * 			);
	 * );
	 * 
	 * 'fight_force' 和 'htid' 这2个字段目前只在“木牛流马”前端模块里用到
	 */
	public function getFriendInfoList();
	
	/**
	 * 获取系统推荐好友信息
	 */
	public function getRecomdFriends();
	
	/**
	 * 根据用户名模糊搜索
	 * @param string $unameLik
	 * @param int $offset( 默认为0 )
	 * @param int $limit（默认为数据库允许最大）
	 */
	public function getRecomdByName( $unameLike, $offset, $limit );
	
	/**
	 * 是否为自己的好友
	 * @param int $fuid 对方uid
	 * @return boolean
	 */
	public function isFriend( $fuid );
	
	/**
	 * 赠送好友体力
	 * @param int $fuid  好友uid
	 */
	public function loveFriend( $fuid );

	/**
	 * 领取某一被赠体力
	 * @param int $time
	 * @param int $uid
	 * @param int $reLove 1 为回赠， 0为不会增
	 */
	public function receiveLove( $time, $uid, $reLove );
	
	/**
	 * 领取所有获赠体力
	 * @return array	可领获赠体力
	 * {
	 * 		array{ 'time' => int, 'uid' => int },
	 * 		..
	 * }
	 */
	public function receiveAllLove();
	
	/**
	 * 可领取体力列表
	 * @return array
	 * {
	 * 		list => array{
	 * 						array{ 'time' => int, 'uid' => int },
	 * 					 }
	 * 		leftLoveTimes => int;
	 * 		..
	 * 
	 * }
	 */
	public function unreceiveLoveList();
	
	/**
	 * 获得好友的pk信息
	 * @param unknown $beuid 好友的id
	 * @return array(
	 * 
	 *  						["pk_num"]=>int(0)
	 *  						["bepk_num"]=>int(0)
	 *  						["friend_bepk_num"]=>int(0)
	 *  						["sameFriendNum"]=>int(0)
	 *  						["isFriend"] =>0 or 1
	 * 				)
	 */
	public function getPkInfo($beuid);
	
	
	/**
	 * 揍他
	 * @param unknown $beuid
	 * @return "notFriend" OR
	 * array(
	 *   ["errcode"]=>string(7) "success" or "fail"
	 *   ["appraisal"]=>string(1) "F"
	 *   ["fightStr"]=> string
	 * )
	 */
	public function pkOnce($beuid);
	
	/**
	 * @return
	 * array
	 * {
	 *   array
	 *   {
	 *   	uid:
	 * 		uname:
	 * 		.
	 * 		.
	 * 		.
	 *   }
	 *
	 * }
	 */
	function getBlackers();
	
	/**
	 * 被拉黑的uid
	 * @param unknown $beBlackUid
	*/
	function blackYou($beBlackUid);
	
	/**
	 * 被解除黑名单的uid
	 * @param unknown $unBlackUid
	*/
	function unBlackYou($unBlackUid);
	
	/**
	 * 拉取被黑的名单
	 * @param int $uid
	 */
	function getBlackUids();
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */