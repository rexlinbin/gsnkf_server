<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: OtherUserObj.class.php 259225 2016-08-29 13:20:02Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/OtherUserObj.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-29 13:20:02 +0000 (Mon, 29 Aug 2016) $
 * @version $Revision: 259225 $
 * @brief
 *
 **/


/**
 * UserObj和OtherUserObj用来修改用户信息。
 * 不要直接使用构造函数，应该EnUser::getInstance。
 *
 * 修改的用户有操蛋的四种状态，分别为：
 * 1、修改自己的数据，uid等于global.uid
 *  这种需要修改db，session。
 * 2、修改其他用户的数据，uid不等于global.uid
 * 	这种情况，保存变化的值，update的时候给lcserver发一个消息。
 * 	如果这个用户在线，则是第三种情况，否则是第四种情况
 * 3. lcserver告诉我，其他用户修改了老子的数据。uid等于global.uid
 * 	这种情况修改db，session，再同步到前端。
 * 4、没有任何在线用户，用来处理lcserve的调用。global.uid==null.修改数据库。
 *
 *
 * 情况2、使用OtherUserObj来实现，
 *
 * 1、3、4可以看做相同的处理：
 * 1、3的处理中，某些属性的修改可能需要通知任务系统。同步到前端，在上层模块中做。
 * 4 可以设置一个global.uid ，修改session、数据库。
 * 使用user.status来判断是否在线，通知任务系统。
 * 使用UserObj来实现， UserObj继承自OtherUserObj，实现update方法。
 *
 * @author idyll
 *
 *
 * 时间相关的处理，比如行动力，
 * 在构造函数的时候进行一次计算
 */



class OtherUserObj
{
	protected $user = array();
	
	protected $userModify = array();
	
	protected $heroManager = null;
	
	/**
	 * 战斗数据缓存
	 * 初始时一定要置为NULL @see modifyBattleData
	 * @var array()
	 * {
	 * 		fightForce:	战斗力
	 * 		arrHero	:阵型中的各个战斗单位数据
	 * 		updateUid: 在mem中设置这个缓存的uid
	 * }
	 */
	protected $battleData = NULL;
		
	/**
	 * 对于随着时间变化而自动改变的属性，转到UserObj进行计算
	 * execution
	 * @var array
	 */
	private $extData = array();
	
	/**
	 * 金币统计信息
	 */
	protected $arrGoldStat = array();
	
	protected $silverTrans2ItemNum = 0;
	
	public function __construct ($uid)
	{
		//如果玩家自己的连接里，尝试从session里面获取数据
		if ($uid == RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_UID))
		{
			$this->user = RPCContext::getInstance ()->getSession(UserDef::SESSION_KEY_USER);
			if( !empty($this->user) && $this->user['uid'] != $uid )
			{
				throw new InterException('uid in session not match the user data in session. uid:%d, user:%s', $uid, $this->user);
			}
			Logger::trace('new useObj from session:%s', $this->user);
		}
		
		if ( empty($this->user) ) 
		{
			$arrField = UserDef::$USER_FIELDS;
			if (defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ))
			{
				$arrField[] = 'server_id';
			}
			
			$userData = UserDao::getUserByUid($uid, $arrField);
			if (empty($userData))
			{
				throw new SysException('fail to get user by uid %d', $uid);
			}
			
			//在用户连接中， 检查一下serverId
			$serverIdInSession = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_SERVER_ID );
			if( $uid == RPCContext::getInstance()->getSession(UserDef::SESSION_KEY_UID)
				&& isset($userData['server_id']) 
				&& !empty($serverIdInSession)
				&& $userData['server_id'] != $serverIdInSession)
			{
				Logger::warning('server not match. uid:%d, serverId in db:%d,  serverId in session:%d', 
							$uid, $userData['server_id'], $serverIdInSession );
			}
			
			Logger::trace('new useObj from db:%s', $userData);		

			$this->user = $userData;
			if ($uid == RPCContext::getInstance()->getUid())
			{
				RPCContext::getInstance ()->setSession(UserDef::SESSION_KEY_USER, $userData);
			}
		}		
	
		$this->user['uid'] = intval($this->user['uid']);
		if ($this->user['uid']==0)
		{
			throw new SysException('uid equal 0.');
		}
	
		$this->userModify = $this->user;
		$this->init();
		$this->extData = array();
	}
	

	protected function init()
	{		      		
		
		$this->refreshExecution();
		$this->refreshStamina();
	}
	

	public function getPid()
	{
		return $this->userModify['pid'];
	}
	
	public function getUtid()
	{
		return $this->userModify['utid'];
	}
	
	public function getUid()
	{
		return $this->userModify['uid'];
	}
	
	public function getUname()
	{
		return $this->userModify['uname'];
	}	
	
	public function getStatus()
	{
		return $this->userModify['status'];
	}
	
	public function getCurExecution()
	{
		return $this->userModify['execution'];
	}
	
	public function getExecutionMaxNum()
	{
	    if(empty($this->userModify['execution_max_num']))
	    {
	        $initAddMaxNum = EnAchieve::initHisExecutionNum($this->getUid());
	        $this->userModify['execution_max_num'] = $initAddMaxNum + UserConf::MAX_EXECUTION;
	    }
	    return $this->userModify['execution_max_num'];
	}
	
	public function getExecutionTime()
	{
		return $this->userModify['execution_time'];;
	}
	
	public function getBuyExecutionAccum()
	{
		return $this->userModify['buy_execution_accum'];
	}

	public function getStamina()
	{
		return $this->userModify['stamina'];
	}
	
	public function getStaminaTime()
	{
	    return $this->userModify['stamina_time'];
	}	
	
	public function getLevel()
	{
		return $this->userModify['level'];
	}
	
	public function getVip()
	{
		return $this->userModify['vip'];
	}
	
	public function getMasterHid()
	{
		return $this->userModify['master_hid'];
	}
	
	public function getAllExp()
	{
		return $this->userModify['exp_num'];
	}
	
	public function getCreateTime()
	{
		return $this->userModify['create_time'];
	}
	
	/**
	 * 获取玩家在当前级别下的经验数目
	 */
	public function getExp()
	{
		$exp = $this->userModify['exp_num'];		
		$level = $this->userModify['level'];
		
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		
		if( isset( $expTable[$level + 1] ) && $exp >= $expTable[$level + 1] )
		{
			Logger::fatal('invalid userdata. exp overflow. uid:%d, exp:%d, level:%d', $this->userModify['uid'], $exp, $level);
			$exp = $expTable[$level + 1] - $expTable[$level] -1;
		}
		else if($exp < $expTable[$level])
		{						
		    $lv = $level;
			while($lv > 1 && $exp < $expTable[$lv] )
			{
				$lv--;
			}
			$this->userModify['level'] = $lv;
			$exp -= $expTable[$lv];
			Logger::fatal('invalid userdata. exp < level. uid:%d, exp:%d, level:%d', $this->userModify['uid'], $exp, $level);
		}
		else
		{
			$exp -= $expTable[$level];
		}
		
		return $exp;
	}
	
	public function getSilver()
	{
		return $this->userModify['silver_num'];
	}
	
	public function getGold()
	{
		return $this->userModify['gold_num'];
	}
	
	public function getFightCdTime()
	{
		return $this->userModify['fight_cdtime'];
	}
	
	public function getBanChatTime()
	{
		return $this->userModify['ban_chat_time'];
	}
	
	public function getSoul()
	{
		return $this->userModify['soul_num'];
	}
	
	public function getJewel()
	{
	    return $this->userModify['jewel_num'];
	}
	
	public function getPrestige()
	{
	    return $this->userModify['prestige_num'];
	}
	
	public function getTgNum()
	{
		return $this->userModify['tg_num'];
	}
	
	public function getWmNum()
	{
		return $this->userModify['wm_num'];
	}
	
	public function getFameNum()
	{
		return $this->userModify['fame_num'];
	}
	
	public function getBookNum()
	{
		return $this->userModify['book_num'];
	}
	
	public function getFsExp()
	{
	    return $this->userModify['fs_exp'];
	}
	
	public function getJH()
	{
		return $this->userModify['jh'];
	}
	
	public function getTallyPoint()
	{
	    return $this->userModify['tally_point'];
	}
	
	public function getUseItemGold()
	{
	    return $this->userModify['user_item_gold'];
	}
	
	public function getBaseGoldNum()
	{
		return $this->userModify['base_goldnum'];
	}
	
	public function getLastLoginTime()
	{
		return $this->userModify['last_login_time'];
	}
	
	public function getLastLogoffTime()
	{
		return $this->userModify['last_logoff_time'];
	}
	
	public function getDressInfo()
	{
	    if(isset($this->userModify['va_user'][VA_USER::DRESSINFO]))
	    {
	        return $this->userModify['va_user'][VA_USER::DRESSINFO];
	    }
	    return array();
	}
	
	public function getAllUnusedHero()
	{
		$arrHero = $this->userModify['va_hero']['unused'];
		$returnData = array();
		foreach($arrHero as $key => $value)
		{
			$returnData[$key] = array(
					'htid' => $value[UserDef::UNUSED_HERO_HTID],
					'level' => isset($value[UserDef::UNUSED_HERO_LEVEL]) ? $value[UserDef::UNUSED_HERO_LEVEL]:1, //没有level字段就表示level=1
					);		
		}
		return $returnData;
	}
	public function getUnusedHero($hid)
	{
		if( isset($this->userModify['va_hero']['unused'][$hid]) )
		{
			$hero = $this->userModify['va_hero']['unused'][$hid];
			$returnData = array(
					'htid' => $hero[UserDef::UNUSED_HERO_HTID],
					'level' => isset($hero[UserDef::UNUSED_HERO_LEVEL]) ? $hero[UserDef::UNUSED_HERO_LEVEL]:1, //没有level字段就表示level=1
					);	
			return $returnData;
		}
		return NULL;
	}
	public function getUnusedHeroHtid($hid)
	{
	    if( isset($this->userModify['va_hero']['unused'][$hid]) )
	    {
	        return $this->userModify['va_hero']['unused'][$hid][UserDef::UNUSED_HERO_HTID];
	    }
	    return NULL;
	}
	
	public function getUnusedHeroNum()
	{
	    if(isset($this->userModify['va_hero']['unused']))
	    {
	        return count($this->userModify['va_hero']['unused']);
	    }
	    return 0;	            
	}
	
	/**
	 * 返回消耗金币信息
	 * @param $beginDate int  起始日期，形式如: 20130710
	 * @return int
	 * )
	 */
	public function getAccumSpendGold($beginDate,$endData=PHP_INT_MAX)
	{
		if( !isset($this->userModify['va_user']['spend_gold']) )
		{
			return 0;
		}
		$arrSpendGold = $this->userModify['va_user']['spend_gold'];

		$goldAccum = 0;
		foreach ($arrSpendGold as $date => $gold)
		{
			if ($date >= $beginDate && $date <= $endData)
			{
				$goldAccum += $gold;
			}
		}
		return $goldAccum;
	}
	
	/**
	 * 返回消耗体力信息
	 * @param $beginDate int  起始日期，形式如: 20130710
	 * @return int
	 * )
	 */
	public function getAccumSpendExecution($beginDate,$endData=PHP_INT_MAX)
	{
	    if( !isset($this->userModify['va_user']['spend_execution']) )
	    {
	        return 0;
	    }
	    $arrSpendExecution = $this->userModify['va_user']['spend_execution'];
	
	    $executionAccum = 0;
	    foreach ($arrSpendExecution as $date => $execution)
	    {
	        if ($date >= $beginDate && $date <= $endData)
	        {
	            $executionAccum += $execution;
	        }
	    }
	    return $executionAccum;
	}
	
	/**
	 * 返回消耗耐力信息
	 * @param $beginDate int  起始日期，形式如: 20130710
	 * @return int
	 * )
	 */
	public function getAccumSpendStamina($beginDate,$endData=PHP_INT_MAX)
	{
	    if( !isset($this->userModify['va_user']['spend_stamina']) )
	    {
	        return 0;
	    }
	    $arrSpendStamina = $this->userModify['va_user']['spend_stamina'];
	
	    $staminaAccum = 0;
	    foreach ($arrSpendStamina as $date => $stamina)
	    {
	        if ($date >= $beginDate && $date <= $endData)
	        {
	            $staminaAccum += $stamina;
	        }
	    }
	    return $staminaAccum;
	}

	/**
	 * 获取封号相关信息，如果没有被封返回NULL
	 * @return NULL
	 */
	public function getBanInfo()
	{
		if( !isset( $this->userModify['va_user']['ban'] ) )
		{
			Logger::trace('in ban, no ban info. uid:%d', $this->userModify['uid']);
			return NULL;
		}
		return $this->userModify['va_user']['ban'];
	}
	
	
	
	/**
	 * @return
	 * <code>
	 * 'uid'=>uid
	 * 'utid'=>utid
	 * 'uname'=>uname
	 * </code>
	 */
	public function getTemplateUserInfo ()
	{
		return array('uid'=>$this->getUid(),
				'utid'=>$this->getUtid(),
				'uname'=>$this->getUname());
	}
	
	public function isOnline()
	{
		return $this->userModify['status'] == UserDef::STATUS_ONLINE;
	}	
	
	/**
	 * 是否禁言
	 * @return bool
	 */
	public function isBanChat()
	{
	    Logger::trace('banchattime %s.now time %s.',$this->userModify['ban_chat_time'],Util::getTime());
		return $this->userModify['ban_chat_time'] > Util::getTime();
	}
	
		
	public function updateSession()
	{
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_USER, $this->userModify);
	}
	
	
	
	/**
	 * 特殊数据，只给otherUser使用， UserObj重载为空
	 * @param unknown_type $key
	 * @param unknown_type $value
	 * @throws Exception
	 */
	protected function addExtData($key, $value)
	{
		switch ($key)
		{
			case 'execution' :
				if (isset($this->extData['execution']))
				{
					$this->extData['execution'] += $value;
				}
				else
				{
					$this->extData['execution'] = $value;
				}
				break;
			case 'stamina' :
				if (isset($this->extData['stamina']))
				{
					$this->extData['stamina'] += $value;
				}
				else
				{
					$this->extData['stamina'] = $value;
				}
				break;
			default:
				throw new InterException('unknow ext data %d', $key);
				break;
		}
	}
	
	
	
	public function addExecution ($num)
	{
		$num = intval($num);
		Logger::debug('addExecution:%d, cur:%d', $num, $this->userModify['execution']);
	
		$this->refreshExecution();

		//这里可能是奖励，直接加吧，不检查最大值了
		$this->userModify['execution'] += $num;
		
		Logger::debug('addExecution result:%d', $this->userModify['execution']);
	
		$this->addExtData('execution', $num);		
		
		if ($this->userModify['execution'] < 0)
		{
			Logger::fatal('invalid execution. sub:%d, now:%d', $num, $this->userModify['execution']);
			return false;
		}		
		return true;
	}
	
	public function subExecution ($num)
	{
		Logger::debug('subExecution %d', $num);
		$num = intval($num);
		if(FALSE == $this->addExecution(-$num))
		{
		    return FALSE;
		}
		$this->sumSpendExecution($num);
		return TRUE;
	}
	
		
	public function refreshExecution()
	{
		$curExe = $this->userModify['execution'];
		$refreshTime = $this->userModify['execution_time'];
	
		$diffTime = Util::getTime() - $refreshTime;
		$addExe = intval( floor(  $diffTime / UserConf::SECOND_PER_EXECUTION  ));
	
		if ($addExe > 0 )
		{
			$refreshTime += UserConf::SECOND_PER_EXECUTION * $addExe;
			$maxExecution = $this->getExecutionMaxNum();
			if ( $curExe < $maxExecution )
			{
				$curExe += $addExe;
				if ($curExe > $maxExecution)
				{
					$curExe = $maxExecution;
				}
			}
	
			Logger::debug('add execution %d, now:%d', $addExe, $curExe);
	
			$this->userModify['execution'] = $curExe;
			$this->userModify['execution_time'] = $refreshTime;
		}
	}
	
	public function addStamina($num)
	{
		$num = intval($num);
		Logger::debug('addStamina:%d, cur:%d', $num, $this->userModify['stamina']);
		
		$this->refreshStamina();
		
		$this->userModify['stamina'] += $num;		
		
		Logger::debug('addStamina result:%d', $this->userModify['stamina']);
		
		$this->addExtData('stamina', $num);
		
		if ($this->userModify['stamina'] < 0)
		{
			Logger::warning('invalid stamina. sub:%d, now:%d', $num, $this->userModify['stamina']);
			return false;
		}
		return true;
	}
	
	public function subStamina($num)
	{
		Logger::debug('subStamina %d', $num);
		$num = intval($num);
		if(FALSE == $this->addStamina(-$num))
		{
		    return FALSE;
		}
		$this->sumSpendStamina($num);
		return TRUE;
	}
	
	public function refreshStamina()
	{	
		$curStm = $this->userModify['stamina'];
		$refreshTime = $this->userModify['stamina_time'];
		
		$diffTime = Util::getTime() - $refreshTime;
		$addStm = intval( floor(  $diffTime / UserConf::SECOND_PER_STAMINA  ));
		
		if ($addStm > 0 )
		{
		    $refreshTime += UserConf::SECOND_PER_STAMINA * $addStm;
		    if ( $curStm < $this->getStaminaMaxNum() )
		    {
		        $curStm += $addStm;
		        if ($curStm > $this->getStaminaMaxNum())
		        {
		            $curStm = $this->getStaminaMaxNum();
		        }
		    }
		
		    Logger::debug('add stamina %d, now:%d', $addStm, $curStm);
		
		    $this->userModify['stamina'] = $curStm;
		    $this->userModify['stamina_time'] = $refreshTime;
		}
		
	}
	
	public function getStaminaMaxNum()
	{
	    return $this->userModify['stamina_max_num'];
	}
	
	public function addStaminaMaxNum ($num)
	{
		$num = intval($num);
	    Logger::info('addStaminaMaxNum %s for user %d',$num,$this->getUid());
	    if($num <= 0)
	    {
	    	Logger::fatal('addStaminaMaxNum invalid num:%d', $num);
	        return;
	    }
	    $this->userModify['stamina_max_num'] += $num;
	}
	
	public function addExecutionMaxNum ($num)
	{
	    $num = intval($num);
	    if($num <= 0)
	    {
	        Logger::fatal('addExecutionMaxNum invalid num:%d', $num);
	        return;
	    }
	    $maxExecution = $this->getExecutionMaxNum();
	    $this->userModify['execution_max_num'] = $maxExecution + $num;
	    Logger::info('addExecutionMaxNum %s for user %d before %d',$num,$this->getUid(),$maxExecution);
	}
	
	public function addGold ($num, $type)
	{
		$num = intval($num);		
		if( $num < 0 )
		{
			throw new InterException('fail to subGold, the num:%d is not positive', $num);
		}
		
		return $this->changeGold($num, $type);
	}
	
	protected function changeGold($num, $type)
	{
		$this->userModify['gold_num'] += $num;
			
		if ($num == 0)
		{
			return true;
		}
		
		//金币统计
		if($type > 0)
		{
			if (isset($this->arrGoldStat[$type]))
			{
				$this->arrGoldStat[$type] += $num;
			}
			else
			{
				$this->arrGoldStat[$type] = $num;
			}
		}
		
		if($this->userModify['gold_num'] > UserConf::GOLD_MAX)
		{
			Logger::fatal('gold_num:%d reach max:%d', $this->userModify['gold_num'], UserConf::GOLD_MAX);
			$this->userModify['gold_num'] = UserConf::GOLD_MAX;
			return true;
		}
		
		if ($this->userModify['gold_num'] < 0)
		{
			Logger::warning('invalid gold_num. sub:%d, now:%d', $num, $this->userModify['gold_num']);
			return false;
		}
		return true;
	}
	
	
	
	public function addSilver ($num)
	{
		$num = intval($num);
		$this->userModify['silver_num'] += $num;
	
		if ( $this->userModify['silver_num'] > UserConf::SILVER_MAX)
		{
			Logger::fatal('silver_num:%d reach max:%d', $this->userModify['silver_num'], UserConf::SILVER_MAX);
			
			$this->silverTrans2ItemNum = UserConf::SILVER_TRANS_MAX;
			$this->userModify['silver_num'] -= UserConf::SILVER_TRANS_MAX;
			if ($this->userModify['silver_num'] > UserConf::SILVER_MAX) 
			{
				$this->userModify['silver_num'] = UserConf::SILVER_MAX;
			}
			
			return true;
		}
		
		
		if ($this->userModify['silver_num'] < 0)
		{
			Logger::warning('invalid silver_num. sub:%d, now:%d', $num, $this->userModify['silver_num']);
			return false;
		}
		
		EnAchieve::updateSilver($this->getUid(), $this->getSilver());
		return true;
	}
	
	
	public function subSilver ($num)
	{
		return $this->addSilver(-$num);
	}

	/**
	 * 
	 * @param int $num
	 * @return int        返回实际添加的经验值(1.如果当前等级等于最大等级，就不在加经验值了 2.经验值的最大值是$expTable[$maxUserLv+1]-1)
	 */
	public function addExp($num)
	{
	    Logger::trace('addExp %d',$num);
		$num = intval($num);
		if($num < 0)
		{
			throw new InterException('cant sub exp');
		}
		if ($this->userModify['level'] >= UserConf::MAX_LEVEL)
		{
			Logger::info('achieve maxLevel:%d', UserConf::MAX_LEVEL);
			return 0;
		}
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		
		$preExp = $this->userModify['exp_num'];
		$exp = $this->userModify['exp_num'] + $num;			
		//不会因为加exp导致level降低
		$lv = $this->userModify['level'];
		while( isset( $expTable[$lv+1] ) && $exp >= $expTable[$lv+1] && $lv < UserConf::MAX_LEVEL )
		{
			$lv++;
		}
		if( isset( $expTable[$lv+1] ) && $exp >= $expTable[$lv+1] )
		{
			Logger::fatal('achieve max exp. uid:%d, exp:%d, maxExp:%d', $this->userModify['uid'], $exp, $expTable[$lv+1]);
			$exp = $expTable[$lv+1]-1;		
		}
		$this->userModify['exp_num'] = $exp;
		if( $lv > $this->userModify['level'])
		{
			Logger::trace('new changed. old:%d, new:%d', $this->userModify['level'], $lv);
			$this->levelUp($lv-$this->getLevel());
		}
		return $exp - $preExp;
	}

	/**
	 * 当前不允许给其他玩家加经验  所以不会导致此函数的调用
	 * @param int $num
	 */
	public function levelUp($num)
	{
	    Logger::trace('ignore levelup in otherUserObj!');
	    return;
	}
	
	public function addSoul($num)
	{
		$num = intval($num);
		$this->userModify['soul_num'] += $num;
		
		if ( $this->userModify['soul_num'] > UserConf::SOUL_MAX)
		{
			Logger::fatal('soul_num:%d reach max:%d', $this->userModify['soul_num'], UserConf::SOUL_MAX);
			$this->userModify['soul_num'] = UserConf::SOUL_MAX;
		}
				
		if ($this->userModify['soul_num'] < 0)
		{
			Logger::warning('invalid soul_num. sub:%d, now:%d', $num, $this->userModify['soul_num']);
			return false;
		}
		
		return true;
	}
	
	public function subSoul ($num)
	{
		return $this->addSoul(-$num);
	}
	
	public function addTallyPoint($num)
	{
	    $num = intval($num);
	    $this->userModify['tally_point'] += $num;
	    
	    if ( $this->userModify['tally_point'] > UserConf::TALLYPOINT_MAX)
	    {
	        Logger::fatal('tally_point:%d reach max:%d', $this->userModify['tally_point'], UserConf::TALLYPOINT_MAX);
	        $this->userModify['tally_point'] = UserConf::TALLYPOINT_MAX;
	    }
	    
	    if ($this->userModify['tally_point'] < 0)
	    {
	        Logger::warning('invalid tally_point. sub:%d, now:%d', $num, $this->userModify['tally_point']);
	        return false;
	    }
	    
	    return true;
	}
	
	public function subTallyPoint($num)
	{
	    return $this->addTallyPoint(-$num);
	}
	
	public function addUseItemGold($num)
	{
		$num = intval($num);
		$this->userModify['user_item_gold'] += $num;
		 
		if ( $this->userModify['user_item_gold'] > UserConf::USE_ITEM_GOLD_MAX)
		{
			Logger::fatal('user_item_gold:%d reach max:%d', $this->userModify['user_item_gold'], UserConf::USE_ITEM_GOLD_MAX);
			$this->userModify['user_item_gold'] = UserConf::USE_ITEM_GOLD_MAX;
		}
		 
		if ($this->userModify['user_item_gold'] < 0)
		{
			Logger::warning('invalid user_item_gold. sub:%d, now:%d', $num, $this->userModify['user_item_gold']);
			return false;
		}
		 
		return true;
	}
	
	public function subUseItemGold($num)
	{
		if (FrameworkConfig::DEBUG) 
		{
			$this->addUseItemGold(-$num);
		}
		else 
		{
			Logger::fatal('can not sub use item gold online!!!');
		}
	}
	
	public function addJewel($num)
	{
	    $num = intval($num);
	    $this->userModify['jewel_num'] += $num;
	    
	    if ( $this->userModify['jewel_num'] > UserConf::JEWEL_MAX)
	    {
	        Logger::fatal('jewel_num:%d reach max:%d', $this->userModify['jewel_num'], UserConf::JEWEL_MAX);
	        $this->userModify['jewel_num'] = UserConf::JEWEL_MAX;
	    }
	    
	    if ($this->userModify['jewel_num'] < 0)
	    {
	        Logger::warning('invalid jewel_num. sub:%d, now:%d', $num, $this->userModify['jewel_num']);
	        return false;
	    }
	    if($num > 0)
	    	EnAchieve::updateJewel($this->getUid(), $this->getJewel());
	    
	    if ($num < 0)
	    	EnNewServerActivity::updateJewel($this->getUid(), -$num);
	    
	    return true;
	}
	
	public function subJewel($num)
	{
	    return $this->addJewel(-$num);
	}
	
	public function addPrestige($num)
	{
	    $num = intval($num);
	    $this->userModify['prestige_num'] += $num;
	    
	    if ( $this->userModify['prestige_num'] > UserConf::PRESTIGE_MAX)
	    {
	        Logger::fatal('prestige_num:%d reach max:%d', $this->userModify['prestige_num'], UserConf::PRESTIGE_MAX);
	        $this->userModify['prestige_num'] = UserConf::PRESTIGE_MAX;
	    }
	    
	    if ($this->userModify['prestige_num'] < 0)
	    {
	        Logger::warning('invalid prestige_num. sub:%d, now:%d', $num, $this->userModify['prestige_num']);
	        return false;
	    }
	    EnAchieve::updatePrestige($this->getUid(), $this->getPrestige());
	    
	    if ($num < 0)
	    	EnNewServerActivity::updatePrestige($this->getUid(), -$num);
	    
	    return true;
	}
	
	public function subPrestige($num)
	{
	    return $this->addPrestige(-$num);
	}
	
	public function addTgNum($num)
	{
		$num = intval($num);
		$this->userModify['tg_num'] += $num;
			
		if ($this->userModify['tg_num'] > UserConf::TG_MAX)
		{
			Logger::fatal('tg_num:%d reach max:%d', $this->userModify['tg_num'], UserConf::TG_MAX);
			$this->userModify['tg_num'] = UserConf::TG_MAX;
		}
			
		if ($this->userModify['tg_num'] < 0)
		{
			Logger::warning('invalid tg_num. sub:%d, now:%d', $num, $this->userModify['tg_num']);
			return false;
		}
		return true;
	}
	
	public function subTgNum($num)
	{
		return $this->addTgNum(-$num);
	}
	
	public function addWmNum($num)
	{
		$num = intval($num);
		$this->userModify['wm_num'] += $num;
			
		if ($this->userModify['wm_num'] > UserConf::WM_MAX)
		{
			Logger::fatal('wm_num:%d reach max:%d', $this->userModify['wm_num'], UserConf::WM_MAX);
			$this->userModify['wm_num'] = UserConf::WM_MAX;
		}
			
		if ($this->userModify['wm_num'] < 0)
		{
			Logger::warning('invalid wm_num. sub:%d, now:%d', $num, $this->userModify['wm_num']);
			return false;
		}
		return true;
	}
	
	public function subWmNum($num)
	{
		return $this->addWmNum(-$num);
	}
	
	public function addFameNum($num)
	{
		$num = intval($num);
		$this->userModify['fame_num'] += $num;
			
		if ($this->userModify['fame_num'] > UserConf::FAME_MAX)
		{
			Logger::fatal('fame_num:%d reach max:%d', $this->userModify['fame_num'], UserConf::FAME_MAX);
			$this->userModify['fame_num'] = UserConf::FAME_MAX;
		}
			
		if ($this->userModify['fame_num'] < 0)
		{
			Logger::warning('invalid fame_num. sub:%d, now:%d', $num, $this->userModify['fame_num']);
			return false;
		}
		return true;
	}
	
	public function addBookNum($num)
	{
		$num = intval($num);
		$this->userModify['book_num'] += $num;
			
		if ($this->userModify['book_num'] > UserConf::BOOK_MAX)
		{
			Logger::fatal('book_num:%d reach max:%d', $this->userModify['book_num'], UserConf::BOOK_MAX);
			$this->userModify['book_num'] = UserConf::BOOK_MAX;
		}
			
		if ($this->userModify['book_num'] < 0)
		{
			Logger::warning('invalid book_num. sub:%d, now:%d', $num, $this->userModify['book_num']);
			return false;
		}
		return true;
	}
	
	public function subBookNum($num)
	{
		return $this->addBookNum(-$num);
	}
	
	public function subFameNum($num)
	{
		return $this->addFameNum(-$num);
	}
	
	public function addJH($num)
	{
		$num = intval($num);
		$this->userModify['jh'] += $num;
		 
		if ($this->userModify['jh'] > UserConf::JH_MAX)
		{
			Logger::fatal('jh:%d reach max:%d', $this->userModify['jh'], UserConf::JH_MAX);
			$this->userModify['jh'] = UserConf::JH_MAX;
		}
		 
		if ($this->userModify['jh'] < 0)
		{
			Logger::warning('invalid jh. sub:%d, now:%d', $num, $this->userModify['jh']);
			return false;
		}
		return true;
	}
	
	public function subJH($num)
	{
		return $this->addJH(-$num);
	}
	
	public function addFsExp($num)
	{
	    $num = intval($num);
	    $this->userModify['fs_exp'] += $num;
	    
	    if ($this->userModify['fs_exp'] > UserConf::FSEXP_MAX)
	    {
	        Logger::fatal('fs_exp:%d reach max:%d', $this->userModify['fs_exp'], UserConf::FSEXP_MAX);
	        $this->userModify['fs_exp'] = UserConf::FSEXP_MAX;
	    }
	    
	    if ($this->userModify['fs_exp'] < 0)
	    {
	        Logger::warning('invalid fs_exp. sub:%d, now:%d', $num, $this->userModify['fs_exp']);
	        return false;
	    }
	    return true;
	}
	
	public function subFsExp($num)
	{
	    return $this->addFsExp(-$num);
	}
	
	public function addFightCd ( $addCd )
	{
		$now = Util::getTime();
		
		//cd没到不能加， 可以防止代码错误导致这个人的fight_cdtime变得太大
		if ($this->userModify['fight_cdtime'] > $now )
		{
			return false;
		}
		
		$this->user['fight_cdtime'] = $now;
		$this->userModify['fight_cdtime'] = $now + $addCd;
		return true;
	}
	
	/**
	 * 测试时，为用户加vip等级时使用
	 * @param unknown_type $vip
	 */
	public function setVip4Test($vip)
	{
		$this->userModify['vip'] = $vip;
	}
	
	
	public function rollback()
	{
		if ($this->heroManager!=null)
		{
			$this->getHeroManager()->rollback();
		}
		$this->userModify = $this->user;
		$this->extData = array();
	}
	
	public function update ()
	{
		if ($this->heroManager != null)
		{
			$this->getHeroManager()->update();
		}	
		
		$arrField = array();
		foreach ($this->user as $key => $value)
		{
			if ($this->userModify[$key]!=$value)
			{								
				if ( in_array($key, UserDef::$OTHER_UPDATE_IGNORE) )
				{
					continue;
				}
				
				if ( in_array($key, UserDef::$OTHER_UPDATE_SET) )
				{
					$arrField[$key] = $this->userModify[$key];
				}
				else if( in_array($key, UserDef::$OTHER_UPDATE_DELT)  )
				{
					$arrField[$key] = $this->userModify[$key]-$value;
				}
				else
				{
					throw new InterException('cant update field:%s in otherobj. org:%s, cur:%d', 
						$key, $value, $this->userModify[$key]);
				}
			}
	
			//特殊数据
			foreach ($this->extData as $key => $value)
			{
				$arrField[$key] = $value;
			}
			if ( !empty($this->arrGoldStat) )
			{
				$arrField['gold_stat'] = $this->arrGoldStat;
			}
		}
	
		if (!empty($arrField))
		{
			Logger::debug('old info:%s, new info:%s, modify other user field:%s',
						$this->user, $this->userModify, $arrField);
			
			$this->user = $this->userModify;
			$this->extData = array();
			$this->arrGoldStat = array();
			
			foreach( $arrField as $key => $value)
			{
				if( in_array($key, UserDef::$FIELD_CANT_NEGATIVE) && $this->userModify[$key] < 0)
				{
					throw new InterException('uid:%d, field:%s cant negtive:%d', $this->user['uid'], $key, $value);
				}
			}
			Logger::trace('send executeTask to lcserver,uid %s,modify %s.',
			        $this->user['uid'],$arrField);
			//给lcserver发消息
			RPCContext::getInstance()->executeTask($this->user['uid'],
					'user.modifyUserByOther',
					array($this->user['uid'], $arrField),
					false);
		}
	}
	
	
	
	/**
	 * 当前类都使用此函数得到$this->heroManager
	 * @return HeroManager
	 */
	public function getHeroManager()
	{
		if ($this->heroManager==null)
		{
			$this->heroManager = new HeroManager($this->userModify['uid']);
		}
		return $this->heroManager;
	}
	
	
	public function getFightForce()
	{
		return $this->userModify['fight_force'];
	}
	
	public function getMaxFightForce()
	{
		return $this->userModify['max_fight_force'];
	}
	
	public function setFightForce($num)
	{
		if ( RPCContext::getInstance()->getUid() == $this->getUid() )
		{
			EnAchieve::updateFightForce($this->getUid(), $num);
			EnNewServerActivity::updateFightForce($this->getUid(), $num);
			if($this->getFightForce() != $num)
			{
			    RPCContext::getInstance()->sendMsg(
			            array($this->getUid()),
			            PushInterfaceDef::USER_FIGHTFORCE_UPDATE,
			            array('fight_force'=>$num));
			}
		}
	    $this->userModify['fight_force']    =   $num; 
	    
	    // 更新历史最大战斗力
	    $maxFightForce = $this->getMaxFightForce();
	    if ($num > $maxFightForce)
	    {
	    	Logger::info('uid[%d], curr max fight force[%d], new fight force[%d], replace', $this->getUid(), $maxFightForce, $num);
	    	$this->userModify['max_fight_force'] = $num;
	    }
	}
	
	public function setBanChatTime($time)
	{
	    $this->userModify['ban_chat_time'] = $time;
	}

	/**
	 * @param array $arrHero
	 * [
	 *     posId=>hid
	 * ]
	 * @param array $arrHeroEquip
	 * [
	 *     hid => array
	 *     [
	 *         equipType=>array
	 *         [
	 *             pos => array
	 *             [
	 *                 item_id:int
	 *                 item_template_id:int
	 *                 item_num:int
	 *                 va_item_text:int
	 *             ]
	 *         ]
	 *     ]
	 * ]
	 * @param array $addAttr
	 * [
	 *     attrTypeKey=>array
	 *     [
	 *         hid=>array
	 *         [
	 *             attrKey=>attrValue
	 *         ]
	 *     ]
	 * ]
	 * @param string $keyPrefix
	 * @return array
	 * [
	 *     uid:int
	 *     name:string
	 *     level:int
	 *     isPlayer:boolean
	 *     fightForce:int
	 *     arrHero:array
	 *     arrPet:array
	 *     craft:array
	 * ]
	 */
	public function getArtificailBattleInfo($arrHid,$arrHeroEquip,$addAttr,$keyPrefix)
	{
	    $battleData = $this->getArtificailBattleData($arrHid,$arrHeroEquip,$addAttr,$keyPrefix);
	    Logger::trace('get battle data from db');
	    $battleFormation = array(
	            'uid' => $this->userModify['uid'],
	            'name' => $this->userModify['uname'],
	            'level' => $this->userModify['level'],
	            'isPlayer' => true,
	            'fightForce' => $battleData['fight_force'],
	            'arrHero' => $battleData['arrHero'],
	            'arrPet' => $battleData['arrPet'],
	            'craft' => $battleData['craft'],
	            );
	    return $battleFormation;
	}
	
	public function getArtificailBattleData($arrHid,$arrHeroEquip,$addAttr,$keyPrefix)
	{
	    $btMcKey = UserLogic::getArtificailBattleInfoKey($keyPrefix, $this->getUid());
	    $battleData = McClient::get($btMcKey);
	    if(!empty($battleData))
	    {
	        $battleDataOfFmt = $this->getBattleData();
	        if(isset($battleDataOfFmt['updateTime'])
	                && $battleData['updateTime'] >= $battleDataOfFmt['updateTime']
	                && $this->isArtificailBattleDataValid($arrHid, $arrHeroEquip, $battleData))
	        {
	            return $battleData;
	        }
	    }
	    $battleData = $this->getArtificailBattleDataNoCache($arrHid,$arrHeroEquip,$addAttr);
	    
	    if( empty($battleData ) )
	    {
	        Logger::warning('empty formation');
	        return $battleData;
	    }
	    
	    $battleData['updateUid'] = RPCContext::getInstance()->getUid();
	    $battleData['updateTime'] = Util::getTime();
	    $ret = McClient::set($btMcKey, $battleData);
	    Logger::info('update ArtificailBattleData of user %d updateuid %d time %d.',
	            $this->getUid(),$battleData['updateUid'],$battleData['updateTime']);
	    if( $ret != 'STORED' )
	    {
	        Logger::warning('mem set battle data failed');
	    }
	    return $battleData;
	}
	
	public function isArtificailBattleDataValid($arrHid,$arrHeroEquip,$battleData)
	{
	    $arrHero = $battleData['arrHero'];
	    if(count($arrHero) != count($arrHid))
	    {
	        Logger::info('isArtificailBattleDataValid false.uid %d arrhid %s',$this->getUid(),$arrHid);
	        return FALSE;
	    }
	    foreach($arrHero as $pos => $heroInfo)
	    {
	        $hid = $heroInfo[PropertyKey::HID];
	        if(!isset($arrHid[$pos]) || $arrHid[$pos] != $hid)
	        {
	            Logger::info('isArtificailBattleDataValid false.uid %d arrhid %s',$this->getUid(),$arrHid);
	            return FALSE;
	        }
	        if($heroInfo[PropertyKey::EQUIP_INFO] != $arrHeroEquip[$hid])
	        {
	            Logger::info('isArtificailBattleDataValid false.uid %d hid %d equipinfo change.',$this->getUid(),$hid);
	            return FALSE;
	        }
	    }
	    return TRUE;
	}
	
	public function getArtificailBattleDataNoCache($arrHid,$arrHeroEquip,$addAttr)
	{
	    //准备原来阵上、小伙伴、现在阵上的所有武将信息
	    $arrHeroInfo = HeroLogic::getArrHero($arrHid, HeroDef::$HERO_FIELDS);
	    if(count($arrHeroInfo) < count($arrHid))
	    {
	        $arrUnusedHero = self::getAllUnusedHero();
	        foreach($arrHid as $hid)
	        {
	            if(isset($arrUnusedHero[$hid]))
	            {
	                $arrHeroInfo[$hid] = HeroLogic::getInitData($this->getUid(), $hid, 
	                        $arrUnusedHero[$hid]['htid'],$arrUnusedHero[$hid]['level']);
	            }
	        }
	    }
	    if(count($arrHeroInfo) < count($arrHid))
	    {
	        throw new FakeException('arrHeroInfo %s arrHid %s',$arrHeroInfo,$arrHid);
	    }
	    //将原来阵上的装备都加到现在的武将身上   准备装备信息
	    $arrHeroObj = array();
	    foreach($arrHid as $pos => $hid)
	    {
	        $heroInfo = $arrHeroInfo[$hid];
	        foreach(HeroDef::$ALL_EQUIP_TYPE as $equipType)
	        {
	            $heroInfo['va_hero'][$equipType] = array(); 
	            if(isset($arrHeroEquip[$hid][$equipType]))
	            {
	                $heroInfo['va_hero'][$equipType] = $arrHeroEquip[$hid][$equipType];
	            }
	        }
	        $arrHeroObj[$pos] = new ArtificialHeroObj($heroInfo);
	    }
	    $craftProfit = EnFormation::getWarcraftProfit($this->getUid());
	    $attrExtraPosProfit = EnFormation::getAttrExtraPosProfit($this->getUid());
	    foreach($arrHid as $pos => $hid)
	    {
	        foreach($addAttr as $attrKey => $arrHeroAttr)
	        {
	            if(isset($arrHeroAttr[$hid]))
	            {
	                $arrHeroObj[$pos]->setAddAttr( $attrKey, $arrHeroAttr[$hid]);
	            }
	        }
	        if( isset( $craftProfit[$pos] ) )
	        {
	            $arrHeroObj[$pos]->setAddAttr( HeroDef::ADD_ATTR_BY_CRAFT, $craftProfit[$pos] );
	        }
	        
	        //助战位加成使用当前的
	        $arrHeroObj[$pos]->setAddAttr(HeroDef::ADD_ATTR_BY_ATTR_EXTRA_POS, $attrExtraPosProfit);
	    }
	    
	    //计算武将的战斗数据
	    $arrHeroInfo = EnFormation::changeObjToInfo($arrHeroObj);
	    $fightForce = 0;
	    foreach($arrHeroInfo as $pos => $heroInfo)
	    {
	        $fightForce += $heroInfo[PropertyKey::FIGHT_FORCE];
	    }
	    
	    $battleData = array(
	            'uname'=>$this->getUname(),
	            'fight_force'=>$fightForce,
	            'arrHero' => $arrHeroInfo,
	            'arrPet' => EnPet::getFightPetInfo($this->getUid()),
	            'craft' => EnFormation::getCurCraftLevelId($this->getUid()),
	    );
	    Logger::trace('getArtificailBattleDataNoCache %s',$battleData);
	    return $battleData;
	 }
	
	 public function modifyArtificailBattleData($keyPrefix)
	 {
	     $btMcKey = UserLogic::getArtificailBattleInfoKey($keyPrefix, $this->getUid());
	     McClient::del($btMcKey);
	 }
	
	public function getBattleFormation($formation = array())
	{
		if(!empty($formation))
		{
			Logger::trace('get battle data with formation:%s', $formation);
			$changed = EnFormation::setFormation($this->userModify['uid'], $formation);
		}
		
		$battleData = $this->getBattleData();
	
		$battleInfo = array(
				'uid' => $this->userModify['uid'],
				'name' => $this->userModify['uname'],
				'level' => $this->userModify['level'],
				'isPlayer' => true,
				'fightForce' => $this->userModify['fight_force'],
				'littleFriend' => $battleData['littleFriend'], //将小伙伴数据放到战报中，最初是为了盗号恢复
				'attrFriend' => $battleData['attrFriend'],
				'arrHero' => $battleData['arrHero'],				
				'arrPet' => $battleData['arrPet'],
				'craft' => $battleData['craft'],
		);
		
		if( !empty($battleData['arrCar']) )
		{
			$battleInfo['arrCar'] = $battleData['arrCar'];
		}
	
		return $battleInfo;
	}

	public function getBattleData()
	{
		$uid = $this->userModify['uid'];
		
		if( !empty( $this->battleData ) )
		{
			Logger::trace('get battle data in local');
			return $this->battleData;
		}
		
		$key = UserLogic::getBattleInfoKey($uid);
		
		//空array时，说明之前请过缓存，就不用再去get了
		if ( is_array($this->battleData) )
		{
			$battleData = array();
		}
		else
		{
			$battleData = McClient::get($key);
		}
		
		/**
			两种情况需要重新计算战斗数据
			1）没有缓存
			2）当前处在自己的线程中，但是发现我的缓存不是我自己设置的。 
				之所以在这种情况下任务缓存是无效的，是因为可能在本请求内部，自己的战斗数据可能已经被修改了
		 */
		$gid = RPCContext::getInstance()->getUid();
		Logger::trace('getBattleData of user %s.',$uid);
		if( empty($battleData) || ( $uid == $gid && $battleData['updateUid'] != $uid  ))
		{
			//没有缓存，就需要去拉数据库
			$battleData = $this->getBattleDataNoCache();
				
			if( empty($battleData ) )
			{
				Logger::warning('empty formation');
				return $battleData;
			}
				
			$battleData['updateUid'] = RPCContext::getInstance()->getUid();
			$battleData['updateTime'] = Util::getTime();
				
			$ret = McClient::set($key, $battleData);
			Logger::info('update BattleData of user %d updateuid %d time %d.',
			        $this->getUid(),$battleData['updateUid'],$battleData['updateTime']);
			if( $ret != 'STORED' )
			{
				Logger::warning('mem set battle data failed');
			}
			Logger::trace('get battle data from db');
		}
		else
		{
			Logger::trace('get battle data in mem');
			
		}
	
		$this->battleData = $battleData;
		return $battleData;
	}
	
	/**
	 * 将缓存中的battle数据删掉
	 * 
	 * 为了防止连续多次调用modifyBattleData导致重复McClient::del
	 * 1）初始时 $this->battleData = NULL
	 * 2）第一次调用modifyBattleData时被设置为 空array()
	 * 3）后面再次调用modifyBattleData时，发现$this->battleData为空array()时，不再McClient::del
	 */
	public function modifyBattleData()
	{
		//NULL,非空array时才需要del
		if ( $this->battleData === NULL ||  !empty( $this->battleData)  )
		{
			$key = UserLogic::getBattleInfoKey( $this->userModify['uid'] );			
			$ret = McClient::del($key);
		}
		
		$this->battleData = array();
	}
	
	public function changeFormation($formation, $isCraftOpen = false)
	{
		if ( $isCraftOpen )
		{
			$this->modifyBattleData();
			Logger::debug('craftopen just del cache');
			return;
		}
		
		$uid = $this->userModify['uid'];
		$key = UserLogic::getBattleInfoKey($uid);
		$battleData = McClient::get($key);
		
		if(empty($battleData))
		{
			Logger::trace('no battle data in mem');
			return;
		}

		$arrHero = $battleData['arrHero'];
		if(count($formation) != count($arrHero) )
		{
			throw new FakeException('num not match in changeFormation');
		}
		$hidHeroMap = Util::arrayIndex($arrHero, PropertyKey::HID);
		$newArrHero = array();
		$changed = false;
		foreach($formation as $pos => $hid)
		{
			if(!isset($hidHeroMap[$hid]))
			{
				$formationInMem = Util::arrayIndexCol($hidHeroMap, PropertyKey::POSITION, PropertyKey::HID);
				throw new FakeException('invalid formation:%s, mem:%s', $formation, $formationInMem);
			}
			if($pos != $hidHeroMap[$hid][PropertyKey::POSITION] )
			{
				$changed = true;
			}
			$newArrHero[$pos] = $hidHeroMap[$hid];
			$newArrHero[$pos][PropertyKey::POSITION] = $pos;
		}
		if($changed)
		{
			$battleData['arrHero'] = $newArrHero;
			$battleData['updateUid'] = RPCContext::getInstance()->getUid();
			
			$ret = McClient::set($key, $battleData);
			if( $ret != 'STORED' )
			{
				throw new SysException('mem set battle data failed');				
			}
			$this->battleData = $battleData;
		}
	}
	
	public function getBattleDataNoCache()
	{
		//1. 先把会影响战斗数据的物品都准备好
		$uid = $this->getUid();
		$arrHid = EnFormation::getArrHidInFormation($uid);
		$this->prepareItem($arrHid);
	    $squadInDb  = EnFormation::getArrHidInSquad($uid);
	    $squad = array();
	    for($i = 0; $i < FormationDef::FORMATION_SIZD; $i++)
	    {
	        if(!isset($squadInDb[$i]))
	        {
	            $squad[$i] = 0;
	        }
	        else 
	        {
	            $squad[$i] = $squadInDb[$i];
	        }
	    }
	    
	    $littleFriend = array();
	    $arrHidLittleFriend = EnFormation::getArrHidInExtra($uid);
	    foreach ( $arrHidLittleFriend as $index => $hid)
	    {
	    	//修改战斗数据中小伙伴数据的结构
	    	//$littleFriend[$hid] = $this->getHeroManager()->getHeroObj($hid)->getHtid();
	    	$littleFriend[] = array(
	    		PropertyKey::HID => $hid,
	    		PropertyKey::HTID => $this->getHeroManager()->getHeroObj($hid)->getHtid(),
	    		PropertyKey::POSITION => $index,
	    	);
	    }
	    
	    //第二套小伙伴，属性小伙伴
	    $attrFriend = array();
	    $arrHidAttrFriend = EnFormation::getArrHidInAttrExtra($uid);
	    foreach ( $arrHidAttrFriend as $index => $hid )
	    {
	    	$heroObj = $this->getHeroManager()->getHeroObj($hid);
	    	$attrFriend[] = array(
	    		PropertyKey::HID => $hid,
	    		PropertyKey::HTID => $heroObj->getHtid(),
	    		PropertyKey::POSITION => $index,
	    		PropertyKey::LEVEL => $heroObj->getLevel(),
	    		PropertyKey::EVOLVE_LEVEL => $heroObj->getEvolveLv(),
	    		'talent' => $heroObj->getCurTalent(),
	    	);
	    }
	    
	    
		Logger::trace('getBattleDataNoCache. uid:%d, arrHid:%s', $uid, $arrHid);
		
		//2. 从阵型模块获得 战斗阵型
		list($formation,$attrExtraProfit) = EnFormation::getArrHeroObjInFormation($uid);
	
		Logger::info('attrExtraProfit %s',$attrExtraProfit);
		if(empty($formation))
		{
			Logger::warning('no hero in formation');
			return NULL;
		}
	
		$arrHeroInfo = EnFormation::changeObjToInfo($formation);
		
		// 将一些为0或者空的字段或者没必要的字段unset掉
		$arrHeroInfo = BattleUtil::unsetEmptyField($arrHeroInfo);
		
		//3. 计算一下战斗力。 反正所需的数据都准备好，就把战斗力一起算好
		$fightForce = 0;
		foreach($arrHeroInfo as $pos => $heroInfo)
		{
		    $fightForce += $heroInfo[PropertyKey::FIGHT_FORCE];
		}
		
		$this->setFightForce($fightForce);
	
		foreach($attrFriend as $index => $friendInfo)
		{
		    $hid = $friendInfo[PropertyKey::HID];
		    if(isset($attrExtraProfit[$hid]))
		    {
		        $friendInfo['attr'] =  HeroUtil::adaptAttrReverse($attrExtraProfit[$hid]);
		        $attrFriend[$index] = $friendInfo;
		    }
		}

		$returnData = array(
		        'uname'=>$this->getUname(),
		        'squad'=>$squad,
				'littleFriend' => $littleFriend,
				'attrFriend' => $attrFriend,
				'arrHero' => $arrHeroInfo,		
				'arrPet' => EnPet::getFightPetInfo($uid),
				'craft' => EnFormation::getCurCraftLevelId($uid),
		);
		
		$arrCarInfo = EnChariot::getChariotSkill($uid);
		if (!empty($arrCarInfo)) 
		{
			$returnData['arrCar'] = $arrCarInfo;
		}
	    
		return $returnData;
	}
	
	public function getHeroLimit()
	{
	    if(!isset($this->userModify['va_user'][VA_USER::HERO_LIMIT]))
	    {
	        $this->userModify['va_user'][VA_USER::HERO_LIMIT] = HeroDef::INIT_HERO_LIMIT_NUM;
	    }
	    return $this->userModify['va_user'][VA_USER::HERO_LIMIT];
	}
	
	/**
	 * 准备好武将身上所有影响战斗的物品（装备，技能书等）
	 * @param unknown_type $arrHid 不超过9个
	 * @throws Exception
	 */
	public function prepareItem($arrHid)
	{
		if (count($arrHid) > FormationDef::FORMATION_SIZD)
		{
			throw new InterException('must less than %d', FormationDef::FORMATION_SIZD + 1);			
		}
	
		$arrEquipId = array();
		$arrGenId = array();
	
		foreach ($arrHid as $hid)
		{
			$heroObj = $this->getHeroManager()->getHeroObj($hid);
			$arrEquipId = array_merge($arrEquipId, $heroObj->getAllEquipId() );
		}
		
		$arrEquipItem = ItemManager::getInstance()->getItems($arrEquipId);
		//宝物上的符印
		$arrInlayId = array();
		foreach ($arrEquipItem as $item_id => $item)
		{
			if ($item==null)
			{
				Logger::fatal('fixing item! uid: %d item_id: %d', $this->getUid(), $item_id);
				continue;
			}
	
			if ($item->getItemType() == ItemDef::ITEM_TYPE_TREASFRAG)
			{
				$arrInlayId = array_merge($arrGenId, array_values($item->getInlay()));
			}
		}
	
		ItemManager::getInstance()->getItems($arrInlayId);
	}
	
	public function getGuildId()
	{
	    return $this->userModify['guild_id'];
	}
	
	public function setGuildId($guildId)
	{
	    $this->userModify['guild_id'] = $guildId;
	}
	
	public function getFigure()
	{
	    return $this->userModify['figure'];
	}
	
	public function setFigure($figure)
	{
	    $this->userModify['figure'] = $figure;
	}
	
	public function getTitle()
	{
		return $this->userModify['title'];
	}
	
	public function setTitle($title)
	{
		$this->userModify['title'] = $title;
	}
	
	protected function sumSpendExecution($num)
	{
	    Logger::info('spend execution of others.');
	}
	
	protected function sumSpendStamina($num)
	{
	    Logger::info('spend stamina of others.');
	}
	
	public function getMasterSkill()
	{
	    $skillInfo = array();
	    if(isset($this->userModify['va_user']['master_skill']))
	    {
	        $skillInfo = $this->userModify['va_user']['master_skill'];
	    }
	    else
	    {
	        $masterSkill = EnStar::getMasterSkill($this->getUid());
	        Logger::info('masterSkill %s',$masterSkill);
	        if(!empty($masterSkill))
	        {
	            if(!empty($masterSkill[PropertyKey::ATTACK_SKILL]))
	            {
	                $skillInfo[PropertyKey::ATTACK_SKILL] = array(
	                        $masterSkill[PropertyKey::ATTACK_SKILL],
	                        MASTERSKILL_SOURCE::STAR,
	                );
	            }
	            if(!empty($masterSkill[PropertyKey::RAGE_SKILL]))
	            {
	                $skillInfo[PropertyKey::RAGE_SKILL] = array(
	                        $masterSkill[PropertyKey::RAGE_SKILL],
	                        MASTERSKILL_SOURCE::STAR,
	                );
	            }
	        }
	    }
	    return $skillInfo;
	}
	
	public function getServerId()
	{
		if(defined('GameConf::MERGE_SERVER_OPEN_DATE'))
		{
			return $this->userModify['server_id'];
		}
		
		return Util::getServerId();
	}
	
	public function getChargeInfo()
	{
	    if ( !isset( $this->userModify['va_charge_info'] ) )
	    {
	        return array();
	    }
	    
	    $arrFirstPayConf = UserLogic::getPayConf(TRUE);
	    
	    $chargeInfo = $this->userModify['va_charge_info'];
	    foreach ( $chargeInfo as $goldNum => $time )
	    {
	        if ( $time < $arrFirstPayConf['startTime'] )
	        {
	            unset( $chargeInfo[$goldNum] );
	        }
	    }
	    
	    return $chargeInfo;
	}
	
	public function getTowerNum()
	{
	    return $this->userModify['tower_num'];
	}
	
	public function addTowerNum($num)
	{
	    $num = intval($num);
	    $this->userModify['tower_num'] += $num;
	    	
	    if ($this->userModify['tower_num'] > UserConf::TOWER_NUM_MAX)
	    {
	        Logger::fatal('tower_num:%d reach max:%d', $this->userModify['tower_num'], UserConf::TOWER_NUM_MAX);
	        $this->userModify['tower_num'] = UserConf::TOWER_NUM_MAX;
	    }
	    	
	    if ($this->userModify['tower_num'] < 0)
	    {
	        Logger::warning('invalid tower_num. sub:%d, now:%d', $num, $this->userModify['tower_num']);
	        return false;
	    }
	    return true;
	}
	
	public function subTowerNum($num)
	{
	    return $this->addTowerNum(-$num);
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
