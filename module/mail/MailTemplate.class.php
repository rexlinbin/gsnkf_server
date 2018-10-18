<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: MailTemplate.class.php 253092 2016-07-25 03:30:54Z QingYao $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mail/MailTemplate.class.php $
 * @author $Author: QingYao $(jhd@babeltime.com)
 * @date $Date: 2016-07-25 03:30:54 +0000 (Mon, 25 Jul 2016) $
 * @version $Revision: 253092 $
 * @brief
 *
 **/

class MailTemplate
{
	
	/**
	 *
	 * 资源矿到期
	 *
	 * @param int $recieverUid				接受者id
	 * @param int $silver					收获的silver
	 * @param int $gatherTime				采集的时间
	 *
	 * @return NULL
	 */
	public static function sendMineralDue($recieverUid, $silver, $gatherTime,$guildSilver =0,$iron=0)
	{
		$mailTemplateId = MailTemplateID::MINERAL_DUE;
		$mailTemplateData = array (
				$gatherTime,
				$silver,
				$guildSilver,
				$iron,
		);
		MailLogic::sendMineralMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	
	/**
	 *
	 * 资源矿抢夺者
	 *
	 * @param int $recieverUid					接受者id
	 * @param array $occupy						占领者信息
	 * <code>
	 * {
	 * 		'uid':int							占领者id
	 * 		'uname':string						占领者name
	 * 		'utid':int							占领者utid
	 * }
	 * </code>
	 * @param int $replayId						战斗录像id
	 * @param boolean $isSuccess				是否攻击成功
	 *
	 * @return NULL
	 */
	public static function sendMineralAttack($recieverUid, $occupy, $replayId, $isSuccess)
	{
		$mailTemplateId = $isSuccess ? MailTemplateID::MINERAL_ATK_SUCCESS : MailTemplateID::MINERAL_ATK_FAIL;
		$mailTemplateData = array (
				$occupy
		);
		MailLogic::sendMineralMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $replayId);
	}
	

	
	/**
	 *
	 * 资源矿防守者
	 *
	 * @param int $recieverUid				接受者id
	 * @param array $attacker				攻击者信息
	 * <code>
	 * {
	 * 		'uid':int						攻击者uid
	 * 		'uname':string					攻击者uname
	 * 		'utid':int						攻击者utid
	 * }
	 * </code>
	 * @param int $replayId					战斗录像id
	 * @param boolean $isSuccess			是否防守成功,当为TRUE，后两项忽略
	 * @param int $gatherTime				采集资源时间,default = 0
	 * @param int $silver					采集收获的silver, default = 0
	 */
	public static function sendMineralDefend($recieverUid, $attacker, $replayId, $isSuccess,$domainType, $gatherTime = 0, $silver = 0,$guildsilver=0,$iron=0)
	{
		$mailTemplateId = $isSuccess ? MailTemplateID::MINERAL_DFD_SUCCESS : MailTemplateID::MINERAL_DFD_FAIL;
		$mailTemplateData = array (
				$attacker
		);
		if ( $isSuccess == FALSE )
		{
			$mailTemplateData[] = array(
					'gather_time' => $gatherTime,
			);
			$mailTemplateData[] = $silver;
		}
		$mailTemplateData[] = array(
				'domain_type' => $domainType,
		);
		if ($isSuccess==FALSE)
		{
			$mailTemplateData[]=$guildsilver;
			$mailTemplateData[]=$iron;
		}
		MailLogic::sendMineralMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $replayId);
	}
	
	/**
	 * 资源矿被强行掠夺
	 * @param int $recieverUid				接收者uid
	 * @param array $occupier
	 * <code>
	 * {
	 * 		'uid':int						强占者uid
	 * 		'uname':string					强占者uname
	 * 		'utid':int						强占者utid
	 * }
	 * </code>
	 * @param bool $issuccess				防守是否成功( true时 后两项忽略 )
	 * @param int domainType
	 * @param int $gatherTime				采集时间
	 * @param int $silver					获得银币
	 */
	public static function sendMineralOccupyForce( $recieverUid, $occupier, $replayId, $issuccess,$domainType, $gatherTime = 0, $silver = 0,$guildsilver=0,$iron=0)
	{
		$mailTemplateId = $issuccess? MailTemplateID::MINERAL_FORCE_DFD_SUCCESS : MailTemplateID::MINERAL_FORCE_DFD_FAIL;
		$mailTemplateData[] = $occupier;
		if ( !$issuccess )
		{
			$mailTemplateData[] = array(
					'gather_time' => $gatherTime,
			);
			$mailTemplateData[] = $silver;
		}
		$mailTemplateData[] = array(
				'domain_type' => $domainType,
		);
		if (!$issuccess)
		{
			$mailTemplateData[] =$guildsilver;
			$mailTemplateData[]=$iron;
		}
		
		MailLogic::sendMineralMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $replayId);
	}
	
	/**
	 * 强行掠夺别人资源矿
	 * @param int $recieverUid				接收者uid
	 * @param array $defender
	 * <code>
	 * {
	 * 		'uid':int						防守者uid
	 * 		'uname':string					防守者uname
	 * 		'utid':int						防守者utid
	 * }
	 * </code>
	 * @param bool $issuccess				是否成功 true为成功
	 */
	public static function sendMineralOccupyForceAtk($recieverUid, $defender, $issuccess )
	{
		$mailTemplateId = $issuccess? MailTemplateID::MINERAL_FORCE_ATK_SUCCESS : MailTemplateID::MINERAL_FORCE_ATK_FAIL;
		$mailTemplateData[] = $defender;
		
		MailLogic::sendMineralMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 资源矿保底收益
	 * @param int $recieverUid				接收者uid
	 * @param array $occupier				抢夺者信息
	 * <code>
	 * {
	 * 		'uid':int						防守者uid
	 * 		'uname':string					防守者uname
	 * 		'utid':int						防守者utid
	 * }
	 * @param int $seconds					占领了多少时间
	 * @param int $silver					获得收益银币
	 * </code>
	 */
	public static function sendMineralOneHour($recieverUid, $occupier, $seconds, $silver,$guildsilver,$domainType, $replayId,$iron=0 )
	{
		$mailTemplateId = MailTemplateID::MINERAL_ONE_HOUR;
		$mailTemplateData[] = $occupier;
		$mailTemplateData[] = $seconds;
		$mailTemplateData[] = $silver;
		$mailTemplateData[] = $domainType;
		$mailTemplateData[]=$guildsilver;
		$mailTemplateData[]=$iron;//这个邮件是策划说的不发精铁数的。现在又要发
		
		MailLogic::sendMineralMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData,  $replayId);
	}
	
	
	/**
	 *
	 * 获得竞技场幸运奖励
	 *
	 * @param int $recieverUid					接受者id
	 * @param int $arenaTurnNum					竞技场轮数
	 * @param int $arenaPosition				竞技场排名
	 * @param int $goldNum						奖励金币数量
	 * <code>
	 * [
	 * 		item_template_id:item_template_num
	 * ]
	 * </code>
	 * @return NULL
	 */
	//TODO
	public static function sendArenaLuckyAward($recieverUid, $arenaTurnNum, $arenaPosition, $goldNum )
	{
		$mailTemplateId = MailTemplateID::ARENA_LUCKY_AWARD;
	
		$mailTemplateData = array (
				array (
						'arena_turn_num' => $arenaTurnNum,
				),
				array (
						'arena_position' => $arenaPosition,
				),
				array(
						'gold' => $goldNum,
				),
		);
	
			MailLogic::sendSysMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	
	/**
	 *
	 * 获得竞技场奖励
	 *
	 * @param int $recieverUid					接受者id
	 * @param int $arenaTurnNum					竞技场轮数
	 * @param int $arenaPosition				竞技场排名
	 * @param int $soul							将魂
	 * @param int $silver						银币
	 * @param int $itemTemplates				物品
	 * @param int $gold							金币
	 *
	 * @return NULL
	 */
	public static function sendArenaAward($recieverUid, $arenaTurnNum, $arenaPosition,
			$soul, $silver, $prestige, $sendTime,$itemTemplates = array())
	{
		$mailTemplateId = MailTemplateID::ARENA_AWARD;
	
		$mailTemplateData = array (
				array (
						'arena_turn_num' => $arenaTurnNum,
				),
				array (
						'arena_position' => $arenaPosition,
				),
				$prestige,
				$silver,
				$soul,
				array(
					'send_time' => $sendTime,
				),
		);
		foreach ( $itemTemplates  as $itemTmpId => $itemNum )
		{
			$mailTemplateData[] = array (
					'item_template_id'	=> $itemTmpId,
					'item_number' => $itemNum
			);
		}
		MailLogic::sendSysMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 竞技场被挑战，防守成功或失败
	 * @param int $recieverUid				接收者uid
	 * @param array $challenger				挑战者信息
	 * <code>
	 * {
	 * 		'uid':int						挑战者uid
	 * 		'uname':string					挑战者uname
	 * 		'utid':int						挑战者utid
	 * }
	 * </code> 
	 * @param bool $issuccess				是否防守成功(是防守哦，小明)true时忽略参数$positionNow
	 * @param int $positionNow				现在的排名
	 * @param string $replayId				战报id
	 * 
	 */
	public static function sendArenaDefend( $recieverUid, $challenger, $issuccess, $positionNow, $replayId, $robSilver = 0 )
	{
		if ( $issuccess && $robSilver > 0 )
		{
			throw new InterException( 'defend successfully, cant be robbed' );
		}
		elseif ( $issuccess ) 
		{
			$mailTemplateId = MailTemplateID::ARENA_DFD_SUCCESS;
			$mailTemplateData[] = $challenger;
		}
		elseif ( !$issuccess && $robSilver > 0 )
		{
			$mailTemplateId = MailTemplateID::ROB_ARENA;
			$mailTemplateData[] = $challenger;
			$mailTemplateData[] = array( 'silver' => $robSilver );
			$mailTemplateData[] = array( 'rank' => $positionNow );
		}
		else 
		{
			$mailTemplateId =  MailTemplateID::ARENA_DFD_FAIL;
			$mailTemplateData[] = $challenger;
			$mailTemplateData[] = array( 'arena_position' => $positionNow,);
		}
		MailLogic::sendBattleMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $replayId );
	}
	
	/**
	 * 竞技场防守失败，但名次未变 31
	 * @param int $recieverUid 收信人id
	 * @param array $challenger
	 * <code>
	 * {
	 * 		'uid':int						挑战者uid
	 * 		'uname':string					挑战者uname
	 * 		'utid':int						挑战者utid
	 * }
	 * </code> 
	 * @param int $replayId 战报id
	 * @param int $robSilver 被掠夺的银币
	 */
	public static function sendArenaRankNotchange( $recieverUid, $challenger, $replayId, $robSilver = 0 )
	{
		$mailTemplateData[] = $challenger;
		if ( empty( $robSilver ) )
		{
			$mailTemplateId = MailTemplateID::ARENA_RANK_NOTCHANGE;
		}
		else 
		{
			$mailTemplateId = MailTemplateID::ARENA_RANK_NOTCHANGEBUTROB;
			$mailTemplateData[] = array( 'silver' => $robSilver );
		}
		
		MailLogic::sendBattleMail($recieverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $replayId );
	}
	
	/**
	 * 好友关系相关的邮件（已经是好友的，发信息请调用sendPlayerMail）
	 * @param int $friendMailType 发送好友邮件的类型（ 申请：11 拒绝：12添加：13 删除：14 ）
	 * @param int $senderUid 邮件发送方
	 * @param int $recieverUid 邮件接收方
	 * @param int $subject 标题
	 * @param int $content 邮件内容
	 */
	public static function sendFriend( $friendMailType, $senderUid ,$recieverUid, $content )
	{
		$vaFriend = array();
		switch ( $friendMailType )
		{
			case FriendDef::APPLY :
				$mailTemplateId = MailTemplateID::FRIEND_APPLY;
				$vaFriend[ 'status' ] = 0;
				break;
			case FriendDef::REJECT :
				$mailTemplateId = MailTemplateID::FRIEND_REJECT;
				break;
			case FriendDef::ADD :
				$mailTemplateId = MailTemplateID::FRIEND_ADD;
				break;
			case FriendDef::DEL :
				$mailTemplateId = MailTemplateID::FRIEND_DEL;
				break;
			default:
				Logger::fatal( 'invalid friendMailType： %d' , $friendMailType );
				break;
		}
		
		return MailLogic::sendFriend( $senderUid, $recieverUid, $mailTemplateId, MailConf::DEFAULT_SUBJECT, $content, $vaFriend );
	}
	/**
	 * 充值
	 * @param int $receiverUid				接收者uid
	 * @param int $chargeNum				充值金额
	 */
	public static function sendCharge( $receiverUid, $chargeNum )
	{
		$mailTemplateId = MailTemplateID::CHARGE;
		$mailTemplateData[] = $chargeNum;
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * vip等级提升
	 * @param int $receiverUid				接收者uid
	 * @param int $vipLevel				现在的vip等级
	 */
	public static function sendVip( $receiverUid, $vipLevel )
	{
		$mailTemplateId = MailTemplateID::VIP_UP;
		$mailTemplateData[] = $vipLevel;
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 夺宝，碎片被抢夺
	 * @param int $recevieUid 	被抢者id
	 * @param int $seizerInfo 	抢夺者信息
	 * <code>
	 * {
	 * 		'uid':int			抢夺者uid	
	 * 		'uname':string		抢夺者uname
	 * 		'utid':int			抢夺者utid
	 * }
	 * </code> 
	 * @param int $fragId		被抢夺的对片id
	 */
	public static function sendFragseize( $recevieUid, $seizerInfo,$fragId, $replayId, $robSilver = 0 )
	{
		if ( $fragId > 0 && $robSilver > 0 )
		{
			$mailTemplateId = MailTemplateID::ROB_FRAGSEIZE;
			$mailTemplateData[] = $seizerInfo;
			$mailTemplateData[] = array( 'fragId' => $fragId );
			$mailTemplateData[] = array( 'silver' => $robSilver );
		}
		elseif ( $fragId > 0 )
		{
			$mailTemplateId = MailTemplateID::FRAG_SEIZE;
			$mailTemplateData[] = $seizerInfo;
			$mailTemplateData[] = array( 'fragId' => $fragId );
		}
		elseif ( $robSilver > 0 ) 
		{
			$mailTemplateId = MailTemplateID::ROB_FRAGSEIZE_SILVER;
			$mailTemplateData[] = $seizerInfo;
			$mailTemplateData[] = array( 'silver' => $robSilver );
		}
		else 
		{
			throw new InterException( 'myself fault or flop' );
		}
		
		MailLogic::sendBattleMail($recevieUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $replayId );
	}
	
	/**
	 * 夺宝掠夺银币
	 * @param int $recevieUid 	被掠者id
	 * @param int $robberInfo 	略夺者信息
	 * <code>
	 * {
	 * 		'uid':int			略夺者uid
	 * 		'uname':string		略夺者uname
	 * 		'utid':int			略夺者utid
	 * }
	 * </code>
	 * @param int $silverNum	银币数量
	 */
	public static function sendFragseizeRob( $recevieUid, $robberInfo, $silverNum, $fragId, $rid )
	{
		$mailTemplateId = MailTemplateID::ROB_FRAGSEIZE;
		$mailTemplateData[] = $robberInfo;
		$mailTemplateData[] = array( 'silver' => $silverNum );
		$mailTemplateData[] = array( 'fragId' => $fragId );
	
		MailLogic::sendBattleMail($recevieUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $rid );
	}
	
	/**
	 * 比武掠夺
	 * @param int $recevieUid 	被掠者id
	 * @param int $robberInfo 	略夺者信息
	 * <code>
	 * {
	 * 		'uid':int			略夺者uid
	 * 		'uname':string		略夺者uname
	 * 		'utid':int			略夺者utid
	 * }
	 * </code>
	 * @param int $silverNum	银币数量
	 * @param int $integral		积分数量
	 */
	public static function sendCompeteRob( $recevieUid, $robberInfo, $silverNum, $integral, $rid )
	{
		if ( $silverNum <= 0 )
		{
			$mailTemplateId = MailTemplateID::ROB_COMPETE_INTEGREL;
		}
		else 
		{
			$mailTemplateId = MailTemplateID::ROB_COMPETE_SILVER;
			$mailTemplateData[] = array( 'silver' => $silverNum );
		}
		$mailTemplateData[] = $robberInfo;
		$mailTemplateData[] = array( 'integral' => $integral );
		
		
		MailLogic::sendBattleMail($recevieUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $rid );
	}

	
	/**
	 * 比武排名奖励
	 * @param int $recevieUid 		接收者id
	 * @param int $rank 			接收者id
	 * @param int $itemTemplates 	奖励物品
	 * <code>
	 * {
	 * 		tplId		=> $num,
	 * 		.
	 * 		.
	 * }
	 * </code>
	 * @param int 	$integral   被夺积分
	 */
	public static function sendCompeteRank( $recevieUid, $rank, $soul, $silver, $gold,$honor,  $itemTemplates = array() )
	{
		$mailTemplateId = MailTemplateID::COMPETE_RANK;
		$mailTemplateData[] = array( 'rank' => $rank );
		$mailTemplateData[] = array( 'soul' => $soul );
		$mailTemplateData[] = array( 'silver' => $silver );
		$mailTemplateData[] = array( 'gold' => $gold );
		$mailTemplateData[] = array( 'honor' => $honor );
		foreach ( $itemTemplates  as $itemTmpId => $itemNum )
		{
			$mailTemplateData[] = array (
					'item_template_id'	=> $itemTmpId,
					'item_number' => $itemNum
			);
		}
		MailLogic::sendSysMail($recevieUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 军团申请 被接受或被拒绝
	 * @param array $receiverUid		接收者uid
	 * @param array $guildInfo			军团信息
	 * {
	 * 		'guild_id' => int,			军团id
	 * 		'guild_name' => string,		军团名字
	 * }
	 * @param bool $accept 				true 被接受 false 被拒绝
	 */
	public static function sendGuildResponse( $receiverUid, $guildInfo, $accept = true )
	{
		$mailTemplateId = $accept? MailTemplateID::GUILD_ACCEPT:MailTemplateID::GUILD_REJECT;
		$mailTemplateData[] = $guildInfo;
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 被踢出公会
	 * @param int $receiverUid			接收者uid
	 * @param array $guildInfo			军团信息
	 * {
	 * 		'guild_id' => int,			军团id
	 * 		'guild_name' => string,		军团名字
	 * }
	 * @param array $kickerInfo			踢人者信息
	 * {
	 * 		'uid' => int,
	 * 		'uname' => str,
	 * 		'utid' => int,
	 * }
	 */
	public static function sendGuildKick( $receiverUid, $guildInfo, $kickerInfo )
	{
		$mailTemplateId = MailTemplateID::GUILD_KICK;
		$mailTemplateData[] = $guildInfo;
		$mailTemplateData[] = $kickerInfo;
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 公会成员切磋
	 * @param int $receiverUid 		接收者uid
	 * @param bool $isSuccess		切磋成功还是失败
	 * @param array $atkerInfo		切磋的对方的信息
	 * {
	 * 		'uid' => int,
	 * 		'uname' => str,
	 * 		'utid' => int,
	 * }
	 * @param int $brid				战报信息
	 * 
	 */
	public static function sendGuildVersus( $receiverUid, $isSuccess,$atkerInfo, $brid )
	{
		$mailTemplateId = $isSuccess ? MailTemplateID::GUILD_COMPETE_SUCCESS : MailTemplateID::GUILD_COMPETE_FAIL;
		$mailTemplateData[] = $atkerInfo;
		MailLogic::sendBattleMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $brid);
	}
	
	/**
	 * 城池战奖励（是发奖的时候，不是玩家领奖的时候，也就是奖励结算的时候）
	 * @param int $receiverUid			接收者id
	 * @param int $cityId				城池的id
	 * @param int $member_type			职务
	 */
	public static function sendCityWarReward($receiverUid, $cityId, $member_type, $reward = array())
	{
		$mailTemplateData[] = array('cityId' => $cityId,);
		$mailTemplateData[] = array('memberType' => $member_type,);
		$mailTemplateId = MailTemplateID::CITY_WAR_REWARD;
		//$mailTemplateData[] = array('reward' => $reward,);
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 
	 * @param 类型 $type，详见MailTemplateID::MINERAL_HELPER_***(共五种)
	 * @param int $receiverUid 	邮件接受者uid
	 * @param int $seconds		占领时间
	 * @param int $silver		获得银币
	 * 
	 * @return boolean
	 */
	public static function sendMineralHelper( $type, $receiverUid, $seconds, $silver, $occupierUid = null)
	{
		$mailTemplateId = $type;
		$mailTemplateData[] = $seconds;
		$mailTemplateData[] = $silver;
		
		if ( 
		$type == MailTemplateID::MINERAL_HELPER_OCCUPY_TIMEUP
		|| $type == MailTemplateID::MINERAL_HELPER_GIVEUP
		
		)
		{
			MailLogic::sendMineralMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
		}
	 	else if(
	 	$type == MailTemplateID::MINERAL_GIVEUP_BYOWNER
	 	|| $type == MailTemplateID::MINERAL_HELPER_BESEIZED
	 	|| $type == MailTemplateID::MINERAL_HELPER_BEOCCUPIED
	 	|| $type == MailTemplateID::MINERAL_HELPER_TIMEUP
	 	)
		{
			if ( empty( $occupierUid ) )
			{
				return;
			}
			$userObj = EnUser::getUserObj($occupierUid);
			$userInfo = $userObj->getTemplateUserInfo();
			$mailTemplateData[] = $userInfo;
			 
			MailLogic::sendMineralMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
		}
		
	}

	public static function sendMineralOwner($receiveUid, $occupyUid, $seconds)
	{
		$userInfo = EnUser::getUserObj($occupyUid)->getTemplateUserInfo();
		$mailTemplateData[] = $userInfo;
		$mailTemplateData[] = $seconds;
		MailLogic::sendMineralMail($receiveUid, MailConf::DEFAULT_SUBJECT, MailTemplateID::MINERAL_HELPER_ANNOUNCE_OWNER, $mailTemplateData);
	}
	
	public static function sendOlympic( $type ,$receiveUid, $rank = -1)
	{	
		$mailTemplateData = array();
		if( $type == MailTemplateID::OLYMP_NORMAL_RANK
		|| $type == MailTemplateID::OLYMP_SECOND
		|| $type == MailTemplateID::OLYMP_FIRST
		|| $type == MailTemplateID::OLYMP_CHEER
		 )
		{
			$mailTemplateData[] = $rank;
		}
		else if($type != MailTemplateID::OLYMP_LUCKY && $type != MailTemplateID::OLYMP_SUPER_LUCKY)
		{
			Logger::fatal('invalid type: %d', $type);
			return;
		}
		
		MailLogic::sendSysMail($receiveUid, MailConf::DEFAULT_SUBJECT, $type, $mailTemplateData);
	}
	
	/**
	 * 
	 * @param unknown $receivceUid
	 * @param unknown $winNum
	 * @param unknown $silverNum
	 */
	public static function sendOlympicPoolBeCut( $receivceUid, $winNum, $silverNum )
	{
		$mailTemplateId = MailTemplateID::OLYMP_POOL_BECUT;
		$mailTemplateData[] = $winNum;
		$mailTemplateData[] = $silverNum;
		MailLogic::sendSysMail($receivceUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	public static function sendOlympicPoolCut( $receivceUid, $winNum, $silverNum, $beCutUserInfo )
	{
		$mailTemplateId = MailTemplateID::OLYMP_POOL_CUT;
		$mailTemplateData[] = $winNum;
		$mailTemplateData[] = $silverNum;
		$mailTemplateData[] = $beCutUserInfo;
		MailLogic::sendSysMail($receivceUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	public static function sendOlympicPoolParticipate( $receivceUid, $winNum, $silverNum, $beCutUserInfo )
	{
		$mailTemplateId = MailTemplateID::OLYMP_POOL_PATICIPATE;
		$mailTemplateData[] = $winNum;
		$mailTemplateData[] = $silverNum;
		$mailTemplateData[] = $beCutUserInfo;
		MailLogic::sendSysMail($receivceUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
		
	}
	
	public static function sendLordwarRank($receiveUid, $round, $teamType, $rank, $db ='')
	{
		if( in_array( $round , LordwarRound::$INNER_ROUND) )
		{
			$field = LordwarField::INNER;
			$mailField = 'INNER';
		}
		elseif ( in_array( $round , LordwarRound::$CROSS_ROUND) )
		{
			$field = LordwarField::CROSS;
			$mailField = 'CROSS';
		}
		else
		{
			throw new FakeException( 'invalid round: $d', $round );
		}
		
		if( $teamType == LordwarTeamType::WIN )
		{
			$teamTypeField = 'WIN';
		}
		elseif ( $teamType == LordwarTeamType::LOSE)
		{
			$teamTypeField = 'LOSE';
		}
		else
		{
			throw new FakeException( 'invalid teamType: %d', $teamType );
		}
		
		$confMgr = LordwarConfMgr::getInstance($field);
		//$rankReward = $confMgr->getConf( 'lordPrize' );
		
		
		$str = 'LORDWAR_'.$mailField.'_'.$teamTypeField.'_';
	
		if( $rank  <= 32 && $rank >= 4  )
		{
			$str.= '4_32';
		}
		elseif( $rank == 2 )
		{
			$str.= '2';
		}
		elseif( $rank == 1 )
		{
			$str.= '1';
		}
		else 
		{
			throw new InterException( 'invalid rank: %d', $rank );
		}
		
		Logger::debug('str are: %s', $str);
		
	 	if( empty(LordwarConf::$MAIL_ID[$str]) )
		{
			throw new InterException( 'str wrong: %s %s %d', $field, $teamType, $rank );
		} 
		$mailTemplateId =  LordwarConf::$MAIL_ID[$str];

		//Logger::debug('lordPrize are: %s',$rankReward);
		/* if( empty( $rankReward[$field][$teamType][$rank] ) )
		{
			throw new ConfigException( 'empty reward for field: %s,teamType:%d, rank: %d ', $field,$teamType,$rank );
		} */
		
		//$reward =  $rankReward[$field][$teamType][$rank];
		$rewarInfo = $confMgr->getReward(LordwarReward::RPOMOTION, $field,$teamType, $rank );
		$mailTemplateData[] = $rank;
		$mailTemplateData[] = $rewarInfo->toArray();
		
		MailLogic::sendSysMail($receiveUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $db);
	}
	
	public static function sendLordwarSupport($receiveUid, $round)
	{
		//发助威奖励一直是在服内发的
		
		if( in_array( $round , LordwarRound::$INNER_ROUND) )
		{
			$mailTemplateId = MailTemplateID::LORDWAR_SUPPORT_INNER;
			$field = LordwarField::INNER;
		}
		elseif ( in_array( $round , LordwarRound::$CROSS_ROUND) )
		{
			$mailTemplateId = MailTemplateID::LORDWAR_SUPPORT_CROSS;
			$field = LordwarField::CROSS;
		}
		else
		{
			throw new InterException( 'invalid round: %d', $round );
		}
		
		$confMgr = LordwarConfMgr::getInstance();
		//$supportReward = $confMgr->getConf( 'supportPrize' );
		
		/* if( empty( $supportReward['supportPrize'][$field] ) )
		{
			Logger::fatal('empty reward round: %s', $round);
			return;
		}
		
		$reward =  $supportReward['supportPrize'][$field]; */
		$rewardInfo = $confMgr->getReward(LordwarReward::SUPPORT, $field);
		$mailTemplateData[] = $rewardInfo->toArray();
		
		//所有奖励都是在服内发所以没有使用db
		MailLogic::sendSysMail($receiveUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 跨服军团战发送战斗结果邮件
	 * 
	 * @param int $receiveUid
	 * @param int $round
	 * @param int $rank
	 * @param bool $isWin
	 * @param string $objGuildName
	 * @param string $objServerName
	 * @param string $db
	 * @throws FakeException
	 */
	public static function sendGuildWarResult($receiveUid, $round, $rank, $isWin, $objGuildName, $objServerName, $db = '')
	{
		if ($round < GuildWarRound::AUDITION) 
		{
			throw new FakeException('MailTemplate::sendGuildWarResultMail failed, wrong round[%d]', $round);
		}
		
		if ($isWin) 
		{
			if ($rank >= 2) 
			{
				$mailTemplateId = MailTemplateID::GUILD_WAR_WIN_NORMAL;

			}
			else 
			{
				$mailTemplateId = MailTemplateID::GUILD_WAR_WIN_FIRST;
				//$mailTemplateData = array($objServerName, $objGuildName);
			}
		}
		else 
		{
			if ($rank > 2) 
			{
				$mailTemplateId = MailTemplateID::GUILD_WAR_LOSE_NORMAL;
				//$mailTemplateData = array($maxRank, $objServerName, $objGuildName);
			}
			else 
			{
				$mailTemplateId = MailTemplateID::GUILD_WAR_LOSE_FIRST;
				//$mailTemplateData = array($objServerName, $objGuildName);
			}
		}
		
		$mailTemplateData = array(
				'rank' => $rank,
				'serverName' => $objServerName,
				'guildName' => $objGuildName);
		
		MailLogic::sendSysMail($receiveUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $db);
	}
	
	/**
	 * 发送助威奖邮件
	 * 
	 * @param int $receiveUid
	 * @param int $round
	 * @param array $rewardArr
	 */
	public static function sendGuildWarSupportReward($receiveUid, $round, $rewardArr)
	{
		$maxRank = GuildWarConf::$round_rank[$round];
		$mailTemplateId = MailTemplateID::GUILD_WAR_REWARD_SUPPORT;
		$mailTemplateData = array( 
				'rewardArr' => $rewardArr,
				'maxRank' => $maxRank,
		 );
		MailLogic::sendSysMail($receiveUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);		
	}
	
	/**
	 * 发送排名奖邮件
	 * 
	 * @param int $receiveUid
	 * @param int $round
	 * @param int $rank
	 * @param array $rewardArr
	 * @param string $db
	 */
	public static function sendGuildWarRankReward($receiveUid, $round, $rank, $rewardArr, $db ='')
	{
		if ($rank == 1) 
		{
			$mailTemplateId = MailTemplateID::GUILD_WAR_REWARD_FIRST;
		}
		else if ($rank == 2) 
		{
			$mailTemplateId = MailTemplateID::GUILD_WAR_REWARD_SECOND;
		}
		else 
		{
			$mailTemplateId = MailTemplateID::GUILD_WAR_REWARD_NORMAL;
		}
		
		$mailTemplateData = array( 
				'rank' => $rank,
				'rewardArr' => $rewardArr,
		 );
		MailLogic::sendSysMail($receiveUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData, $db);
	}
	
	/**
	 * 67抢夺前通知一下
	 * @param int $robberGuildId	发起抢夺的公会id
	 * @param int $lampGuildId		被抢夺的公会的id
	 * @param int $seconds			还有多久就开始抢夺的秒数
	 */
	public static function guildrobNotice( $robberGuildId, $lampGuildId, $seconds )
	{
		//RPCContext::getInstance()->executeTask(SPECIAL_UID::GUILD_ROB, 'mail.guildrobNotice', array($robberGuildId, $lampGuildId, $seconds ));
		//Util::asyncExecute( 'mail.guildrobNotice' , array($robberGuildId, $lampGuildId, $seconds ));

		try
		{
			$guildNameArr = EnGuild::getArrGuildInfo( array( $robberGuildId ), array(GuildDef::GUILD_NAME) );
			$robberGuildName = $guildNameArr[$robberGuildId][GuildDef::GUILD_NAME];
			$mailTemplateId = MailTemplateID::GUILD_ROB_NOTICE;
			$mailTemplateData[] = array( 'guildName' => $robberGuildName );
			$mailTemplateData[] = array( 'seconds' => $seconds );
				
			$lampMemberList = EnGuild::getMemberList($lampGuildId, array(  GuildDef::USER_ID ));
			foreach ( $lampMemberList as $memberUid => $memberInfo )
			{
				MailLogic::sendSysMail($memberUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
			}
				
		}
		catch ( Exception $e )
		{
			Logger::fatal('send guildrobNotice failed robberGuildId: %s lampGuildId: %s second: %s ',$robberGuildId,$lampGuildId, $seconds );
		}
		
	}
	
	/**
	 * 68 分粮
	 * @param int $receiverUid  接受者uid	
	 * @param int $guildRole 	职位
	 * @param int $grainNum	得到的粮草数量
	 */
	public static function distributeGrain( $receiverUid, $guildRole, $grainNum )
	{
		$mailTemplateId = MailTemplateID::GUILD_DISTRIBUTE_GRAIN;
		$mailTemplateData[] = array ( 'guildRole' => $guildRole);
		$mailTemplateData[] = array( 'grainNum' => $grainNum );
		
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 69. 抢夺结束后抢夺方获得的东西
	 * @param int $receiverUid	接收者uid
	 * @param string $lambGuildName	被抢的军团名字
	 * @param int $grainNum	抢到的粮草
	 * @param int $meritNum	获得的功勋
	 * @param int $guildGainGrainNum 公会获得的粮草
	 */
	public static function endGuildRobRobber( $receiverUid, $lambGuildName, $grainNum, $meritNum, $guildGainGrainNum  )
	{
		$mailTemplateId = MailTemplateID::GUILD_ROB_GAIN;
		$mailTemplateData[] = array( 'lambGuildName' => $lambGuildName );
		$mailTemplateData[] = array( 'grainNum'=> $grainNum );
		$mailTemplateData[] = array( 'meritNum' => $meritNum );
		$mailTemplateData[] = array( 'guildGainGrainNum' => $guildGainGrainNum );
		
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 *70. 抢夺技术后被抢方的损失
	 * @param int $receiverUid 接收者uid
	 * @param int $robberGuildName 抢夺者军团名字
	 * @param int $grainNum 军团被抢的粮草
	 */
	public static function endGuildRobLamp( $receiverUid, $robberGuildName, $grainNum  )
	{
		$mailTemplateId = MailTemplateID::GUILD_ROB_LOSE;
		$mailTemplateData[] = array( 'robberGuildName' => $robberGuildName );
		$mailTemplateData[] = array( 'grainNum' => $grainNum );
		
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 71.过关斩将排名奖励
	 * @param unknown $receiverUid
	 * @param unknown $rank
	 * @param unknown $rewardArr
	 */
	public static function sendPassRank( $receiverUid, $rank, $rewardArr )
	{
		if( !is_array( $rewardArr ) )
		{
			$rewardArr = $rewardArr->toArray();
		}
		$mailTemplateId = MailTemplateID::PASS_RANK_REWARD;
		$mailTemplateData[] = array( 'rank' => $rank );
		$mailTemplateData[] = array( 'reward' => $rewardArr );
		
		MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}

	/**
	 * 81.木牛流马主公提醒，到达终点
	 * @param unknown $receiverUid
	 */
	public static function sendChargeDartUserFinish( $receiverUid, $reward, $rewarBedRobed )
	{
	    $mailTemplateId = MailTemplateID::CHARGE_DART_FINISH_USER;
	    $mailTemplateData[] = array( 'reward' => $reward );
	    $mailTemplateData[] = array( 'rewardRobed' => $rewarBedRobed );
	
	    MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 82.木牛流马协助者提醒，到达终点
	 * @param unknown $receiverUid
	 */
	public static function sendChargeDartAssistFinish( $receiverUid, $userInfo, $reward )
	{
	    $mailTemplateId = MailTemplateID::CHARGE_DART_FINISH_ASSIST;
	    $mailTemplateData[] = array( 'userInfo' => $userInfo );
	    $mailTemplateData[] = array( 'reward' => $reward );
	
	    MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 83.木牛流马掠夺者提醒，掠夺成功
	 * @param unknown $receiverUid
	 */
	public static function sendChargeDartRobSuccess( $receiverUid, $userInfo, $quality, $reward, $arrBridId)
	{
	    $mailTemplateId = MailTemplateID::CHARGE_DART_ROB_ROBBER;
	    $mailTemplateData[] = array( 'userInfo' => $userInfo );
	    $mailTemplateData[] = array( 'quality' => $quality);
	    $mailTemplateData[] = array( 'reward' => $reward );
	    $mailTemplateData[] = array( 'arrBridId' => $arrBridId);
	
	    MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
	
	/**
	 * 84.木牛流马被掠夺者提醒，掠夺成功
	 * @param unknown $receiverUid
	 */
	public static function sendChargeDartBeRobSuccess( $receiverUid, $userInfo, $quality, $reward, $arrBridId)
	{
	    $mailTemplateId = MailTemplateID::CHARGE_DART_BE_ROBBED;
	    $mailTemplateData[] = array( 'userInfo' => $userInfo );
	    $mailTemplateData[] = array( 'quality' => $quality);
	    $mailTemplateData[] = array( 'reward' => $reward );
	    $mailTemplateData[] = array( 'arrBridId' => $arrBridId);
	    
	    MailLogic::sendSysMail($receiverUid, MailConf::DEFAULT_SUBJECT, $mailTemplateId, $mailTemplateData);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */