<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GroupOnLogic.class.php 153189 2015-01-16 13:34:48Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/GroupOnLogic.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-01-16 13:34:48 +0000 (Fri, 16 Jan 2015) $$
 * @version $$Revision: 153189 $$
 * @brief 
 *  
 **/
class GroupOnLogic
{
    //判断玩家团购活动是否开启
    public static function isGroupOnOpen()
    {
        $user = EnUser::getUserObj();
        //活动配置
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        $confData = $conf['data'];
        $confSpecific = $confData[GroupOnConf::DEFAULT_ID];
        if($user->getLevel() >= $confSpecific[GroupOnDef::VIP])
        {
            return TRUE;
        }
        return FALSE;
    }

    public static function checkState()
    {
        if(EnActivity::isOpen(ActivityName::GROUPON) == FALSE)
        {
            throw new FakeException('now %d is not during groupon time', Util::getTime());
        }
        if(self::isGroupOnOpen() == FALSE)
        {
            throw new FakeException('groupon is not open for this user whose vip is %d', EnUser::getUserObj()->getVip());
        }
    }

    public static function getShopInfo()
    {
        //场景标识
        RPCContext::getInstance()->setSession(SPECIAL_ARENA_ID::SESSION_KEY, SPECIAL_ARENA_ID::GROUPON);
      
        $groupOnPub = GroupOnPub::getInstance();
        $myGroupOn = new MyGroupOn();
        
        $shopInfo = array(
        		GroupOnDef::TBL_FIELD_GOODSLIST => array(),
        		'day' => $groupOnPub->getDayIndex(),
        );
        
        $goodsList = $groupOnPub->getGoodsListOfDay();
        foreach($goodsList as $goodId => $soldNum)
        {
            $shopInfo[GroupOnDef::TBL_FIELD_GOODSLIST][$goodId] = array(
            	'soldNum' => $soldNum,
            	'state' => $myGroupOn->hasBuyGood($goodId) ? 1 : 0,
            	'rewards' => $myGroupOn->getRecRewardId($goodId),
            );
        }

        $myGroupOn->updateUserData();

        self::refreshGoodList();
        
        return $shopInfo;
    }


    public static function buyGood($goodId)
    {
        $uid = RPCContext::getInstance()->getUid();
        $myGroupOn = new MyGroupOn($uid);
        $groupOnPub = GroupOnPub::getInstance();
        
        $goodsList = $groupOnPub->getGoodsListOfDay();
        if(!in_array($goodId, array_keys($goodsList)))
        {
            throw new FakeException('do not has this goodid:%d in goodlist:%s', $goodId, $goodsList);
        }

        //获取活动配置
        $confData = GroupOnUtil::getGoodConf();
        if( $myGroupOn->hasBuyGood($goodId) )
        {
            throw new FakeException('has buy goodId:%d', $goodId);
        }
        $needGold = $confData[$goodId][GroupOnDef::PRICE]; //所需金币数量
        $userObj = EnUser::getUserObj($uid);
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GROUPON_SPEND_GOLD) == false)
        {
            throw new FakeException('GroupOn::bugGood subGold failed');
        }
        //转出请求 总购买次数+1
        RewardUtil::reward3DArr($uid, $confData[$goodId][GroupOnDef::GOOD], StatisticsDef::ST_FUNCKEY_GROUPON_GOOD);
        Logger::trace('uid:%d executeTask to increace times of groupon on goodid %d.', $uid, $goodId);
        RPCContext::getInstance()->executeTask(GroupOnConf::SPECIAL_UID, 'groupon.incGroupOnTimes', array(GroupOnConf::SPECIAL_UID, $goodId), false);
     
        $myGroupOn->buyGood($goodId);
  
        $pushInfo = array(
        		$goodId => $groupOnPub->getGoodBuyNum($goodId) + 1
        );
        //给前端推送，现在该商品的团购人数
        RPCContext::getInstance()->sendFilterMessage(
            'arena',
            SPECIAL_ARENA_ID::GROUPON,
            PushInterfaceDef::GROUPON_BUY_GOOD,
            $pushInfo
        );
        
        //更新数据
        $myGroupOn->updateUserData();
        return 'ok';
    }

    public static function recReward($uid, $goodId, $rewardId)
    {
        //获取活动配置
        $actConf = GroupOnUtil::getGoodConf();

        $rewardIndex = intval( substr($rewardId, strlen(GroupOnDef::REWARD)) ) - 1;
        if($rewardIndex < 0)
        {
            throw new FakeException("invalid param. rewardIndex:%d smaller than zero rewardId:%d", $rewardIndex, $rewardId);
        }
        if( !isset($actConf[$goodId][GroupOnDef::REWARD][$rewardIndex]) )
        {
        	throw new FakeException('invalid param. goodId:%d, reward:%s', $goodId, $rewardId);
        }
        if( !isset($actConf[$goodId][GroupOnDef::NUM][$rewardIndex] ) )
        {
        	throw new ConfigException('have rewardId:%s, but not found num', $rewardId);
        }

        $groupOnPub = GroupOnPub::getInstance();
        if( $groupOnPub->getGoodBuyNum($goodId) < $actConf[$goodId][GroupOnDef::NUM][$rewardIndex] )
        {
        	throw new FakeException('cant rec reward goodId:%d, rewardId:%s, curBuyNum:%d, needBuyNum:%d', 
        				$goodId, $rewardId, $groupOnPub->getGoodBuyNum($goodId), $actConf[$goodId][GroupOnDef::NUM][$rewardIndex]);
        }

        $myGroupOn = new MyGroupOn($uid);
        if( !$myGroupOn->hasBuyGood($goodId) )
        {
            throw new FakeException('not buy goodId:%d', $goodId);
        }
        if( $myGroupOn->hasRecReward($goodId, $rewardId) )
        {
            throw new FakeException('already rec goodId:%d, reward:%s', $goodId, $rewardId);
        }

        $myGroupOn->recReward($goodId, $rewardId);
        
        RewardUtil::reward3DArr($uid, $actConf[$goodId][GroupOnDef::REWARD][$rewardIndex], StatisticsDef::ST_FUNCKEY_GROUPON_GOOD);
        
        //更新数据
        $myGroupOn->updateUserData();
        return 'ok';
    }

    public static function refGoodsList()
    {
        $groupOnPub = GroupOnPub::getInstance();
        
        if( $groupOnPub->needRefreshGoodList() )
        {
        	$groupOnPub->refreshGoodList();
        }
        
        $groupOnPub->updateGroupOnData();
    }

    public static function incGroupOnTimes($goodId)
    {
        $groupOnPub = GroupOnPub::getInstance();
        
        $groupOnPub->addGoodBuyNum($goodId);
        
        $groupOnPub->updateGroupOnData();
    }
    
    public static function refreshGoodList()
    {
    	$groupOnPub = GroupOnPub::getInstance();
    	if( $groupOnPub->needRefreshGoodList() )
    	{
    		Logger::info('need refresh good list');
    		RPCContext::getInstance()->executeTask(
    			GroupOnConf::SPECIAL_UID, 
    			'groupon.refGoodsList', 
    			array(GroupOnConf::SPECIAL_UID), 
    			false);
    	}
    }

    public static function doReissue()
    {
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        $beginTime = $conf['start_time'];
        $endTime = $conf['end_time'];
        if( Util::getTime() > $endTime ) //活动结束
        {
            throw new InterException('groupon activtiy ended. cannot doreissue. now:%d, endtime:%d', Util::getTime(), $endTime);
        }
        
        $goodConf =  $conf['data'];
        $actConf = $conf['data'][GroupOnConf::DEFAULT_ID];

        $arrRet = GroupOnDao::selectAllUserData($beginTime, $endTime);
        $userList = Util::arrayIndex($arrRet, GroupOnDef::UID);
        foreach($userList as $uid => $data)
        {
        	try 
        	{
        		$buyTime = $data[GroupOnDef::BUYTIME];
        		$userData = $data[GroupOnDef::USERVADATA];
        		
        		if( FrameworkConfig::DEBUG == false && Util::isSameDay($buyTime) )
        		{
        			throw new InterException('uid:%d buy today', $uid);
        		}
        		
        		$lastBuyDay = self::getActivityDay($beginTime, $buyTime);
        		
        		list($arrLeftReward, $arrLeftRewardId) = MyGroupOn::getLeftReward($userData, $lastBuyDay, $actConf, $goodConf);
        		
        		if( empty($arrLeftRewardId) )
        		{
        			Logger::info('uid:%d no left reward. ignore', $uid);
        			continue;
        		}
        		
        		$arrRet = EnReward::getRewardByUidTime($uid, RewardSource::GROUPON, $beginTime);
        		if( !empty( $arrRet ) )
        		{
        			throw new InterException('uid:%d already get left reward. ignore', $uid);
        		}
  				
        		MyGroupOn::setUserRecReward($uid, $userData, $arrLeftRewardId);
        		
        		RewardUtil::reward3DtoCenter($uid, $arrLeftReward, RewardSource::GROUPON);
        	
        		Logger::info('send left reward to center. uid:%d, reward:%s', $uid, $arrLeftReward);
        	}
        	catch (Exception $e)
        	{
        		Logger::fatal('reissue failed. uid:%d, error:%s', $uid, $e->getMessage() );
        	}
           
        }
    }
    
    public static function getActivityDay($startTime, $checkTime)
    {
    	if( $startTime > $checkTime )
    	{
    		return -1;
    	}

    	$ret = intval(
    					(	strtotime ( date ( "Y-m-d ", $checkTime ) ) -
    						strtotime ( date ( "Y-m-d ", $startTime ) )
                    	) / 86400 
                    );
        return $ret;
    	 
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */