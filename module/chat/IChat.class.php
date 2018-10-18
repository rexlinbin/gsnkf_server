<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IChat.class.php 170715 2015-05-04 07:39:37Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/IChat.class.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2015-05-04 07:39:37 +0000 (Mon, 04 May 2015) $
 * @version $Revision: 170715 $
 * @brief
 *
 **/
interface IChat
{

	/**
	 *
	 * 私人聊天
	 *
	 * @param int $targetUid 要发送到的用户uid，可以通过User.getUidByUname得到
	 * @param string $message 消息内容
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @return array
	 * <code>
	 * {
	 * 		error_code:int
	 * 						10000 成功
	 * 						10001 目标用户不在线
	 * 						10100 非法请求
	 * 		message:string	格式化后的消息
	 * 		target_utid:int	目标用户的utid
	 * }
	 * </code>
	 * @see IUser::getUidByUname()
	 */
	function sendPersonal($targetUid, $message, $ignoreFilter = FALSE);

	/**
	 *
	 * 世界消息
	 *
	 * @param string $message
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @return 
	 * ban 用户被禁言
	 * noPermissions 没有资格
	 * noGold 金币不够
	 * noItem 物品不够
	 * err 网络问题
	 * '' 成功
	 *
	 */
	function sendWorld($message,$type ,$ignoreFilter = FALSE);


	/**
	 *
	 * 同一个工会的消息
	 *
	 * @param string $message
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @return
	 * ban 禁言
	 * noguild 没有军团
	 * true 
	 *
	 */
	function sendGuild($message, $ignoreFilter = FALSE);


	/**
	 *
	 * 同一个副本的消息
	 *
	 * @param string $message
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @return boolean
	 */
	function sendCopy($message, $ignoreFilter = FALSE);

	/**
	 *
	 * 发送系统广播消息
	 *
	 * @param string $message
	 * @param int $type					发送类型 1:银币 2:道具
	 *
	 * @return 
	 * ban 用户被禁言
	 * noPermissions 没有资格
	 * noGold 金币不够
	 * noItem 物品不够
	 * err 网络问题
	 * '' 成功
	 */
	
	function sendBroadCast($message, $type);

	/**
	 * 发弹幕
	 * @param str $message 发送信息
	 * @param int $type 发送场景
	 * @param int $filterId 二级场景区分
	 * @param array $extra 前段自定义，配置相关等
	 */
	function sendScreen( $message, $type, $filterId, $extra );
	
	
	/**
	 *
	 * 聊天模板参数(无法调用)
	 *
	 * @param int $param
	 * <code>
	 * {
	 * 		user:array						用户
	 * 		{
	 * 			'uid':int
	 * 			'uname':string
	 * 			'utid':int
	 * 		}
	 * 		item:array						物品
	 * 		{
	 * 			'item_id':int				如果item_id=0,则表示是可叠加物品,
	 * 										只含有item_template_id和item_num
	 * 			'item_template_id':int
	 * 			'item_num':int
	 *			'item_time':int
	 * 			'va_item_text':array
	 * 		}
	 * 		boss:array						boss
	 * 		{
	 * 			'boss_id':int
	 * 		}
	 * 		title:array						称号
	 * 		{
	 * 			'title_id':int
	 * 		}
	 * 		hero:array						英雄模板
	 * 		{
	 * 			'htid':int
	 * 		}
	 * 		achievement:array				成就
	 * 		{
	 * 			'achievement_id':int
	 * 		}
	 * 		copy:array						副本
	 * 		{
	 * 			'copy_id':int
	 * 		}
	 *		treasure_map:array				藏宝图
	 *		{
	 *			'map_id':int
	 *		}
	 *		task:array						任务
	 *		{
	 *			'task_id':int
	 *		}
	 *		guild:array						公会
	 *		{
	 *			'guild_id':int
	 *			'guild_name':int
	 *		}
	 *		world_resource_id:array			世界资源
	 *		{
	 *			'world_resource_id':int
	 *		}
	 *		battle_record:array				战斗录像
	 *		{
	 *			'brid':int
	 *		}
	 *		boss reward:array				boss奖励
	 *		{
	 *			'boss_reward':array
	 *			{
	 *				'belly':int
	 *				'prestige':int
	 *				'experience':int
	 *				'gold':int
	 *				'items':array			同item
	 *			}
	 *		}
	 *
	 * chat模块回调数据结构（两大类）：
	 * 1、以uid区分接收者
	 * 	array
	 * 	{
	 * 		'sendMsg',
	 * 		array
	 * 		{
	 * 			arrTargetUid ,							要接受用户的uid组
	 * 			array
	 * 			{
	 * 				'err' => 'ok',
	 * 				'callbackName' => callbackName,		前端的函数
	 * 				'ret' => array{ @see below }		参数：见below
	 * 			},
	 * 		}
	 *  }
	 * 2、以给定条件区分接收者
	 * array
	 * {
	 * 		'sendFilterMessage',
	 * 		array
	 * 		{
	 * 			$filterType,							分类类型（如按公会、按副本等）
	 * 			$filterValue,							分类数值（如公会id、副本id等）
	 * 			array
	 * 			{
	 * 				'err' => 'ok',
	 * 				array{ 'callbackName' => callbackName, },	前端函数
	 * 				'ret' => array { @see below }				参数：见below
	 * 			},
	 * 		}
	 * }
	 *
	 * below:参数
	 * array 
	 * {
	 *		'message_text' => $message,
	 *		'sender_uid' => $sender_uid,
	 *		'sender_uname' => $sender_uname,
	 *		'sender_utid' => $sender_utid,
	 *		'sender_utype' => $sender_utype,
	 *		'send_time' => Util::getTime(),
	 *		'channel' => $channel,
	 *		'showFace' => $isShowFace,
	 *		'sender_gender' => $sender_gender,
	 *		'$sender_tmpl' => $sender_tmpl,
	 *	}
	 * }
	 * 
	 *
	 *</code>
	 */
	
	
	function chatTemplate($param);
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
