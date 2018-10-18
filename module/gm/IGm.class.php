<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: IGm.class.php 99610 2014-04-12 10:27:27Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/gm/IGm.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2014-04-12 10:27:27 +0000 (Sat, 12 Apr 2014) $
 * @version $Revision: 99610 $
 * @brief 用于处理所有和gm相关的功能
 *
 **/
interface IGm
{

	/**
	 * 前端的错误信息
	 * @param string $message
	 */
	public function reportClientError($message);

	/**
	 * 获取服务器时间
	 * @return int 服务器时间
	 */
	public function getTime();

	/**
	 *
	 * 通知前端收到新的公告
	 *
	 * @return NULL
	 */
	public function newBroadCast();

	/**
	 *
	 * 通知前端收到新的测试公告
	 *
	 * @param int $uid				测试的uid
	 * @param int $bid				公告ID
	 *
	 * @return NULL
	 */
	public function newBroadCastTest($uid, $bid);

	
	/**
	 *
	 * 发送系统邮件(一个邮件最多携带5个物品)
	 *
	 * @param int $recieverUid					收件人id
	 * @param string $subject					邮件标题
	 * @param string $content					邮件内容
	 *
	 * @return boolean
	 */
	public function sendSysMail($recieverUid, $subject, $content);
	
	
	
	
	/**
	 * 脚本后台功能相关
	 *
	 * 后台通过ServerProxy::sendScriptToClient($uid, $script)发送一段脚本（lua）给前端执行
	 * 
	 * 对应前端回调接口：re.gm.runScript
	 * 
	 * 前端通过reportScriptResult接口将接口传给后端存着
	 * 后台在通过getScriptResult接口获取结果
	 *
	 * 前端上报的结果存在memcached中结构如下
	 * [
	 * 		{
	 * 			time:int
	 * 			msg:array/string
	 * 		}
	 * ]
	 *
	 *
	 * @param unknown $msg  暂时不限制msg长度
	 */
	public function reportScriptResult($msg);
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */