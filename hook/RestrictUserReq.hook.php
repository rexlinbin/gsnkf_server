<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UpdateFightForce.hook.php 48299 2013-05-24 05:44:55Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/hook/UpdateFightForce.hook.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2013-05-24 13:44:55 +0800 (星期五, 24 五月 2013) $
 * @version $Revision: 48299 $
 * @brief 
 *  
 **/

class RestrictUserReq
{
	const DAY_NUM = 3;
	
	private static $ARR_CHECK_METHOD = array(
	        'user.userLogin',
			'bag.sellItems',
			'bag.useItem',
			'bag.bagInfo',
			'ecopy.buyAtkNum',
			'ecopy.doBattle',
			'ecopy.enterCopy',
			'ecopy.getEliteCopyInfo',
			'ecopy.leaveCopy',
			'formation.addHero',
			'formation.getFormation',
			'formation.getSquad',
			'formation.setFormation',
			'fragseize.fuse',
			'fragseize.getRecRicher',
			'fragseize.getSeizerInfo',
			'fragseize.seizeRicher',
			'hero.equipBestArming',
			'hero.evolve',
			'hero.getHeroBook',
			'hero.sell',
			'levelfund.gainLevelfundPrize',
			'levelfund.getLevelfundInfo',
			'mysmerchant.getShopInfo',
			'ncopy.doBattle',
			'ncopy.enterBaseLevel',
			'ncopy.getCopyList',
			'ncopy.getPrize',
			'ncopy.leaveBaseLevel',
			'online.gainGift',
			'shop.buyGoods',
			'shop.buyVipGift',
			'shop.getShopInfo',
			'shop.silverRecruit',
			'sign.gainAccSignReward',
			'sign.gainMonthSignReward',
			'sign.gainNormalSignReward',
			'sign.getMonthSignInfo',
			'sign.getNormalInfo',
			'star.addFavorByAllGifts',
			'star.getAllStarInfo',
			'supply.supplyExecution',
	);
	
	private static $ARR_LOGIN_METHOD = array(
	    'reward.getRewardList',
        'user.getChargeGold',
        'user.getChargeInfo',
        'dressroom.getDressRoomInfo',
        'guildrob.getGuildRobInfo',
        'boss.getBossOffset',
        'vipbonus.getVipBonusInfo',
        'desact.getDesactInfo',
        'iteminfo.getGodWeaponBook',
        'iteminfo.getTallyBook',
        'activity.getActivityConf',
        'countrywarinner.getCoutrywarInfoWhenLogin',
        'mysmerchant.getShopInfo',
        'friend.getBlackUids',
        'retrieve.getRetrieveInfo',
        'hero.getHeroBook',
	);
	
	private static $CHECK_LOGIN_REQ_TIME = '2016-4-21 16:53:00';
	
	public static function needRestrictRequestSpeed()
	{
		if ( !defined('PlatformConfig::PLAT_NAME') || !in_array(PlatformConfig::PLAT_NAME, array('android', 'appstore', 'yueyu') ) )
		{
			return false;
		}
		
		if (  !defined('GameConf::SERVER_OPEN_YMD') )
		{
			return false;
		}
	
		$openserverTime = strtotime(GameConf::SERVER_OPEN_YMD."000000");
		$dayIndex = intval( (Util::getTime() - $openserverTime) / SECONDS_OF_DAY );
	
		return $dayIndex < self::DAY_NUM;
	}
	
	
	public static function genBanUserNumKey($uid)
	{
		return 'ban_user_num_'.$uid;
	}
	
	public static function getBanNum($uid)
	{
		$key = self::genBanUserNumKey($uid);
		$ret = McClient::get($key);
		if ( empty($ret) )
		{
			return 0;
		}
		return $ret;
	}
	
	public static function setBanNum($uid, $num)
	{
		$key = self::genBanUserNumKey($uid);
		$ret = McClient::set($key, $num, 86400 * self::DAY_NUM);
		if ( $ret != 'STORED' )
		{
			Logger::warning('set ban user num failed. info:%s', $num);
		}
	}
	
	public static function getConnNumOfIp()
	{
		try 
		{
			$proxy = new PHPProxy ( 'lcserver' );
			$ip = RPCContext::getInstance()->getFramework()->getClientIp();
			$ret = $proxy->getConnNumOfIp( $ip );
			return $ret;
		}
		catch(Exception $e)
		{
			Logger::warning("getConnNumOfIp fail. err:%s", $e->getMessage());
		}
		return 0;
	}
	
	function execute ($arrRequest)
	{
		if( WorldUtil::isCrossGroup() )
		{
			Logger::debug('is cross group');
			return $arrRequest;
		}
		
		if ( empty( $arrRequest ['private'] ) )
		{
			$requestMethodType = RequestMethodType::E_PUBLIC;
		}
		else
		{
			$requestMethodType = $arrRequest ['private'];
		}
		//只能在用户发起的请求内处理
		if ( $requestMethodType != RequestMethodType::E_PUBLIC )
		{
			return $arrRequest;
		}
		
		if ( !in_array( $arrRequest['method'], self::$ARR_CHECK_METHOD )
		    &&  !in_array( $arrRequest['method'], self::$ARR_LOGIN_METHOD ) )
		{
			return $arrRequest;
		}
		
		if( !self::needRestrictRequestSpeed() )
		{
			return $arrRequest;
		}
		
		$uid = RPCContext::getInstance()->getUid();
		if ($uid < FrameworkConfig::MIN_UID)
		{
		    if($arrRequest['method'] == 'user.userLogin')
		    {
		        self::initUidReqInfo($arrRequest['args'][0], Util::getTime());
		    }
			return $arrRequest;
		}

		$userObj = EnUser::getUserObj($uid);
		if ( $userObj->getLevel() >= 40 || $userObj->getVip() > 0 )
		{
			Logger::debug('user level:%d, vip:%d ignore', $userObj->getLevel(), $userObj->getVip());
			return $arrRequest;
		}
		
		$reqNum = RPCContext::getInstance()->getSession('global.reqNum');
		if ( empty($reqNum) )
		{
			Logger::debug('no reqNum in session or reqNum = 0, ignore');
			return $arrRequest;
		}
		
		$arrSpecReq = array('bag.bagInfo');
		$specReqNum = RPCContext::getInstance()->getSession('global.specReqNum');
		if( empty($specReqNum) )
		{
			$specReqNum = 0;
		}
		if( in_array($arrRequest['method'], $arrSpecReq) )
		{
			$specReqNum += 1;
			RPCContext::getInstance()->setSession('global.specReqNum', $specReqNum);
		}
		
		$banByRule = 0;
		
		$arrReqTooEarly = array('fragseize.getRecRicher', 'ncopy.doBattle');
		if( in_array($arrRequest['method'], $arrReqTooEarly) && $reqNum < 40 )
		{
		    $uidReqInfo = self::getUidReqInfo($uid);
		    if(  !empty($uidReqInfo)
		        && count($uidReqInfo['req']) < count(self::$ARR_LOGIN_METHOD) )
		    {
		        $banByRule = 1;
		        Logger::warning('uid:%d, numLoginReq:%d, curReqNum:%d', $uid, count($uidReqInfo['req']), $reqNum);
		    }
		}
		if( in_array($arrRequest['method'], self::$ARR_LOGIN_METHOD) )
		{
		    $uidReqInfo = self::getUidReqInfo($uid);
		    if( !empty($uidReqInfo)
		        && !in_array($arrRequest['method'], $uidReqInfo['req'])  )
		    {
		        $uidReqInfo['req'][] = $arrRequest['method'];
		        self::setUidReqInfo($uid, $uidReqInfo);
		    }
		}
		
		$reqNumForSpeed = $reqNum - 64; //经验性扣除登陆请求
		if ( $reqNumForSpeed < 0  )
		{
		    $reqNumForSpeed = 0;
		}
		$now = Util::getTime();
		$timeDelt = $now - $userObj->getLastLoginTime();
		$speed = $reqNumForSpeed * 60 / ($timeDelt > 0 ? $timeDelt : 1);
		Logger::debug('uid:%d, reqNum:%d, timeDelt:%d, speed:%f, level:%d', $uid, $reqNum, $timeDelt, $speed, $userObj->getLevel());
		
		$arrCond = array(
				array(10, 15, 120),
				array(20, 3, 300),
				array(20, 5, 120),
				array(25, 6, 0),
				array(35, 4, 0),
				array(30, 3, 300),
				array(60, 1, 0),
		);
		$minSpeedCond = 9999;
		foreach( $arrCond as $value )
		{
			if( $minSpeedCond > $value[0] )
			{
				$minSpeedCond = $value[0];
			}
		}
		
		if ( ( $timeDelt > 30 && $speed > $minSpeedCond) || $banByRule > 0)
		{
			$conNumOfIp = self::getConnNumOfIp();
			Logger::warning('uid:%d, reqNum:%d, timeDelt:%d, speed:%f, connNum:%d, specReqNum:%d', $uid, $reqNum, $timeDelt, $speed, $conNumOfIp, $specReqNum);
			foreach( $arrCond as $value )
			{
				if( $speed >= $value[0] && $conNumOfIp >= $value[1] && $timeDelt >= $value[2] )
				{
					$banByRule = 2;
					break;
				}
			}
			
			if ($banByRule == 0 && $specReqNum > 2 && !in_array($arrRequest['method'], $arrSpecReq))
			{
				$info = RestrictUser::getIpLoginInfo();
				if ( count($info['uids']) > 5 )
				{
					Logger::warning('specReq_ip. specReqNum:%d, loginNum:%d', $specReqNum, count($info['uids']));
					$banByRule = 3;
				}
			}
			
			if ( $banByRule > 0 )
			{
				$banNum = self::getBanNum($uid);
				$banTime = 30*60*pow(4, $banNum);
				$banNum += 1;
				if ( $banTime > 3*86400 )
				{
					$banTime = 3*86400;
				}
				
				$userObj->ban(Util::getTime() + $banTime, "账号封停，请远离外挂");
				
				Logger::warning('too speed, ban user. uid:%d, reqNum:%d, timeDelt:%d, speed:%f, conNum:%d, banTime:%d, banNum:%d, specReqNum:%d, banByRule:%d',
					$uid, $reqNum, $timeDelt, $speed, $conNumOfIp, $banTime, $banNum, $specReqNum, $banByRule);
				
				$userObj->update();
				self::setBanNum($uid, $banNum);
				throw new Exception('close');
			}
		}
		
		return $arrRequest;
		
	}
	
	public static function getUidReqKey($uid)
	{
	    return 'user_req_'.$uid;
	}
	
	public static function initUidReqInfo($uid, $loginTime)
	{
	    $key = self::getUidReqKey($uid);
	    $info = array(
	        'time' => $loginTime,
	        'req' => array(),
	    );
	    $ret = McClient::set($key, $info, 3600*3);
	    Logger::info('initUidReqInfo for uid:%d', $uid);
	}
	
	public static function getUidReqInfo($uid)
	{
	    $key = self::getUidReqKey($uid);
	    $ret = McClient::get($key);
	    $userObj = EnUser::getUserObj($uid);
	    if($userObj->getLastLoginTime() > strtotime(self::$CHECK_LOGIN_REQ_TIME) 
		        && $userObj->getLastLoginTime() == $ret['time'] )
	    {
	        return $ret;
	    }
	    return array();
	}
	
	public static function setUidReqInfo($uid, $info)
	{
	    //$info['req'] = self::array2bin($info['req']);
	    
	    $key = self::getUidReqKey($uid);
	    $ret = McClient::set($key, $info, 3600*3);
	    if ( $ret != 'STORED' )
	    {
	        Logger::warning('setUidReqInfo failed. info:%s', $info);
	    }
	}
	
	

	public static function array2bin( $arrInt, $width = 25 )
	{
	    $arrBin = array();
	    foreach($arrInt as $i)
	    {
	        $binIndex = intval($i / $width);
	        $i = intval($i % $width);
	        $bin = 0;
	        if(isset($arrBin[$binIndex]))
	        {
	            $bin = $arrBin[$binIndex];
	        }
	        $bin += (1<<$i);
	
	        $arrBin[$binIndex] = $bin;
	    }
	
	    return $arrBin;
	}
	
	
	
	public static function bin2Array( $arrBin, $width = 25 )
	{
	    $arrInt = array();
	    foreach($arrBin as $binIndex => $bin)
	    {
	        for($i = 0; $i < $width; $i++)
	        {
	            if(  ($bin & (1<<$i)) )
	            {
	                $arrInt[] = $binIndex * $width + $i;
	            }
	        }
	    }
	
	    return $arrInt;
	}
	

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
