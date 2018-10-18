<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Cardbigrun.class.php 66734 2013-09-27 04:07:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/cardbigrun/Cardbigrun.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-27 04:07:30 +0000 (Fri, 27 Sep 2013) $
 * @version $Revision: 66734 $
 * @brief 
 *  
 **/
class Cardbigrun implements ICardbigrun
{
	public $uid;
	public function __construct()
	{
		$this->uid  = RPCContext::getInstance()->getUid(); 
	}
	
	public function getCardRunInfo()
	{
		$ret = array();
		$this->checkValidate();
		$exVa = EnUser::getExtraInfo( ActivityName::CARD_BIG_RUN );
		$ret[ 'reward' ] = $exVa;
	}
	
	public function checkValidate()
	{
		if ( !EnActivity::isOpen( ActivityName::CARD_BIG_RUN ) )
		{
			throw new FakeException( 'invalid time for cardbigrun' );
		}
	}
	
	public function pickCard( $id )
	{
		$this->checkValidate();
		$conf = EnActivity::getConfByName( ActivityName::CARD_BIG_RUN );
		$confData = $conf[ 'data' ];
		
		if ( !isset( $confData[ $id ]) )
		{
			throw new FakeException( 'id: %d is invaild for cardbigrun' , $id );
		}
		$confSpecific = $confData[ $id ];
		
		$dropId = $confSpecific[ 'dropId' ];
		
		$dropHeros = Drop::dropItem( $dropId , DropDef::DROP_TYPE_HERO );
		
		if ( empty( $dropHeros) )
		{
			throw new FakeException( 'nohero for uid: %d' , $this->uid );
		}
		
		$actualDropnum = 0;
		foreach ( $dropHeros as $htid => $num )
		{
			$actualDropnum += $num;
		}
		if ( $actualDropnum != intval( $confSpecific[ 'heroNum' ] ) )
		{
			throw new InterException( 'actual dropnum: %d is diff from expect: %d', 
					$actualDropnum, $confSpecific[ 'dropNum' ]);
		}
		
		//减金币
		$needGold = $confSpecific[ 'needGold' ];
		$userObj = EnUser::getUserObj();
		if ( !$userObj->subGold( $needGold , StatisticsDef::ST_FUNKEY_BUY_CARD ) )
		{
			throw new FakeException( 'lack money!! diaosi: %d' ,$this->uid );
		}
		
		$heroMgr = EnUser::getUserObj( $this->uid )->getHeroManager();
		$heroMgr->addNewHeroes($dropHeros);
		
		$userObj->update();
		//TODO找前端确认是否用给返回
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */