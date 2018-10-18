<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MyGroupOn.class.php 151576 2015-01-10 09:59:17Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/groupon/MyGroupOn.class.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2015-01-10 09:59:17 +0000 (Sat, 10 Jan 2015) $$
 * @version $$Revision: 151576 $$
 * @brief 
 *  
 **/

/**
 * 团购个人数据管理类
 * protected $userModify 玩家团购数据
 * array
 * [
 *      usergrdata => array
 *      [
 *          活动期间内，所有买过的东西都记录，然后，给前端返回的时候，过滤出来，当天买的
 *          goodid:array 已购商品id => array(rewardid:int 已领取奖品id)
 *      ]
 * ]
 */
class MyGroupOn
{
    protected $uid = 0;
    protected $userData = NULL;
    protected $userModify = NULL;

    protected $buytime = 0;
    //protected $leftReward = NULL;

    public function __construct($uid=0)
    {
        if(empty($uid))
        {
            $uid = RPCContext::getInstance()->getUid();
        }
        $this->uid = $uid;
        $this->loadUserData($uid);
        Logger::debug('userModify:%s', $this->userModify);
    }

    public function loadUserData($uid)
    {
        if($this->userModify != null)
        {
        	Logger::trace('aready loaddata');
           	return;
        }
        $data = GroupOnDao::selectGroupOnUser($uid);
        if( empty($data) )
        {
        	$this->userModify = self::getInitData();
        	$this->buytime = 0;//最后一次购买时间
        }
        else
        {
        	$this->userModify = $data[GroupOnDef::USERVADATA];
        	$this->buytime = $data[GroupOnDef::BUYTIME];
        }
        Logger::debug('loadUserData. buyTime:%d, userModify:%s', $this->buytime, $this->userModify);
        
        $conf = EnActivity::getConfByName(ActivityName::GROUPON);
        if( $this->buytime < $conf['start_time']  )
        {
        	Logger::info('clear last round data');
        	$this->userModify = self::getInitData();
        }

        $this->userData = $this->userModify;
        
        //刷新数据， 检查是否有未领取奖励 奖励都放到最后发
        /*if( !Util::isSameDay($this->buytime) )
        {
        	$actConf = GroupOnLogic::getActConf();
        	$goodConf = GroupOnLogic::getGoodConf();
        	
        	$lastBuyDay = EnActivity::getActivityDay(ActivityName::GROUPON, $this->buytime);
        	list($this->leftReward, $arrRewardId) = self::getLeftReward($this->userModify, $lastBuyDay, $actConf, $goodConf);
        	
        	$this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA] = array();
        }*/

    }

    /**
     * 待补发的奖励
     * @param $userData
     * @param $lastBuyDay
     * @param $actConf
     * @param $goodConf
     * @return array
     */
    public static function getLeftReward($userData, $lastBuyDay, $actConf, $goodConf)
    {
    	$arrBuyInfo = $userData[GroupOnDef::TBL_FIELD_USER_GROUP_DATA];
    	$arrLeftReward = array();
    	$arrLeftRewardId = array();
    	
    	if( !empty($arrBuyInfo)
    		&& $lastBuyDay >=0 )
    	{ 
    		$groupOnPub = GroupOnPub::getInstance();
    		$goodsList = $groupOnPub->getGoodList();
    		 
    		foreach($goodsList as $goodId => $soldNum)
    		{
    			if ( !isset( $arrBuyInfo[$goodId]  ) )
    			{
    				continue;
    			}
    			for( $i = 1; $i <= $goodConf[$goodId][GroupOnDef::NUMTOP]; $i++)
    			{
	    			if( $soldNum < $goodConf[$goodId][GroupOnDef::NUM][$i - 1] )
	    			{
	    				break;
	    			}
	    			$rewardId = "reward$i";
	    			if(in_array($rewardId, $arrBuyInfo[$goodId]))
	    			{
	    				continue;
	    			}
	 
	    			$arrLeftReward[] = $goodConf[$goodId][GroupOnDef::REWARD][$i - 1];
	    			if( isset( $arrLeftRewardId[$goodId] ))
	    			{
	    				$arrLeftRewardId[$goodId][] = $rewardId;
	    			}
	    			else 
	    			{
	    				$arrLeftRewardId[$goodId] = array( $rewardId );
	    			}
    			}
    		}
    	}
    	
    	Logger::debug('getLeftReward:%s', $arrLeftReward);
    	
    	return array($arrLeftReward, $arrLeftRewardId);
    }

    public static function setUserRecReward($uid, $userData, $arrGoodIdRewardId)
    {
    	$arrBuyInfo = $userData[GroupOnDef::TBL_FIELD_USER_GROUP_DATA];
    	
    	foreach( $arrGoodIdRewardId as $goodId => $arrRewardId )
    	{
    		if( !isset( $arrBuyInfo[$goodId] ) )
    		{
    			throw new InterException('uid:%d not buy goodId:%d. arrGoodIdRewardId:%s', $uid, $goodId, $arrGoodIdRewardId);
    		}
    		$intersect = array_intersect( $arrRewardId,  $arrBuyInfo[$goodId]);
    		if (  !empty($intersect) )
    		{
    			throw new InterException('some reward already rec. uid:%d, goodId:%d, userData:%s, arrGoodIdRewardId:%s',
    					$uid, $goodId, $userData, $arrGoodIdRewardId);
    		}
    		
    		$arrBuyInfo[$goodId] = array_merge($arrBuyInfo[$goodId], $arrRewardId);
    		
    		Logger::debug('uid:%d rec reward. goodId:%d, arrRewardId:%s', $uid, $goodId, $arrRewardId);
    	}
    	
    	$userData[GroupOnDef::TBL_FIELD_USER_GROUP_DATA] = $arrBuyInfo;
    	
    	$arrField = array(
    			GroupOnDef::UID => $uid,
    			GroupOnDef::USERVADATA => $userData,
    	);
    	$arrRet = GroupOnDao::updateUserData($uid, $arrField);
   		if ( $arrRet[ DataDef::AFFECTED_ROWS ] != 1 )
		{
			throw new InterException( 'update failed. uid:%d', $uid );
		}
    }

    /**
     * 返回 已领过的奖励Id
     * @param $goodId
     * @return array
     */
    public function getRecRewardId($goodId)
    {
    	if( !isset( $this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA][$goodId] ) )
    	{
    		return array();
    	}
    	return $this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA][$goodId];
    }

    public function hasBuyGood($goodId)
    {
    	return isset( $this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA][$goodId] );
    }
    
    public function buyGood($goodId)
    {
    	if( $this->hasBuyGood($goodId) )
    	{
    		throw new FakeException('already buy goodId:%d', $goodId);
    	}
    	$this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA][$goodId] = array();
    	$this->buytime = Util::getTime();
    }
  
    public function hasRecReward($goodId, $rewardId)
    {
    	if( ! $this->hasBuyGood($goodId) )
    	{
    		throw new FakeException('not buy goodId:%d', $goodId);
    	}
    	return in_array($rewardId, $this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA][$goodId]);
    }
    
    public function recReward($goodId, $rewardId)
    {
    	if( ! $this->hasBuyGood($goodId) )
    	{
    		throw new InterException('not buy goodId:%d, cant rec reward:%s', $goodId, $rewardId);
    	}
    	
    	if ( $this->hasRecReward($goodId, $rewardId) )
    	{
    		throw new InterException('already rec reward. goodId:%d, rewardId:%s', $goodId, $rewardId);
    	}
    	
    	$this->userModify[GroupOnDef::TBL_FIELD_USER_GROUP_DATA][$goodId][] = $rewardId;
    }
    
    public function updateUserData()
    {
        //只能在自己的连接中改数据
        $uid = RPCContext::getInstance()->getUid();
        if($uid != $this->uid)
        {
            throw new InterException('Cant update data in other user connection. uid:%d in session, this uid:%d', $uid, $this->uid);
        }
        if($this->userModify != $this->userData)
        {
            $arrField = array(
                GroupOnDef::UID => $this->uid,
                GroupOnDef::BUYTIME => $this->buytime,
                GroupOnDef::USERVADATA => $this->userModify
            );
            GroupOnDao::iOrUUsrData($arrField);

            /*if( !empty($this->leftReward) && $this->uid == RPCContext::getInstance()->getUid() )
            {
                Logger::info('send left reward to center. uid:%d, reward:%s', $this->uid, $this->leftReward);
                RewardUtil::reward3DtoCenter($this->uid, $this->leftReward, RewardSource::GROUPON);
                $this->leftReward = array();
            }*/
        }
        $this->userData = $this->userModify;
        
    }

    public static function getInitData()
    {
    	return array(
    			GroupOnDef::TBL_FIELD_USER_GROUP_DATA => array()
    	);
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */