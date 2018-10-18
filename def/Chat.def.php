<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Chat.def.php 170438 2015-04-30 03:54:55Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/def/Chat.def.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2015-04-30 03:54:55 +0000 (Thu, 30 Apr 2015) $
 * @version $Revision: 170438 $
 * @brief 聊天相关的定义
 *
 **/

/**
 * 聊天频道定义
 *
 */
class ChatDef
{

	/**
	 * 发送消息的callback
	 * @var string
	 */
	const MESSAGE_CALLBACK 						= 're.chat.getMsg';

	const CHAT_ERROR_CODE_NAME 					= 'error_code';
	const CHAT_ERROR_CODE_OK					= 10000;
	const CHAT_ERROR_CODE_USER_OFFLINE 			= 10001;
	const CHAT_ERROR_CODE_IN_CD					= 10002;
	const CHAT_ERROR_CODE_FORBIDDEN				= 10003;
	const CHAT_ERROR_CODE_INVALID_REQUEST		= 10100;

	const CHAT_SESSION_MSG_TIMES				= 'chat.msg_times';
	const CHAT_SESSION_FORBIDDEN				= 'chat.forbidden_time';

	const CHAT_TEMPLATE_ID_NAME					= 'template_id';
	const CHAT_TEMPLATE_DATA_NAME				= 'template_data';
	const CHAT_MESSAGE							= 'message';
	const CHAT_UTID								= 'utid';

	const CHAT_SYS_UID							= 0;
	const CHAT_SYS_UNAME						= '';

	//ITEMS
	const CHAT_ITEM_STACKABLE					= 'item_stackable';
	const CHAT_ITEM_NOT_STACKABLE				= 'item_not_stackable';
	
	const REFRESH_HOUR							= "040000";
}

class ChatChannel
{

	/**
	 * 广播频道
	 * @var int
	 */
	const BROATCAST = 1;
		
	/**
	 * 世界频道（用户发的消息，出现在世界频道）
	 * @var int
	 */
	const WORLD = 2;
	
	/**
	 * 系统频道 (系统发的消息，出现在世界频道)
	 * @var int
	 */
	const SYSTEM = 3;
		
	
	/**
	 * 私人频道
	 * @var int
	 */
	const PERSONAL = 4;
	
	
	/**
	 * 私人广播频道(大喇叭)
	 * @var int
	 */
	const HORN = 5;
	
	

	/**
	 * 副本频道
	 * @var int
	 */
	const COPY = 100;

	/**
	 * 公会频道
	 * @var int
	 */
	const GUILD = 101; 

	/**
	 * 弹幕
	 * @var unknown
	 */
	const SCREEN = 102;
	


}

class ChatMsgFilter
{
	const GUILD = 'guild';
	const COPY = 'copy';
	const MINERAL = 'resource';
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
