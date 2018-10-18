<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FriendLogic.class.php 252542 2016-07-20 08:15:50Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/FriendLogic.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-07-20 08:15:50 +0000 (Wed, 20 Jul 2016) $
 * @version $Revision: 252542 $
 * @brief 
 *  
 **/
class FriendLogic
{

	public static function getAllFriendInfo( $uid )
	{
		$friendRaws =  self::getFriendUidRawInfo( $uid );
		$friendIds = array_keys( $friendRaws );
		$ret = self::friendUidsToInfo( $friendIds );
		$arrGuildId = array(); // 存放好友的公会(军团)id
		$arrHid = array();
		foreach ( $ret as $key => $info )
		{
			if ( !isset( $friendRaws[ $info[ 'uid' ] ] ) )
			{
				throw new FakeException('no love info,userinfo:$s loveinfo: %s', $ret, $friendRaws );
			}
			$ret[ $key ][ 'lovedTime' ] = $friendRaws[ $info[ 'uid' ] ]['lovedTime'];
			// 获取好友的公会(军团)id
			if ( !empty($info['guild_id']) )
			{
				$arrGuildId[] = $info['guild_id'];
			}
			// 获取好友主角的hid
			$arrHid[] = $info['master_hid'];
		}
		// 获得好友的公会(军团)名字数组
		$arrGuildInfo = EnGuild::getArrGuildInfo($arrGuildId,array(GuildDef::GUILD_NAME));
		// 获取好友htid数组
		$arrHtid = HeroUtil::getArrHero($arrHid, array('htid'));
		foreach ($ret as $key => $info)
		{
			if ( !empty($info['guild_id']) )
			{
				$guildId = $info['guild_id'];
				$ret[$key]['guild_name'] = $arrGuildInfo[$guildId]['guild_name'];
			}
			$hid = $info['master_hid'];
			$ret[$key]['htid'] = $arrHtid[$hid];
		}
				
		return $ret;
	}
	
	public static function friendUidsToInfo( $friendUids )
	{
		if ( empty( $friendUids ) )
		{
			return array();
		}
		$arrRet = array();
		$offset = 0;
		$mapUid2User = array();
		//分批从user表中拉取数据
		do
		{
			$arrayPart = array_slice( $friendUids , $offset , FriendCfg::MAX_FRIEND_NUM );
			if ( empty( $arrayPart ) )
			{
				break;
			}
			$arrFields =  array ('uid', 'uname', 'status', 'utid', 'level', 'fight_force', 'guild_id', 'master_hid');
			$mapUid2UserPart = EnUser::getArrUser ( $arrayPart , $arrFields );
			$mapUid2User = $mapUid2User + $mapUid2UserPart;
			$offset += count( $mapUid2UserPart );
		}
		while ( count( $mapUid2User ) < count( $friendUids ) );
		
		//给所有的user添加一个order字段用以排序
		foreach ( $mapUid2User as $key => $val )
		{
			if ( $val[ 'status' ] == UserDef::STATUS_ONLINE )
			{
				$mapUid2User[ $key ][ 'order' ] = UserConf::MAX_LEVEL + 2000;
			}
			else 
			{
				$mapUid2User[ $key ][ 'order' ] = $val[ 'level' ];
			}
		}
		$arrLevel = Util::arrayExtract( $mapUid2User , 'level' );
		$arrOrder = Util::arrayExtract( $mapUid2User , 'order' );
		//array_multisort( $arrOrder , SORT_DESC , $arrLevel , SORT_DESC , $mapUid2User );
		
		usort( $mapUid2User , array( 'friendLogic','cprRecFriend' ));
		
		//排好序了
		$mapUid2User = Util::arrayIndex( $mapUid2User , 'uid' );

		foreach ( $friendUids as $uid )
		{
			if ( !isset ( $mapUid2User [ $uid ] ))
			{
				Logger::fatal ( "user:%d not found in db", $uid );
				continue;
			}
			unset( $mapUid2User [ $uid ]['order'] );
		}
		$arrRet = array_merge( $mapUid2User );
		
		return $arrRet;
	}
	
	public static function getFriendUidRawInfo( $uid )
	{
		$uid = intval( $uid );
		$arrCond = array(
				array( 'uid' , '=' , $uid ) ,
				array( 'status' , '=' , FriendDef::STATUS_OK ),
		);
		$friendList1 = self::_getPartFriendList( $arrCond, 'B' );
		$friendInfo1 =  Util::arrayIndex( $friendList1 , 'fuid' );
		
		$arrCond = array(
				array( 'status' , '=' , FriendDef::STATUS_OK ),
				array( 'fuid' , '=' , $uid )
		);
		$friendList2 = self::_getPartFriendList( $arrCond, 'A' );
		$friendInfo2 =  Util::arrayIndex( $friendList2 , 'uid' );
		
		$friendInfos = $friendInfo1 + $friendInfo2;
		if ( empty( $friendInfos ) )
		{
			return array();
		}
		
		if ( count( $friendInfos ) > FriendCfg::MAX_FRIEND_NUM )
			Logger::warning( 'friend num: %d beyond the max: %d ' , count( $friendInfos ), FriendCfg::MAX_FRIEND_NUM);
		
		return $friendInfos;
	}
	
	public static function _getPartFriendList( $arrCond, $AorB )
	{
		$friendList = array();
		$offset = 0;
		do
		{
			$partFriendList = FriendDao::getFriendList($arrCond, $offset, FriendCfg::MAX_FETCH, $AorB);
			if ( empty( $partFriendList ) )
			{
				break;
			}
			$friendList = array_merge( $friendList , $partFriendList );
			$offset = $offset + count( $partFriendList );
		}
		while ( count( $partFriendList ) == FriendCfg::MAX_FETCH );
		
		return $friendList;
	}
	
	public static function applyFriend( $fromUid , $toUid , $content )
	{
		MailTemplate::sendFriend ( FriendDef::APPLY , $fromUid , $toUid ,$content );
	}
	
	public static function addFriend( $applicantUid , $accepterUid )
	{
		FriendDao::addFriend( $applicantUid, $accepterUid );
		MailTemplate::sendFriend ( FriendDef::ADD , $accepterUid , $applicantUid ,'' );
		RPCContext::getInstance ()->sendMsg ( array (intval($applicantUid) ), 're.friend.newFriend', array () );
	}
	
	public static function rejectFriend( $rejectUid , $beRejectUid )
	{
		MailTemplate::sendFriend ( FriendDef::REJECT , $rejectUid , $beRejectUid ,'' );
	}
	
	public static function delFriend( $delUid , $beDelUid )
	{
		FriendDao::delFriend( $delUid , $beDelUid );
		MailTemplate::sendFriend ( FriendDef::DEL , $delUid , $beDelUid ,'' );
		RPCContext::getInstance()->sendMsg( array( $beDelUid ) , PushInterfaceDef::FRIEND_DEL, array( $delUid ));
	}
	
	public static function recomdFriends( $forUid )
	{
		//获取等级上下限
		$userObj = EnUser::getUserObj( $forUid );
		$userLevel = EnUser::getUserObj( $forUid )->getLevel();
		
		$minLevel = $userLevel > FriendCfg::RECMOD_LEVEL_OFFSET ? 
		( $userLevel - FriendCfg::RECMOD_LEVEL_OFFSET ) : 0;
		
		$maxLevel = $userLevel + FriendCfg::RECMOD_LEVEL_OFFSET > UserConf::MAX_LEVEL ? 
		UserConf::MAX_LEVEL : $userLevel + FriendCfg::RECMOD_LEVEL_OFFSET;
		
		//获取大于最小等级的好友120个
		$offset = 0;
		$friendListRaw = array();
		
		//=================================试一下效果
		$arrFields = array('uid', 'utid', 'uname', 'level', 'status', 'fight_force', 'last_logoff_time');
		$friendListRaw = EnUser::getArrUserBetweenLevel($arrFields, $minLevel, $maxLevel, DataDef::MAX_FETCH);
		//=================================试一下效果
		
		if ( empty( $friendListRaw ) )
		{
			return array();
		}
		
		//获取已经拥有的好友ids
		$myFriendRaw = self::getFriendUidRawInfo( $forUid );
		$myFriendIds = array_keys( $myFriendRaw );
		//过滤掉不符合条件的并为排序准备相关数据
		foreach ( $friendListRaw as $key => $val )
		{
			if ( $val[ 'level' ] > $maxLevel )
			{
				unset( $friendListRaw[ $key ] );
				continue;
			}
			elseif ( $val[ 'uid' ] == $forUid || in_array( $val[ 'uid' ] , $myFriendIds ) )
			{
				unset( $friendListRaw[ $key ] );
				continue;
			}
			else 
			{
				if ( $val['status'] == UserDef::STATUS_ONLINE )
				{
					$friendListRaw[ $key ][ 'last_logoff_time' ] = Util::getTime()+ 10;					
				}
			}
		}
		
		if ( empty( $friendListRaw ) )
		{
			return array();
		}
		//排序
		$arrLogoff = Util::arrayExtract( $friendListRaw , 'last_logoff_time' );
		
		usort( $friendListRaw, array( 'friendLogic', 'cprFriend' ));
		
		$recmodFriends = array_merge( $friendListRaw );
		//滤掉无用数据 
		$recomdFriendsFinal = array();
		$num = 0;
		foreach ( $recmodFriends as $key => $friend )
		{
			$num++;
			$arr[ 'uid' ] = $friend[ 'uid' ];
			$arr[ 'utid' ] = $friend[ 'utid' ];
			$arr[ 'uname' ] = $friend[ 'uname' ];
			$arr[ 'level' ] = $friend[ 'level' ];
			$arr[ 'status' ] = $friend[ 'status' ];
			$arr[ 'fight_force' ] = $friend[ 'fight_force' ];
			$recomdFriendsFinal[ $key ] = $arr;
			if ( $num >= FriendCfg::RECOND_TOTAL_NUM )
			{
				break;
			}
		}
		return $recomdFriendsFinal;
	}
	
	public static function getRecmodByName( $nameLike, $offset = 0 , $limit = CData::MAX_FETCH_SIZE )
	{
		if ( empty( $nameLike ) )
		{
			throw new FakeException( 'namelike should not be empty' );
		}
		$arrFields = array('uid', 'utid', 'uname', 'level', 'status', 'fight_force');
		$arrRet = EnUser::getByFuzzyName( $nameLike, $arrFields, $offset, $limit );
		
		return $arrRet;
		
	}

	public static function reachMaxFrdNum( $uid )
	{
		$friendNum = FriendDao::getFriendCount( $uid );
		if ( $friendNum >= FriendCfg::MAX_FRIEND_NUM )
		{
			return true;
		}
		return false;
	}
	
	public static function getFriendNum($uid)
	{
		$friendNum = FriendDao::getFriendCount( $uid );
		return $friendNum;
	}
	
	public static function isFriend( $uid , $checkUid )
	{
		$friendShip = self::getFriendShip($uid, $checkUid);
		return !empty( $friendShip );
	}
	
	public static function getFriendShip( $uid , $checkUid  )
	{
		return FriendDao::getFriendship( $uid, $checkUid );
	}
	
	/**
	 * 获取自己的所有在线好友的id
	 * @param int $uid
	 */
	public static function getOnlineFriendIds( $ofUid )
	{
		$allFriendInfo = self::getAllFriendInfo( $ofUid );
		$onlineFriendIds= array();
		foreach ( $allFriendInfo as $val )
		{
			if ( $val[ 'status' ] == UserDef::STATUS_ONLINE )
			{
				$onlineFriendIds[] = $val[ 'uid' ];
			}
		}
		
		return $onlineFriendIds;
	}
	
	public static function getFriendInfo( $fuid )
	{
		$friendObj = EnUser::getUserObj ( $fuid );
		$arrFriendInfo = array (
				'uid' => $fuid,
				'uname' => $friendObj->getUname (),
				'utid' => $friendObj->getUtid (),
				'status' => $friendObj->getStatus (),
				'level' => $friendObj->getLevel ()
		);
		return $arrFriendInfo;
	}
	
	public static function cprFriend( $friendA, $friendB )
	{
		$sortKeys = array( 'last_logoff_time','uid' );
		foreach ( $sortKeys as $key )
		{
			if ( $friendA[ $key ] == $friendB[ $key ] )
			{
				return 0;
			}
			return $friendA[ $key ] > $friendB[ $key ]? -1:1;
		}
	}
	
	public static function cprRecFriend( $recFriendA, $recFriendB )
	{
		$sortKeys = array( 'order', 'level','uid' );
		foreach ( $sortKeys as $key )
		{
			if ( $recFriendA[ $key ] == $recFriendB[ $key ] )
			{
				return 0;
			}
			return $recFriendA[ $key ] > $recFriendB[ $key ]? -1:1;
		}
	}
	
	public static function getUnreceiveList()
	{
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		$friendList = self::getFriendUidRawInfo( $uid );
		//只操作自己的
		$friendLoveInst = FriendLoveObj::getInstance();
		$unreceiveList = $friendLoveInst->getAllLove();
		$valove = $unreceiveList['va_love'];
		//多次subUnfriend 也是没问题的
		$needUpdate = false;
		foreach ( $valove as $index => $loveInfo )
			{
				if ( !isset( $friendList[ $loveInfo['uid']  ]) )
				{
					$friendLoveInst->subUnfriendLove( $loveInfo['uid'] );
					$needUpdate = true;
				}
			}
			if ( $needUpdate )
			{
				$friendLoveInst->update();
				$unreceiveList = $friendLoveInst->getAllLove();
			}
		
		
		return $unreceiveList;
	}

	public static function loveFriend( $uid, $fuid )
	{
		//uid要赠送给fuid
		$friendShip = self::getFriendShip($uid, $fuid);
		if( empty( $friendShip ) )
		{
			return 'notfriend';
		}
		//如果被赠送人在A位置
		if ( $fuid == $friendShip['uid'] )
		{
			$lastLoveTime = $friendShip['alove_time'];
		}
		//如果被赠送人在B的位置
		else if ( $fuid == $friendShip['fuid'] )
		{
			$lastLoveTime = $friendShip['blove_time'];
		}
		else 
		{
			throw new InterException( 'user: %d be loved neither in pos A nor B', $fuid );
		}
		
		if ( Util::isSameDay( $lastLoveTime ) )
		{
			throw new FakeException( 'on time: %d, fuid: %d has been loved', $lastLoveTime, $fuid );
		}
		//修改数据阶段，先修改赠送时间，再转出去修改别人的
		if ( $fuid == $friendShip['uid']  )
		{
			$wheres = array(
					array('uid', '=', $fuid),
					array('fuid', '=', $uid),
					array( 'status', '=', FriendDef::STATUS_OK ),
			);
			$vals = array( 'alove_time' => Util::getTime() );
		}
		else 
		{
			$wheres = array(
					array('uid', '=', $uid),
					array('fuid', '=', $fuid),
					array( 'status', '=', FriendDef::STATUS_OK ),
			);
			$vals = array( 'blove_time' => Util::getTime() );
		}
		
		FriendDao::setLoveTime( $wheres, $vals);
		
		Logger::trace('send executeLovedByOther to uid %d',$fuid);
		//给lcserver发消息
		RPCContext::getInstance()->executeTask( $fuid, 'friend.lovedByOther', array( $uid, $fuid ), false );

		return 'ok';
	}
	
	public static function receiveLove( $myuid, $time, $uid )
	{
		$conf = btstore_get()->FRIEND_LOVE;
		
		$user = EnUser::getUserObj();
		$maxStamina = $user->getStaminaMaxNum();
		if ( $user->getStamina() >= $maxStamina)
		{
			throw new FakeException( 'curExe: %d reach the maxLoveExe', $user->getStamina() );
		}
		
		$friendLoveInst = FriendLoveObj::getInstance();
		$allLove = $friendLoveInst->getAllLove();
		
		if ( $allLove['num'] >= $conf[ 'maxReceiveNum'] )
		{
			throw new FakeException( 'num: %d reach max receive num', $allLove['num'] );
		}
		
		$loveExecution = $allLove['va_love'];
		$exist = false;
		foreach ( $loveExecution as $index => $exeInfo )
		{
			if ( $exeInfo['time'] == $time && $exeInfo['uid'] == $uid )
			{
				$exist = true;
				unset( $loveExecution[ $index ] );
			}
		}
		if ( !$exist )
		{
			throw new FakeException( 'loveExe not exist or expired, time: %d, uid: %d', $time, $uid );
		}
		
		$friendLoveInst->addReceiveNum( 1 );
		$friendLoveInst->setVaLove( $loveExecution );
		$user->addStamina( $conf['exePerLove'] );
		
		$friendLoveInst->update();
		$user->update();
	}
	
	public static function receiveAllLove( $uid )
	{
		$conf = btstore_get()->FRIEND_LOVE;
		
		$friendLoveInst = FriendLoveObj::getInstance();
		$allLove = $friendLoveInst->getAllLove();
		//是否今天已经达到领取的上限
		if ( $allLove[ 'num' ] >= $conf['maxReceiveNum'] )
		{
			throw new FakeException( 'num: %d reach max receive num', $allLove['num'] );
		}
		else
		{
			$receiveTimes = $conf['maxReceiveNum'] - $allLove['num'];
		}
		//考虑到好友赠送的体力上限150，还能加多少次
		$user = EnUser::getUserObj();
		$curSta = $user->getStamina();
		$maxSta = $user->getStaminaMaxNum();
		$canAddExe = $maxSta - $curSta;
		if ( $canAddExe <= 0 )
		{
			throw new FakeException( 'reach max loveExe' );
		}
		else 
		{
			//取可以加到上限的次数
			$receiveTimesByExe = ($canAddExe + ( $conf['exePerLove'] - 1 ))/$conf['exePerLove'];
		}
		
		//取两者中较小的来减
		if ( $receiveTimes > $receiveTimesByExe )
		{
			$receiveTimes = $receiveTimesByExe;
			Logger::debug('use receivetimes: %d by curExe', $receiveTimesByExe);
		}
		
		//若获赠体力条数大于我现在可领取的条数，那么领取老的，从前往后unset，并且记录下unset掉的uid
		//此要给好友回赠，处理方式为直接领，不管是不是好友；在回赠的时候如果自己的好友列表里没有了，就直接不回赠
		$needResponseUidArr = array();
		$loveExecution = $allLove['va_love'];
		if ( count( $loveExecution ) > $receiveTimes )
		{
			for ( $i = 0; $i < $receiveTimes; $i++ )
			{
				if ( !isset( $loveExecution[ $i ] ) )
				{
					throw new InterException( 'no index: %d in loveExecution: %s', $i, $loveExecution );
				}
				if ( !in_array( $loveExecution[$i]['uid'], $needResponseUidArr ) )
				{
					$needResponseUidArr[] = $loveExecution[$i]['uid'];
				}
				unset( $loveExecution[ $i ] );
			}
		}
		else 
		{
			//如果获赠条数可以全部领完，则将领取条数置为获赠条数
			$receiveTimes = count( $loveExecution );
			foreach ( $loveExecution as $index => $loveInfo )
			{
				if ( !in_array( $loveInfo['uid'] , $needResponseUidArr) )
				{
					$needResponseUidArr[] = $loveInfo['uid'];
				}
			}
			$loveExecution = array();
		}
		
		//回赠,理论上是不会为空的
		if ( !empty( $needResponseUidArr ) )
		{
			self::responseAll( $uid, $needResponseUidArr );
		}
		
		//添加领取次数、更新获赠体力、添加体力
		$friendLoveInst->addReceiveNum( $receiveTimes );
		$friendLoveInst->setVaLove( $loveExecution );
		$user->addStamina( $receiveTimes * $conf['exePerLove'] );
		
		//更新
		$friendLoveInst->update();
		$user->update();
		
		return array(
			'list' => $loveExecution,
			'receivedNum' => $receiveTimes,
		);
	}
	
	public static function responseAll( $uid, $uidArr )
	{
		//获取以好友id为key的好友列表
		$friendList = self::getFriendUidRawInfo($uid);
		
		$needResponseUids= array();
		$needResponseFuids = array();
		foreach ( $uidArr as $index => $oneUid )
		{
			if ( isset( $friendList[ $oneUid ] ) && !Util::isSameDay($friendList[ $oneUid ]['lovedTime']) )
			{
				if ( $oneUid == $friendList[ $oneUid ]['uid'] )
				{
					//好友在A位置
					$needResponseUids[] = $oneUid;
				}
				else 
				{
					//好友在B位置
					$needResponseFuids[] = $oneUid;
				}
			}
		}
		if ( !empty( $needResponseUids ) )
		{
			$wheres = array( 
					array('uid','IN',$needResponseUids),
					array('fuid','=',$uid),
			);
			$values = array('alove_time' => Util::getTime());
			FriendDao::setLoveTime($wheres, $values);
		}

		if ( !empty( $needResponseFuids ) )
		{
			$wheres = array(
					array('fuid','IN',$needResponseFuids),
					array('uid','=',$uid),
			);
			$values = array('blove_time' => Util::getTime());
			FriendDao::setLoveTime($wheres, $values);
		}
		//转出执行
		$allUids = array_merge( $needResponseUids, $needResponseFuids );
		
		if (count( $allUids ) > 0)
		{
			EnActive::addTask( ActiveDef::LOVE, count( $allUids ) );
		}
		
		foreach ( $allUids as $aUid )
		{
			RPCContext::getInstance()->executeTask( $aUid, 'friend.lovedByOther', array( $uid, $aUid ), false );
		}
		
	}
	
	public static function lovedByOther( $uid, $fuid )
	{
		//$uid送体力给fuid
		$guid = RPCContext::getInstance ()->getUid();
		if ($guid == null)
		{
			RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, $fuid );
		}
		else if ($fuid != $guid)
		{
			Logger::fatal ( 'lovedByOther error, fuid:%d, guid:%d', $fuid, $guid );
			return;
		}
		
		$friendLoveInst = FriendLoveObj::getInstance();
		$veryNew = $friendLoveInst->lovedByOther( $uid );
		$friendLoveInst->update();
		if ( $veryNew )
		{
			RPCContext::getInstance()->sendMsg( array( $fuid ) , PushInterfaceDef::FRIEND_MUCHLOVE, array());
		}
	}
	
	public static function getPkInfo($beuid)
	{
		$uid = RPCContext::getInstance()->getUid();
		
		$pkInst = FriendLoveObj::getInstance();
		$my = $pkInst->getAllLove();
		$friend = FriendDao::getAllLove($beuid);
		
		$ret['isFriend'] = 1;
		$ret['pk_num'] = $my['pk_num'];
		//$ret['bepk_num'] = $my['bepk_num'];
		$ret['friend_bepk_num'] = empty($friend['bepk_num']) ? 0 : $friend['bepk_num'];
		
		$sameFriendInfo = self::getSameFriendPkNum($uid, $beuid);
		if ( $sameFriendInfo == 'notFriend' ) 
		{
			$ret['isFriend'] = 0;
		}
		$ret['sameFriendNum'] = $sameFriendInfo['num'];
		
		return $ret;
	}
	
	public static function getSameFriendPkNum( $uid,$beuid )
	{
		$ret = array();
		
		//要保证只有一个玩家对一个字段操作
		$friendShip = self::getFriendShip($uid, $beuid);

		if ( empty( $friendShip ) )
		{
			return 'notFriend';
		}
		
		if ( $beuid == $friendShip['uid'] )
		{
			if ( !Util::isSameDay( $friendShip['reftime_apk'] ) )
			{
				$friendShip['reftime_apk'] = Util::getTime();
				$friendShip['apk_num'] = 0;
				
			}
			$ret['reftime'] = $friendShip['reftime_apk'];
			$ret['num'] = $friendShip['apk_num'];
			$ret['mark'] = 'A';
			return $ret;
		}
		else if ( $beuid == $friendShip['fuid'] )
		{
			if ( !Util::isSameDay( $friendShip['reftime_bpk'] ) )
			{
				$friendShip['reftime_bpk'] = Util::getTime();
				$friendShip['bpk_num'] = 0;
				
			}
			$ret['reftime'] = $friendShip['reftime_bpk'];
			$ret['num'] = $friendShip['bpk_num'];
			$ret['mark'] = 'B';
			return $ret;
		}
		
	}
	
	
	public static function pkOnce( $uid, $beuid )
	{
		$ret = array();
		$ret['errcode'] = 'success';
		
		$pkInst = FriendLoveObj::getInstance();
		$pkInfo = $pkInst->getAllLove();
		
		$normalConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_FRIEND_PK_NUM];
		
		if ( $pkInfo['pk_num'] >= $normalConf[0] )
		{
			throw new FakeException( 'have no pk num' );
		}
		
		$key = "friend.pk.$beuid";
		$locker = new Locker();
		$locker->lock($key);
		
		try 
		{
			$bePkInfo = FriendDao::getAllLove($beuid);
			$bePkNum = empty($bePkInfo['bepk_num']) ? 0 : $bePkInfo['bepk_num'];
			if ( $bePkNum >= $normalConf[1] )
			{
				throw new FakeException( 'uid:%d have no bepk num',$beuid );
			}
			
			$sameFriend = self::getSameFriendPkNum($uid, $beuid);
			if ( $sameFriend == 'notFriend' || $sameFriend['num'] >= $normalConf[2] )
			{
				//不是朋友了 或者被别人比了
				throw new FakeException( 'uid:%d have no bepk num for this user',$beuid );
			}
			
			$pkInst->addPkNum();
			$user = EnUser::getUserObj($uid);
			$beuser = EnUser::getUserObj($beuid);
			$userBattleFor = $user->getBattleFormation();
			$beuserBattleFor = $beuser->getBattleFormation();
			
			$userFF = $user->getFightForce();
			$beuserFF = $beuser->getFightForce();
			$atkType = EnBattle::setFirstAtk(0, $userFF >= $beuserFF);
			
			$btlRet = EnBattle::doHero( $userBattleFor, $beuserBattleFor, $atkType);
			
			$pkInst->update();//修改自己的数据其实不用锁
			$wheres = array(array('status','=',FriendDef::STATUS_OK));
			$values = array();
			if ( $sameFriend['mark'] == 'A' )
			{
				$wheres[] = array('uid', '=', $beuid);
				$wheres[] = array('fuid', '=', $uid);
				
				$values['apk_num'] = (++$sameFriend['num']);
				$values['reftime_apk'] = $sameFriend['reftime'];
			}
			else 
			{
				$wheres[] = array('fuid', '=', $beuid);
				$wheres[] = array('uid', '=', $uid);
				
				$values['bpk_num'] = (++$sameFriend['num']);
				$values['reftime_bpk'] = $sameFriend['reftime'];
			}
			FriendDao::setSameFriendBepkNum($wheres, $values);
			RPCContext::getInstance()->executeTask($beuid, 'friend.addBepkNumByOther', array($beuid), false);
		}
		catch (Exception $e)
		{
			$ret['errcode'] = 'fail';
			$locker->unlock($key);
			return $ret;
		}
		$locker->unlock($key);
		
		$ret['appraisal'] = $btlRet[ 'server' ]['appraisal'];
		$ret['fightStr'] = $btlRet[ 'client' ];
		
		EnAchieve::updateFriendsPlayWithEachOther($uid, 1);
		
		return $ret;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
