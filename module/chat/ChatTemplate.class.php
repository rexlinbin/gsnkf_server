<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: ChatTemplate.class.php 215580 2015-12-14 09:41:52Z ShiyuZhang $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/chat/ChatTemplate.class.php $
 * @author $Author: ShiyuZhang $(jhd@babeltime.com)
 * @date $Date: 2015-12-14 09:41:52 +0000 (Mon, 14 Dec 2015) $
 * @version $Revision: 215580 $
 * @brief
 *
 **/

class ChatTemplate
{
	
	private static function sendItemQuality($user, $chatTemplateIdRed,
			 $chatTemplateIdPurple, $items, $isSendRed = TRUE)
	{/* 
		foreach ( $items[ChatDef::CHAT_ITEM_STACKABLE] as $itemTemplateId => $itemNum )
		{
			$itemType = ItemManager::getInstance()->getItemType($itemTemplateId);
			if($itemType == ItemDef::ITEM_GOODWILL)
			{
				continue;
			}
			$itemQuality = ItemManager::getInstance()->getItemQuality($itemTemplateId);
			$itemInfo = array(
				'item_id' => ItemDef::ITEM_ID_NO_ITEM,
				'item_template_id' => $itemTemplateId,
				'item_num' => $itemNum,
			);
			if ( $itemQuality  == ItemDef::ITEM_QUALITY_RED && $isSendRed)
			{
				self::sendItemRedQuality($user, $chatTemplateIdRed, $itemInfo);
			}
			else if ( $itemQuality >= ItemDef::ITEM_QUALITY_PURPLE )
			{
				self::sendItemPurpleQuality($user, $chatTemplateIdPurple, $itemInfo);
			}
		}

		foreach ( $items[ChatDef::CHAT_ITEM_NOT_STACKABLE] as $itemId )
		{
			$item = ItemManager::getInstance()->getItem($itemId);
			if ( $item === NULL )
			{
				continue;
			}
			$itemQuality = $item->getItemQuality();
			$itemInfo = $item->itemInfo();

			if ( $itemQuality  == ItemDef::ITEM_QUALITY_RED && $isSendRed)
			{
				self::sendItemRedQuality($user, $chatTemplateIdRed,  $itemInfo);
			}
			else if ( $itemQuality >= ItemDef::ITEM_QUALITY_PURPLE )
			{
				self::sendItemPurpleQuality($user, $chatTemplateIdPurple, $itemInfo);
			}
		}
	 */}

	private static function sendItemRedQuality($user, $chat_template_id, $itemInfo)
	{
/* 		$message = self::makeMessage($chat_template_id,
			array (
				$user,
				$itemInfo,
			) 
		);
		//TODO没有阵营了，暂时先发世界消息
		ChatLogic::sendSystem( ChatDef::CHAT_SYS_UID , $message );
		*/
	}

	private static function sendItemPurpleQuality($user, $chat_template_id, $itemInfo)
	{
/* 		$message = self::makeMessage($chat_template_id,
			array (
				$user,
				$itemInfo,
			)
		);
		ChatLogic::sendSystem(ChatDef::CHAT_SYS_UID, $message); */
	}

	public static function makeMessage($templateId, $tempalteData)
	{
		return array (
			ChatDef::CHAT_TEMPLATE_ID_NAME => $templateId,
			ChatDef::CHAT_TEMPLATE_DATA_NAME => $tempalteData,
		);
	}
	
	/**
	 *
	 * VIP等级升级
	 * @param array $vipInfo			VIP用户
	 * <code>
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid

	 * }
	 * @param int $vipLevel				用户vip等级
	 * @return NULL
	 */
	public static function sendSysVipLevelUp1($vipInfo, $vipLevel)
	{
		return ;
		$message = self::makeMessage(ChatTemplateID::MSG_VIPLEVEL_UP1, 
							array($vipInfo,
								  array('vip' => $vipLevel)
								 )
							);
		ChatLogic::sendSystem($message);
	}
	
	/**
	 *
	 * VIP等级升级（公告）
	 * @param array $vipInfo			VIP用户
	 * <code>
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $vipLevel				用户vip等级
	 * @return NULL
	 */
	public static function sendBroadcastVipLevelUp2($vipInfo, $vipLevel)
	{
		return;
		$message = self::makeMessage(ChatTemplateID::MSG_VIPLEVEL_UP2, 
							array($vipInfo,
								  array('vip' => $vipLevel)
								  )
							);
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
		//ChatLogic::sendSystem(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 *  招募到高星级武将
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $htid
	 */
	public static function sendEmployHero( $user, $htid )
	{
		return;
		$tempalteData = array(
				$user,
				array(
						'htid' => $htid,
		),
		);
		$message = self::makeMessage( 
				ChatTemplateID::MSG_HERO_EMPLOY, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	/**
	 * 使用积分获得高星级武将
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $htid
	 */
	public static function sendEmployHeroIntegral( $user, $htid )
	{
		return;
		$tempalteData = array(
				$user,
				array(
						'htid' => $htid,
				),
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_HERO_EMPLOY_INTERGRAL, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 使用武魂获得高星级武将
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $htid
	 */
	public static function sendEmployHeroFragment( $user, $htid )
	{
		return;
		$tempalteData = array(
				$user,
				array(
						'htid' => $htid,
				),
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_HERO_EMPLOY_FRAGMENT, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 竞技场第一名改变
	 * @param array $fromUser			原来的第一名
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $toUser				现在的第一名
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 */
	public static function sendArenaTopChange( $fromUser, $toUser )
	{
		return;
		$tempalteData = array(
				$fromUser,
				$toUser,
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_ARENA_TOP_CHANGE, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 武将转生
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $htid
	 * @param int $num					武将转生的等级（转生次数）
	 */
	public static function sendHeroEnvolve( $user, $htid, $num )
	{
		return;
		$tempalteData = array(
				$user,
				array( 'htid' => $htid ),
				array( 'times' => $num ),
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_HERO_ENVOLVE, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 玩家与名将好感度达到某心数
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $starTid				名将模板id
	 * @param int $heartNum				现在的心数
	 */
	public static function sendStarFavor( $user, $starTid, $heartNum)
	{
		return;
		$tempalteData = array(
				$user,
				array( 'star_tid' => $starTid ),
				array( 'times' => $heartNum ),
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_STAR_FAVOR, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 主英雄卡牌进化
	 * @param unknown $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $nowMasterHeroStar	现在的主角卡牌星级
	 */
	public static function sendMasterHeroEnforce( $user, $nowMasterHeroStar )
	{
		return ;
		$tempalteData = array(
				$user,
				array( 'star_num' => $nowMasterHeroStar ),
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_MASTER_HERO_ENFORCE, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 英雄进阶 16.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $htid					进阶的英雄的htid
	 * @param int $evolveLv				进阶后多少级
	 */
	public static function sendHeroEvolve( $user, $htid, $evolveLv )
	{
		if( $evolveLv < 5 )
		{
			return ;
		}
		$tempalteData = array(
				$user,
				array( 'evolveLv' => $evolveLv ),
				array( 'htid' => $htid ),
		);
		$message = self::makeMessage(
				ChatTemplateID::MSG_HERO_EVOLVE, $tempalteData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
		
	}
	/**
	 * 招将广播 17. 18.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $heroArr			获得的所有英雄（也可以只传紫色英雄）
	 * {
	 * 		htid => num,
	 * 		htid => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 * @param int $mode					招将模式：金银铜@see ShopDef
	 * @param bool $isTenrecruit		是否十连抽		true OR false
	 */
	public static function sendRecruitHero( $user, $heroArr, $mode, $isTenrecruit )
	{
		if( $mode != ShopDef::RECRUIT_TYPE_BRONZE && $mode!= ShopDef::RECRUIT_TYPE_SILVER 
		&& $mode != ShopDef::RECRUIT_TYPE_GOLD )
		{
			throw new FakeException( 'no such mode: %d', $mode ); 
		}
		if ( $isTenrecruit )
		{
			$templateId = ChatTemplateID::MSG_HERO_RECRUIT_TEN;
		}
		else 
		{
			$templateId = ChatTemplateID::MSG_HERO_RECRUIT;
		}
		$heroArrPurple = self::getPurpleHero( $heroArr );
		if ( empty( $heroArrPurple ) )
		{
			return ;
		}
		if ( $isTenrecruit )
		{
			$templateData = array(
					$user,
					$heroArrPurple,
			);
		}
		else 
		{
			$templateData = array(
					$user,
					$heroArrPurple,
					array( 'mode' => $mode ),
			);
		}
		
		$message = self::makeMessage( $templateId, $templateData );
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	/**
	 * 翻牌相关广播 19. 20. 21.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $itemArr			获得的所有物品（也可以只传紫色）
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 * @param int $module				翻牌模块 @see FlopDef
	 */
	public static function sendFlopItem( $user, $itemArr, $module )
	{
		return;
		
		switch ( $module )
		{
			case FlopDef::FLOP_TYPE_ARENA:
				$templateId = ChatTemplateID::MSG_FLOP_ARENA;
				break;
			case FlopDef::FLOP_TYPE_COMPETE :
				$templateId = ChatTemplateID::MSG_FLOP_COMPETE;
				break;
			case FlopDef::FLOP_TYPE_FRAGSEIZE :
				$templateId = ChatTemplateID::MSG_FLOP_FRAGSEIZE;
				break;
			default:
				Logger::fatal('invalid sendflop type:%s ', $module );
				break;
		}
		$itemArrPurple = self::getPurpleItem($itemArr);
		if ( empty( $itemArrPurple ) )
		{
			return ;
		}
		
		$templateData = array(
				$user,
				$itemArrPurple,
		);
		
		$message = self::makeMessage($templateId, $templateData);
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 副本天降宝物 22.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $itemArr			获得的所有物品（也可以只传紫色）
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 */
	public static function sendGodGiveItem( $user, $itemArr )
	{
		return;
		$itemArrPurple = self::getPurpleItem($itemArr);
		if ( empty( $itemArrPurple ) )
		{
			return ;
		}
		$templateId = ChatTemplateID::MSG_COPY_GODGIVE;
		$templateData = array(
				$user,
				$itemArrPurple,
		);
		
		$message = self::makeMessage($templateId, $templateData);
		
		ChatLogic::sendBroadCast(ChatDef::CHAT_SYS_UID, $message);
	}
	
	/**
	 * 背包打开宝箱获得物品 23.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param int $boxId				箱子id
	 * @param array $itemArr
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 */
	public static function openBox( $user, $boxID, $itemArr )
	{
		return;
		$itemArrPurple = self::getPurpleItem($itemArr);
		if ( empty( $itemArrPurple ) )
		{
			return ;
		}
		$templateId = ChatTemplateID::MSG_BOX_ITEM;
		$templateData = array(
				$user,
				$itemArrPurple,
				array( 'box' => $boxID ),
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	
	/**
	 * 占星领取奖励获得物品 24.
	  * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $itemArr			获得的所有物品（也可以只传紫色）
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 */
	public static function getDiviItem( $user, $itemArr )
	{
		return;
		$itemArrPurple = self::getPurpleItem($itemArr);
		if ( empty( $itemArrPurple ) )
		{
			return ;
		}
		$templateId = ChatTemplateID::MSG_DIVI_ITEM;
		$templateData = array(
				$user,
				$itemArrPurple,
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	/**
	 * 打开首充礼包获得的物品和银币 25.
	  * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $itemArr			获得的所有物品
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 * @param int $silverNum			获得的银币
	 */
	public static function firstTopupPack( $user, $silverNum, $itemArr )
	{
		return;
		$templateId = ChatTemplateID::MSG_FIRSTTOP_REWARD;
		$templateData = array(
				$user,
				$itemArr,
				array( 'silver' => $silverNum ),
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	
	/**
	 * 副本掉落物品 26.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $itemArr			获得的所有物品（也可以只传紫色）
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 */
	public static function sendCopyDropItem( $user, $itemArr )
	{
		return;
		$itemArrPurple = self::getPurpleItem($itemArr);
		if ( empty( $itemArrPurple ) )
		{
			return ;
		}
		$templateId = ChatTemplateID::MSG_COPY_DROP_ITEM;
		$templateData = array(
				$user,
				$itemArrPurple,
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	
	/**
	 * 副本宝箱开启获得物品 27.
	 * @param array $user
	 * {
	 * 		'uid':int					用户uid
	 * 		'uname':string				用户uname
	 * 		'utid':int					用户utid
	 * }
	 * @param array $itemArr			获得的所有物品（也可以只传紫色）
	 * {
	 * 		item_templateId => num,
	 * 		item_templateId => num,
	 * 		.
	 * 		.
	 * 		.
	 * }
	 */
	public static function sendCopyBoxItem( $user, $itemArr )
	{
		return ;
		$itemArrPurple = self::getPurpleItem($itemArr);
		if ( empty( $itemArrPurple ) )
		{
			return ;
		}
		$templateId = ChatTemplateID::MSG_COPY_BOX_ITEM;
		$templateData = array(
				$user,
				$itemArrPurple,
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	
	public static function sendBossComing( $bossId )
	{
		$templateId = ChatTemplateID::MSG_BOSS_COMING;
		$templateData = array(
				array( 'bossId' => $bossId ),
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	
	public static function sendBossStart( $bossId )
	{
		$templateId = ChatTemplateID::MSG_BOSS_START;
		$templateData = array(
				array( 'bossId' => $bossId ),
		);
		$message = self::makeMessage( $templateId , $templateData );
		ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
	}
	
	public static function sendBossResult( $bossId, $topThreeAndKiller )
	{
		if ( $topThreeAndKiller['killer'][0] != 0 )
		{
			$userKill = EnUser::getUserObj( $topThreeAndKiller['killer'][0] );
			$killerInfo = $userKill->getTemplateUserInfo();
			$killerInfo['bossId'] = $bossId;
			$templateId = ChatTemplateID::MSG_BOSS_KILL;
			$templateData = $killerInfo;
			$message = self::makeMessage( $templateId , $templateData );
			ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
		}
		
		$tpldata = array();
		foreach ( $topThreeAndKiller['rank'] as $key => $val )
		{
			if ( $val[0] != 0 )
			{
				$userRank = EnUser::getUserObj( $val[0] );
				$rankerInfo = $userRank->getTemplateUserInfo();
				$rankerInfo['rank'] = $key;
				$rankerInfo['percent'] = $val[1];
				$tpldata[] = $rankerInfo;
			}
		}
		if (!empty( $tpldata ))
		{
			$templateId = ChatTemplateID::MSG_BOSS_RANK;
			$templateData = $tpldata;
			$message = self::makeMessage( $templateId , $templateData );
			ChatLogic::sendBroadCast( ChatDef::CHAT_SYS_UID , $message);
		}
		
		Logger::debug('boss result is %d , %s',$bossId, $topThreeAndKiller);
	}
	
	public static function getPurpleHero( $heroArr )
	{
		foreach ( $heroArr as $htid => $num )
		{
			$quality = HeroLogic::getHeroQualityByHtid($htid);
			if ( $quality < HERO_QUALITY::PURPLE_HERO_QUALITY )
			{
				unset( $heroArr[ $htid ] );
			}
		}
		return $heroArr;
	}
	
	public static function getPurpleItem( $itemArr )
	{
		$itemMgr =  ItemManager::getInstance();
		foreach ( $itemArr as $tplId => $num )
		{
			$itemType = $itemMgr->getItemType( $tplId );
			$quality = $itemMgr->getItemQuality($tplId);
			
			if ( $quality < ItemDef::ITEM_QUALITY_PURPLE || $itemType == ItemDef::ITEM_TYPE_GOODWILL )
			{
				unset( $itemArr[ $tplId ] );
			}
		}
		return $itemArr;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */