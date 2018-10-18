<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Barter.class.php 108678 2014-05-16 03:09:41Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/barter/Barter.class.php $
 * @author $Author: MingTian $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-05-16 03:09:41 +0000 (Fri, 16 May 2014) $
 * @version $Revision: 108678 $
 * @brief 
 *  
 **/
class Barter extends Mall implements IBarter
{
	public static $hids = array();
	
	
	public function __construct()
	{
		$uid = RPCContext::getInstance()->getUid();
		parent::__construct($uid, MallDef::MALL_TYPE_BARTER,
		StatisticsDef::ST_FUNCKEY_MALL_BARTER_COST,
		StatisticsDef::ST_FUNCKEY_MALL_BARTER_GET);
	}
	
	public function getBarterInfo()
	{
		$this->checkValidate();
		return $this->getInfo( );
	}
	
	public function barterExchange( $exchangeId , $arrHid = array() )
	{
		
		self::$hids = $arrHid;
		
		$this->checkValidate();
		$barterConf = EnActivity::getConfByName( ActivityName::BARTER );
		if ( empty( $barterConf[ 'data' ][ $exchangeId ] ) )
		{
			throw new FakeException( 'no such exchange id: %d' , $exchangeId );
		}
		$ret = $this->exchange(  $exchangeId ,$num = 1);
		
		return $ret;
	}
	
	public function subExtra( $exchangeId , $num = 1 )
	{
		if ( $num != 1 )
		{
			throw new InterException( 'subExtra num should = 1 in barter' );
		}
		
		if ( empty( $exchangeId ) || intval( $exchangeId ) < 0 )
		{
			throw new fakeException( 'invalid exchangeId : %d' ,$exchangeId );
		}
	
		//需要的英雄htid
		$conf = EnActivity::getConfByName( ActivityName::BARTER );
		$needArrHtid = $conf[ 'data' ][ $exchangeId ][ 'needHero' ];
		if ( empty( $needArrHtid ) )
		{
			Logger::info( 'no hero be deleted' );
			return true;
		}
		//需要的htid非空而提供的hid是空的
		if ( empty( self::$hids ) )
		{
			throw new FakeException( 'nohero provide for exchangeId: %d' , self::$exchangeId );
		}
		
		$arrHtidProvide = self::standardHero( self::$hids );
		
		foreach ( $needArrHtid as $htid => $htidNum)
		{
			if ( !isset( $arrHtid[ $htid ] ) )
			{
				throw new FakeException( 'need htid: %d to exchangeId: $d ',
						$htid ,$exchangeId );
			}
			else if ( $arrHtidProvide[ $htid ] != $htidNum )
			{
				throw new FakeException( 'for htid: %d, needNum: %d provideNum: %d,not same',
						$htid , $htidNum , $arrHtidProvide[ $htid ] );
			}
			else 
			{
				unset( $arrHtidProvide[ $htid ] );
			}
		}
		if ( !empty( $arrHtidProvide ) )
		{
			throw new FakeException( 'provide htid is more than need' );
		}
		
		//判定完毕，开始删除英雄
		$userObj = EnUser::getUserObj();
		$heroMgr = $userObj->getHeroManager();
		foreach ( self::$hids as $hid )
		{
			$heroMgr->delHeroByHid( $hid );
		}
		
		return true;
	}
	
	public static function standardHero( $hids )
	{
		if ( empty( $hids ) )
		{
			return array();
		}
		$uid = RPCContext::getInstance()->getUid();
		$arrHtid = array();
		$heroMgr = EnUser::getUserObj( $uid )->getHeroManager();
		foreach ( $hids as $oneHid )
		{
			if ( empty( $oneHid ) )
			{
				continue;
			}
			$heroObj = $heroMgr->getHeroObj( $oneHid );
			$htid = $heroObj->getHtid();
			if ( !isset( $arrHtid[ $htid ] ) )
			{
				$arrHtid[ $htid ] = 1;
			}
			else
			{
				$arrHtid [ $htid ]++;
			}
		}
		return $arrHtid;
	}
	
	public function getExchangeConf( $exchangeId )
	{
		$arrRet = array();
		
		$conf = EnActivity::getConfByName( ActivityName::BARTER);
		$confData = $conf[ 'data' ][ $exchangeId ];

		$arrReq = array(
				MallDef::MALL_EXCHANGE_START 	=> $conf[ 'start_time' ],
				MallDef::MALL_EXCHANGE_END 		=> $conf[ 'end_time' ],
				MallDef::MALL_EXCHANGE_SERVICE 	=> $conf['need_open_time'],
				MallDef::MALL_EXCHANGE_SILVER 	=> $confData[ 'needSilver' ],
				MallDef::MALL_EXCHANGE_GOLD 	=> $confData[ 'needGold' ],
				MallDef::MALL_EXCHANGE_SOUL 	=> $confData[ 'needSoul' ],
				MallDef::MALL_EXCHANGE_ITEM 	=> $confData[ 'needItem' ],
				MallDef::MALL_EXCHANGE_LEVEL 	=> $confData[ 'needLevel' ],
				MallDef::MALL_EXCHANGE_VIP 		=> $confData[ 'needVip' ],
				MallDef::MALL_EXCHANGE_EXTRA	=> array(
						MallDef::MALL_EXCHANGE_HERO 	=> $confData[ 'needHero' ],
				 ),
		);
		$arrAcq = array(
				MallDef::MALL_EXCHANGE_SILVER 	=> $confData[ 'gainSilver' ],
				MallDef::MALL_EXCHANGE_GOLD 	=> $confData[ 'gainGold' ],
				MallDef::MALL_EXCHANGE_SOUL 	=> $confData[ 'gainSoul' ],
				MallDef::MALL_EXCHANGE_HERO 	=> $confData[ 'gainHero' ],
				MallDef::MALL_EXCHANGE_ITEM 	=> $confData[ 'gainItem' ],
		);
		
		
		$arrRet = array(
				MallDef::MALL_EXCHANGE_REQ 		=> $arrReq,
				MallDef::MALL_EXCHANGE_ACQ 		=> $arrAcq,
		);
		return $arrRet;
	}
	
	
	public function checkValidate()
	{
		if ( !EnActivity::isOpen( ActivityName::BARTER ) )
		{
			throw new FakeException( 'invalid time for this activity' );
		}
		if ( !EnActivity::isOpen( ActivityName::BARTER_FRONT ) )
		{
			throw new ConfigException( 'base conf of two csv are different' );
		}
	}
	
	
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */