<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: EnUser.class.php 234891 2016-03-25 05:19:41Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL:
 * svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/module/user/EnUser.class.php $
 * 
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 *         @date $Date: 2016-03-25 05:19:41 +0000 (Fri, 25 Mar 2016) $
 * @version $Revision: 234891 $
 *          @brief
 *         
 *         
 */
class EnUser
{
	/**
	 * 保存UserObj
	 * uid => UserObj
	 *
	 * @var unknown_type
	 */
	private static $arrUser = array();
	
	/**
	 * 别的服或者跨服上，通过lcserver获取某个玩家的战斗数据
	 * 
	 * @param number $serverId
	 * @param number $pid
	 * @param string $db
	 * @return array
	 */
	public static function getBattleFormationByOtherGroup($serverId, $pid, $db = NULL)
	{
		$battleFormation = array();
		try
		{
			$group = Util::getGroupByServerId($serverId);
			$proxy = new ServerProxy();
			$proxy->init($group, Util::genLogId());
			$battleFormation = $proxy->syncExecuteRequest('user.getBattleFormation', array($serverId, $pid));
		}
		catch (Exception $e)
		{
			Logger::fatal('getBattleFormation error serverGroup:%s', $serverId);
		}
		return $battleFormation;
	}

	/**
	 *
	 * @return UserObj
	 */
	public static function getUserObj ($uid = 0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
			if ($uid == null)
			{
				throw new FakeException('uid and global.uid are 0');				
			}
		}
	
		if (!isset(self::$arrUser[$uid]))
		{
			if ($uid == RPCContext::getInstance()->getUid())
			{
				self::$arrUser[$uid] = new UserObj($uid);
			}
			else
			{
				self::$arrUser[$uid] = new OtherUserObj($uid);
			}
		}
		return self::$arrUser[$uid];
	}
	

	public static function release ($uid = 0)
	{
		if ($uid == 0)
		{
			self::$arrUser = array();
		}
		else if (isset(self::$arrUser[$uid]))
		{
			unset(self::$arrUser[$uid]);
		}
	}
	

	/**
	 * 取等级>=$level的$num个uid
	 * @param int $level
	 * @param int $num
	 * @return
	 * array
	 * {
	 * 		array
	 * 		{
	 * 			uid=>
	 * 			level=>
	 * 			....
	 * 		}
	 * }
	 */
	public static function getUsersWithHigherLevel($level, $num, $offset=0, $arrField=null)
	{
		if(empty($arrField))
		{
			$arrField = UserDef::$USER_FIELDS;
		}
		return UserDao::getArrUsersByLevel($level, $num, '>', $arrField, $offset );
	}
	public static function getUsersWithHigherEqualLevel($level, $num, $offset=0, $arrField=null)
	{
		if(empty($arrField))
		{
			$arrField = UserDef::$USER_FIELDS;
		}
	    return UserDao::getArrUsersByLevel($level, $num, '>=', $arrField, $offset );
	}
	
	/**
	 *
	 * 取等级>=$level的$num个uid
	 * @param int $level
	 * @param int $num
	 */
	public static function getUsersWithLowerLevel($level, $num,$offset=0,$arrField=null)
	{
	    if(empty($arrField))
	    {
	        $arrField = UserDef::$USER_FIELDS;
	    }
		return UserDao::getArrUsersByLevel($level, $num, '<', $arrField ,$offset);
	}
	
	public static function getUsersWithLowerEqualLevel($level,$num,$offset=0,$arrField=null)
	{
	    if(empty($arrField))
	    {
	        $arrField = UserDef::$USER_FIELDS;
	    }
	    return UserDao::getArrUsersByLevel($level, $num, '<=', $arrField ,$offset);
	}
	
	/**
	 * 随机获取等级范围[$lowLevel, $highLevel]之前随机num个玩家
	 */
	public static function getArrUserBetweenLevel($arrField, $lowLevel, $highLevel, $num)
	{
		if($num > CData::MAX_FETCH_SIZE)
		{
			Logger::fatal('cant get too much data.num is %d.',$num);
			return array();
		}
		$allNum = UserDao::getUserNumBetweenLevel($lowLevel, $highLevel);
		if($allNum <= 0)
		{
			Logger::trace('not found user between level[%d, %d]', $lowLevel, $highLevel);
			return array();
		}
		
		$offset = 0;
		if($allNum > $num)
		{
			$offset = rand(0, $allNum - $num);
		}
		$arrRet = UserDao::getArrUserBetweenLevel($arrField, $lowLevel, $highLevel, $offset, $num);
		
		return $arrRet;
	}
	
	/**
	 * 返回历史充值过的所有金币数
	 */
	public static function getSumGold ()
	{
		return User4BBpayDao::getSumGoldByUid(RPCContext::getInstance()->getUid());
	}
	
	/**
	 * 返回 >=time1 <=time2 的充值总数
	 * 
	 * @param int $time1        	
	 * @param int $time2        	
	 * @param int $uid        	
	 * @return number
	 */
	public static function getRechargeGoldByTime ($time1, $time2, $uid=0, $includeItem=FALSE)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		return User4BBpayDao::getSumGoldByTime($time1, $time2, $uid, $includeItem);
	}
	
	/**
	 * 获取玩家在某个时间段内的所有充值订单
	 * @param int $time1
	 * @param int $time2
	 * @param int $uid
	 * @return array
	 * [
	 *     array
	 *     [
	 *         order_id:string 充值订单号
	 *         gold_num:int 充值金额
	 *         mtime:int    充值时间
	 *     ]
	 * ]
	 */
	public static function getChargeOrderByTime($time1, $time2, $uid = 0)
	{
	    if ($uid == 0)
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    return User4BBpayDao::getArrOrderByTime($time1, $time2, $uid);
	}
	
	public static function isCurUserOwnItem($itemId,$itemType=ItemDef::ITEM_TYPE_ARM)
	{
	    $type = NULL;
	    foreach(HeroDef::$EQUIPTYPE_TO_ITEMTYPE as $equipTypeDef => $itemTypeDef)
	    {
	        if($itemTypeDef == $itemType)
	        {
	            $type = $equipTypeDef;
	        }
	    }
	    if($type === NULL)
	    {
	        throw new FakeException('no such itemtype %s',$itemType);
	    }
		$heroes	=	Enuser::getUserObj()->getHeroManager()->getAllHeroObjInSquad();
		foreach($heroes as $hero)
		{
			$itemIds	=	$hero->getEquipByType ($type);
			if(in_array($itemId, $itemIds))
			{
				return TRUE;	
			}
		}
		$bag = BagManager::getInstance()->getBag();
		if($bag->isItemExist($itemId))
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
	public static function getExtraInfo($key,$uid=0)
	{
	    if ($uid == 0)
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
		$extraInfo    =    UserExtraDao::getUserExtra($uid, UserExtraDef::$ARR_FIELD);
		if(empty($extraInfo))
		{
		    return FALSE;
		}
		if(!isset($extraInfo['va_user'][$key]))
		{
		    return FALSE;
		}
		return $extraInfo['va_user'][$key];
	}
	
	
	public static function getExtraField($key,$uid=0)
	{
		if ($uid == 0)
		{
			$uid = RPCContext::getInstance()->getUid();
		}
		if(!isset(UserExtraDef::$VALID_FIELD[$key]))
		{
			throw new InterException('no such field %s.',$key);
		}
		$extraInfo = UserExtraDao::getUserExtra($uid, UserExtraDef::$ARR_FIELD);
		if(empty($extraInfo))
		{
			return FALSE;
		}
		if(!isset($extraInfo[$key]))
		{
			throw new InterException('no such field %s in DB.',$key);
		}
		return $extraInfo[$key];
	}
	
	/**
	 * 初始化用户时调用的
	 * @param unknown_type $arrValue
	 * @return multitype:number multitype: unknown
	 */
	public static function initUserExtra($arrValue)
	{
		$uid = $arrValue['uid'];
		//只需要uid字段
		return self::doInitUserExtra(array('uid' => $uid));
	}

	public static function doInitUserExtra($arrValue)
	{
		$uid = $arrValue['uid'];
		$arrField = array(
				UserExtraDef::USER_EXTRA_FIELD_UID => $uid,
				UserExtraDef::USER_EXTRA_FIELD_EXECUTION_TIME => 0,
		        UserExtraDef::USER_EXTRA_FIELD_SHARE_TIME => 0,
				UserExtraDef::USER_EXTRA_FIELD_OPEN_GOLD_NUM => 0,
				UserExtraDef::USER_EXTRA_FIELD_VA_USER => array()
		);
		foreach($arrField as $key => $value)
		{
			if( isset($arrValue[$key]) )
			{
				$arrField[$key] = $arrValue[$key];
			}
		}		
		UserExtraDao::initUserExtra($uid, $arrField);
		return $arrField;
	}
	
	
	/**
	 * 设置va中的字段
	 */
	public static function setExtraInfo($key, $value, $uid=0)
	{
	    if ($uid == 0)
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    $extraInfo = UserExtraDao::getUserExtra($uid, UserExtraDef::$ARR_FIELD);
	    if(empty($extraInfo))
	    {
	    	$arrValue = array(
	    			'uid' => $uid,
	    			'va_user' => array($key => $value),
	    			);
	        self::doInitUserExtra($arrValue);	        
	    }
	    else
	    {
		    $extraInfo['va_user'][$key] = $value;
		    $arrValue = array(
	    			'uid' => $uid,
	    			'va_user' => $extraInfo['va_user'],
	    			);
		    UserExtraDao::updateUserExtra($uid, $arrValue);
	    }
	    return true;
	}
	
	/**
	 * 设置 一般字段（不是va中的）
	 */
	public static function setExtraField($key, $value, $uid=0)
	{
	    if ($uid == 0)
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    if(!isset(UserExtraDef::$VALID_FIELD[$key]))
	    {
	        throw new InterException('no such field %s.',$key);
	    }
	    
	    //一般set之前都会先获取这个数据，所以当set时本地缓存中应该已有这个数据了
	    $extraInfo = UserExtraDao::getUserExtra($uid, UserExtraDef::$ARR_FIELD);
	    if(empty($extraInfo))
	    {
	    	$arrValue = array(
	    			'uid' => $uid,
	    			$key => $value,
	    	);
	    	self::doInitUserExtra($arrValue);
	    }
	    else
	    {
	    	$arrValue = array(
	    			$key => $value,
	    	);
	    	UserExtraDao::updateUserExtra($uid, $arrValue);
	    }
	    
	    return true;
	}
	
	
	
	/**
	 * 根据用户民模糊搜索
	 * @param string $uname
	 * @param array $arrField
	 * @param int $offset
	 * @param int $limit
	 * @return array
	 */
	public static function getByFuzzyName($uname, $arrField, $offset = 0, $limit = CData::MAX_FETCH_SIZE)
	{
		return UserDao::getByFuzzyName($uname, $arrField, $offset, $limit);
	}
	

	public static function getArrUserByArrPid($arrPid, $arrField, $afterLastLoginTime)
	{
		if (empty ( $arrPid ))
		{
			return array ();
		}
	
		if (! in_array ( 'uid', $arrField ))
		{
			$arrField [] = 'uid';
		}
		
		//取以下字段，需要计算
		$hasField = array_intersect (
	            array ('execution', 'execution_time', 'stamina', 'stamina_time' ),
	            $arrField );
		if (! empty ( $hasField ))
		{
			throw new InterException( 'not support some field for getArrUserByPid:%s', $hasField );
		}
		
		$arrWhere = array(
				array('last_login_time', '>=', $afterLastLoginTime),
				);
		$arrRet = UserDao::getArrUserByArrPid($arrPid, $arrField, $arrWhere);
	    
	    return Util::arrayIndex ( $arrRet, 'uid' );
	}
	
	
	/**
	 * 得到批量用户属性
	 * @param array $arrUid
	 * @param array $arrField
	 * @param bool $noCache 当arrUid较多时，建议不用缓存
	 */
	public static function getArrUser($arrUid, $arrField, $noCache = false, $db = '')
	{	
	    if (empty ( $arrUid ))
	    {
	        return array ();
	    }
	    
	    if (! in_array ( 'uid', $arrField ))
	    {
	        $arrField [] = 'uid';
	    }
	
	    //取以下字段，需要计算
	    $hasField = array_intersect (
	            array ('execution', 'execution_time', 'stamina', 'stamina_time' ),
	            $arrField );
	    if (! empty ( $hasField ))
	    {
	        throw new InterException( 'not support some field for getArrUser:%s', $hasField );
	    }
	
	    $arrRet = UserDao::getArrUserByArrUid($arrUid, $arrField, $noCache, $db);
	    
	    return Util::arrayIndex ( $arrRet, 'uid' );
	}
	
	
	public static function getUserLevel($uid, $db)
	{
		$level = -1;
		if( empty($db) )
		{
			$level = EnUser::getUserObj($uid)->getLevel();
		}
		else
		{
			$arrRet = EnUser::getArrUser( array($uid) , array('level'), false, $db);
			if( empty($arrRet) )
			{
				throw new InterException('not found uid:%d', $uid);
			}
			$level = $arrRet[$uid]['level'];
			Logger::debug('get level of other db. uid:%d, db:%s, level:%d', $uid, $db, $level);
		}
		return $level;
	}
	
	/**
	 * 返回玩家阵容上的武将htid信息。
	 * 注意：在阵容中允许出现隔位放（即 0=>htid1, 2=>htid一般情况下不会出现这种情况)，为了放置这样的数据传给前端时，解析出错（map和array的问题）
	 * 此函数返回时进行了array_values操作（即返回： 0=>htid1, 1=>htid2)
	 * @param array $arrUid
	 * @param bool $showDress        是否展示时装
	 * $return array
	 * [
	 *     uid:array
	 *     [
	 *         array
	 *         [
	 *             htid:int
	 *             dress:array
	 *         ]
	 *     ]
	 * ]
	 */
	public static function getArrUserSquad($arrUid,$showDress=TRUE)
	{
	    if(empty($arrUid))
	    {
	        return array();
	    }
	    $hid2Uid = array();
	    $arrHid = array();
	    $arrSquad = EnFormation::getArrUserSquad($arrUid);
	    foreach($arrSquad as $uid=>$squad)
	    {
	        foreach($squad as $index=>$hid)
	        {
	            $arrHid[] = $hid;
	            $hid2Uid[$hid] = $uid;
	        }
	    }
	    //从hero表中取出所有使用过多hero
	    $arrHeroUsed = HeroLogic::getArrHero($arrHid, array('hid','htid'));
	    $arrHero = $arrHeroUsed;
	    //从user表中取出unused hero	    
	    if(count($arrHeroUsed) < count($arrHid))
	    {
	    	$unusedHid2Uid = $hid2Uid;
	    	foreach($arrHeroUsed as $hid => $value)
	    	{
	    		unset( $unusedHid2Uid[$hid]  );
	    	}	    	
	        $arrHeroUnused = self::getUnusedArrHero($unusedHid2Uid);
	        $arrHero = $arrHeroUnused + $arrHeroUsed;
	    }
	    if($showDress)
	    {
	        $arrUser = EnUser::getArrUser($arrUid, array('uid','master_hid','va_user'));
	        foreach($arrUser as $uid => $userInfo)
	        {
	            $arrUser[$uid]['dress'] = array();
	            if(isset($userInfo['va_user'][VA_USER::DRESSINFO]))
	            {
	                $arrUser[$uid]['dress'] = $userInfo['va_user'][VA_USER::DRESSINFO];
	            }
	            unset($arrUser[$uid]['va_user']);
	        }
	    }
	    $ret = array();
	    foreach($arrSquad as $uid=>$squad)
	    {
	        $ret[$uid] = array();
	        $masterHid = $arrUser[$uid]['master_hid'];
	        foreach($squad as $index=>$hid)
	        {
	            if($masterHid == $hid && ($showDress) && (isset($arrUser[$uid]['dress'])))
	            {
	                $ret[$uid][$index]['dress'] = $arrUser[$uid]['dress'];
	            }
	            $ret[$uid][$index]['htid'] = $arrHero[$hid]['htid'];
	        }
	        ksort($ret[$uid]);
	        $ret[$uid] = array_values($ret[$uid]);
	    }
	    return $ret;
	}
	
	/**
	 * 
	 * @param array $arrUid
	 * @param array $arrField
	 * [
	 *     uid
	 *     name
	 *     level
	 *     fight_force
	 *     htid         玩家的主角htid   
	 *     dress        玩家的时装信息
	 * ]
	 * @return array
	 * [
	 *     uid:array
	 *     [
	 *         uid:int
	 *         name:string
	 *         level:int
	 *         htid:int
	 *         dress:array        可能为空array
	 *         [
	 *             1=>int
	 *         ]
	 *     ]
	 * ]
	 */
	public static function getArrUserBasicInfo($arrUid,$arrField, $db = '')
	{
	    if(count($arrUid) > CData::MAX_FETCH_SIZE)
	    {
	        throw new FakeException('can not fetch so many data.count is %d',count($arrUid));
	    }
	    if(empty($arrUid) || empty($arrField))
	    {
	        return array();
	    }
	    $arrUserField = array();
	    foreach($arrField as $field)
	    {
	        if(in_array($field, UserDef::$USER_FIELDS))
	        {
	            $arrUserField[] = $field;
	        }
	        else if($field == 'htid' && (in_array('master_hid', $arrUserField) == FALSE))
	        {
	            $arrUserField[] = 'master_hid';
	        }
	        else if($field == 'dress' && (in_array('va_user', $arrUserField) == FALSE))
	        {
	            $arrUserField[] = 'va_user';
	        }
	        else if ($field == 'server_id' && defined('GameConf::MERGE_SERVER_OPEN_DATE'))
	        {
	        	$arrUserField[] = 'server_id';
	        }
	        else 
	        {
	            throw new FakeException('can not fetch this field %s of user',$field);    
	        }
	    }
	    $arrUser = EnUser::getArrUser($arrUid, $arrUserField, TRUE, $db);
	    $arrHid = array();
	    foreach($arrUser as $uid => $userInfo)
	    {
	        if(in_array('dress', $arrField))
	        {
	            $arrUser[$uid]['dress'] = array();
	            if(isset($userInfo['va_user'][VA_USER::DRESSINFO]))
	            {
	                $arrUser[$uid]['dress'] = $userInfo['va_user'][VA_USER::DRESSINFO];
	            }
	        }
	        if(in_array('htid', $arrField))
	        {
	            $arrHid[] = $userInfo['master_hid'];
	        }
	        if(!in_array('master_hid', $arrField))
	        {
	            unset($arrUser[$uid]['master_hid']);
	        }
	        if(!in_array('va_user', $arrField))
	        {
	            unset($arrUser[$uid]['va_user']);
	        }
	    }
	    if(empty($arrHid))
	    {
	        return $arrUser;
	    }
	    $arrHero = HeroLogic::getArrHero($arrHid,array('htid','uid'), FALSE, $db);
	    $arrHero = Util::arrayIndex($arrHero, 'uid');
	    foreach($arrHero as $uid => $heroInfo)
	    {
	        $arrUser[$uid]['htid'] = $heroInfo['htid']; 
	    }
	    return $arrUser;
	}
	
	/**
	 * 获取N个小于等于某等级的用户的基本信息
	 * 
	 * @param int $level
	 * @param int $num
	 * @param array $arrField
	 * @param int $offset
	 * @param array $arrCond 
	 * [
	 *     uid
	 *     name
	 *     level
	 *     fight_force
	 *     htid         玩家的主角htid
	 *     dress        玩家的时装信息
	 * ]
	 * @return array
	 * [
	 *     uid:array
	 *     [
	 *         uid:int
	 *         name:string
	 *         level:int
	 *         htid:int
	 *         dress:array        可能为空array
	 *         [
	 *             1=>int
	 *         ]
	 *     ]
	 * ]
	 */
	public static function getArrUserBasicInfoWithLowerEqualLevel($num, $level, $arrField, $offset = 0, $arrCond = array(), $db = '')
	{
		if($num > CData::MAX_FETCH_SIZE)
		{
			throw new FakeException('can not fetch so many data.num is %d',$num);
		}
		if(empty($level) || empty($arrField))
		{
			return array();
		}
		$arrUserField = array();
		foreach($arrField as $field)
		{
			if(in_array($field, UserDef::$USER_FIELDS))
			{
				$arrUserField[] = $field;
			}
			else if($field == 'htid' && (in_array('master_hid', $arrUserField) == FALSE))
			{
				$arrUserField[] = 'master_hid';
			}
			else if($field == 'dress' && (in_array('va_user', $arrUserField) == FALSE))
			{
				$arrUserField[] = 'va_user';
			}
			else if($field == 'server_id' && defined('GameConf::MERGE_SERVER_OPEN_DATE'))
			{
				$arrUserField[] = 'server_id';
			}
			else
			{
				throw new FakeException('can not fetch this field %s of user',$field);
			}
		}
		$arrUser =  UserDao::getArrUsersByLevel($level, $num, '<=', $arrUserField, $offset, $arrCond, $db);
		$arrUser = Util::arrayIndex($arrUser, 'uid');
		$arrHid = array();
		foreach($arrUser as $uid => $userInfo)
		{
			if(in_array('dress', $arrField))
			{
				$arrUser[$uid]['dress'] = array();
				if(isset($userInfo['va_user'][VA_USER::DRESSINFO]))
				{
					$arrUser[$uid]['dress'] = $userInfo['va_user'][VA_USER::DRESSINFO];
				}
			}
			if(in_array('htid', $arrField))
			{
				$arrHid[] = $userInfo['master_hid'];
			}
			if(!in_array('master_hid', $arrField))
			{
				unset($arrUser[$uid]['master_hid']);
			}
			if(!in_array('va_user', $arrField))
			{
				unset($arrUser[$uid]['va_user']);
			}
		}
		if(empty($arrHid))
		{
			return $arrUser;
		}
		$arrHero = HeroLogic::getArrHero($arrHid,array('htid','uid'), FALSE, $db);
		$arrHero = Util::arrayIndex($arrHero, 'uid');
		foreach($arrHero as $uid => $heroInfo)
		{
			$arrUser[$uid]['htid'] = $heroInfo['htid'];
		}
		return $arrUser;
	}
	
	private static function getUnusedArrHero($hid2Uid)
	{
	    $arrUid = array_unique( $hid2Uid );
	    $arrUserInfo = EnUser::getArrUser($arrUid, array('uid','va_hero'));
	    $returnData = array();
	    foreach($hid2Uid as $hid => $uid)
	    {
	    	if( empty($arrUserInfo[$uid]['va_hero']['unused'][$hid][UserDef::UNUSED_HERO_HTID]) )
	    	{
	    		throw new InterException('not found hid:%d for uid:%d', $hid, $uid);
	    	}
	        $htid = $arrUserInfo[$uid]['va_hero']['unused'][$hid][UserDef::UNUSED_HERO_HTID];
	        $returnData[$hid] = array('hid'=>$hid,'htid'=>$htid);
	    }
	    return $returnData;
	}
	/**
	 * // 掉落物品
     * const DROP_TYPE_ITEM = 0;	
     * // 掉落武将		
     *const DROP_TYPE_HERO = 1;
     * // 掉落银币
     * const DROP_TYPE_SILVER = 2;
     * // 混合掉落
     * const DROP_TYPE_MIXED = 3;
     * // 掉落将魂
     * const DROP_TYPE_SOUL = 4;
     * // 掉落宝物碎片
     * const DROP_TYPE_TREASFRAG = 5;
	 * @param int $uid
	 * @param array $arrDropId
	 * @param bool $needError 如果为true，会返回错误信息： bagfull
	 * @param bool $inTmpBag 是否加到临时背包
	 * @param bool $checkHero 是否检查武将背包
	 * @param bool $addup 数值类是否相加
	 * @param array $special 特殊掉落
	 * @param bool $ifUpdateFrag 是否立刻更新宝物碎片
	 * {num(特定次数), tplId(物品id), tplNum(物品数量)，count(统计次数)}
	 * @return 
	 * $needError＝false时返回
	 * array
	 * [
	 *     item=>array
	 *     [
	 *         ItemTmplId=>num
	 *     ]
	 *     hero=>array
	 *     [
	 *         Htid=>num
	 *     ]
	 *     silver=>int
	 *     soul=>int
	 *     treasFrag=>array
	 *     [
	 *         TreasFragTmplId=>num
	 *     ]
	 * ]
	 * $needError ＝ true时返回错误信息
	 * array
	 * [
	 * 		ret = bagfull|herofull
	 * ]
	 * 
	 */
	public static function drop($uid, $arrDropId, $needError = false, $inTmpBag = true, $checkHero = false, $addup = true, $special = array(), $ifUpdateFrag=true)
	{
	    $userObj    =    EnUser::getUserObj($uid);
	    $dropGot    =    array();
	    foreach($arrDropId as $dropId)
	    {
	        if(empty($dropId))
	        {
	            continue;
	        }
	        $arrDrop    =    Drop::dropMixed($dropId);
	        foreach($arrDrop as $type => $dropInfo)
	        {
	            switch($type)
	            {
	                case DropDef::DROP_TYPE_ITEM:
	                case DropDef::DROP_TYPE_HERO:
	                case DropDef::DROP_TYPE_TREASFRAG:
	                	if (DropDef::DROP_TYPE_ITEM == $type && !empty($special))
	                	{
	                		//特殊次数，特殊物品，物品数量，统计次数
	                		list($num, $tplId, $tplNum, $count) = $special;
	                		//如果统计次数等于特殊次数
	                		if (++$count == $num)
	                		{
	                			$dropInfo = array($tplId => $tplNum);
	                		}
	                		//如果掉落物品是特殊物品
	                		if (key($dropInfo) == $tplId)
	                		{
	                			$count = 0;
	                		}
	                		$special = array($num, $tplId, $tplNum, $count);
	                	}
	                    foreach($dropInfo as $tplId => $num)
	                    {
	                        if( !isset( $dropGot[$type][$tplId] ) )
	                        {
	                            $dropGot[$type][$tplId] = 0;
	                        }
	                        $dropGot[$type][$tplId] += $num;
	                    }
	                    break;
	                case DropDef::DROP_TYPE_SILVER:
	                    if(!empty($dropInfo))
	                    {
	                        $userObj->addSilver($dropInfo[0]);
	                        if ($addup) 
	                        {
	                        	if(!isset($dropGot[$type]))
		                        {
		                            $dropGot[$type] = 0;
		                        }
		                        $dropGot[$type] += $dropInfo[0];
	                        }
	                        else 
	                        {
	                        	$dropGot[$type][] = $dropInfo[0];
	                        }
	                    }
	                    break;
	                case DropDef::DROP_TYPE_SOUL:
	                    if(!empty($dropInfo))
	                    {
	                        $userObj->addSoul($dropInfo[0]);
	                        if ($addup) 
	                        {
	                        	if(!isset($dropGot[$type]))
		                        {
		                            $dropGot[$type] = 0;
		                        }
		                        $dropGot[$type] += $dropInfo[0];
	                        }
	                        else 
	                        {
	                        	$dropGot[$type][] = $dropInfo[0];
	                        }
	                	}
	                    break;
	            }
	        }
	    }
	    
	    if( !empty($dropGot[DropDef::DROP_TYPE_ITEM]) )
	    {
	    	$bag = BagManager::getInstance()->getBag($uid);
	    	if ( !$bag->addItemsByTemplateID( $dropGot[DropDef::DROP_TYPE_ITEM] , $inTmpBag) )
	    	{
	    		if( $needError )
	    		{
	    			return array('ret' => 'bagfull');	
	    		}
	    		throw new FakeException( 'bag is full: %s', $dropGot[DropDef::DROP_TYPE_ITEM]);
	    	}
	    	Logger::trace('drop. add item:%s', $dropGot[DropDef::DROP_TYPE_ITEM]);
	    }
	    if( !empty($dropGot[DropDef::DROP_TYPE_HERO]) )
	    {
 	    	if( $checkHero && $userObj->getHeroManager()->hasTooManyHeroes())
 	    	{
 	    		if( $needError )
 	    		{
 	    			return array('ret' => 'herofull');
 	    		}
 	    		throw new FakeException('too many hero. %s', $dropGot[DropDef::DROP_TYPE_HERO]);
 	    	}
	    	Logger::trace('drop. add hero:%s', $dropGot[DropDef::DROP_TYPE_HERO]);
	    	$userObj->getHeroManager()->addNewHeroes( $dropGot[DropDef::DROP_TYPE_HERO] );
	    }
	    if( !empty($dropGot[DropDef::DROP_TYPE_TREASFRAG]) )
	    {
	        $uid = $userObj->getUid();
	        EnFragseize::addTreaFrag($uid, $dropGot[DropDef::DROP_TYPE_TREASFRAG], $ifUpdateFrag);
	        Logger::trace('drop.add treasure fragment %s.',$dropGot[DropDef::DROP_TYPE_TREASFRAG]);   
	    }
	    $ret = array();
	    foreach($dropGot as $type => $value)
	    {
	        if(!isset(DropDef::$DROP_TYPE_TO_STRTYPE[$type]))
	        {
	            throw new FakeException('no such type drop');
	        }
	        $keyStr = DropDef::$DROP_TYPE_TO_STRTYPE[$type];
	        $ret[$keyStr] = $value;
	    }
	    if (!empty($special)) 
	    {
	    	$ret['special'] = $special;
	    }
	    return $ret;
	}
	
	public static function isPay($uid, $startTime=0, $endTime=PHP_INT_MAX)
	{
	    return User4BBpayDao::isPay($uid, $startTime, $endTime);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */