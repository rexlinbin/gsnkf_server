<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Weal.class.php 259698 2016-08-31 08:07:55Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/weal/Weal.class.php $
 * @author $Author: BaoguoMeng $(zhangshiyu@babeltime.com)
 * @date $Date: 2016-08-31 08:07:55 +0000 (Wed, 31 Aug 2016) $
 * @version $Revision: 259698 $
 * @brief 
 *  
 **/

class Weal implements IWeal
{
	private $sessionKey = 'weal.conf';
	private $sessionKeyValidity = 'weal.setTime';
	private $sessionWealEndTime = 'weal.endTime';
	
	//福利活动不能及时生效修复
	private $sessionWealBeginTime = 'weal.beginTime';
	
	public function getWealConf( $profitType )
	{
		$wealInSession = RPCContext::getInstance()->getSession( $this->sessionKey );
		$validityInSession = RPCContext::getInstance()->getSession( $this->sessionKeyValidity );
		
		//福利活动不能及时生效修复
		$sessionWealBeginTime = RPCContext::getInstance()->getSession( $this->sessionWealBeginTime );
		//多条福利活动，时间相距很紧，下一条不能及时生效的问题
		$sessionWealEndTime = RPCContext::getInstance()->getSession( $this->sessionWealEndTime );
		
		if ( empty( $validityInSession ) )
		{
			$validityInSession = 0;
		}
		
		$time = Util::getTime();
		
		//没有数据 不是数组 有效期过了都要刷新一下
		if (!isset( $wealInSession ) || !is_array( $wealInSession )|| $validityInSession + WealDef::WEAL_VALID_TIME < $time 
		//福利活动不能及时生效修复
		|| ( $sessionWealBeginTime >= $validityInSession && Util::getTime() >= $sessionWealBeginTime )
		|| ( $sessionWealEndTime >= $validityInSession && Util::getTime() >= $sessionWealEndTime  ) )
		{
			//某些活动在拉取活动配置之前拉取 某一个副本的数据或者前段拉取失败会走到这里
			$wealInSession = $this->refreshWealSession();
		}
		//至此 肯定已经刷新过了
		//时间到了 ，空数组  没有福利活动
		$wealEndTime = RPCContext::getInstance()->getSession( $this->sessionWealEndTime );
		if ( $wealEndTime < $time  || empty( $wealInSession ))
		{
			Logger::debug('all weal is end at %d',$wealEndTime);
			return false;
		}
	
		//没有这个福利
		if ( !isset( $wealInSession[ WealDef::$type[$profitType] ] ) ) 
		{
			Logger::debug('particular weal: %s is end', $profitType);
			return false;
		}
		else 
		{
			//返回这个福利的数据
			Logger::debug('now weal return %s',$wealInSession[ WealDef::$type[$profitType] ] );
			return $wealInSession[ WealDef::$type[$profitType] ];
		}
	}
	
	public function refreshWealSession()
	{
		Logger::debug('now begin refreshWealSession');
		$newerWealInfo = array();
		$time = Util::getTime();
		if ( !EnActivity::isOpen( ActivityName::WEAL ) )
		{
			Logger::debug('weal is not happen');
			//设置为 1.配置为空 2.setsession的时间为现在 3.活动结束
			$this->dealSessions($newerWealInfo, $time, $time -10);
			return $newerWealInfo;
		}
		$conf = EnActivity::getConfByName( ActivityName::WEAL );
		$dataConf = $conf['data'];
		$endTime = $conf['end_time'];
		
		//只有一条配置是有效的, 解析的时候检测并拦住了,再检测一次，防止扩展的时候改漏了
		if ( count( $dataConf ) > 1 )
		{
			throw new InterException( 'weal dataconf > 1' );
		}

		$onePiece = current( $dataConf );
		foreach ( WealDef::$type as $wealName => $wealInt )
		{
			if ( !empty( $onePiece[$wealName]  ) )
			{
				$newerWealInfo[$wealInt] = $onePiece[$wealName];
			}
		}
		
		$this->dealSessions($newerWealInfo, $time, $endTime);
		
		Logger::debug('weal conf after refresh is: %s',$newerWealInfo );
		return $newerWealInfo;
	}
	
	public function dealSessions($conf,$setSessionTime,$wealEndTime)
	{
		RPCContext::getInstance()->setSession( $this->sessionKey , $conf);
		RPCContext::getInstance()->setSession( $this->sessionKeyValidity, $setSessionTime );
		RPCContext::getInstance()->setSession( $this->sessionWealEndTime , $wealEndTime);
		
		//福利活动不能及时生效修复
		$ret = EnActivity::getConfByName( ActivityName::WEAL );
		RPCContext::getInstance()->setSession( $this->sessionWealBeginTime , $ret['start_time']);
	}
	
	public function getKaInfo()
	{
		if (!self::checkKaValid())
		{
			return array(
					'point_today' => 0,
					'refresh_time' => Util::getTime(),
					'point_add' => 0,
			);
			
			throw new FakeException( 'invalid time or conf' );
		}
		
		$kaRfrType = EnWeal::getKaRfrType();
		if ( KaDef::KA_RFR_TYPE_DAY != $kaRfrType
		    && KaDef::KA_RFR_TYPE_ACT != $kaRfrType )
		{
		    throw new ConfigException("wrong type ka refresh conf:%s.", $kaRfrType);
		}
	
		$kaObj = KaObj::getInstance();
		$front = $kaObj->getKaInfo();
		//unset( $front['point_add'] );
		
		return $front;
	}
	
	
	public function kaOnce()
	{
	if (!self::checkKaValid())
		{
			throw new FakeException( 'invalid time or conf' );
		}
	
		$kaObj = KaObj::getInstance();
		$kaInfo = $kaObj->getKaInfo();
		$kaConsume = EnWeal::getWeal( WealDef::KA_CONSUME );
		if ( $kaInfo['point_today'] < $kaConsume )
		{
			throw new FakeException( 'ka num: %d < %d',$kaInfo['point_today'],$kaConsume );
		}
		
		$uid = RPCContext::getInstance()->getUid();
		$kaReward = $this->getKaSuprise();
		$realReward = array($kaReward[0]);
		RewardUtil::reward3DArr($uid, $realReward, StatisticsDef::ST_FUNCKEY_WEAL_KA);
		
		$kaObj->subKaPoint( $kaConsume );
		$kaObj->update();
		$user = EnUser::getUserObj($uid);
		$bag = BagManager::getInstance()->getBag($uid);
		$user->update();
		$bag->update();
		
		$rewardForFront = array();
		foreach ( $kaReward as $key =>$onePiece )
		{
			if ($onePiece[1]!=0)
			{
				$rewardForFront[$key] = array(
						$onePiece[0]=>array(
								$onePiece[1]=>$onePiece[2] ,
						),
						);
			}
			else
			{
				$level = 1;
				if ( $onePiece[0] == RewardConfType::SILVER_MUL_LEVEL ||
				$onePiece[0] == RewardConfType::SOUL_MUL_LEVEL || 
						$onePiece[0] == RewardConfType::EXP_MUL_LEVEL)
				{
					$level = $user->getLevel();
				}
				
				$rewardForFront[$key] = array(
						$onePiece[0] => $onePiece[2] * $level,
				);
			}
				
		}
		
		return $rewardForFront ;
	}
	
	
	public static function checkKaValid()
	{
		$open = EnWeal::getWeal( WealDef::KA_OPEN );
		$kaConsume = EnWeal::getWeal( WealDef::KA_CONSUME );
		$kaLimit = EnWeal::getWeal( WealDef::KA_INTERGRAL_LIMIT );
	
		if (empty($open)|| empty( $kaConsume )||empty( $kaLimit ))
		{
			return false;
		}
		Logger::debug('ka check valid true');
		return true;
	}
	
	public function getKaSuprise()
	{
		$uid = RPCContext::getInstance()->getUid();
		$conf = btstore_get()->KAREWARD->toArray();
		Logger::debug('==========1 %s', $conf);
		if (empty( $conf ))
		{
			throw new ConfigException( 'no conf gor kadrop' );
		}
		$ret = Util::noBackSample($conf,ActivityConf::KA_SAMPLE_NUM); //6);
		Logger::debug('==========2 %s', $ret);
		if (count( $ret ) < ActivityConf::KA_SAMPLE_NUM)//KaConf::KA_SAMPLE_NUM)
		{
			throw new ConfigException( 'pieces of conf is not enough' );
		}
		
		
		$standardReward = array();
		
		foreach ( $ret as $retKey => $retVal )
		{
			$rewardDetail = $conf[$retVal];
			Logger::debug('$rewardDetail is : %s, $retVal %d', $rewardDetail,$retVal);
			if (!empty($rewardDetail['rewardInfo']))
			{
				//这里要保证只有一条有效
				Logger::debug('$rewardDetail : %s', $rewardDetail['rewardInfo']);
				$standardReward[$retKey] = $rewardDetail['rewardInfo'][0];
			}
			else if (!empty( $rewardDetail['dropId'] )) 
			{
				$dropId = $rewardDetail['dropId'];
				$dropThing = Drop::dropMixed($dropId);
				//只取第一类的第一项
				if ( empty( $dropThing ) || count( $dropThing ) > 1 )
				{
					throw new ConfigException( 'dropId: %d err, empty or >1 , drop thing: %s', $dropId, $dropThing );
				}
				$firstPieceType = key( $dropThing );
				$firstPieceThing = $dropThing[$firstPieceType];
				if ( empty( $firstPieceThing ) || count( $firstPieceThing ) > 1 )
				{
					throw new ConfigException( 'dropId: %d err, empty or >1, drop thing %s', $dropId, $dropThing );
				}
				
				$firstThingId = key( $firstPieceThing );
				$standardReward[$retKey] = array(
						RewardConfType::$drop2reward[$firstPieceType],
						$firstThingId,
						$firstPieceThing[$firstThingId],
				);
			}
			else 
			{
				Logger::fatal('empty for kaOnce');
			}
		}
		
		return $standardReward;
	}
	
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */