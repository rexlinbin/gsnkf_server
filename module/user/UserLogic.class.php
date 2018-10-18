<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: UserLogic.class.php 254621 2016-08-03 12:58:26Z GuohaoZheng $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/UserLogic.class.php $
 * @author $Author: GuohaoZheng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-03 12:58:26 +0000 (Wed, 03 Aug 2016) $
 * @version $Revision: 254621 $
 * @brief
 *
 **/


class UserLogic
{

	public static function login($arrReq)
	{
		$pid = BabelCrypt::decryptNumber($arrReq['pid']);
		return array($pid,1);
	}



    public static function getUsers($pid)
	{
		$arrField = array('uid', 'utid', 'uname');
        $arrRet =  UserDao::getArrUserByPid($pid, $arrField);

        Logger::debug("get users by pid:%d, users:%s", $pid, $arrRet);
        return $arrRet;
	}

	public static function createUser($pid,$utid,$uname,$uid=false)
	{
		$arrRet = array('ret'=>'ok');
		//1. 检查参数
		//check uname 名字是否包含敏感词或者不符合规范
		$arrRet['ret'] = Util::checkName($uname);
		if ('ok'!=$arrRet['ret'])
		{
			Logger::debug('invalid uname:%s', $uname);
			//随机名字库可能有包含敏感词或者非法字符的名字，也设置为已经使用
			UserDao::setRandomNameStatus($uname, UserDef::RANDOM_NAME_STATUS_USED);
			return $arrRet;
		}

		//check uname length
		$len = (mb_strlen($uname, 'utf8')+strlen($uname))/2; //中文算两个字符，英文算一个字符。 strlen对于utf-8的中文算3个字符
		if (UserConf::MAX_USER_NAME_LEN < $len || UserConf::MIN_USER_NAME_LEN > $len)
		{
			throw new FakeException('the length(%d) of uname(%s) error', $len, $uname);
		}

		if (!isset(UserConf::$USER_INFO[$utid]))
		{
			throw new FakeException('invalid utid:%d', $utid);
		}

        //检查角色数量是否等于最大值
        $userNum = UserDao::getUsersNumByPid($pid);
		if ($userNum>=UserConf::MAX_USER_NUM)
		{
			throw new FakeException('Cannot create user. The number of user must <= %d',
								UserConf::MAX_USER_NUM);
		}

		//2. 获取初始用户数据
		if(empty($uid))
		{
			$uid = IdGenerator::nextId('uid');
		}
		if ($uid==null)
		{
			return array('ret'=>'fail');
		}
		$strTime = strftime("%Y-%m-%d %H:00:00", Util::getTime());
		$executionTime = strtotime($strTime);
		$arrUserInfo = array(
			'uid' => $uid,
            'uname' => $uname,
            'pid' => $pid,
            'utid' => $utid,
            'status' => UserDef::STATUS_OFFLINE,
			'create_time' => Util::getTime(),
			'last_login_time' => 0,
			'last_logoff_time' => 0,
			'online_accum_time' => 0,
			'ban_chat_time' => 0,
			'mute' => 0,
			'level' => 1,
			'vip' => UserConf::INIT_VIP,
			'master_hid' => 0,
			'gold_num' => UserConf::INIT_GOLD,
			'silver_num' => UserConf::INIT_SILVER,
			'exp_num' => UserConf::INIT_EXPERIENCE,
			'soul_num' => 0,
		    'jewel_num' => 0,
			'execution' => UserConf::INIT_EXECUTION,
		    'execution_max_num'=>UserConf::MAX_EXECUTION,
			'execution_time' => $executionTime,
			'buy_execution_time' => 0,
			'buy_execution_accum' =>0,
			'stamina' => UserConf::INIT_STAMINA,
		    'stamina_max_num' => UserConf::MAX_STAMINA,
			'stamina_time' => $executionTime,
		    'prestige_num'=> 0,
			'tg_num' => 0,
			'wm_num' => 0,
			'fame_num' => 0,
			'book_num' => 0,
	        'fs_exp' => 0,
			'jh'=>0,
            'tally_point'=>0,
			'user_item_gold'=>0,
		    'tower_num' => 0,
			'buy_stamina_time' => 0,
			'buy_stamina_accum' =>0,
			'fight_cdtime' => 0,
			'fight_force' => 0,
		    'figure' => 0,
			'title' => 0,
			'base_goldnum' => 0,
			'va_hero' => array('unused' => array() ),
			'va_user' => array(
			        VA_USER::HERO_LIMIT=>HeroDef::INIT_HERO_LIMIT_NUM,
			        VA_USER::FLOP_NUM=>0,
			        VA_USER::DRESSINFO =>array(),
			        ),
		    'va_charge_info' => array(),
			);

		//配置的一些初始数据
		if (isset(GameConf::$USER_INIT_INFO))
		{
			foreach (GameConf::$USER_INIT_INFO as $key=>$value)
			{
				$arrUserInfo[$key] = $value;
			}
		}

		if (!empty(UserConf::$INIT_ARR_HTID))
		{
			$arrHtid = array();
			$arrHid = IdGenerator::nextMultiId('hid', array_sum(UserConf::$INIT_ARR_HTID));
			$hid = current($arrHid);
			foreach (UserConf::$INIT_ARR_HTID as $htid => $num)
			{
				$arrHtid[] = $htid;
				if( !HeroUtil::checkHtid($htid) )
				{
					Logger::fatal('create init hero failed. invalid htid:%d', $htid);
					continue;
				}
				for($i = 0; $i < $num; $i++)
				{
					$arrUserInfo['va_hero']['unused'][$hid] = array(
							UserDef::UNUSED_HERO_HTID => $htid,
							);
					$hid = next($arrHid);
				}
			}
			HeroLogic::updateHeroBook($uid, $arrHtid);
		}

		//3. 初始化主角
		$masterHid = IdGenerator::nextId('hid');
		if ( empty($masterHid) )
		{
			return array('ret'=>'fail');
		}

		if(! HeroUtil::isMasterHtid( UserConf::$USER_INFO[$utid][1] ))
		{
			throw new ConfigException('utid:%d, htid:%d not master hero', $utid, UserConf::$USER_INFO[$utid][1] );
		}

		$arrUserInfo['master_hid'] = $masterHid;

		//worldVip==
		if( defined('PlatformConfig::WORLD_VIP') && PlatformConfig::WORLD_VIP == WorldDef::WORLD_VIP_OPEN )
		{
			$baseGold = UserWorldDao::getCreateBaseGoldByPid($pid);
			$createVip = UserWorldDao::getCreateVip($baseGold);

			$arrUserInfo['vip'] = $createVip;
			$arrUserInfo['base_goldnum'] = $baseGold;
		}

		//4. 将用户数据插入数据库
		try
		{
			$ret = UserDao::createUser($arrUserInfo);
			if ($ret['affected_rows']!=1)
			{
				$arrRet['ret'] = 'name_used';
				Logger::trace('fail to create user, name %s is used', $uname);
				//更新随机名字库
				UserDao::setRandomNameStatus($uname, UserDef::RANDOM_NAME_STATUS_USED);
				return $arrRet;
			}

			//修改随机名字表的状态
			UserDao::setRandomNameStatus($uname, UserDef::RANDOM_NAME_STATUS_USED);
		}
		catch ( Exception $e )
		{
			Logger::debug('%d(pid) fail to create user %d(utid). msg:%s, trace:%s',
			$pid, $utid, $e->getMessage(), $e->getTraceAsString());
			$arrRet['ret'] = 'name_used';
			return $arrRet;
		}

		$heroAttr = HeroLogic::addNewHero($uid, $masterHid, UserConf::$USER_INFO[$utid][1]);

		//5. 其他模块初始化
		foreach( UserConf::$CREATE_USER_FUNC_LIST as $func )
		{
			call_user_func($func, $arrUserInfo);
		}

		Logger::trace('create user. uid:%d, utid:%d, uname:%s', $uid, $utid, $uname);
		$arrRet['uid'] = $uid;
		return $arrRet;
	}

	public static function getRandomName($num, $gender)
	{
		if ($num > UserConf::NUM_RANDOM_NAME)
		{
			$num = UserConf::NUM_RANDOM_NAME;
		}
		$offset = rand(0, 1000);
		$arrFields = array('name');
		return UserDao::getRandomName($arrFields, $gender, $num, $offset);

	}

    public static function userLogin($uid, $pid)
    {
    	$userObj = EnUser::getUserObj($uid);

    	//1. 检查是否被封号
    	$banInfo = $userObj->getBanInfo();
    	if( !empty($banInfo) )
    	{
    		if($banInfo['time'] > Util::getTime())
    		{
	    		return array(
	    				'ret' => 'ban',
	    				'info' => $banInfo
	    				);
    		}
    		else
    		{
    			$userObj->unsetBan();
    		}
    	}

    	if( !RestrictUser::canIpLogin($uid) )
    	{
    		return array(
    				'ret' => 'failed',
    		);
    	}

    	$leftBadGoldNum = self::checkBadOrder($uid);
    	if ( $leftBadGoldNum > 0 )
    	{
    		//这里没有updateuser，这样会攒到够扣的时候一起扣
    		return array(
    				'ret' => 'badorder',
    				'num' => $leftBadGoldNum
    		);
    	}

    	//2. 其他模块
    	foreach( UserConf::$LOGIN_FUNC_LIST as $func )
    	{
    		call_user_func($func, $uid);
    	}

    	//3. 更新数据   	更新字段：last_login_time status。
    	//在其他模块之后更新last_login_time。这样其他模块中可以使用last_login_time
    	$userObj->login();

    	//我在这里update user了， 其他模块里面就不用update了
    	$userObj->update ();

    	return array( 'ret' => 'ok');
    }

    public static function delayLoginCall($uid)
    {
    	foreach( UserConf::$LOGIN_DELAY_CALL_FUNC_LIST as $func )
    	{
    		call_user_func($func, $uid);
    	}
    }

    public static function userLogoff($uid, $arrLogoff)
    {
    	$userObj = EnUser::getUserObj();
    	$loginTime = $userObj->getLastLoginTime();

    	//1. 玩家数据更新
    	$userObj->logoff();

    	//2. 在线时间统计
    	Statistics::loginTime ( $loginTime, Util::getTime () );

    	//3. 其他模块
    	foreach( UserConf::$LOGOFF_FUNC_LIST as $func )
    	{
    		call_user_func($func, $uid, $loginTime);
    	}

    	//我在这里update user了， 其他模块里面就不用update了
    	$userObj->update ();
    }




    /**
     * 在mem中保存玩家战斗数据的key
     * @param int $uid
     * @return string
     */
    public static function getBattleInfoKey($uid)
    {
    	return 'battle_info#' . $uid;
    }

    public static function getArtificailBattleInfoKey($keyPrefix,$uid)
    {
        return $keyPrefix."#".$uid;
    }


    /**
     * 获取uid充值总金币数
     * @param int $uid
     * @return number
     */
    public static function getSumGoldByUid($uid)
    {
    	return User4BBpayDao::getSumGoldByUid($uid);
    }

    /**
     * 获取升级奖励
     * @param int $lvUp        玩家升级数目
     */
    public static function getLevelUpReward($lvUp)
    {
        if($lvUp <= 0)
        {
            return;
        }
        $getGold    =    $lvUp * UserConf::PRE_LEVEL_UP_GET_GOLD;
        $user    =    Enuser::getUserObj();
        $user->addGold($getGold, StatisticsDef::ST_FUNCKEY_LVUP_REWARD);
    }




    public static function getTopLevel($offset, $limit)
    {
    	$arrField = array('uid', 'level', 'upgrade_time');
    	//每次读最大值， 从0开始，保证前面是稳定的
    	$arrRet = UserDao::getTopLevel(0, UserConf::MAX_TOP, $arrField);
    	if (empty($arrRet))
    	{
    		return $arrRet;
    	}

    	//去掉最后一个不稳定的值
    	$min = end($arrRet);
    	$minLevel = $min['level'];
    	$arrTmp = array();
    	foreach ($arrRet as $ret)
    	{
    		if ($ret['level'] > $minLevel)
    		{
    			$arrTmp[] = $ret;
    		}
    	}
    	$arrRet = $arrTmp;

    	//level降序，按照upgrade_time升序 uid 升序
    	$sortCmp = new SortByFieldFunc(array(
    			'level'=>SortByFieldFunc::DESC,
    			'upgrade_time'=>SortByFieldFunc::ASC,
    			'uid'=>SortByFieldFunc::ASC));
    	usort($arrRet, array($sortCmp, 'cmp'));


    	//还需要查询最后一名的值
    	$num = $offset + $limit - count($arrRet);
    	if ($num > 0 )
    	{
    		//最小等级的都取出来, 按照升级时间升序
    		$arrMinRet = UserDao::getArrUserEqLevel($minLevel, $arrField, $num);
    		//第一次查询的去掉最小等级的所有值，然后跟所有最小等级的值合并
    		$arrRet = array_merge($arrRet, $arrMinRet);
    	}

    	$arrRet = array_slice($arrRet, $offset, $limit);

    	//查询user表信息
    	$arrUid = Util::arrayExtract($arrRet, 'uid');
    	$arrUser = UserDao::getArrUserByArrUid($arrUid, array('uid', 'uname', 'utid', 'level', 'guild_id'));

    	$arrGuildId = Util::arrayExtract($arrUser, 'guild_id');
    	$arrGuildName = EnGuild::getMultiGuild($arrGuildId, array('guild_name'));

    	foreach($arrUser as &$user)
    	{
    		$user['guild_name'] = '';
    		if ($user['guild_id']!=0)
    		{
    			$user['guild_name'] = $arrGuildName[$user['guild_id']]['guild_name'];
    		}
    	}

    	return $arrUser;
    }


    public static function getTopArena($offset, $limit)
    {
    	$arrField = array('uid', 'position');
    	$arrRet = EnArena::getTop($offset, $limit, $arrField);
    	if (empty($arrRet))
    	{
    		return array();
    	}

    	//level降序，按照upgrade_time升序 uid 升序
    	$sortCmp = new SortByFieldFunc(array('position'=>SortByFieldFunc::ASC));
    	usort($arrRet, array($sortCmp, 'cmp'));

    	$arrPos = Util::arrayIndexCol($arrRet, 'uid', 'position');
    	$arrUid = Util::arrayExtract($arrRet, 'uid');
    	$arrUser = UserDao::getArrUserByArrUid($arrUid, array('uid', 'uname', 'utid', 'level', 'guild_id'));
    	$arrGuildId = Util::arrayExtract($arrUser, 'guild_id');
    	$arrGuildName = EnGuild::getMultiGuild($arrGuildId, array('guild_name'));

   	 	foreach($arrUser as &$user)
    	{
    		$user['position'] = $arrPos[$user['uid']];

    		$user['guild_name'] = '';
    		if ($user['guild_id']!=0)
    		{
    			$user['guild_name'] = $arrGuildName[$user['guild_id']]['guild_name'];
    		}
    	}
    	return $arrUser;
    }

    public static function isFirstPay($uid,$orderType)
    {
        $curConf = self::getCurFirstPayConf();

        if(Enuser::isPay($uid, $curConf['startTime'], $curConf['endTime']))
        {
            return FALSE;
        }
        if($orderType == OrderType::ERROR_FIX_ORDER
                || ($orderType == OrderType::FULI_ORDER)
                  || ($orderType == OrderType::NORMAL_ORDER))
        {
            return TRUE;
        }
        return FALSE;
    }

    public static function getPayConf($firstPay)
    {
        if(defined('PlatformConfig::TOP_UP_CONFIG_INDEX') == FALSE)
        {
            throw new InterException('no such constant PlatformConfig::TOP_UP_CONFIG_INDEX.');
        }
        if($firstPay)
        {
            return self::getCurFirstPayConf(TRUE);
        }
        return btstore_get()->PAY_BACK[PlatformConfig::TOP_UP_CONFIG_INDEX]->toArray();
    }

    public static function getPayBack($addGold,$firstPay, $uid = 0)
    {
        $payBack = 0;

        /* 20160607 修改首充

        if($firstPay)
        {
            $payConf = self::getPayConf(TRUE);
            $arrFirstPayBack = $payConf['pay_back'];
            ksort($arrFirstPayBack);
            $cur = 0;
            foreach( $arrFirstPayBack as $gold => $back )
            {
            	if( $addGold < $gold )
            	{
            		break;
            	}
            	$cur = $gold;
            }
            if($cur > 0)
            {
            	$addGold -= $cur;
            	$payBack += $arrFirstPayBack[$cur];
            }
        }

        */

        $payConf = self::getPayConf(TRUE);

        $arrFirstPayBack = $payConf['pay_back'];
        $arrFirstPayBackType = $payConf['type'];

        $userObj = EnUser::getUserObj($uid);
        $arrChargeInfo = $userObj->getChargeInfo();

        ksort($arrFirstPayBack);

        $cur = 0;

        foreach ( $arrFirstPayBack as $gold => $back )
        {
            if( $addGold < $gold )
            {
                break;
            }

            if ( FIRST_PAY_BACK_TYPE::ALL == $arrFirstPayBackType && TRUE == $firstPay )
            {
                $cur = $gold;
            }
            elseif ( FIRST_PAY_BACK_TYPE::PART == $arrFirstPayBackType )
            {
                $arrChargeGold = array_keys($arrChargeInfo);

                if ( !in_array($gold, $arrChargeGold) )
                {
                    $cur = $gold;
                }
            }
            else
            {
                Logger::info("uid:%d addGold:%d firstPay:%s do nothing.", $uid, $addGold, var_export($firstPay, TRUE));
            }
        }

        if($cur > 0)
        {
            $addGold -= $cur;
            $payBack += $arrFirstPayBack[$cur];

            if ( FIRST_PAY_BACK_TYPE::PART == $arrFirstPayBackType )
            {
                $arrChargeInfo[$cur] = Util::getTime();
            }
        }

        $arrPayBack = self::getPayConf(FALSE);
        //从小到大排序
        ksort($arrPayBack);
        $minGold = current( array_keys($arrPayBack) );
        $payBackLv = array();
        while($addGold >= $minGold)
        {
        	//到最大的一级
        	$cur = 0;
        	foreach( $arrPayBack as $gold => $back )
        	{
        		if( $addGold < $gold )
        		{
        			break;
        		}
        		$cur = $gold;
        	}
        	if($cur <= 0)
        	{
        		break;
        	}
        	$addGold -= $cur;
        	$payBack += $arrPayBack[$cur];
        }
        return array(
            'pay_back' => $payBack,
            'charge_info' => $arrChargeInfo,
        );
    }

    public static function changeUserName($uid,$uname,$spendType)
    {
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        if($spendType == UserDef::CHANGE_NAME_SPEND_GOLD)
        {
            $needGold = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_CHANGE_NAME]['need_gold'];
            if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_CHANGE_NAME) == FALSE)
            {
                throw new FakeException('change name need gold %d.have %d.',$needGold,$userObj->getGold());
            }
        }
        else if($spendType == UserDef::CHANGE_NAME_SPEND_ITEM)
        {
            $needItem = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_CHANGE_NAME]['need_item'];
            if($bag->deleteItemsByTemplateID($needItem) == FALSE)
            {
                throw new FakeException('spend item failed.need item %s.',$needItem);
            }
        }
        else
        {
            throw new FakeException('no such spendtype %d for change name.',$spendType);
        }
        $arrRet['ret'] = Util::checkName($uname);
        if ('ok'!=$arrRet['ret'])
        {
            Logger::debug('invalid uname:%s', $uname);
            UserDao::setRandomNameStatus($uname, UserDef::RANDOM_NAME_STATUS_USED);
            return $arrRet['ret'];
        }

        $len = (mb_strlen($uname, 'utf8')+strlen($uname))/2; //中文算两个字符，英文算一个字符。 strlen对于utf-8的中文算3个字符
        if (UserConf::MAX_USER_NAME_LEN < $len || UserConf::MIN_USER_NAME_LEN > $len)
        {
            throw new FakeException('the length(%d) of uname(%s) error', $len, $uname);
        }
//         if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
//         {
//             $uname .= Util::getSuffixName();
//         }

        $retUid = UserDao::unameToUid($uname);
        if (!empty($retUid))
        {
            Logger::debug('name used:%s by uid %d.', $uname,$retUid);
            return 'duplication';
        }
        $userObj->setUname($uname);
        try
        {
            $bag->update();
            $userObj->update();
        }
        catch(Exception $e)
        {
            Logger::warning('change name failed.error msg is %s.',$e->getMessage());
            return 'duplication';
        }
        return 'ok';
    }

    /**
     * 更改性别
     * @author jinyang
     * @param int $uid
     * @throws FakeException
     */
    public static function changeUserSex($uid)
    {
        $userObj = EnUser::getUserObj($uid);
        //消耗物品
        $bag = BagManager::getInstance()->getBag($uid);
        $itemTempNeed = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_CHANGE_SEX];
        if ($bag->deleteItembyTemplateID($itemTempNeed, 1) == FALSE)//一张变性卡
            throw new FakeException('change sex fail: not enough item %s.', $itemTempNeed);

        //改变用户性别utid
        $newSex = ($userObj->getUtid()==1)?2:1;
        $userObj->setUtid($newSex);

        //改变主角武将模板htid
        $oldMasterHtid = $userObj->getHeroManager()->getMasterHeroObj()->getHtid();
        $sexChangeConf = btstore_get()->SEX_CHANGE;
        $sexChangeConf = $sexChangeConf->toArray();

        if (isset($sexChangeConf[$oldMasterHtid]))
        {
            $newMasterHtid = intval($sexChangeConf[$oldMasterHtid]);//男htid=>女htid
        }
        else
        {
            $sexChangeConf = array_flip($sexChangeConf);
            if (isset($sexChangeConf[$oldMasterHtid]))
                $newMasterHtid = intval($sexChangeConf[$oldMasterHtid]);//女htid=>男htid
            else
                throw new FakeException('change sex fail: new htid is empty, old htid is %s', $oldMasterHtid);
        }

        $userObj->getHeroManager()->getMasterHeroObj()->setMasterHtid($newMasterHtid);

        //修改t_user:va_user:master_skill改变主角普通技能、怒气技能（目前所有初始技能和星魂技能分男女，其他技能暂时没有分男女20160705）

        $oldMasterSkills = $userObj->getMasterSkill();

        if (!empty($oldMasterSkills))
        {
        	$originSkiTypes = array(
        			PropertyKey::ATTACK_SKILL => CreatureAttr::ATTACK_SKILL,
        			PropertyKey::RAGE_SKILL => CreatureAttr::RAGE_SKILL
        	);
        	
        	$athenaSkiTypes = array(
        			PropertyKey::ATTACK_SKILL => AthenaSql::NORMAL,
        			PropertyKey::RAGE_SKILL => AthenaSql::RAGE
        	);
        	
        	foreach ($originSkiTypes as $skillType => $confColumn)
        	{
        		if (!empty($oldMasterSkills[$skillType])) 
        		{
        			//现有技能是否为初始技能
        			
        			$currentSkill = $oldMasterSkills[$skillType][0];
        			$skillOfOldHtid = Creature::getHeroConf($oldMasterHtid, $confColumn);
        			
        			if ($currentSkill == $skillOfOldHtid) 
        			{	//是初始技能
        				
        				$skillOfNewHtid = Creature::getHeroConf($newMasterHtid, $confColumn);
        				$userObj->learnMasterSkill($skillType, $skillOfNewHtid, $oldMasterSkills[$skillType][1]);
        			}
        			else 
        			{	//不是初始再判断是否为星魂技能
        				
        				if (!isset($athenaSkills)) 
        					$athenaSkills = EnAthena::getSkillList($uid);
        				
        				if (isset($athenaSkills[$athenaSkiTypes[$skillType]])) 
        				{
        					$skillsLearned = $athenaSkills[$athenaSkiTypes[$skillType]];//获取玩家所学到的所有星魂技能[普通或怒气]
        					
        					if (!empty($skillsLearned) && in_array($currentSkill, $skillsLearned))
        					{	//是星魂技能，那么换成另一性别的星魂技能
        						
        						$skillOfNewHtid = EnAthena::getCrspSkill($currentSkill, $newSex);
        						if ($skillOfNewHtid == 0) 
        							throw new FakeException("cant find skill:%s 's corresponding athena skill of opposite sex", $currentSkill);
        						
        						$userObj->learnMasterSkill($skillType, $skillOfNewHtid, MASTERSKILL_SOURCE::ATHENA);
        					}
        				}
        			}
        				//都不是，那就是从其他渠道学到的技能，例如名将技能，跟性别无关。
        		}
        	}
        }

        //修改t_athena: va_data:主角已经学到的星魂技能全部替换成另一性别的
        EnAthena::rebuildDBVaData($uid);
        
        $bag->update();
        $userObj->update();

        return 'ok';
    }

    /**
     * 判断等级
     * @param int $uid 用户id
     * @return boolean
     */
    public static function judgeLevel($uid)
    {
    	Logger::trace('User::judgeLevel Start.');

    	$userObj = $userObj = EnUser::getUserObj($uid);

    	$level = $userObj->getLevel();

    	$minLevel = 20;

    	if ($level < $minLevel)
    	{
    		return false;
    	}

    	Logger::trace('User::judgeLevel End.');

    	return true;
    }

    /**
     * 个人排名
     * @param int $uid 用户名
     * @param string $rankColumn 根据什么排行（战力还是等级）
     * @return int 返回个人排名
     */
    public static function getPrivateRankNumByColumn($uid,$arrColumn)
    {
    	Logger::trace('UserLogic::getPrivateRankNumByColumn Start.');

    	$rank = 0;

    	$userObj = EnUser::getUserObj($uid);

    	$arrPrivateColumn = 0;
    	if ($arrColumn =='fight_force')
    	{
    		$arrPrivateColumn = $userObj->getFightForce();
    	}
    	elseif ($arrColumn == 'level')
    	{
    		$arrPrivateColumn = $userObj->getLevel();
    	}

    	$arrExp = $userObj->getAllExp();
    	$arrUid = $userObj->getUid();

    	$arrRank = UserDao::getUserNumByCondition($arrColumn, $arrPrivateColumn, $arrExp, $arrUid);

    	$rank = $arrRank[0]+$arrRank[1]+$arrRank[2];

    	Logger::trace('UserLogic::getPrivateRankNumByColumn End.');

    	return $rank+1;
    }

    /**
     * 全服前50排行榜
     * @param int $uid 用户名
     * @param string $rankColumn 根据什么排行(战力还是等级)
     * @param int $offset
     * @param int $limit
     * @return array 返回个人排名和前50排行用户信息
     */
    public static function getRankByColumn($uid,$arrColumn,$offset,$limit)
    {
    	Logger::trace('UserLogic::getRankByColumn Start.');

    	if (self::judgeLevel($uid) == false )
    	{
    		throw new FakeException('user:%d does not reach level 20.',$uid);
    	}

    	$retRankList = UserDao::getRankListByColumn($arrColumn, $offset, $limit);

    	$arrGuildId   = Util::arrayExtract($retRankList,'guild_id');
    	$arrUserId    = Util::arrayExtract($retRankList, 'uid');

    	$guildName = EnGuild::getArrGuildInfo($arrGuildId,array('guild_name'));
    	$userInfo = EnUser::getArrUserBasicInfo($arrUserId, array('htid','dress'));

    	foreach ($retRankList as $key => $value)
    	{
    		$user = $retRankList[$key];

    		$rank = $key+1;
    		$user['rank'] = $rank;

    		$guildId = $user['guild_id'];
    		unset($user['guild_id']);

    		if ($guildId!=0)
    			$user['guild_name'] = $guildName[$guildId]['guild_name'];

    		$userId = $user['uid'];

    		$user['htid']       = $userInfo[$userId]['htid'];
    		$user['dressInfo']  = $userInfo[$userId]['dress'];

    		$retRankList[$key] = $user;
    	}

    	$retSelfRankNum = self::getPrivateRankNumByColumn($uid, $arrColumn);

    	$retRankNum = array();
    	$retRankNum['selfRank'] = $retSelfRankNum;

    	Logger::trace('UserLogic::getRankByColumn End.');

    	return array(
    			$retRankNum,
    			$retRankList
    	);
    }

    public static function getUnionProfit($uid)
    {
        $arrHidInFmt = EnFormation::getArrHidInFormation($uid);
        $arrHidInExtra = EnFormation::getArrHidInExtra($uid);
        $arrHidInAttrExtra = EnFormation::getArrHidInAttrExtra($uid);
        $arrHid = array_merge($arrHidInExtra,$arrHidInFmt,$arrHidInAttrExtra);
        $arrHeroObj = EnUser::getUserObj($uid)->getHeroManager()->getArrHeroObj($arrHid);
        foreach($arrHeroObj as $hid => $heroObj)
        {
            Logger::trace('hero %d info %s',$hid,$heroObj->getInfo());
        }
        $unionProfit = EnFormation::getUnionProfitByFmt($arrHeroObj,$uid);
        $arrAddAttr = array();
        foreach($unionProfit as $hid => $addAttr)
        {
            if(FALSE == in_array($hid, $arrHidInFmt))
            {
                continue;
            }
            $arrAddAttr[$hid] = $addAttr;
        }
        Logger::trace('getUnionProfit %s',$unionProfit);
        return $arrAddAttr;
    }


    public static function checkBadOrder($uid)
    {
    	if ( !defined('PlatformConfig::COMPENSATE_BAD_ORDER')
    		|| PlatformConfig::COMPENSATE_BAD_ORDER <= 0 )
    	{
    		Logger::trace('not check bad order');
    		return 0;
    	}

    	$sumBadGold = BadOrderDao::getSumNeedSubNumOfUid( $uid );
    	if ( $sumBadGold <= 0 )
    	{
    		Logger::trace('no bad order');
    		return 0;
    	}

    	$userObj = EnUser::getUserObj($uid);

    	$alreadySubNum = $userObj->getSubNumByBadOrder();
    	if ( $alreadySubNum >= $sumBadGold )
    	{
    		Logger::trace('already sub all bad gold. already:%d, sub:%s', $alreadySubNum, $sumBadGold);
    		return 0;
    	}

    	$subNum = min( $sumBadGold - $alreadySubNum, $userObj->getGold() );

    	if ( ! $userObj->subGold($subNum, StatisticsDef::ST_FUNCKEY_BAD_ORDER) )
    	{
    		throw new InterException('sub gold failed');
    	}

    	$userObj->addSubNumByBadOrder($subNum);

    	$left = $sumBadGold - $alreadySubNum - $subNum;
    	Logger::info('uid:%d sub bad orde gold:%d, left:%d, all:%d',
    			$uid, $subNum, $left, $sumBadGold);

    	return $left;
    }

    public static function getCurFirstPayConf($needToArray=FALSE)
    {
        $now = Util::getTime();

        if(defined('PlatformConfig::TOP_UP_CONFIG_INDEX') == FALSE)
        {
            throw new InterException('no such constant PlatformConfig::TOP_UP_CONFIG_INDEX.');
        }

        $arrFirstPayConf = btstore_get()->FIRSTPAY_REWARD[PlatformConfig::TOP_UP_CONFIG_INDEX];

        if ($needToArray)
        {
            $arrFirstPayConf = $arrFirstPayConf->toArray();
        }

        $arrCurConf = array();

        foreach ($arrFirstPayConf as $conf)
        {
            if (!isset($conf['startTime']) || !isset($conf['endTime']))
            {
                throw new FakeException('old conf found, new conf needed.');
            }

            if ($conf['startTime'] <= $now && $now <= $conf['endTime'])
            {
                $arrCurConf = $conf;
                break;
            }
        }

        if (empty($arrCurConf))
        {
            throw new FakeException('no useful conf for first pay.');
        }

        return $arrCurConf;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */