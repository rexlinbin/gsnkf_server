<?php
/***************************************************************************
 * 
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: GiftCodeLogic.class.php 150846 2015-01-07 12:59:31Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/reward/giftCode/GiftCodeLogic.class.php $
 * @author $Author: ShiyuZhang $(lanhongyu@babeltime.com)
 * @date $Date: 2015-01-07 12:59:31 +0000 (Wed, 07 Jan 2015) $
 * @version $Revision: 150846 $
 * @brief 
 *  
 **/

class GiftCodeLogic
{	
	private static function isSignRight4LY($qid, $code)
	{
		//没有合服配置
		if (!isset(GameConf::$MERGE_LY_SERVER_ID))
		{
			$md5str = $qid . GameConf::LY_SERVER_ID . GameConf::LY_KEY;
			$sign = strtoupper(md5($md5str));
			if ($sign==$code)
			{
				return true;
			}
			return false;
		}
		
		//遍历合服server_id
		foreach (GameConf::$MERGE_LY_SERVER_ID as $serverId)
		{
			$md5str = $qid . $serverId . GameConf::LY_KEY;
			$sign = strtoupper(md5($md5str));
			if ($sign == $code)
			{
				return true;
			}
		}
		return false;
	}
	
	public static function getGiftByCode($uid, $code)
	{
		$arrRet = array('ret' => 'ok', 'info' => '');
		$arrGift = array();
		
		$user = EnUser::getUserObj($uid);
		$platform = ApiManager::getApi();		
		
		$argv = array(
				'pid' => $user->getPid(), 
				'group' => RPCContext::getInstance()->getFramework()->getGroup(),
				'serverKey' => Util::getServerId(), 
				'uid' => $uid, 
				'code' => $code 		
		);
		$arrGift = $platform->users('getGiftByCard', $argv);
			
				
		if (isset($arrGift['error']))
		{
			$arrRet['ret'] = $arrGift['error'];
			Logger::warning('fail to getGiftByCode, platform return error %s, code number:%s', $arrGift['error'], $code);
			return $arrRet;	
		}
		Logger::debug('platform return gift:%s', $arrGift);
		
		//没有奖励内容时，返回错误3
		if( empty($arrGift['ret']) )
		{
			$arrRet['ret'] = 3;
			return $arrRet;
		}
		
		$arrReward = array();
		foreach ($arrGift['ret'] as $gift)
		{
			if ( $gift['item_type'] == RewardConfType::ITEM || $gift['item_type'] == RewardConfType::ITEM_MULTI  )
			{
				$arrReward[] = array( 'type' => RewardConfType::ITEM_MULTI, 
						'val' => array( array( $gift[ 'item_id' ], $gift[ 'item_num' ] ) ) 
				);
			}
			elseif ($gift['item_type'] == RewardConfType::HERO || $gift['item_type'] == RewardConfType::HERO_MULTI)
			{
				if ( !HeroUtil::checkHtid( $gift['item_id'] ) )
				{
					throw new FakeException( 'invalid htid: %d', $gift['item_id'] );
				}
				$arrReward[] = array( 'type' => RewardConfType::HERO_MULTI,
						'val' => array( array( $gift[ 'item_id' ], $gift[ 'item_num' ] ) )
				);
			}
			elseif ($gift['item_type'] == RewardConfType::TREASURE_FRAG_MULTI )
			{
				$arrReward[] = array( 'type' => RewardConfType::TREASURE_FRAG_MULTI, 
						'val' => array( array( $gift[ 'item_id' ], $gift[ 'item_num' ] ) ) 
				);
			}
			else 
			{
				$arrReward[] = array('type' => $gift['item_type'], 'val' => $gift['item_num']);
			}
		}
		
		$ret = RewardUtil::reward($uid, $arrReward, StatisticsDef::ST_FUNCKEY_REWARD_GIFT_CODE, true);
		if ($ret['userModify'])
		{
			$user->update();
		}
		if ($ret['bagModify']) 
		{
			BagManager::getInstance()->getBag($uid)->update();
		}
		$arrRet['reward'] = $ret['rewardInfo'];
		
		$arrRet['info'] = $arrGift['info'];
		
		return $arrRet;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */