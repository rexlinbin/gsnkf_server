<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Levelfund.class.php 62946 2013-09-04 09:29:40Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/levelfund/Levelfund.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-09-04 09:29:40 +0000 (Wed, 04 Sep 2013) $
 * @version $Revision: 62946 $
 * @brief 
 *  
 **/
class Levelfund implements ILevelfund
{
	
	public function getLevelfundInfo()
	{

		$exVa = EnUser::getExtraInfo( UserExtraDef::LEVELUP_REWARD );
		if ( $exVa == FALSE  )
		{
			return array();
		}
		return $exVa;
	}
	
	public function gainLevelfundPrize( $id )
	{

		$confData = btstore_get()->LEVEL_FUND;
		$exVa = $this->getLevelfundInfo();
		//判定
		if ( !isset( $confData[ $id ] ) )
		{
			throw new FakeException( 'index: %d not exist' ,$id );
		}
		if ( in_array( $id , $exVa ) )
		{
			throw new FakeException( 'already get reward index: %d ',$id );
		}
		$userObj = EnUser::getUserObj();
		if ( $userObj->getLevel() < $confData[ $id ][ 'needLevel' ] )
		{
			throw new FakeException( 'level: %d is lower than requirment: %d ',
					$userObj->getLevel(),$confData[ $id ][ 'needLevel' ] );
		}
		//符合条件发给奖励
		$this->setExtraVa( $id );
		$uid = RPCContext::getInstance()->getUid();
		$ret = RewardUtil::reward($uid, $confData[ $id ]['rewardArr'], StatisticsDef::ST_FUNKEY_LEVELUP_PRIZE);
		EnUser::getUserObj()->update();
		BagManager::getInstance()->getBag()->update();
		
		return 'ok';		
	}
	
	public function setExtraVa( $index )
	{
		$exVa = EnUser::getExtraInfo( UserExtraDef::LEVELUP_REWARD );
		if (  $exVa == false )
		{
			$exVa = array();
		}
		$exVa[] = $index;
		EnUser::setExtraInfo( UserExtraDef::LEVELUP_REWARD , $exVa );
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */