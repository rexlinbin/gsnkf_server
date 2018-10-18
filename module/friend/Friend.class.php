<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Friend.class.php 114046 2014-06-13 04:12:32Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/friend/Friend.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-06-13 04:12:32 +0000 (Fri, 13 Jun 2014) $
 * @version $Revision: 114046 $
 * @brief 
 *  
 **/
class Friend implements IFriend
{
	private $uid;
	public function __construct()
	{
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	public function applyFriend( $toUid , $content )
	{
		Logger::trace( 'begin applyFriend' );
		
		if ( $toUid == $this->uid )
		{
			throw new FakeException( 'applyFriend uid: %d is self', $toUid );
		}
		if (BlackLogic::isInBlack($this->uid, $toUid))
		{
			return 'black';
		}
		
		if( BlackLogic::isInBlack($toUid,$this->uid)  )
		{
			return 'beblack';
		}
		
		if ( FriendLogic::isFriend( $this->uid, $toUid ) )
		{
			return 'alreadyfriend';
		}
		if ( !MailLogic::canApplyFriend( $toUid, $this->uid) )
		{
			return 'applied';
		}
		if ( FriendLogic::reachMaxFrdNum( $this->uid ))
		{
			return 'reach_maxnum';
		}
		FriendLogic::applyFriend($this->uid, $toUid , $content );
		Logger::trace( 'end applyFriend' );
		return 'ok';
	}
	
	public function addFriend( $applicantUid )
	{
		Logger::trace( 'begin addFriend' );
		if ( $applicantUid == $this->uid )
		{
			throw new FakeException( 'addfriend uid: %d is self', $applicantUid );
		}
		if (BlackLogic::isInBlack($this->uid, $applicantUid))
		{
			return 'black';
		}
		if( BlackLogic::isInBlack($applicantUid,$this->uid)  )
		{
			return 'beblack';
		}
		
		if( FriendLogic::reachMaxFrdNum( $applicantUid ))
		{
			return 'applicant_reach_maxnum';
		}
		elseif ( FriendLogic::reachMaxFrdNum( $this->uid ) )
		{
			return 'accepter_reach_maxnum';
		}
		if ( FriendLogic::isFriend( $applicantUid , $this->uid ) )
		{
			return 'isfriend';
		}
		
		FriendLogic::addFriend( $applicantUid , $this->uid );
		EnAchieve::updateFriend($this->uid, FriendLogic::getFriendNum($this->uid));
		//EnNAchieve::updateFriend($applicantUid, $finish_num);
		//EnAchieve::updateFriend($this->uid, 1);
		RPCContext::getInstance()->executeTask($applicantUid, 'friend.addFriendNachieveByOther', array($applicantUid));
		
		Logger::trace( 'end addFriend' );
		return 'ok';
	}
	
	//为了成就系统单独搞的
	public function addFriendNachieveByOther($myuid)
	{
		EnAchieve::updateFriend($myuid, FriendLogic::getFriendNum( $myuid ));
	}
	
	public function rejectFriend( $beRejectUid )
	{
		Logger::trace( 'begin rejectFriend' );
		if ( $beRejectUid == $this->uid )
		{
			throw new FakeException( 'rejectFriend uid: %d is self', $beRejectUid );
		}
		if ( FriendLogic::isFriend( $this->uid, $beRejectUid ) )
		{
			return 'isfriend';
		}
		
		FriendLogic::rejectFriend( $this->uid , $beRejectUid );
		Logger::trace( 'end rejectFriend' );
		return 'ok';
	}
	
	public function delFriend( $beDelUid )
	{
		Logger::trace( 'begin delFriend' );
		
		if ( $beDelUid == $this->uid )
		{
			throw new FakeException( 'delFriend uid: %d is self', $beDelUid );
		}
		
		if ( !FriendLogic::isFriend( $this->uid , $beDelUid ) )
		{
			return 'notfriend';
		}
		FriendLogic::delFriend( $this->uid , $beDelUid );
		Logger::trace( 'end delFriend' );
		return 'ok';
	}
	
	public function getFriendInfo( $fuid )
	{
		Logger::trace( 'begin getFriendInfo' );
		if ( !FriendLogic::isFriend( $this->uid , $fuid ) )
		{
			throw new FakeException( 'uid: %d and uid: %d are not friend' , $this->uid , $fuid );
		}
		$ret = FriendLogic::getFriendInfo( $fuid );
		Logger::trace( 'end getFriendInfo' );
		return $ret;
	}
	
	public function getFriendInfoList()
	{
		Logger::trace( 'begin getFriendInfoList' );
		$arrRet = FriendLogic::getAllFriendInfo( $this->uid );
		Logger::trace( 'end getFriendInfoList' );
		return $arrRet;
	}
	
	public function getRecomdFriends()
	{
		Logger::trace( 'begin getRecomdFriends' );
		$arrRet = FriendLogic::recomdFriends( $this->uid );
		Logger::trace( 'end getRecomdFriends' );
		return $arrRet;
	}
	
	public function getRecomdByName( $nameLike, $offset = 0, $limit = CData::MAX_FETCH_SIZE )
	{
		Logger::trace( 'begin getRecomdByName: args: %s', $nameLike );
		$arrRet = FriendLogic::getRecmodByName( $nameLike, $offset , $limit );
		Logger::trace( 'end getRecomdByName' );
		
		return $arrRet;
	}
	
	public function isFriend( $checkUid )
	{
		Logger::trace( 'begin isFriend' );
		$ret = FriendLogic::isFriend( $this->uid , $checkUid );
		Logger::trace( 'end isFriend' );
		return $ret;
	}
	

	public function loveFriend( $fuid )
	{
		Logger::trace( 'begin loveFriend' );
		$ret = FriendLogic::loveFriend( $this->uid, $fuid );
		
		EnActive::addTask( ActiveDef::LOVE );
		
		Logger::trace( 'end loveFriend' );
		
		return $ret;
	}
	
	public function lovedByOther( $uid, $fuid )
	{
		Logger::trace( 'begin lovedByOther' );
		FriendLogic::lovedByOther( $uid, $fuid );
		Logger::trace( 'end lovedByOther' );
	}
	
	public function receiveLove( $time, $uid, $reLove = 0 )
	{
		//会出现领了但没有回赠的情况（已经不是好友了）
		Logger::trace( 'begin receiveLove' );
		if ( $reLove !=0 && $reLove != 1 )
		{
			throw new FakeException( 'what did you do?' );
		}
		FriendLogic::receiveLove( $this->uid, $time, $uid );
		if ( $reLove == 1 )
		{
			$ret = FriendLogic::isFriend( $this->uid , $uid);
			if ( !$ret)
			{
				throw new FakeException( 'not friend now, fuid: %d', $uid );
			}
			FriendLogic::loveFriend( $this->uid , $uid );
		}
		Logger::trace( 'end receiveLove' );
		
		return 'ok';
	}
	

	public function receiveAllLove()
	{
		Logger::trace( 'begin receiveAllLove' );
		$ret = FriendLogic::receiveAllLove( $this->uid );
		Logger::trace( 'end receiveAllLove' );
		
		return $ret;
	}

	public function unreceiveLoveList()
	{	
		Logger::trace( 'begin unreceiveLoveList' );
		$ret = FriendLogic::getUnreceiveList( $this->uid );
		Logger::trace( 'end unreceiveLoveList' );
		return $ret;
	}
	/* (non-PHPdoc)
	 * @see IFriend::getPkInfo()
	 */
	public function getPkInfo($beuid) 
	{
		$ret = FriendLogic::getPkInfo($beuid);
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see IFriend::pkOnce()
	 */
	public function pkOnce($beuid) 
	{
		if (!FriendLogic::isFriend($this->uid, $beuid))
		{
			//其实也可以不做这个判定，没什么必要，不赚金不赚银的
			return 'notFriend';
		}
		return FriendLogic::pkOnce($this->uid,$beuid);
	}

	public function addBepkNumByOther($uid)
	{
		if(empty($this->uid) )
		{
			RPCContext::getInstance()->setSession( 'global.uid', $uid);
		}
		
		$normalConf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_FRIEND_PK_NUM];
		$pkInst = FriendLoveObj::getInstance();
		$pkInfo = $pkInst->getAllLove();
		if ( $pkInfo['bepk_num'] >= $normalConf[1] )
		{
			return;
		}
		
		$pkInst->addBepkNum();
		$pkInst->update();
	}
	
	/* (non-PHPdoc)
	 * @see IChat::getBlackers()
	*/
	public function getBlackers()
	{
		$ret = BlackLogic::getBlackers($this->uid);
	
		return $ret;
	}
	
	/* (non-PHPdoc)
	 * @see IChat::blackYou()
	*/
	public function blackYou($beBlackUid)
	{
		BlackLogic::blackYou($this->uid, $beBlackUid);
	
		return 'ok';
	}
	
	/* (non-PHPdoc)
	 * @see IChat::unblackYou()
	*/
	public function unBlackYou($unBlackUid)
	{
		BlackLogic::unBlackYou($this->uid, $unBlackUid);
	
		return 'ok';
	}
	
	public function getBlackUids()
	{
		return BlackLogic::getBlackUids($this->uid);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */