<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TopupFund.class.php 159375 2015-02-27 06:01:30Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/topupfund/TopupFund.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-02-27 06:01:30 +0000 (Fri, 27 Feb 2015) $
 * @version $Revision: 159375 $
 * @brief 
 *  
 **/

class TopupFund implements ITopupFund
{
	private function getTopupGold( $beginTime )
	{
		$beginDayTime = strtotime( date( 'Ymd', $beginTime).'000000' );
		$currTime = Util::getTime();
		$topupGold = EnUser::getRechargeGoldByTime( $beginDayTime , $currTime );
		
		return $topupGold;
	}

	public function getConfig()
	{
		//只在获取配置的时候进行判定 接口都要调用该方法
		if ( !EnActivity::isOpen( ActivityName::TOPUP_FUND ) )
		{
			throw new FakeException( 'invalid time for topupFund activity' );
		}
		$conf = EnActivity::getConfByName( ActivityName::TOPUP_FUND );
		
		return $conf;
	}

	public function getTopupReward( $conf )
	{
		$arrRet = array();
		$user = EnUser::getUserObj();
		$arrSpendReward = EnUser::getExtraInfo( UserExtraDef::TOPUP_REWARD );

		$key = $this->getTopupRewardKey($conf['start_time'] , $conf['end_time']);

		if (isset($arrSpendReward[$key]))
		{
			$arrRet = $arrSpendReward[$key];
		}
		return $arrRet;
	}
	//userExtra中key的格式‘起始时间-结束时间’
	public function getTopupRewardKey($beginTime, $endTime) 
	{
		return  $beginTime . '-' . $endTime;
	}

	/* (non-PHPdoc)
	 * @see ISpend::getInfo()
	*/
	public function getTopupFundInfo()
	{
		$arrRet = array();

		$conf = $this->getConfig();
		$beginTime = $conf['start_time'];
		$arrRet['gold_accum'] = intval( $this->getTopupGold($beginTime) );
		$arrRet['reward'] = array();
		//$user = EnUser::getUserObj();
		$arrRet['reward'] = $this->getTopupReward($conf);

		return $arrRet;
	}

	/* (non-PHPdoc)
	 * @see ISpend::getReward()
	*/
	public function gainReward ( $id )
	{
		$conf = $this->getConfig();
		$confData = $conf[ 'data' ];

		//检查id
		if (!isset($confData[$id]))
		{
			throw new FakeException( 'fail get spend reward, id: %d not exist',$id );
		}

		//id是否已经领取
		$user = EnUser::getUserObj();
		$reward = $this->getTopupReward($conf);//这是已经领取的奖励
		if (in_array($id, $reward))
		{
			throw new FakeException('fail get spend reward, the id %d is rewarded ', $id);
		}

		$beginTime = $conf[ 'start_time' ];
		$topupGold = $this->getTopupGold($beginTime);
		if ( $topupGold < $confData[ $id ]['needTopup'] )
		{
			throw new FakeException('fail get reward, topup: %d not enough' , $topupGold );
		}
		//发奖励
		$rewardArr = $confData[ $id ]['rewardArr'];
		Logger::debug('reward arr in topupFund is: %s', $rewardArr);
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		RewardUtil::reward($uid, $rewardArr, StatisticsDef::ST_FUNKEY_TOPUP_PRIZE);
		
		$bag = BagManager::getInstance()->getBag();
		//保存到用户ex信息库（extraVa）并更新用户数据
		$this->setTopupReward($id, $conf['start_time'], $conf['end_time']);
		$bag->update();
		$user->update();

		return 'ok';
	}

	public function setTopupReward( $rewardId, $beginTime, $endTime )
	{
		$key = $this->getTopupRewardKey( $beginTime, $endTime );
		//先获取到数据库中的数据，没有数据，没有本次活动的数据，已经有了领取奖励的数据
		$exVa = EnUser::getExtraInfo( UserExtraDef::TOPUP_REWARD );
		if ( empty( $exVa ) )
		{
			$exVa = array( $key => array() );
		}
		if ( !isset( $exVa[ $key ] ) )
		{
			$exVa[ $key ] = array();
		}
		if ( in_array( $rewardId , $exVa[ $key ]) )
		{
			//之前是查过一遍的
			throw new FakeException( 'id: %d , key: %d already rewarded' , $rewardId ,$key );
		}
		//加进去
		$exVa[ $key ][] = $rewardId;
		//更新用户extraVa字段
		EnUser::setExtraInfo( UserExtraDef::TOPUP_REWARD , $exVa );

		return true;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */