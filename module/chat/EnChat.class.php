<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnChat.class.php 47978 2013-05-22 07:25:05Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/EnChat.class.php $
 * @author $Author: wuqilin $(jiangzhichao@babeltime.com)
 * @date $Date: 2013-05-22 07:25:05 +0000 (Wed, 22 May 2013) $
 * @version $Revision: 47978 $
 * @brief 
 *  
 **/
/**********************************************************************************************************************
 * Class       : EnChat
 * Description : chat内部接口类
 * Inherit     :
 **********************************************************************************************************************/
class EnChat
{
	/**
	 * 过滤敏感词汇
	 */
	public static function filterMessage($message)
	{
		ChatLogic::validateMessage($message);
		return ChatLogic::filterMessage($message);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */