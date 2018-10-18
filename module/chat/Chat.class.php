<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Chat.class.php 170712 2015-05-04 07:37:45Z ShiyuZhang $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/Chat.class.php $
 * @author $Author: ShiyuZhang $(hoping@babeltime.com)
 * @date $Date: 2015-05-04 07:37:45 +0000 (Mon, 04 May 2015) $
 * @version $Revision: 170712 $
 * @brief 世界聊天频道接口
 *
 **/




class Chat implements IChat
{
	//当前用户ID
	private $m_uid;

	public function Chat()
	{

		$this->m_uid = RPCContext::getInstance()->getUid();
		if ( empty($this->m_uid) )
		{
			Logger::FATAL('invalid uid:%d', $this->m_uid);
			throw new Exception('fake');
		}
		
	}

	/* (non-PHPdoc)
	 * @see IChat::sendWorld()
	 */
	public function sendWorld($message,$type, $ignoreFilter = FALSE)
	{
		$ignoreFilter = false;
		//是否被禁言
		if ( $this->isBan() == TRUE )
		{
			return 'ban';
		}
		// 是否可以世界聊天
		if (!$this->canSendWorld())
		{
			return 'noPermissions';
		}
		
		$mWorld = btstore_get()->CHAT[ ChatConfig::WORLD_CONFID ];
		$user = EnUser::getUserObj();
		$bag = BagManager::getInstance()->getBag();
		// 用银币发送
		if( $type == ChatConfig::SEND_TYPE_GOLD )
		{
			if ( ($user->subGold( $mWorld[ 'need_gold' ] , StatisticsDef::ST_FUNCKEY_CHAT_SEND )) == FALSE )
			{
				return 'noGold';
			}
		}
		// 用道具发送
		else if($type == ChatConfig::SEND_TYPE_ITEM )
		{
			//TODO
// 			if ( $bag->deleteItemsByTemplateID($mWorld['cost_item']) == FALSE )
// 			{
// 				Logger::DEBUG("No enough items!");
// 				return 'noItem';
// 			}
		}
		else
		{
			return 'noItem';
		}
		// 发送世界聊天
		$ret = ChatLogic::sendWorld ( $this->m_uid, $message, $ignoreFilter  );
		if($ret == FALSE)
		{
			return 'err';
		}
		
		$user->update();
		$bag->update();
		
		return '';
	}

	/* (non-PHPdoc)
	 * @see IChat::sendGuild()
	 */
	public function sendGuild($message, $ignoreFilter = FALSE)
	{
		$ignoreFilter = false;
		//是否被禁言
		if ( $this->isBan() == TRUE )
		{
			return 'ban';
		}

		$user = EnUser::getUserObj();
		$guildId = $user->getGuildId();

		if ( empty($guildId) )
		{
			return 'noguild';
		}

		return ChatLogic::sendGuild ( $this->m_uid, $guildId, $message, $ignoreFilter );
	}

	/* (non-PHPdoc)
	 * @see IChat::sendCopy()
	 */
	public function sendCopy($message, $ignoreFilter = FALSE)
	{
		$ignoreFilter = false;
		//是否被禁言
		if ( $this->isBan() == TRUE )
		{
			return 'ban';
		}

		$copyId = RPCContext::getInstance ()->getSession (CopySessionName::COPYID);

		if ( empty($copyId) )
		{
			Logger::FATAL('copy id is null!');
			throw new Exception('fake');
		}

		return ChatLogic::sendCopy ( $this->m_uid, $copyId, $message, $ignoreFilter );
	}

	/* (non-PHPdoc)
	 * @see IChat::sendPersonal()
	 */
	public function sendPersonal($targetUid, $message, $ignoreFilter = FALSE)
	{
		$ignoreFilter = false;
		$return = array ( ChatDef::CHAT_ERROR_CODE_NAME => ChatDef::CHAT_ERROR_CODE_INVALID_REQUEST );
		
		if(BlackLogic::isInBlack($targetUid, $this->m_uid))
		{
			return 'beBlack';
		}
		//是否被禁言
		if ( $this->isBan() == TRUE )
		{
			return $return;
		}
		
		// 用户等级不到15级,禁止改功能
		if ( !$this->canSendPersonal() )
		{
			return 'noPermissions';
		}		

		//如果接受者和发送者为同一个用户,则返回错误
		if ( $targetUid == $this->m_uid )
		{
			return $return;
		}

		try
		{
			$targetUser = EnUser::getUserObj($targetUid);
		} catch (Exception $e)
		{
			throw new Exception("close");
		}

		//如果目标用户不在线
		if ( $targetUser->isOnline() == FALSE )
		{
			return 'userOffline';
		}

		if ( ChatLogic::sendPersonal ( $this->m_uid, $targetUid, $message, $ignoreFilter ) == FALSE )
		{
			return $return;
		}

		$return[ChatDef::CHAT_ERROR_CODE_NAME] = ChatDef::CHAT_ERROR_CODE_OK;
		$return[ChatDef::CHAT_MESSAGE] = ChatLogic::filterMessage($message);
		$return[ChatDef::CHAT_UTID] = $targetUser->getUtid();
		return $return;
	}

	/* (non-PHPdoc)
	 * @see IChat::sendBroadCast()
	 */
	public function sendBroadCast($message, $type)
	{
		//是否被禁言
		if ( $this->isBan() == TRUE )
		{
			return 'ban';
		}
		// 判断是否可以使用大喇叭
		if (!$this->canSendHorn())
		{
			return 'noPermissions';
		}
		
		$mBrost = btstore_get()->CHAT[ ChatConfig::HORN_CONFID ];
		$user = EnUser::getUserObj();
		$bag = BagManager::getInstance()->getBag();
		// 用银币发送
		if( $type == ChatConfig::SEND_TYPE_GOLD )
		{
			if ( ($user->subGold( $mBrost[ 'need_gold' ] , StatisticsDef::ST_FUNCKEY_CHAT_SEND )) == FALSE )
			{
				return 'noGold';
			}
		}
		// 用道具发送
		else if($type == ChatConfig::SEND_TYPE_ITEM )
		{
			//TODO
// 			if ( $bag->deleteItemsByTemplateID( $mBrost['cost_item'] ) == FALSE )
// 			{
// 				Logger::DEBUG("No enough items!");
// 				return 'noItem';
// 			}
		}
		else 
		{
			return 'noItem';
		}

		// 发送广播
		$ret = ChatLogic::sendPersonalBroadCast ( $this->m_uid, $message );
		if( $ret == FALSE)
		{
			return 'err';
		}
	
		// 用户更新
		$user->update();
		// 背包更新
		$bag->update();
		
		return '';
	}

	
	
	
	/* (non-PHPdoc)
	 * @see IChat::chatTemplate()
	 */
	public function chatTemplate($param){	/*do nothing*/		}

	/**
	 *
	 * 是否被禁言
	 *
	 * @return boolean				TRUE表示被禁言,FALSE表示没有被禁言
	 */
	private function isBan()
	{
		$user = EnUser::getUserObj();
		return $user->isBanChat();
	}
	
	
	private function canSendHorn()
	{
		$userObj = EnUser::getUserObj();
		$userLevel = $userObj->getLevel();
		$vipLevel = $userObj->getVip();
		$needUserLevel = btstore_get()->CHAT[ ChatConfig::HORN_CONFID ][ 'need_level' ];
		$needVipLevel = btstore_get()->CHAT[ ChatConfig::HORN_CONFID ][ 'need_vip' ];
		if ( $userLevel < $needUserLevel || $vipLevel < $needVipLevel )
		{
			return false;
		}
		return true;
	}
	
	private function canSendWorld()
	{
		$userObj = EnUser::getUserObj();
		$userLevel = $userObj->getLevel();
		$vipLevel = $userObj->getVip();
		$vipConf = btstore_get()->VIP[$vipLevel];
		if ( $vipConf['isChatOpen'] == 1 )
		{
			return true;
		}
		
		$needUserLevel = btstore_get()->CHAT[ ChatConfig::WORLD_CONFID ][ 'need_level' ];
		$needVipLevel = btstore_get()->CHAT[ ChatConfig::WORLD_CONFID ][ 'need_vip' ];
		if ( $userLevel < $needUserLevel || $vipLevel < $needVipLevel)
		{
			return false;
		}
		return true;
	}
	
	private function canSendPersonal()
	{
		$userObj = EnUser::getUserObj();
		$userLevel = $userObj->getLevel();
		$vipLevel = $userObj->getVip();
		$vipConf = btstore_get()->VIP[$vipLevel]; 
		if ( $vipConf['isChatOpen'] == 1 )
		{
			return true;
		}
		
		$needUserLevel = btstore_get()->CHAT[ ChatConfig::PERSONAL_CONFID ][ 'need_level' ];
		if ( $userLevel < $needUserLevel )
		{
			return false;
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see IChat::sendScreen()
	 */
	public function sendScreen( $message, $type, $filterId, $extra ) 
	{
		if( $type != ChatConfig::SCREEN_TYPE_BOSS && $type != ChatConfig::SCREEN_TYPE_MINE
		&& $type != ChatConfig::SCREEN_TYPE_ROBL )
		{
			throw new FakeException( 'invalid type: %s', $type );
		}
		if( $type == ChatConfig::SCREEN_TYPE_ROBL && $filterId <= 0 )
		{
			throw new FakeException( 'invalid robId: %s', $filterId );
		}
		
		$ret = ChatLogic::sendScreen( $this->m_uid, $type, $filterId, $message, false, $extra );
	}

	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */