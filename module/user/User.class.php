<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: User.class.php 255252 2016-08-09 07:30:35Z GuohaoZheng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/User.class.php $
 * @author $Author: GuohaoZheng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-09 07:30:35 +0000 (Tue, 09 Aug 2016) $
 * @version $Revision: 255252 $
 * @brief
 *
 **/


class User implements IUser
{

	/* (non-PHPdoc)
	 * @see IUser::login()
	 */
	public function login($arrReq, $clientInfo = '' )
	{
		Logger::debug ( 'login with req:%s.', $arrReq );

		$server = new ServerProxy ();
		$onlineNum = $server->getTotalCount ();
		if ($onlineNum >= UserConf::MAX_ONLINE_USER)
		{
			Logger::warning ( 'the server online user:%d, full.', $onlineNum );
			return 'full';
		}

		if (FrameworkConfig::DEBUG)
		{
			if (! is_array ( $arrReq ))
			{
				$sid = $arrReq;
				$arrReq = array ('pid' => $sid, 'ptype' => 0 );
			}
			else if (! isset ( $arrReq ['ptype'] ))
			{
				$arrReq ['ptype'] = 0;
			}
			if ( empty($arrReq ['serverID'])  )
			{
				$arrReq ['serverID'] = 'game'.Util::getServerId();
			}
		}

		list ( $pid, $userType ) = UserLogic::login ( $arrReq );
		if (false === $pid && FrameworkConfig::DEBUG)
		{
			$pid = intval ( $arrReq ['pid'] );
		}

		if ($pid === - 1)
		{
			Logger::warning ( 'fail to login, timeout' );
			return 'timeout';
		}
		else if (empty ( $pid ))
		{
			Logger::warning ( 'fail to get pid by session %s', $arrReq );
			return 'fail';
		}

		if ($pid <= UserConf::PID_MAX_RETAIN)
		{
			throw new FakeException('fail to login, pid must more than %d', UserConf::PID_MAX_RETAIN );
		}
		$pid = intval ( $pid );

		$gpid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_PID);
		if( !empty($gpid) && $gpid != $pid)
		{
			Logger::fatal('login with different pid. pid:%d, gpid:%d', $pid, $gpid);
			throw new Exception ( 'close' );
		}

		RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_PID, $pid );
		RPCContext::getInstance ()->setSession ( 'global.userType', intval ( $userType ) );
		//设备uuid，在给平台的统计请求中加上这个信息
		if( !empty( $arrReq ['uuid'] ) )
		{
			RPCContext::getInstance ()->setSession ( 'global.clientUuid', $arrReq ['uuid'] );
		}
		if( !empty( $arrReq ['bind'] ) )
		{
		    RPCContext::getInstance ()->setSession ( 'global.bindid', $arrReq ['bind'] );
		}
		if( !empty( $arrReq ['plosgn'] )  )
		{
			RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_PLOSGN, intval (  $arrReq ['plosgn'] ) );
		}

		//合服相关
		if (defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ))
		{
			$serverId = intval ( substr ( $arrReq ['serverID'], 4 ) );
			if (! in_array ( $serverId, Util::getAllServerId () ))
			{
				Logger::fatal ( 'server id %s err', $serverId );
				throw new Exception ( 'sys' );
			}
			RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_SERVER_ID, $serverId );
		}

		Logger::info ("pid:%d login suc. clientInfo:%s. bind:%s", $pid, $clientInfo, empty($arrReq ['bind'])?'no':$arrReq ['bind'] );

		$openVip = false;
		$createVip = 0;
		if( defined( 'PlatformConfig::WORLD_VIP') && PlatformConfig::WORLD_VIP > WorldDef::WORLD_VIP_CLOSE)
		{
			$openVip = true;
			$baseGoldNum = UserWorldDao::getCreateBaseGoldByPid($pid);
			$createVip = UserWorldDao::getCreateVip($baseGoldNum);
		}

		return
 		array(
				'res' => 'ok',
				'vipSwitch' => $openVip,
				'createVip' => $createVip,
		);

		//return 'ok';
	}

	/* (non-PHPdoc)
	 * @see IUser::getUsers()
	 */
	public function getUsers()
	{
		$pid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_PID );
		if ($pid == null)
		{
			throw new FakeException('not login');
		}

		$arrUsers = UserLogic::getUsers ( $pid );

		foreach( $arrUsers as $key => $value )
		{
			$arrUsers[$key]['uname'] = ' '.base64_encode($value['uname']);
		}

		return $arrUsers;
	}

	/* (non-PHPdoc)
	 * @see IUser::createUser()
	 */
	public function createUser($utid, $uname)
	{
		$pid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_PID );
		if ($pid == null)
		{
			throw new FakeException('not login');
		}
		$utid = intval ( $utid );
		$uname = strval ( $uname );

		Logger::debug ( '%d create user %d uname %s', $pid, $utid, $uname );

		$info = RestrictUser::beforeCreate();
		$arrRet = UserLogic::createUser ( $pid, $utid, $uname );
		if ($arrRet ['ret'] == 'ok' && !FrameworkConfig::DEBUG)
		{
			$platfrom = ApiManager::getApi ();
			$argv = array ('pid' => $pid, 'serverKey' => Util::getServerId (),
					'ip' => RPCContext::getInstance ()->getFramework ()->getServerIp (),
					'uid' => $arrRet ['uid'], 'uname' => $uname ,
			        'clientIp' => RPCContext::getInstance()->getFramework()->getClientIp());

			//$platfrom->users ( 'addRole', $argv );
			Logger::trace ( 'create user uid:%d uname:%s pid:%d', $arrRet ['uid'], $uname, $pid );

		}
		RestrictUser::afterCreate(array(), isset($arrRet ['uid'])?$arrRet ['uid']:0 );
		return $arrRet ['ret'];
	}


	/* (non-PHPdoc)
	 * @see IUser::getRandomName()
	 */
	public function getRandomName($num, $gender = 0)
	{
		Logger::debug ( 'getRandomName %d', $num );
		return UserLogic::getRandomName ( $num, $gender );
	}



	/* (non-PHPdoc)
	 * @see IUser::userLogin()
	 */
	public function userLogin($uid)
	{
		//1. 检查参数
		$pid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_PID );
		if ($pid == null)
		{
			throw new FakeException('not login');
		}

		$uid = intval ( $uid );
		$guid = RPCContext::getInstance()->getUid();
		if($guid != 0 && $guid != $uid )
		{
			Logger::fatal('login with different uid. pid:%d, guid:%d, uid:%d', $pid, $guid, $uid);
			throw new Exception ( 'close' );
		}
		RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, $uid );
		$userObj = EnUser::getUserObj($uid);
		if( $userObj->getPid() != $pid )
		{
			throw new FakeException('try to login with other uid. pid:%d, uid:%d', $pid, $uid);
		}

		//2. 把自己加入lcserver中的connection map
		$proxy = new ServerProxy ();
		$ret = $proxy->checkUser ( $uid );
		if ($ret)
		{
			Logger::trace ( "user:%d already login", $uid );
			$loginRetryCount = RPCContext::getInstance ()->getSession ( 'login.rc' );
			if ($loginRetryCount > UserConf::MAX_LOGIN_RC)
			{
				Logger::fatal("uid:%d userLogin exceed max retry count", $uid);

				if(($userObj->getStatus()==UserDef::STATUS_ONLINE)
						&& (Util::getTime() - $userObj->getLastLoginTime()>UserConf::SAFE_DEL_TIME))
				{
					Logger::fatal("user:%d status is online, but last_on_time is too long, maybe stucked", $uid);
					RPCContext::getInstance()->delConnection($uid);
				}
				return 'fail';
			}
			RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
			$arrCallback = RPCContext::getInstance ()->getFramework ()->resetCallback ();
			RPCContext::getInstance ()->executeUserTask ( 'user.userLogin', array ($uid ),
					$arrCallback );
			RPCContext::getInstance ()->setSession ( 'login.rc', ++ $loginRetryCount );
			usleep ( UserConf::LOGIN_RC_INTERVAL );
			return;
		}

		//前面获取过user数据，在check完才能保证userLogoff执行完了，所以这里需要清一下缓存
		EnUser::release($uid);
		RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
		CData::$QUERY_CACHE = NULL;

		//3. 尝试登录，可能因为封号等原因登录失败
		$ret = UserLogic::userLogin ( $uid, $pid );
		if( 'ok' != $ret['ret'] )
		{
			Logger::info ( 'login failed. ret=%s', $ret );
			return $ret;
		}

		//4. 登录成功，干点啥
		RPCContext::getInstance ()->addListener ( 'user.userLogoff' );
		RPCContext::getInstance ()->addConnection ();

		//global.utid直接放主角的htid    在master_hero update的时候更新global.utid
		RPCContext::getInstance ()->setSession ( 'global.utid', $userObj->getHeroManager()->getMasterHeroObj()->getHtid());
		//user update的时候更新global.uname global.level
		RPCContext::getInstance ()->setSession ( 'global.uname', $userObj->getUname() );
		RPCContext::getInstance ()->setSession ( 'global.level', $userObj->getLevel() );
		RPCContext::getInstance ()->setSession( UserDef::SESSION_KEY_VIP, $userObj->getVip() );

		//时装，目前只存一个时装id
		$dressInfo = $userObj->getDressInfo() ;
		if( empty($dressInfo) )
		{
			$dressId = 0;
		}
		else
		{
			$dressId = current( $dressInfo );
		}

		RPCContext::getInstance ()->setSession ( 'global.dressId', $dressId );

		return 'ok';
	}

	public function userLogoff($arrLogoff)
	{
		$uid = RPCContext::getInstance()->getUid ();

		UserLogic::userLogoff ( $uid, $arrLogoff );
	}

	/* (non-PHPdoc)
	 * @see IUser::getUser()
	 */
	public function getUser()
	{
		$uid = RPCContext::getInstance()->getUid();
		RestrictUser::beforeLogin($uid);

		$userObj = EnUser::getUserObj($uid);
		$masterHid = $userObj->getMasterHid();
		$htid    =    HeroUtil::getHtidByHid($masterHid);
		$userInfo = array(
				'uid' => $uid,
				'utid' => $userObj->getUtid(),
		        'htid' =>$htid,
		        'dress'=>$userObj->getDressInfo(),
				'uname' => $userObj->getUname(),
				'level'=>$userObj->getLevel(),
				'create_time' => $userObj->getCreateTime(),
				'execution' => $userObj->getCurExecution(),
		        'execution_max_num' => $userObj->getExecutionMaxNum(),
				'execution_time' => $userObj->getExecutionTime(),
				'buy_execution_accum' => $userObj->getBuyExecutionAccum(),
				'vip' => $userObj->getVip(),
				'silver_num' => $userObj->getSilver(),
				'gold_num' => $userObj->getGold(),
				'exp_num' => $userObj->getExp(),
				'soul_num'=>$userObj->getSoul(),
		        'jewel_num'=>$userObj->getJewel(),
		        'prestige_num'=>$userObj->getPrestige(),
				'tg_num' => $userObj->getTgNum(),
				'wm_num' => $userObj->getWmNum(),
				'stamina'=>$userObj->getStamina(),
		        'stamina_time'=>$userObj->getStaminaTime(),
		        'stamina_max_num'=>$userObj->getStaminaMaxNum(),
				'fight_cdtime' => $userObj->getFightCDTime(),
				'ban_chat_time' => $userObj->getBanChatTime(),
		        'max_level'=>UserConf::MAX_LEVEL,
		        VA_USER::HERO_LIMIT=>$userObj->getHeroLimit(),
				'dayOffset' => FrameworkConfig::DAY_OFFSET_SECOND,
		        'figure'=>$userObj->getFigure(),
				'title'=>$userObj->getTitle(),
		        'masterSkill'=>$userObj->getMasterSkill(),
		        'fight_force'=>$userObj->getFightForce(),
				'honor_num' => EnCompete::getHonor($uid),
				'fame_num' => $userObj->getFameNum(),
				'book_num' => $userObj->getBookNum(),
		        'fs_exp'=>$userObj->getFsExp(),
				'jh'=>$userObj->getJH(),
				'tally_point'=>$userObj->getTallyPoint(),
				'cross_honor'=>EnWorldCompete::getCrossHonor($userObj->getServerId(), $userObj->getPid(), $uid),
				);

		if (defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ))
		{
			$userInfo['mergeServerTime'] = strtotime(GameConf::MERGE_SERVER_OPEN_DATE);
			$userInfo['mergeServerCount'] = count(GameConf::$MERGE_SERVER_DATASETTING);
		}
		else
		{
			$userInfo['mergeServerTime'] = 0;
			$userInfo['mergeServerCount'] = 1;
		}

		$userInfo['timeConf'] = array(
			'guildrob' => EnGuildRob::getEffectTime(),
			'pass' => EnPass::getPassConfTimeArr(),
			'boss' => EnBoss::getTimeConfig(),
			'olympic' => OlympicLogic::getTimeConfig(),
			'newserveractvitiy' => defined('PlatformConfig::NEW_SERVER_ACTIVITY_TIME') ? strtotime(PlatformConfig::NEW_SERVER_ACTIVITY_TIME) : 0, //新服活动(开服7天乐)上线的时间戳
		);

		$userInfo['uname'] = ' '.base64_encode($userInfo['uname']);

		$userInfo['pid'] = $userObj->getPid();
		$userInfo['server_id'] = Util::getServerIdOfConnection();

		UserLogic::delayLoginCall($uid);
		$userObj->update();
		return $userInfo;
	}


	public function modifyUserByOther($uid, $arrAttr)
	{
		if ($uid == 0)
		{
			throw new InterException( 'uid is 0' );
		}

		//如果用户不在线，就设置一下session。伪装自己在当前用户的连接中
		$guid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_UID );
		if ($guid == null)
		{
			RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, $uid );
		}
		else if ($uid != $guid)
		{
			Logger::fatal ( 'modifyUserByOther error, uid:%d, guid:%d', $uid, $guid );
			return;
		}

		$userObj = EnUser::getUserObj ( $uid );
		$userObj->modifyUserByOther ( $arrAttr );
		$userObj->update ();

		//在线用户，推到前端 。 这个判断等效于  $guid==$uid
		if ($userObj->isOnline ())
		{
			$userInfo = RPCContext::getInstance()->getSession ( UserDef::SESSION_KEY_USER );
			$arrRet = array ();
			foreach ( $arrAttr as $key => $tmp )
			{
			    if ($key == 'gold_stat')
			    {
			        continue;
			    }
				$arrRet [$key] = $userInfo [$key];
			}

			//execution_time 跟 execution  一起传给前端
			if (isset ( $arrRet ['execution'] ))
			{
				$arrRet ['execution_time'] = $userInfo ['execution_time'];
			}

			if(isset($arrRet['stamina']))
			{
			    $arrRet ['execution_time'] = $userInfo ['stamina_time'];
			}

			if (! empty ( $arrRet ))
			{
				RPCContext::getInstance ()->sendMsg ( array ($uid ), PushInterfaceDef::USER_UPDATE_USER_INFO, $arrRet );
			}
		}
	}

	/**
	 *
	 * 用户处理lcserver转接的背包修改请求
	 *
	 * @param int $uid
	 * @param array(int) $arrItemId				需要放入用户背包中的
	 * @param array(int) $arrItemIdTmp		可以放入临时背包中的
	 *
	 * @throws Exception
	 *
	 * @return NULL
	 */
	public function addItemsOtherUser($uid, $arrItemId, $arrItemIdTmp)
	{

		$uid = intval ( $uid );

		if ($uid == 0)
		{
			throw new InterException( 'uid is 0' );
		}

		$guid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_UID );
		if ($guid == null)
		{
			RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, $uid );
		}
		else if ($uid != $guid)
		{
			throw new InterException( 'addItemsOtherUser error, uid:%d, guid:%d', $uid, $guid );
		}
		Logger::trace('addItemsOtherUser: arrItemId:%s, arrItemIdTmp:%s', $arrItemId, $arrItemIdTmp);

		$bag = BagManager::getInstance ()->getBag ();

		$bag->addItems ( $arrItemId, FALSE );
		$bag->addItems ( $arrItemIdTmp, TRUE );

		$bag->update ();
	}




	/* (non-PHPdoc)
	 * @see IUser::unameToUid()
	 */
	public function unameToUid($uname)
	{

		$ret    =    UserDao::getUserByUname($uname, array('uid'));
		if(empty($ret))
		{
		    return 0;
		}
		return $ret['uid'];
	}


	/* (non-PHPdoc)
	 * @see IUser::buyExecution()
	 */
	public function buyExecution($num)
	{

		$num = intval ( $num );
		if ($num <= 0)
		{
			throw new FakeException( 'fail to buyExecution, the num %d is <= 0', $num );
		}

		$userObj = EnUser::getUserObj ();
		$ret = $userObj->buyExecution ( $num );
		if ($ret == 'ok')
		{
			$userObj->update ();
		}
		return $ret;
	}


	/* (non-PHPdoc)
	 * @see IUser::isPay()
	*/
	public function isPay()
	{
		$uid = RPCContext::getInstance ()->getUid ();

		$curConf = UserLogic::getCurFirstPayConf();

		$ret    =    EnUser::isPay($uid, $curConf['startTime'], $curConf['endTime']);
		return $ret;
	}


	/**
	 * (non-PHPdoc)
	 * @see IUser::addGold4BBpay()
	 */
	public function addGold4BBpay($uid, $orderId, $addGold, $addGoldExt = 0, $qid = '', $orderType = 0)
	{
		if (isset ( GameConf::$CLOSE_ADD_GOLD ))
		{
			if (GameConf::$CLOSE_ADD_GOLD === true)
			{
				Logger::fatal ( 'fail to addGold4BBpay, the GameConf::CLOSE_ADD_GOLD is true' );
				return 'fail';
			}
		}
		if ($addGold < 0 || $addGoldExt < 0)
		{
			throw new FakeException( 'fail to addGold4BBpay, the num is less than 0' );
		}
		Logger::info (
				'addGoldForBbpay for uid %d, orderId: %s, num: %d,  ext num:%d, qid:%s, order_type:%s',
				$uid, $orderId, $addGold, $addGoldExt, $qid, $orderType );
		if ($uid == 0)
		{
			throw new FakeException( 'uid is 0' );
		}

		// 通过uid 0 发给游戏。 如果uid 0 积压了大量的请求，此时玩家可能已经上线了。
		$guid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_UID );
		if ($guid == null)
		{
			$proxy = new ServerProxy ();
			$ret = $proxy->checkUser ( $uid );
			if( $ret )
			{
				Logger::warning('uid:%d just login when addGold4BBpay', $uid);
			}
		}

		$firstPay = FALSE;
		$userObj = EnUser::getUserObj ( $uid );
		if(UserLogic::isFirstPay($uid, $orderType))
		{
		    $firstPay = TRUE;
		}
        //$payBack = UserLogic::getPayBack($addGold, $firstPay, $uid);
        $arrPayBackInfo = UserLogic::getPayBack($addGold, $firstPay, $uid);
        $payBack = $arrPayBackInfo['pay_back'];
        $arrChargeInfo = $arrPayBackInfo['charge_info'];

		$level = $userObj->getLevel ();
		$pid = $userObj->getPid();

		//获得使用道具获得金币增加的vip经验值
		$useItemGold = $userObj->getUseItemGold();
		Logger::info('addGoldForBbpay for uid %d, cur useItemGold %d', $uid, $useItemGold);

		//$guid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_UID );
		$newVip = User4BBpayDao::update ( $uid, $orderId, $addGold, $addGoldExt, $payBack, $qid,
				$orderType, $level, $useItemGold, $arrChargeInfo );
		$curGoldNum = $userObj->getGold() + $addGold + $addGoldExt + $payBack;
        if($firstPay)
        {
            $payConf = UserLogic::getPayConf($firstPay);
            $reward = $payConf['reward'];
            EnReward::sendReward($uid, RewardSource::FIRST_TOPUP, $reward);
            Statistics::gold(StatisticsDef::ST_FUNCKEY_FIRST_PAY_REWARD, $payBack, $curGoldNum, $pid);
            ChatTemplate::firstTopupPack($userObj->getTemplateUserInfo(), $reward[RewardType::SILVER], $reward[RewardType::ARR_ITEM_TPL]);
        }
        else
        {
            Statistics::gold(StatisticsDef::ST_FUNCKEY_PAY_BACK, $payBack, $curGoldNum, $pid);
        }
		$oldVip = $userObj->getVip ();
		//在线用户，推到前端
		if ($guid != null && $userObj->isOnline ())
		{
			//修改对象中的值      User4BBpayDao::update没有经过userObj->addGold直接写入数据库
			$userObj->modifyFields (
					array ('gold_num' => $addGold + $addGoldExt + $payBack, 'vip' => $newVip - $oldVip ) );
			$userObj->setFields(
			        array(
			            'va_charge_info' => $arrChargeInfo,
			         )
			    );
			$userObj->updateSession();
			RPCContext::getInstance()->sendMsg( array($uid),
			        PushInterfaceDef::USER_UPDATE_USER_INFO,
			        array('gold_num'=>$userObj->getGold (),
			                'vip'=>$userObj->getVip ()));
			$chargeGold = User4BBpayDao::getSumGoldByUid ( $uid );
			$msg    =    array(
			        'gold_num' => $userObj->getGold (), //当前的金币数目
			        'vip' => $userObj->getVip (),//当前的VIP等级
					'charge_gold_sum' => $chargeGold + $useItemGold,//当前充值金额
					'charge_gold' => $addGold,//此次充值金额
			        'pay_back' => $addGoldExt + $payBack, //充值返还（平台返还+配置返还）
			        'first_pay' => $firstPay,//是否是首充
			        'charge_type' => CHARGE_TYPE::CHARGE_GOLD,
			        );
			RPCContext::getInstance ()->sendMsg ( array ($uid ),
			        PushInterfaceDef::CHARGE_GOLD_UPDATE_USER,$msg);
			RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_VIP, $newVip);
		}
		else
		{
		    Logger::trace('user %s is not onlie.',$uid);
		}

		if (($newVip - $oldVip) > 0)
		{
		    ChatTemplate::sendSysVipLevelUp1($userObj->getTemplateUserInfo(), $newVip);
			MailTemplate::sendVip($userObj->getUid(), $newVip);
			ChatTemplate::sendBroadcastVipLevelUp2($userObj->getTemplateUserInfo(), $newVip);
		}

		return 'ok';
	}

	public function getChargeGold()
	{
	    $uid = RPCContext::getInstance()->getUid();
	    $chargeGold = User4BBpayDao::getSumGoldByUid ( $uid );
	    $useItemGold = EnUser::getUserObj()->getUseItemGold();
	    return $chargeGold + $useItemGold;
	}

	/* (non-PHPdoc)
	 * @see IUser::setMute()
	 */
	public function setMute($isMute)
	{

		if ($isMute != 0 && $isMute != 1)
		{
			throw new FakeException( 'fail to setMute, argv %d is invalid', $isMute );
		}

		$user = EnUser::getUserObj ();
		$user->setMute ( $isMute );
		$user->update ();
		return 'ok';
	}

	public function setArrConfig($key, $value)
	{

		$user = EnUser::getUserObj ();
		$user->setArrConfig ( $key, $value );
		$user->update ();
		return 'ok';
	}

	public function getArrConfig()
	{

		$user = EnUser::getUserObj ();
		return $user->getArrConfig ();
	}

	public function setVaConfig($vaConfig)
	{
	    $user = EnUser::getUserObj ();
	    $user->setVaConfig ( $vaConfig );
	    $user->update ();

	    return 'ok';
	}

	public function getVaConfig()
	{

		$user = EnUser::getUserObj ();
		return $user->getVaConfig ();
	}


	public function getBattleDataOfUsers($arrUid)
	{
	    $ret    =    array();
	    $arrGuildId = array();
	    foreach($arrUid as $uid)
	    {
	        $userObj = Enuser::getUserObj($uid);
	        $userBtData = $userObj->getBattleData();
	        Logger::trace('battledata of user %s is %s.',$uid,$userBtData);
	        $guildId = $userObj->getGuildId();
	        if(!empty($guildId))
	        {
	            $arrGuildId[$uid] = $guildId;
	        }

	        foreach($userBtData['arrHero'] as $pos=>$heroBtData)
	        {
	            $simpleHeroBtData = array(
	                    'hid'=>$heroBtData['hid'],
	                    'htid'=>$heroBtData['htid'],
	                    'level'=>$heroBtData['level'],
	            		'destiny'=>$heroBtData['destiny'],
	                    'evolve_level'=>$heroBtData[PropertyKey::EVOLVE_LEVEL],
	                    'max_hp'=>$heroBtData[PropertyKey::MAX_HP],
	                    'general_atk'=>intval($heroBtData[PropertyKey::GENERAL_ATTACK_BASE]
	                            * (1 + $heroBtData[PropertyKey::GENERAL_ATTACK_ADDITION]/UNIT_BASE)
	                    		+ $heroBtData[PropertyKey::ABSOLUTE_GENERAL_ATTACK] ),
	                    'physical_def'=>intval($heroBtData[PropertyKey::PHYSICAL_DEFEND_BASE]
	                            * (1 + $heroBtData[PropertyKey::PHYSICAL_DEFEND_ADDITION]/UNIT_BASE)
	            				+ $heroBtData[PropertyKey::ABSOLUTE_PHYSICAL_DEFEND]),
	                    'magical_def'=>intval($heroBtData[PropertyKey::MAGIC_DEFEND_BASE]
	                            * (1 + $heroBtData[PropertyKey::MAGIC_DEFEND_ADDITION]/UNIT_BASE)
	            				+ $heroBtData[PropertyKey::ABSOLUTE_MAGIC_DEFEND]),
	                    'fight_force'=>$heroBtData[PropertyKey::FIGHT_FORCE],
	                    'equipInfo'=>$heroBtData['equipInfo'],
	                    'pillInfo'=>$heroBtData[PropertyKey::PILL_INFO],
	                    'rage_skill'=>$heroBtData[PropertyKey::RAGE_SKILL],
	                    'attack_skill'=>$heroBtData[PropertyKey::ATTACK_SKILL],
	                    );
	            if(HeroUtil::isMasterHtid($heroBtData['htid']))
	            {
	                $simpleHeroBtData['dress'] = $heroBtData[PropertyKey::DRESS_INFO];
	            }
	            $userBtData['arrHero'][$pos] = $simpleHeroBtData;
	        }
	        $userBtData['vip'] = $userObj->getVip();
	        //查看对方阵容，显示称号信息
	        $userBtData['title'] = $userObj->getTitle();
	        $userBtData['craft_info'] = EnFormation::getCraftInfo($uid);
	        $userBtData['union'] = UnionLogic::getInfoByLogin($uid);
	        $userBtData['masterTalent'] = EnFormation::getMasterTalentInfo($uid);
	       
	        $ret[$uid] = $userBtData;
	    }
	    $arrGuildName = EnGuild::getArrGuildInfo($arrGuildId, array(GuildDef::GUILD_ID,GuildDef::GUILD_NAME));
	    foreach($ret as $uid => $userData)
	    {
	        if(!isset($arrGuildId[$uid]))
	        {
	            continue;
	        }
	        $guildId = $arrGuildId[$uid];
	        if(!isset($arrGuildName[$guildId]))
	        {
	            continue;
	        }
	        $ret[$uid][GuildDef::GUILD_NAME] = $arrGuildName[$guildId][GuildDef::GUILD_NAME];
	    }
	    // 2016年1月版的需求"查看对方整容时,可以看到助战位强化等级",故在此新增助战位等级的字段,by林杰鑫20160105
	    $ret[$uid]['attrExtraLevel'] = EnFormation::getAttrExtraLevel($uid);
	    return $ret;
	}

	public function getSwitchInfo($uid = 0)
	{
	    if(empty($uid))
	    {
	        $uid = RPCContext::getInstance()->getUid();
	    }
	    $switch    =    SwitchLogic::getSwitchArray($uid);
	    return $switch;
	}

	/* (non-PHPdoc)
     * @see IUser::getUserInfoByUid()
     */
    public function getUserInfoByUname ($uname)
    {
        $ret    =    UserDao::getUserByUname($uname, array('uid','utid','level','fight_force','master_hid','va_user'));
        if(empty($ret))
        {
            return array('err'=>'nosuchname');
        }
        $htid = HeroUtil::getHtidByHid($ret['master_hid']);
        $ret['htid'] = $htid;
        $ret['err'] = 'ok';
        $ret['dress'] = array();
        if(isset($ret['va_user'][VA_USER::DRESSINFO]))
        {
            $ret['dress'] = $ret['va_user'][VA_USER::DRESSINFO];
        }
        unset($ret['va_user']);
        unset($ret['master_hid']);
        return $ret;
    }

    /**
     * 开5个武将格子
     */
    public function openHeroGrid($type=1)
    {
        $userObj    =    EnUser::getUserObj();
        if($userObj->getHeroLimit() < HeroDef::INIT_HERO_LIMIT_NUM)
        {
            $userObj->setHeroGrid(HeroDef::INIT_HERO_LIMIT_NUM);
            return 'ok';
        }
        $userObj->openHeroGrid();
        if($type == HeroDef::OPEN_HEROGRID_TYPE_GOLD)
        {
            $curLimit = $userObj->getHeroLimit();
            $needGold =HeroDef::PRE_HERO_LIMIT_ADD * HeroDef::INIT_HERO_GRID_NEED_GOLD+
            ($curLimit - HeroDef::INIT_HERO_LIMIT_NUM-HeroDef::PRE_HERO_LIMIT_ADD)*
            HeroDef::PRE_HERO_LIMIT_NEED_GOLD;
            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_OPEN_HEROLIMIT) == FALSE)
            {
                throw new FakeException('openHeroGrid.sub gold failed.');
            }
        }
        else
        {
            $bag = BagManager::getInstance()->getBag();
            if($bag->deleteItembyTemplateID(BagConf::BAG_UNLOCK_ITEM_ID, 1) == FALSE)
            {
                throw new FakeException('delete item %d failed',BagConf::BAG_UNLOCK_ITEM_ID);
            }
            $bag->update();
        }
        $userObj->update();
        return 'ok';
     }


     public function closeMe()
     {
     	Logger::trace('close me');
     	$uid = RPCContext::getInstance()->getUid();
     	RPCContext::getInstance ()->closeConnection ( $uid );
     }


     public function getArrUserByPid($arrPid, $arrField)
     {
     	return UserDao::getArrUserByArrPid ( $arrPid, $arrField );
     }

     public function getArrUserDressInfo($arrUid)
     {
         if(empty($arrUid) || (!is_array($arrUid)))
         {
             Logger::warning('invalid param.%s.',$arrUid);
             return array();
         }
         $ret = Enuser::getArrUserBasicInfo($arrUid, array('uid','utid','dress'));
         $ret = array_merge($ret,array());
         return $ret;
     }


     /**
      * 内部接口，返回订单信息
      */
     public function getOrder($orderId, $arrField)
     {
     	return User4BBpayDao::getByOrderId ( $orderId, $arrField );
     }

     public function getArrOrder($arrField, $beginTime, $endTime, $offset, $limit,
     		$orderType = 0)
     {
     	return User4BBpayDao::getArrOrder ( $arrField, $beginTime, $endTime, $offset,
     			$limit, $orderType );
     }

     public function getItemOrder($orderId,$arrField)
     {
         return User4BBpayDao::getByItemOrderId( $orderId, $arrField );
     }

     public function getArrItemOrder($arrField, $beginTime, $endTime, $offset, $limit)
     {
         return User4BBpayDao::getArrItemOrder ( $arrField, $beginTime, $endTime, $offset,
                 $limit );
     }

     public function getByUname($uname, $arrField, $orderField = null, $orderType = 0)
     {
     	if (! in_array ( 'uid', $arrField ))
     	{
     		$arrField [] = 'uid';
     	}

     	$ret = UserDao::getByUname ( $uname, $arrField );
     	if (empty ( $ret ))
     	{
     		return array ();
     	}

     	if ($orderField != null)
     	{
     		$order = User4BBpayDao::getArrOrderByUid (  $ret ['uid'], $orderType, $orderField );
     		$ret ['order'] = $order;
     	}

     	return $ret;
     }

     public function getByPid($pid, $arrField)
     {
     	if (! in_array ( 'uid', $arrField ))
     	{
     		$arrField [] = 'uid';
     	}

     	$ret = UserDao::getArrUserByPid ( $pid, $arrField );
     	if (empty ( $ret ))
     	{
     		return array ();
     	}
     	else
     	{
     		$ret = $ret [0];
     	}

     	return $ret;
     }

     public function getMultiInfoByPid($arrPid, $arrMultiField, $afterLastLoginTime)
     {

     	$arrRet = array ();
     	$arrUid = array ();
     	if (isset ( $arrMultiField ['user'] ))
     	{
     		$arrField = $arrMultiField ['user'];
     		$arrUser = EnUser::getArrUserByArrPid ( $arrPid, $arrField, $afterLastLoginTime );
     		$arrUid = array_keys ( $arrUser );
     		$arrRet ['user'] = $arrUser;
     	}
     	else
     	{
     		$arrUid = array_keys (
     				EnUser::getArrUserByArrPid ( $arrPid, array ('uid' ), $afterLastLoginTime ) );
     	}

     	if (isset ( $arrMultiField ['guild'] ))
     	{
     		if (isset ( $arrMultiField ['guild'] ['guild_member'] ))
     		{
     			$arrGuildMember = EnGuild::getMultiMember ( $arrUid, $arrMultiField ['guild'] ['guild_member'] );
     			$arrRet ['guild'] ['guild_member'] = $arrGuildMember;
     			if (isset ( $arrMultiField ['guild'] ['guild'] ))
     			{
     				$arrGuildId = Util::arrayExtract ( $arrGuildMember, 'guild_id' );
     				$arrGuild = EnGuild::getMultiGuild ( $arrGuildId, $arrMultiField ['guild'] ['guild'] );
     				$arrRet ['guild'] ['guild'] = $arrGuild;
     			}
     		}
     	}
     	return $arrRet;
     }

     public function getTopEn($type, $offset, $limit)
     {
    	if ($limit > UserConf::MAX_TOP || $offset < 0 )
     	{
     		throw new FakeException( 'fail to getTopLevel, max is over %d', UserConf::MAX_TOP );
     	}

     	switch ($type)
     	{
     		case 'level' :
     			return UserLogic::getTopLevel ( $offset, $limit );
     			break;

     		case 'arena' :
     			return UserLogic::getTopArena ( $offset, $limit );
     			break;

     		case 'copy' :
     			return EnCopy::getTopUserByCopy($offset, $limit);
     			break;

     		default :
     			throw new FakeException('fail to getTop, type %s unknown', $type );
     			break;

     	}
     }


     /**
      * 封号
      * @param unknown_type $uid
      * @param unknown_type $time
      * @param unknown_type $msg
      * @return void|string
      */
     public function ban($uid, $time, $msg)
     {
     	$guid = RPCContext::getInstance ()->getUid ();
     	if ($guid == null)
     	{
     		RPCContext::getInstance ()->setSession ( 'global.uid', $uid );
     	}
     	else if ($uid != $guid)
     	{
     		Logger::fatal ('ban failed, uid:%d, guid:%d', $uid, $guid );
     		return;
     	}

     	if ($time > FrameworkConfig::MAX_UINT)
     	{
     		$time = FrameworkConfig::MAX_UINT;
     	}

     	$user = EnUser::getUserObj ();
     	$user->ban ( $time, $msg );
     	$user->update ();

     	//在线用户kick掉
     	if ($guid != null)
     	{
     		RPCContext::getInstance ()->closeConnection ( $uid );
     	}
     	return 'ok';
     }

     /**
      * 封号信息
      * Enter description here ...
      * @param unknown_type $uid
      * @return
      * <code>
      * time:封号截止时间戳
      * msg:封号原因
      * <code>
      */
     public function getBanInfo($uid)
     {

     	$user = EnUser::getUserObj ( $uid );
     	return $user->getBanInfo ();
     }
     /**
      * 前后端对比数值是否一致
      * @param string $key
      * @param int $value
      * @param strint $method
      */
     public function checkValue($key,$value,$method)
     {

         $keyVal = intval($value);
         $trueVal = 0;
         $userObj = EnUser::getUserObj();
         switch($key)
         {
             case "exp_num":
                 $trueVal = $userObj->getExp();
                 break;
             case 'silver_num':
                 $trueVal = $userObj->getSilver();
                 break;
             case 'soul_num':
                 $trueVal = $userObj->getSoul();
                 break;
             case 'gold_num':
                 $trueVal = $userObj->getGold();
                 break;
             case 'execution':
                 $userObj->refreshExecution();
                 $trueVal = $userObj->getCurExecution();
                 break;
             case 'stamina':
                 $userObj->refreshStamina();
                 $trueVal = $userObj->getStamina();
                 break;
             case 'level':
                 $trueVal = $userObj->getLevel();
                 break;
             case 'stamina_time':
                 $userObj->refreshStamina();
                 $trueVal = $userObj->getStaminaTime();
                 break;
             case 'server_time':
                 $trueVal = Util::getTime();
                 break;
             case 'jewel_num':
                 $trueVal = $userObj->getJewel();
                 break;
             case 'prestige_num':
                 $trueVal = $userObj->getPrestige();
             case 'tg_num':
             	 $trueVal = $userObj->getTgNum();
             case 'wm_num':
             	 $trueVal = $userObj->getWmNum();
             case 'jh':
             	 $trueVal = $userObj->getJH();
                 break;
             case 'tally_point':
                 $trueVal = $userObj->getTallyPoint();
                 break;
             case 'user_item_gold':
             	 $trueVal = $userObj->getUseItemGold();
                 break;
             default:
                 throw new FakeException('no such value %s to check.valide key:silver_num\soul_num\stamina\gold_num\exp_num\level\stamina_time\execution;',$key);
         }
         if($keyVal != $trueVal)
         {
            Logger::fatal('user.checkValue not equal.error appear. key:%s, value:%s, true:%s, method:%s',$key, $value, $trueVal, $method);
         }
         else
         {
            Logger::info('user.checkValue equal. key:%s, value:%s, true:%s, method:%s',$key, $value, $trueVal, $method);
         }

         $userInfo = array(
         		'level'=>$userObj->getLevel(),
         		'execution' => $userObj->getCurExecution(),
         		'execution_time' => $userObj->getExecutionTime(),
         		'vip' => $userObj->getVip(),
         		'silver_num' => $userObj->getSilver(),
         		'gold_num' => $userObj->getGold(),
         		'exp_num' => $userObj->getExp(),
         		'soul_num'=>$userObj->getSoul(),
         		'stamina'=>$userObj->getStamina(),
         		'stamina_time'=>$userObj->getStaminaTime(),
                'server_time'=>Util::getTime(),
                'jewel_num'=>$userObj->getJewel(),
                'prestige_num'=>$userObj->getPrestige(),
         		'tg_num'=>$userObj->getTgNum(),
         		'wm_num'=>$userObj->getWmNum(),
         		'jh'=>$userObj->getJH(),
                'tally_point'=>$userObj->getTallyPoint(),
         		'user_item_gold'=>$userObj->getUseItemGold(),
         );
         return $userInfo;
     }

     public function share()
     {
         $shareTime = EnUser::getExtraField(UserExtraDef::USER_EXTRA_FIELD_SHARE_TIME);
         $userObj = EnUser::getUserObj();
         $ret = array();
         if(empty($shareTime))
         {
             $ret['gold'] = UserConf::FIRST_SHARE_GETGOLD;
             $ret['silver'] = UserConf::DAILY_FIRST_SHARE_GETSILVER * $userObj->getLevel();
             $userObj->addGold($ret['gold'], StatisticsDef::ST_FUNCKEY_FIRST_SHARE);
             $userObj->addSilver($ret['silver']);
             EnUser::setExtraField(UserExtraDef::USER_EXTRA_FIELD_SHARE_TIME, Util::getTime());
         }
         else if(!Util::isSameDay($shareTime))
         {
             $ret['silver'] = UserConf::DAILY_FIRST_SHARE_GETSILVER * $userObj->getLevel();
             $ret['execution'] = UserConf::DAILY_FIRST_SHARE_EXECUTION;
             $userObj->addSilver($ret['silver']);
             $userObj->addExecution(UserConf::DAILY_FIRST_SHARE_EXECUTION);
             EnUser::setExtraField(UserExtraDef::USER_EXTRA_FIELD_SHARE_TIME, Util::getTime());
         }
         $userObj->update();
         return $ret;
     }

     public function changeName($uname,$spendType)
     {
         $uid = RPCContext::getInstance()->getUid();
         if(empty($uid))
         {
             throw new FakeException('guid %d is NULL.can not change name.',$uid);
         }
         $ret = UserLogic::changeUserName($uid, $uname, $spendType);
         return $ret;
     }

     public function setFigure($figure)
     {
         $userObj = EnUser::getUserObj();
         $userObj->setFigure($figure);
         $userObj->update();
         return 'ok';
     }

     public function getChargeInfo()
     {
         $uid = RPCContext::getInstance()->getUid();

         $curConf = UserLogic::getCurFirstPayConf();

         $userObj = EnUser::getUserObj($uid);

         return array(
                 'is_pay'=>EnUser::isPay($uid, $curConf['startTime'], $curConf['endTime']),
                 'can_buy_monthlycard'=>MonthlyCardLogic::canBuyMonthlyCard($uid),
                 'charge_info' => $userObj->getChargeInfo(),
                 );
     }

     public function buyItem($uid, $orderId, $type, $itemTplId, $itemNum, $goldNum)
     {
         $uid = intval($uid);
         $type = intval($type);
         $itemTplId = intval($itemTplId);
         $itemNum = intval($itemNum);
         $goldNum = intval($goldNum);
         Logger::info('buyItem params uid %d orderid %s type %d itemid %d num %d goldnum %d',
                 $uid,$orderId,$type,$itemTplId,$itemNum,$goldNum);
         Logger::fatal('now user.buyItem is close.');
         return;
         if ($uid == 0)
         {
             throw new FakeException( 'uid is 0' );
         }
         if($goldNum == 0)
         {
             throw new InterException('buyItem goldnum is 0.');
         }
         // 通过uid 0 发给游戏。 如果uid 0 积压了大量的请求，此时玩家可能已经上线了。
         $guid = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_UID );
         if ($guid == null)
         {
             $proxy = new ServerProxy ();
             $ret = $proxy->checkUser ( $uid );
             if( $ret )
             {
                 Logger::warning('uid:%d just login when addGold4BBpay', $uid);
             }
         }
         if($type == BUYITEM_TYPE::MONTHLYCARD)
         {
             MonthlyCardLogic::buyCard($uid, $orderId, $type, $itemTplId, $itemNum, $goldNum);
         }
         else
         {
             throw new FakeException('no valid buyitem type %d',$type);
         }
     }

     public function canBuyItem($uid, $type, $itemTmplId, $itemNum)
     {
         $uid = intval($uid);
         $type = intval($type);
         $itemTplId = intval($itemTmplId);
         $itemNum = intval($itemNum);
         Logger::info('canBuyItem params uid %d type %d itemid %d num %d',
                 $uid,$type,$itemTplId,$itemNum);
         Logger::fatal('now user.buyItem is close.');
         return;
         if($type == BUYITEM_TYPE::MONTHLYCARD)
         {
             if(MonthlyCardLogic::canBuyMonthlyCard($uid))
             {
                 Logger::info('canBuyItem ok');
                 return 'ok';
             }
             else
             {
                 Logger::warning('canBuyItem err');
             }
         }
         else
         {
             Logger::fatal('no valid buyitem type %d',$type);
         }
         return 'err';
     }

     public function rankByFightForce()
     {
     	Logger::trace('User::rankByFightForce Start.');

     	$uid = RPCContext::getInstance()->getUid();
     	$arrColumn = 'fight_force';
     	$offset = 0;
     	$limit = 50;

     	$ret = UserLogic::getRankByColumn($uid, $arrColumn, $offset, $limit);

     	Logger::trace('User::rankByFightForce End.');

     	return $ret;
     }

     public function rankByLevel()
     {
     	Logger::trace('User::rankByLevel Start.');

     	$uid = RPCContext::getInstance()->getUid();
     	$arrColumn = 'level';
     	$offset = 0;
     	$limit = 50;

     	$ret = UserLogic::getRankByColumn($uid, $arrColumn, $offset, $limit);

     	Logger::trace('User::rankByLevel End.');

     	return $ret;
     }

     public function addBadOrder($uid, $orderId, $goldNum, $subNum, $kickUser = true, $check = true)
     {
     	if ($uid == 0)
     	{
     		Logger::warning('invalid param. uid:%s', $uid);
     		return 'invalid_param';
     	}

     	if ( $check )
     	{
     		$ret = User4BBpayDao::getByOrderId($orderId, array('uid', 'gold_num'));
     		if ( empty($ret) )
     		{
     			Logger::warning('not found order. uid:%d, orderId:%s', $uid, $orderId);
     			return 'not_found_order';
     		}
     		if ( $ret['gold_num'] != $goldNum )
     		{
     			Logger::warning('invalid gold num. uid:%d, orderId:%s, goldNum:%d, trueNum:%d',
     						$uid, $orderId, $goldNum, $ret['gold_num']);
     			return 'invalid_gold_num';
     		}
     	}

     	$needSubGoldNum = intval($subNum);
     	if ( $needSubGoldNum <= 0 )
     	{
     		if ( !defined('PlatformConfig::COMPENSATE_BAD_ORDER')
     			|| PlatformConfig::COMPENSATE_BAD_ORDER <= 0 )
     		{
     			Logger::fatal('not set sub percent. uid:%d, orderId:%s', $uid, $orderId);
     			return 'not_set_sub_percent';
     		}
     		$needSubGoldNum = intval($goldNum * PlatformConfig::COMPENSATE_BAD_ORDER);
     		if ( $needSubGoldNum <= 0 )
     		{
     			Logger::fatal('invalid sub percent. uid:%d, orderId:%s', $uid, $orderId);
     			return 'invalid_sub_percent';
     		}
     	}

     	Logger::info('addBadOrder. uid:%d, orderId:%s, goldNum:%d, subNum:%d, needSub:%d',
     				$uid, $orderId, $goldNum, $subNum, $needSubGoldNum);

     	BadOrderDao::insertBadOrder($uid, $orderId, $goldNum, $needSubGoldNum);

     	if ($kickUser)
     	{
     		//用户如果在线，把他踢了
     		$proxy = new ServerProxy ();
     		$ret = $proxy->checkUser ( $uid );
     		if( $ret )
     		{
     			Logger::info('addBadOrder kick uid:%d', $uid);
     		}
     	}

     	return 'ok';

     }


     public function removeSkill($skillType)
     {
         if($skillType == 1)
         {
             $skillType = PropertyKey::ATTACK_SKILL;
         }
         else if($skillType == 2)
         {
             $skillType = PropertyKey::RAGE_SKILL;
         }
         $userObj = EnUser::getUserObj();
         if(FALSE == $userObj->removeSkill($skillType))
         {
             throw new FakeException('not learn skill of this type.skillinfo %s',$userObj->getMasterSkill());
         }
         $userObj->update();
         return 'ok';
     }

     /**
      * 在别的服上，或者跨服上通过lcserver获取某个玩家的战斗数据
      *
      * @param number $serverId
      * @param number $pid
      * @return array
      */
     public function getBattleFormation($serverId, $pid)
     {
     	$arrUserInfo = UserDao::getArrUserByPid($pid, array('uid'), $serverId);
     	if (empty($arrUserInfo))
     	{
     		throw new InterException('not valid pid[%d], no user info', $pid);
     	}
     	$uid = $arrUserInfo[0]['uid'];

     	return EnUser::getUserObj($uid)->getBattleFormation();
     }



     /**
      * 获取核心用户信息，核心用户定义：每日任务积分>=100的玩家
      *
      * @param number $minPoint
      * @return array
      */
     public function getCoreUser($minPoint = 100)
     {
     	// 获取积分大于等于参数的玩家
     	$arrValidInfo = EnActive::getActiveInfoByPoint($minPoint);

     	// 批量拉取pid信息
     	$arrRet = array();
     	$offset = 0;
     	while (TRUE)
     	{
     		$arrPartInfo = array_slice($arrValidInfo, $offset, CData::MAX_FETCH_SIZE);
     		$arrPartInfo = Util::arrayIndex($arrPartInfo, 'uid');
     		$arrUid = Util::arrayExtract($arrPartInfo, 'uid');

     		$arrField = array('uid', 'pid', 'gold_num');
     		if (defined('GameConf::MERGE_SERVER_OPEN_DATE'))
     		{
     			$arrField[] = 'server_id';
     		}

     		$arrUserInfo = EnUser::getArrUserBasicInfo($arrUid, $arrField);
     		foreach ($arrUid as $aUid)
     		{
     			if (empty($arrUserInfo[$aUid]['pid']))
     			{
     				Logger::fatal('no user info of uid[%d]', $aUid);
     				continue;
     			}
     			$arrRet[] = array
     				(
     						'pid' => $arrUserInfo[$aUid]['pid'],
     						'uid' => $aUid,
     						'point' => $arrPartInfo[$aUid]['point'],
     						'gold_num' => $arrUserInfo[$aUid]['gold_num'],
     						'server_id' => defined('GameConf::MERGE_SERVER_OPEN_DATE') ? $arrUserInfo[$aUid]['server_id'] : Util::getServerId(),
     			);
     		}

     		$offset += count($arrPartInfo);
     		if (count($arrPartInfo) < CData::MAX_FETCH_SIZE)
     		{
     			break;
     		}
     	}

     	return $arrRet;
     }

     public function getTopActivityInfo()
     {
     	$ret = array();

     	$uid = RPCContext::getInstance()->getUid();

     	$ret['compete'] = EnCompete::getTopActivityInfo();
     	$ret['worldcompete'] = EnWorldCompete::getTopActivityInfo();
     	$ret['pass'] = EnPass::getTopActivityInfo();
     	$ret['moon'] = EnMoon::getTopActivityInfo();
     	$ret['worldpass'] = EnWorldPass::getTopActivityInfo();
     	$ret['tower'] = EnTower::getTopActivityInfo();
     	$ret['dragon'] = EnDragon::getTopActivityInfo();
     	$ret['dart'] = EnChargeDart::getTopActivityInfo($uid);
     	$ret['helltower'] = EnTower::getHellTopActivityInfo();

     	return $ret;
     }

     public function addVipExp($uid, $expNum)
     {

     	// 非法uid
     	if ($uid == 0)
     	{
     		throw new FakeException('uid is 0');
     	}

     	// 加的vip经验值
     	if ($expNum <= 0)
     	{
     		throw new FakeException('invalid exp num:%d', $expNum);
     	}

     	// 检查是在用户线程还是0号线程
     	$guid = RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_UID);
     	if ($guid == null)
     	{
     		$proxy = new ServerProxy();
     		$ret = $proxy->checkUser($uid);
     		if ($ret)
     		{
     			Logger::warning('uid:%d just login when addGold4BBpay', $uid);
     		}
     		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
     	}

     	// 加到使用物品获得vip经验数值这里
     	$userObj = EnUser::getUserObj($uid);
     	$userObj->addUseItemGold($expNum);
     	Logger::info('uid:%d add vip exp:%d', $uid, $expNum);

		// 获得玩家累积vip经验
     	$useItemGold = $userObj->getUseItemGold();
     	$sumGold = User4BBpayDao::getSumGoldByUid($uid);
     	$sumGold += $useItemGold;

     	// 计算新vip，保存旧vip
     	$oldVip = $userObj->getVip();
     	$newVip = 0;
     	foreach (btstore_get()->VIP as $vipInfo)
     	{
     		if ($vipInfo['totalRecharge'] > $sumGold)
     		{
     			break;
     		}
     		else
     		{
     			$newVip = $vipInfo['vipLevel'];
     		}
     	}

     	if (($newVip - $oldVip) > 0)
     	{
     		$userObj->setVip($newVip);
     	}

     	//在线用户，推到前端
     	if ($guid != null && $userObj->isOnline())
     	{
     		//修改对象中的值      User4BBpayDao::update没有经过userObj->addGold直接写入数据库
     		$userObj->updateSession();
     		RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::USER_UPDATE_USER_INFO, array('gold_num' => $userObj->getGold(), 'vip' => $userObj->getVip()));

     		$chargeGold = User4BBpayDao::getSumGoldByUid($uid);
     		$msg    =    array(
     				'gold_num' => $userObj->getGold(), //当前的金币数目
     				'vip' => $userObj->getVip(),//当前的VIP等级
     				'charge_gold_sum' => $chargeGold + $useItemGold,//当前充值金额
     				'charge_gold' => $expNum,//此次充值金额
     				'pay_back' => 0, //充值返还（平台返还+配置返还）
     				'first_pay' => FALSE,//是否是首充
     				'charge_type' => CHARGE_TYPE::CHARGE_GOLD,
     		);
     		//RPCContext::getInstance()->sendMsg(array($uid), PushInterfaceDef::CHARGE_GOLD_UPDATE_USER, $msg);
     		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_VIP, $newVip);
     	}
     	else
     	{
     		Logger::trace('user %s is not onlie.',$uid);
     	}

     	if (($newVip - $oldVip) > 0)
     	{
     		ChatTemplate::sendSysVipLevelUp1($userObj->getTemplateUserInfo(), $newVip);
     		MailTemplate::sendVip($userObj->getUid(), $newVip);
     		ChatTemplate::sendBroadcastVipLevelUp2($userObj->getTemplateUserInfo(), $newVip);
     	}

     	$userObj->update();

     	return 'ok';
     }

     /**
      * (non-PHPdoc)
      * @see IUser::changeSex()
      */
     public function changeSex()
     {
        $uid = RPCContext::getInstance()->getUid();
        if (empty($uid))
        {
            throw new FakeException('uid is empty when trying to change sex.');
        }
        $return = UserLogic::changeUserSex($uid);
        if ($return == 'ok')
        {
            $ret = EnUser::getUserObj()->getHeroManager()->getMasterHeroObj()->getInfo();
            return $ret;
        }
        return 'fail';//其实在UserLogic::changeUserSex中变性失败直接报fake了，走不到这
     }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
