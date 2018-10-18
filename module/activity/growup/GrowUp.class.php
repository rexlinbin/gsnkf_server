<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GrowUp.class.php 82651 2013-12-24 02:44:43Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/growup/GrowUp.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2013-12-24 02:44:43 +0000 (Tue, 24 Dec 2013) $
 * @version $Revision: 82651 $
 * @brief 
 *  
 **/
class GrowUp implements IGrowUp
{

	/* (non-PHPdoc)
	 * @see IGrowUp::getInfo()
	*/
	public function getInfo()
	{
		$uid = RPCContext::getInstance()->getUid();
		$ret = $this->checkValidate( $uid );
		if ( $ret != 'ok' )
			return $ret;
		// 获取成长计划
		$growUpInfo = GrowUpDao::getGrowUpInfo( $uid );
		// 检查返回值 没有激活的话数据库无数据，返回-1
		if ($growUpInfo === false || $growUpInfo['activation_time'] == 0)
		{
			return 'unactived';
		}
		
		else if (!isset($growUpInfo['va_grow_up']['already']))
		{
			throw new InterException( 'data is not init right, fix me!' );
		}
		return array('prized' => $growUpInfo['va_grow_up']['already'], 'active_time' => $growUpInfo['activation_time']);
	}

	/* (non-PHPdoc)
	 * @see IGrowUp::activation()
	*/
	public function activation()
	{
		$uid = RPCContext::getInstance()->getUid();
		$ret = $this->checkValidate( $uid );
		if ( $ret != 'ok' )
		{
			throw new FakeException( 'invalid, %s', $ret);
		}
		// 获取用户信息
		$user =  EnUser::getUserObj( $uid );
		$conf = btstore_get()->GROWUP_FUND;
		// 检查VIP
		if ($user->getVip() < $conf['needVip'])
		{
			throw new FakeException( 'Can not activation by slave!, user vip is %d.', $user->getVip() );
		}
		// 获取所需金币
		$gold = $conf['needGold'];
		// 检查金币
		if ($user->getGold() < $gold)
		{
			throw new FakeException('Can not activation by poor!, user gold is %d.', $user->getGold());
		}
		// 获取成长计划
		$growUpInfo = GrowUpDao::getGrowUpInfo($uid);
		// 判断是否还没有激活
		if ($growUpInfo === false || empty($growUpInfo['uid']))
		{
			// 扣除金币
			$user->subGold( $gold , StatisticsDef::ST_FUNCKEY_GROWUP_COST);
			// 更新到数据库
			$user->update();
			// 插入数据库
			$growUpInfo = GrowUpDao::addNewGrowUpInfo($uid);
		}
		else
		{
			throw new FakeException( 'already activ' );
		}
		return 'ok';
	}


	/* (non-PHPdoc)
	 * @see IGrowUp::fetchPrize()
	*/
	public function fetchPrize( $index )
	{
		$uid = RPCContext::getInstance()->getUid();
		$ret = $this->checkValidate( $uid );
		if ( $ret != 'ok' )
		{
			return $ret;
		}
		$user =  EnUser::getUserObj( $uid );
		// 获取成长计划
		$growUpInfo = GrowUpDao::getGrowUpInfo($uid);
		$conf = btstore_get()->GROWUP_FUND;
		// 判断是否还没有激活
		if ($growUpInfo === false || empty($growUpInfo['uid']))
		{
			throw new FakeException('Can not fetch prize before activation!');
		}
		// 检查配置
		if (!isset($conf['lvAndGold'][$index]))
		{
			throw new FakeException('Error para, is %d!', $index);
		}
		// 如果已经领取过了
		if ( in_array( $index , $growUpInfo['va_grow_up']['already'] ))
		{
			throw new FakeException('Already fetch this one!');
		}
		// 检查奖励领取条件
		if ( $user->getLevel() < $conf[ 'lvAndGold' ][ $index ][ 'needLevel' ] )
		{
			throw new FakeException( 'uid: %d, level: %d is low to get this prize ：%d' ,$uid,$user->getLevel(),$index );
		}
		// 设置为已领取
		$growUpInfo['va_grow_up']['already'][] = $index;
		// 查看可以领取的金币并领取
		$gold = $conf['lvAndGold'][$index]['fundGold'];
		$user->addGold($gold , StatisticsDef::ST_FUNCKEY_GROWUP_REWARD);
		// 更新数据库
		GrowUpDao::updNewGrowUpInfo($uid, $growUpInfo['va_grow_up']);
		$user->update();
		
		return 'ok';
	}
	
	public function checkValidate()
	{
		$uid = RPCContext::getInstance()->getUid();
		$growUpInfo = GrowUpDao::getGrowUpInfo( $uid );
		$userObj = EnUser::getUserObj( $uid );
		$userCreatTime = $userObj->getCreateTime();
		$conf = btstore_get()->GROWUP_FUND;
		if ( Util::getTime() > ( $userCreatTime + $conf[ 'timeLast' ] )&& ( $growUpInfo === false )  )
		{
			return 'invalid_time';
		}
		//领取了所有奖励
		if ( count( $growUpInfo['va_grow_up']['already'] ) >= count( $conf[ 'lvAndGold' ] ) )
		{
			return 'fetch_all';
		}
		
		return 'ok';
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */