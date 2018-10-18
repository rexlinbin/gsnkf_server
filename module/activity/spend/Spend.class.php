<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Spend.class.php 88627 2014-01-23 12:58:24Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/spend/Spend.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2014-01-23 12:58:24 +0000 (Thu, 23 Jan 2014) $
 * @version $Revision: 88627 $
 * @brief 
 *  
 **/

class Spend implements ISpend
{
	private function getSpendGold( $beginTime )
	{
		$beginDate = strftime( "%Y%m%d", $beginTime );
		$user = EnUser::getUserObj();
		$goldAccum = $user->getAccumSpendGold( $beginDate );
		return $goldAccum;
	}

	public function getConfig()
	{
		//只在获取配置的时候进行判定 接口都要调用该方法
		if ( !EnActivity::isOpen( ActivityName::SPEND ) )
		{
			throw new FakeException( 'invalid time for spend activity' );
		}
		$conf = EnActivity::getConfByName( ActivityName::SPEND );
		
		$startTime = $conf['start_time'];
		$endTime = $conf['end_time'];
		
		$days = ceil( ($endTime - $startTime)/86400 );
		if ( $days > UserConf::SPEND_GOLD_DATE_NUM )
		{
			throw new ConfigException( 'conf days:%d beyond max: %d', $days, UserConf::SPEND_GOLD_DATE_NUM );
		}
		
		$confData = $conf[ 'data' ];
		
		return $conf;
	}

	public function getSpendReward( $conf )
	{
		$arrRet = array();
		$user = EnUser::getUserObj();
		$arrSpendReward = EnUser::getExtraInfo( UserExtraDef::SPEND_REWARD );
		
		$key = $this->getSpendRewardKey($conf['start_time'] , $conf['end_time']);
				
		if (isset($arrSpendReward[$key]))
		{
			$arrRet = $arrSpendReward[$key];
		}
		return $arrRet;
	}
	//userExtra中key的格式‘起始时间-结束时间’
	public function getSpendRewardKey($beginTime, $endTime)
	{
		return  $beginTime . '-' . $endTime;
	}

	/* (non-PHPdoc)
	 * @see ISpend::getInfo()
	*/
	public function getInfo ()
	{
		$arrRet = array();
		
		$conf = $this->getConfig();
		$beginTime = $conf['start_time'];
		$arrRet['gold_accum'] = $this->getSpendGold($beginTime);
		$arrRet['reward'] = array();
		//$user = EnUser::getUserObj();
		$arrRet['reward'] = $this->getSpendReward($conf);

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
		$reward = $this->getSpendReward($conf);//这是已经领取的奖励
		if (in_array($id, $reward))
		{
			throw new FakeException('fail get spend reward, the id %d is rewarded ', $id);
		}
		
		$beginTime = $conf[ 'start_time' ];
		$spendGold = $this->getSpendGold($beginTime);
		if ($spendGold < $confData[ $id ]['needSpend'])
		{
			throw new FakeException('fail get reward, spend: %d not enough' , $spendGold );
		}
		
		$rewardArr = $confData[ $id ]['rewardArr'];
		Logger::debug('reward arr in spend is: %s', $rewardArr);
		$uid = RPCContext::getInstance()->getUid();
		if ( empty( $uid ) )
		{
			throw new FakeException( 'invalid uid: %d', $uid );
		}
		RewardUtil::reward($uid, $rewardArr, StatisticsDef::ST_FUNKEY_SPEND_PRIZE);
		
		$bag = BagManager::getInstance()->getBag();
		//保存到用户ex信息库（extraVa）并更新用户数据
		$this->setSpendReward($id, $conf['start_time'], $conf['end_time']);
		$bag->update();
		$user->update();
		
		
		return $confData[ $id ];
	}
	
	public function setSpendReward( $rewardId, $beginTime, $endTime )
	{
		$key = $this->getSpendRewardKey( $beginTime, $endTime );
		//先获取到数据库中的数据，没有数据，没有本次活动的数据，已经有了领取奖励的数据
		$exVa = EnUser::getExtraInfo( UserExtraDef::SPEND_REWARD );
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
			Logger::fatal( 'id: %d , key: %d already rewarded' , $rewardId ,$key );
			return false;
		}		
		//加进去
		$exVa[ $key ][] = $rewardId;
		//更新用户extraVa字段
		EnUser::setExtraInfo( UserExtraDef::SPEND_REWARD , $exVa );
		
		return true;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */