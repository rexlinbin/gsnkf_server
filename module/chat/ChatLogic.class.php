<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ChatLogic.class.php 227570 2016-02-16 01:51:32Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/ChatLogic.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2016-02-16 01:51:32 +0000 (Tue, 16 Feb 2016) $
 * @version $Revision: 227570 $
 * @brief
 *
 **/


class ChatLogic
{
	/**
	 *
	 * 发送公会消息
	 *
	 * @param int $sender_uid			发送者uid,系统发送者请使用ChatDef::CHAT_SYS_UID
	 * @param int $guildId				公会id
	 * @param string $message			信息
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @return boolean
	 */
	public static function sendGuild($sender_uid, $guildId, $message, $ignoreFilter = FALSE)
	{
		return self::sendFilterMessage ( $sender_uid, ChatChannel::GUILD,
			 ChatMsgFilter::GUILD, $guildId, $message, $ignoreFilter );
	}

	public static function sendScreen($sender_uid, $type, $filterId, $message, $ignoreFilter, $extra )
	{
		if( $type == ChatConfig::SCREEN_TYPE_BOSS )
		{
			return self::sendFilterMessage ( $sender_uid, ChatChannel::SCREEN,
					'arena', SPECIAL_ARENA_ID::BOSS, $message, $ignoreFilter, $extra );
		}
		elseif ( $type == ChatConfig::SCREEN_TYPE_MINE )
		{
			return self::sendFilterMessage ( $sender_uid, ChatChannel::SCREEN,
					'arena', SPECIAL_ARENA_ID::MINERAL, $message, $ignoreFilter, $extra );
		}
		elseif( $type == ChatConfig::SCREEN_TYPE_ROBL )
		{
			return self::sendFilterMessageGuildRob($sender_uid, ChatChannel::SCREEN, $filterId, $message, $ignoreFilter, $extra);
		}
		else 
		{
			throw new FakeException('invalid type: %s', $type);
		}
		
	}
	

	/**
	 * 发送副本消息
	 * 
	 * @param int $sender_uid
	 * @param int $copyId
	 * @param string $message
	 * @param boolean $ignoreFilter
	 * 
	 * @return boolean
	 */
	public static function sendCopy($sender_uid, $copyId, $message, $ignoreFilter = FALSE)
	{
		return self::sendFilterMessage ( $sender_uid, ChatChannel::COPY,
			 ChatMsgFilter::COPY, $copyId, $message, $ignoreFilter );
	}


	/**
	 *
	 * 发送世界消息
	 *
	 * @param int $sender_uid			发送者uid,系统发送者请使用ChatDef::CHAT_SYS_UID
	 * @param string $message			信息
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @return boolean
	 */
	public static function sendWorld($sender_uid, $message, $ignoreFilter = FALSE)
	{
		return self::sendMessage($sender_uid, ChatChannel::WORLD, $message, $ignoreFilter);
	}

	/**
	 *
	 * 发送系统消息(系统频道)
	 *
	 * @param string $message
	 *
	 * @return boolean
	 */
	public static function sendSystem($message)
	{
		//FIXME  等前端处理好聊天窗口系统消息的bug后再打开
		//return self::sendMessage(ChatDef::CHAT_SYS_UID, ChatChannel::SYSTEM, $message);
	}


	/**
	 *
	 * 发送系统消息(按公会ID区分接受者)(系统频道)
	 *
	 * @param int $guildId				公会ID
	 * @param string $message			信息
	 *
	 * @return boolean
	 */
	public static function sendSystemByGuild($guildId, $message)
	{
		return self::sendSysMessage(ChatMsgFilter::GUILD, $guildId, $message);
	}

	/**
	 *
	 * 发送系统消息(按uids区分接受者)(系统频道)
	 *
	 * @param array(int) $uids			用户uids
	 * @param string $message			信息
	 *
	 * @throws Exception
	 *
	 * @return boolean
	 */
	public static function sendSystemByPersonal($uids, $message)
	{
		self::validateMessage($message);

		$args = self::prepareCallBackArgs(ChatDef::CHAT_SYS_UID, ChatChannel::SYSTEM, $message);
		RPCContext::getInstance()->sendMsg($uids, ChatDef::MESSAGE_CALLBACK, $args);
		return TRUE;
	}

	/**
	 *
	 * 发送系统广播(私人大喇叭)
	 *
	 * @param int $sender_uid			发送者uid,系统发送者请使用ChatDef::CHAT_SYS_UID
	 * @param string $message			信息
	 *
	 * @return boolean
	 */
	public static function sendPersonalBroadCast($sender_uid, $message)
	{
		return self::sendMessage($sender_uid, ChatChannel::HORN, $message);
	}


	/**
	 *
	 * 发送系统广播
	 *
	 * @param int $sender_uid			发送者uid,系统发送者请使用ChatDef::CHAT_SYS_UID
	 * @param string $message			信息
	 *
	 * @return boolean
	 */
	public static function sendBroadCast($sender_uid, $message)
	{
		//在0点到1点关闭广播
		try 
		{
			$now = Util::getTime();
			$endTime = strtotime(strftime("%Y%m%d", $now).' 01:00:00');
			if( $now < $endTime  )
			{
				$msgTpl = isset( $message['template_id'] ) ? $message['template_id'] : 0;
				Logger::info('broadcast. templateId:%d', $msgTpl);
				return;
			}
		}
		catch( Exception $e)
		{
			Logger::warning('sendBroadCast. %s', $e->getMessage() );
		}
		
		return self::sendMessage($sender_uid, ChatChannel::BROATCAST, $message);
	}

	/**
	 *
	 *	发送私人消息
	 *
	 * @param int $sender_uid			发送者id
	 * @param int $receiver_uid			接受者id
	 * @param string $message			信息
	 * @param boolean $ignoreFilter		是否忽略过滤器
	 *
	 * @throws Exception				如果信息为空,或者信息超长,则fake
	 *
	 * @return boolean
	 */
	public static function sendPersonal($sender_uid, $receiver_uid, $message, $ignoreFilter = FALSE)
	{
		self::validateMessage($message);

		$args = self::prepareCallBackArgs($sender_uid, ChatChannel::PERSONAL, $message, $ignoreFilter);
		RPCContext::getInstance()->sendMsg(array(intval($receiver_uid)), ChatDef::MESSAGE_CALLBACK, array($args));
		return TRUE;
	}

	private static function sendSysMessage($filterType, $filterValue, $message)
	{
		self::validateMessage($message);

		$args = self::prepareCallBackArgs(ChatDef::CHAT_SYS_UID, ChatChannel::SYSTEM, $message);

		RPCContext::getInstance ()->sendFilterMessage($filterType,
			intval($filterValue), ChatDef::MESSAGE_CALLBACK, $args);
		return TRUE;
	}

	private static function sendMessage($sender_uid, $channel, $message, $ignoreFilter = FALSE)
	{
		self::validateMessage($message);

		$args = self::prepareCallBackArgs($sender_uid, $channel, $message, $ignoreFilter);

		if( $channel ==  ChatChannel::WORLD && EnUser::getUserObj($sender_uid)->getVip() <= UserConf::INIT_VIP )
		{
			$filterObj = ChatFilter::getInstance();
			if ( $filterObj->filterMsg($message) )
			{
				$filterObj->update();
				RPCContext::getInstance()->sendMsg(array($sender_uid), ChatDef::MESSAGE_CALLBACK, $args);
				return true;
			}
			$filterObj->update();
		}
		
		RPCContext::getInstance()->sendMsg(array(0), ChatDef::MESSAGE_CALLBACK, $args);
		return TRUE;
	}


	
	
	private static function sendFilterMessage($sender_uid, $channel,
			 $filterType, $filterValue, $message, $ignoreFilter = FALSE, $extra = array() )
	{
		self::validateMessage($message);

		$args = self::prepareCallBackArgs($sender_uid, $channel, $message, $ignoreFilter, $extra);

		RPCContext::getInstance ()->sendFilterMessage($filterType,
			intval($filterValue), ChatDef::MESSAGE_CALLBACK, $args);
		return TRUE;
	}

	private static function sendFilterMessageGuildRob($sender_uid, $channel,$robId, $message, $ignoreFilter = FALSE, $extra = array())
	{
		self::validateMessage($message);
		
		$args = self::prepareCallBackArgs($sender_uid, $channel, $message, $ignoreFilter, $extra);
		RPCContext::getInstance()->broadcastGroupBattle( $robId, $args, ChatDef::MESSAGE_CALLBACK );
		
		return true;
	}
	
	private static function prepareCallBackArgs($sender_uid, $channel, $message, $ignoreFilter = FALSE, $extra = array())
	{
		$sender_uname = '';
		$sender_utid = 0;
		$sender_utype = 0;
		$sender_vip = 0;
		$sender_level = 0;
		$sender_fight = 0;
		$sender_tmpl = 0;
		$sender_gender = 1;
		$guild_status = 3;//0 平民  1军团战 2副军团长 3么有加入军团
		
		$sender_figure = array();//时装信息
		$sender_headpic = 0;

		//如果不是系统用户
		if ( $sender_uid != ChatDef::CHAT_SYS_UID )
		{
			$user = EnUser::getUserObj($sender_uid);
			$userInfoNew = EnUser::getArrUserBasicInfo(array($sender_uid), array('uname','figure'));
			$userInfo = $userInfoNew[$sender_uid];
			$masterhero = $user->getHeroManager()->getMasterHeroObj();
			
			
			$sender_uname = $userInfo['uname'];
			$sender_utid = $user->getUtid();
			$sender_utype = $user->getUserType();
			$sender_vip    = $user->getVip();
			$sender_level = $user->getLevel();
			$sender_fight = $user->getFightForce();
			
			$sender_tmpl = $masterhero->getHtid();
			$sender_gender = UserConf::$USER_INFO[ $sender_utid ][ 0 ];
			$guild_status = EnGuild::getMemberType( $sender_uid );
			$sender_headpic = $userInfo['figure'];
			
			$sender_figure = $user->getDressInfo();
			//$user->getFigure();
			 
			//并且不忽略屏蔽词
			if ( $ignoreFilter == FALSE )
			{
				$message = self::filterMessage ( $message );
			}
		}

		$args = array (
			'message_text' => $message,
			'sender_uid' => $sender_uid,
			'sender_uname' => $sender_uname,
			'sender_utype' => $sender_utype,//TODO 现在是没用 需要用的时候直接用
		    'sender_vip'=> $sender_vip,
			'sender_level' => $sender_level,
			'sender_fight' => $sender_fight,
			'send_time' => Util::getTime(),
			'channel' => $channel,
			'sender_tmpl' => $sender_tmpl,
			'sender_gender' => $sender_gender,
			'guild_status' => $guild_status,
			'figure' => $sender_figure,//figure這個在chat里是時裝形象，在user里是頭像，所有現在我也要加頭像，然後就傻逼了
			'headpic' => $sender_headpic,
		);
		
		if( !empty( $extra ) )
		{
			$args['extra'] = $extra;
		}

		return $args;
	}
	
	/**
	 *
	 * 检测数据是否合法
	 *
	 * @param string $message
	 *
	 * @throws Exception
	 */
	public static function validateMessage($message)
	{
		//TODO聊天的字数限制如果确定是不同的
		if ( is_string($message) && strlen($message) != 0
			&& mb_strlen($message, ChatConfig::CHAT_ENCODING) > ChatConfig::MAX_CHAT_LENGTH )
		{
			Logger::FATAL('message length is invalid!message:%s', $message);
			throw new Exception('fake');
		}
	}

	/**
	 *
	 * 对数据进行敏感词过滤
	 *
	 * @param string $message
	 */
	public static function filterMessage($message)
	{
		if ( is_string($message) )
		{
			return TrieFilter::mb_replace ( $message );
		}
		else
		{
			return $message;
		}
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
