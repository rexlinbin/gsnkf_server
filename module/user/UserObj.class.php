<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: UserObj.class.php 259379 2016-08-30 07:36:12Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/UserObj.class.php $
 * @author $Author: BaoguoMeng $(lanhongyu@babeltime.com)
 * @date $Date: 2016-08-30 07:36:12 +0000 (Tue, 30 Aug 2016) $
 * @version $Revision: 259379 $
 * @brief
 *
 **/





class UserObj extends OtherUserObj
{

	protected $attrFunc = array(

			'execution'		=>		'addExecution',
	        'stamina'       =>    	'addStamina',
			'silver_num'    =>   	'addSilver',
			'gold_num'      =>   	'addGoldFromOther',
			'exp_num'      	=>		'addExp',
	        'soul_num'    	=>    	'addSoul',
			'ban_chat_time'	=> 		'setBanChatTime',
	        'fight_force'   => 		'setFightForce',
			'gold_stat'		=>   	'goldStat',
			'vip' 			=> 		'fromOtherSetVip4Test'

	);


	public function __construct ($uid)
	{
        parent::__construct($uid);
	}



	protected function init()
	{
		parent::init();

		$now = Util::getTime();

		//1. 行动力相关。 购买行动力只可能在UserObj中，所以在OtherUserObj中不需要更新这个
		if (!Util::isSameDay($this->userModify['buy_execution_time']))
		{
			$this->userModify['buy_execution_accum'] = 0;
		}

	}


	public function getUserType()
	{
		return RPCContext::getInstance()->getSession('global.userType');
	}




	public function updateFightForce()
	{
		if($this->user['fight_force'] != $this->userModify['fight_force'])
		{
			Logger::trace('update fight_force. old:%d, new:%d', $this->user['fight_force'], $this->userModify['fight_force']);

			$values = array(
					'fight_force' => $this->userModify['fight_force'],
					);

			$this->user['fight_force'] = $this->userModify['fight_force'];

			if ($this->user['max_fight_force'] < $this->userModify['max_fight_force'])
			{
				Logger::info('update max_fight_force. old:%d, new:%d', $this->user['max_fight_force'], $this->userModify['max_fight_force']);
				$values['max_fight_force'] = $this->userModify['max_fight_force'];
				$this->user['max_fight_force'] = $this->userModify['max_fight_force'];
			}

			UserDao::updateUser($this->userModify['uid'], $values);
			$this->updateSession();
		}
	}


	/**
	 * 根据经验值修复玩家的等级（原则：不修改经验值）
	 */
	public function fixLevel()
	{
		$exp = $this->userModify['exp_num'];
		$level = $this->userModify['level'];

		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];

		if( $exp < $expTable[$level] )
		{
			$lv = $level;
			while($lv > 1 && $exp < $expTable[$lv] )
			{
				$lv--;
			}
			$this->userModify['level'] = $lv;

			Logger::fatal('fix level. exp < level. uid:%d, exp:%d, level:%d, newLevel:%d',
			$this->userModify['uid'], $exp, $level, $this->userModify['level']);
		}

		else if( isset( $expTable[$level + 1] ) && $exp >= $expTable[$level + 1] )
		{
			$lv = $level;
			while( isset( $expTable[$lv+1] ) && $exp >= $expTable[$lv+1] && $lv < UserConf::MAX_LEVEL )
			{
				$lv++;
			}
			$this->userModify['level'] = $lv;
			if( $lv >= UserConf::MAX_LEVEL && $exp >= $expTable[$lv+1] )
			{
				//$this->userModify['exp_num'] = $expTable[$lv];  不要随便修改经验，出现这个情况后和策划讨论方案
				Logger::fatal('need fix exp. old:%d, new:%d', $exp, $expTable[$lv]);
			}

			Logger::fatal('fix level. exp > level. uid:%d, exp:%d, level:%d, newLevel:%d',
			$this->userModify['uid'], $exp, $level, $this->userModify['level']);
		}

		//等级发生改变时，更新一下主角武将的等级
		if($level != $this->userModify['level'])
		{
			$this->getHeroManager()->getMasterHeroObj();
		}
	}



	public function fromOtherSetVip4Test($num)
	{
		$this->userModify['vip'] += $num;
		$this->userModify['va_user']['vip_ics_time'] = Util::getTime();
	}


	/**
	 * 登录：设置登录时间，修改状态。这个只能在UserObj中执行， OtherUserObj中不能执行
	 */
	public function login()
	{
		$this->userModify['last_login_time'] = Util::getTime();
		$this->userModify['status'] = UserDef::STATUS_ONLINE;

		$this->fixLevel(); //FIXME:上线之后应该注释掉。
	}

	public function logoff()
	{
		$now = Util::getTime();
		$this->userModify['last_logoff_time'] = $now;
		$this->userModify['online_accum_time'] += $now - $this->userModify['last_login_time'];
		$this->userModify['status'] = UserDef::STATUS_OFFLINE;

		if(UserConf::ANTI_WALLOW)
		{
			$this->antiWallow();
		}
	}


	public function setVip($vip)
	{
	    if(!isset(btstore_get()->VIP[$vip]))
	    {
	        throw new FakeException('this vip level is not configed.');
	    }
		$this->userModify['vip'] = $vip;
	}

	public function subGold($num, $type, $isSpendGold = TRUE)
	{
		$num = intval($num);
		if($num < 0)
		{
			throw new InterException('fail to subGold, the num %d is not positive', $num);
		}

		if ( !parent::changeGold(-$num, $type))
		{
			return false;
		}

		if ($isSpendGold)
		{
			$this->sumSpendGold($num);
		}

		return true;
	}

	protected function addGoldFromOther($num)
	{
		$num = intval($num);

		return parent::changeGold($num, 0);
	}


	/**
	 * 修改用户信息. 输入的属性数组为变化的值
	 * @param array $arrField
	 */
	public function modifyUserByOther($arrField)
	{
		foreach ($arrField as $attrName=>$num)
		{
			if (isset($this->attrFunc[$attrName]))
			{
				call_user_func(array($this, $this->attrFunc[$attrName]), $num);
			}
			else
			{
				throw new FakeException('can not modify other user attribute %s', $attrName);
			}
		}
	}


	/**
	 * 修改user字段的值，包括对象本身, 但是不修改数据库。
	 * 例如用于充值加金币，先使用batch update，然后调用此函数
	 * @param unknown_type $arrFields
	 */
	public function modifyFields($arrFields)
	{
		foreach ($arrFields as $key=>$value)
		{
			$this->user[$key] += $value;
			$this->userModify[$key] += $value;
		}
	}

	public function setFields($arrFields)
	{
	    foreach ($arrFields as $key=>$value)
	    {
	        $this->user[$key] = $value;
	        $this->userModify[$key] = $value;
	    }
	}




	public function buyExecution ($num)
	{
		$num = intval($num);
		$cur = $this->getCurExecution();
		$max = $this->getExecutionMaxNum();
		if ($cur+$num > $max )
		{
			Logger::warning('ovserflow. cur:%d, num:%d', $cur, $num);
			return 'overflow';
		}

		//检查是否能买
		$vip = $this->getVip();
		$numCanBuy = btstore_get()->VIP[$vip]['execution_gold']['num'];

		if (!Util::isSameDay($this->userModify['buy_execution_time']))
		{
			$this->userModify['buy_execution_accum'] = 0;
		}

		if ($num + $this->userModify['buy_execution_accum'] > $numCanBuy)
		{
			Logger::warning('fail to buy execution, over num of can buy.');
			return 'nobuynum';
		}

		//sub gold
		$price = btstore_get()->VIP[$vip]['execution_gold']['gold'];
		$costGold = $price * $num;
		if ($this->subGold($costGold, StatisticsDef::ST_FUNCKEY_BUY_EXECUTION)==false)
		{
			throw new FakeException('fail to buy execution, the gold is not enough.');
		}

		$this->userModify['buy_execution_accum'] += $num;
		$this->userModify['buy_execution_time'] = Util::getTime();
		$this->userModify['execution'] += $num;
		return 'ok';
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
				$arrField[$key] = $this->userModify[$key];
			}
		}

		if (empty($arrField))
		{
			Logger::debug('no change');
			return;
		}

		foreach( $arrField as $key => $value)
		{
			if( in_array($key, UserDef::$FIELD_CANT_NEGATIVE) && $value < 0)
			{
				throw new InterException('uid:%d, field:%s cant negtive:%d', $this->user['uid'], $key, $value);
			}
		}

		//保存到数据库
		UserDao::updateUser($this->user['uid'], $arrField);
		EnSwitch::getSwitchObj()->save();

		if(isset($arrField['uname']))
		{
		    $this->modifyBattleData();
		    RPCContext::getInstance ()->setSession ( 'global.uname', $this->getUname() );
		}
		if(isset($arrField['level']))
		{
		    $this->modifyBattleData();
		    RPCContext::getInstance ()->setSession ( 'global.level', $this->getLevel() );
		}
		//升级奖励
		if ( $this->userModify['level'] > $this->user['level'] )
		{
		    foreach(UserConf::$LEVEL_REWARD_ITEM as $level => $reward)
		    {
		        if($this->userModify['level'] == $level)
		        {
		            $bag = BagManager::getInstance()->getBag();
		            $bag->addItemsByTemplateID($reward);
		            $bag->update();
		            break;
		        }
		    }
		}
		
		//如果银币超限，会将多余的银币转化为物品
		if ($this->silverTrans2ItemNum > 0) 
		{
			$itemNum = intval($this->silverTrans2ItemNum / UserConf::SILVER_ITEM_VALUE);
			$arrReward = array(array(array(RewardConfType::ITEM_MULTI, UserConf::SILVER_ITEM_TEMPLATE, $itemNum)));
			RewardUtil::reward3DtoCenter($this->user['uid'], $arrReward, RewardSource::SILVER_TRANS_2_ITEM);
			Logger::info('add silver item num:%d, tatal value:%d, curr silver:%d', $itemNum, $this->silverTrans2ItemNum, $this->userModify['silver_num']);
			
			$this->silverTrans2ItemNum = 0;
			
			RPCContext::getInstance ()->sendMsg (array($this->user['uid']), PushInterfaceDef::USER_UPDATE_USER_INFO, array('silver_num' => $this->userModify['silver_num']));
		}
		
		//通知平台
		if ( $this->userModify['level'] > $this->user['level'] &&
		        ($this->userModify['level'] >= UserConf::NOTIFY_START_LEVEL))
		{
		    try
		    {
		        $platfrom = ApiManager::getApi ();
		        $argv = array (
		                'pid' => $this->userModify['pid'],
		                'serverKey' => Util::getServerId (),
		                'uid' => $this->userModify['uid'],
		                'uname' => $this->userModify['uname'],
		                'level' => $this->userModify['level'],
		        );
		        $platfrom->users ( 'roleLvUp', $argv );
		    }
		    catch (Exception $e)
		    {
		        Logger::warning('fail to notify platform level up');
		    }
		}
		//添加信任设备
		if($this->userModify['gold_num'] < $this->user['gold_num'])
		{
		    TrustDevice::doneTask($this->getUid(), TrustDevice::TASK_CONSUME_GOLD);
		}

		$this->user = $this->userModify;
		$this->updateSession();

		//金币统计
		foreach ($this->arrGoldStat as $type=>$num)
		{
			$isSub = true;
			if ($num==0)
			{
				continue;
			}

			Statistics::gold($type, $num, $this->getGold() );
		}
		$this->arrGoldStat = array();
	}

	/**
	 * 记录金币花费
	 * @param int $costGold
	 */
	protected function sumSpendGold($costGold)
	{
		$today = date("Ymd", Util::getTime());
		if(isset($this->userModify['va_user']['spend_gold']))
		{
		    reset($this->userModify['va_user']['spend_gold']);
		}
		if (!isset($this->userModify['va_user']['spend_gold'][$today]))
		{
			$this->userModify['va_user']['spend_gold'][$today] = 0;
		}

		$this->userModify['va_user']['spend_gold'][$today] += $costGold;

		//不能保存太多天的
		while (count($this->userModify['va_user']['spend_gold']) > UserConf::SPEND_GOLD_DATE_NUM)
		{
			$first = key($this->userModify['va_user']['spend_gold']);
			unset($this->userModify['va_user']['spend_gold'][$first]);
		}
	}

	protected function sumSpendExecution($costExecution)
	{
	    $today = date("Ymd", Util::getTime());
	    if(isset($this->userModify['va_user']['spend_execution']))
	    {
	        reset($this->userModify['va_user']['spend_execution']);
	    }
	    if (!isset($this->userModify['va_user']['spend_execution'][$today]))
	    {
	        $this->userModify['va_user']['spend_execution'][$today] = 0;
	    }

	    $this->userModify['va_user']['spend_execution'][$today] += $costExecution;

	    //不能保存太多天的
	    while (count($this->userModify['va_user']['spend_execution']) > UserConf::SPEND_EXECUTION_DATE_NUM)
	    {
	        $first = key($this->userModify['va_user']['spend_execution']);
	        unset($this->userModify['va_user']['spend_execution'][$first]);
	    }
	}

	protected function sumSpendStamina($costStamina)
	{
	    $today = date("Ymd", Util::getTime());
	    if(isset($this->userModify['va_user']['spend_stamina']))
	    {
	        reset($this->userModify['va_user']['spend_stamina']);
	    }
	    if (!isset($this->userModify['va_user']['spend_stamina'][$today]))
	    {
	        $this->userModify['va_user']['spend_stamina'][$today] = 0;
	    }

	    $this->userModify['va_user']['spend_stamina'][$today] += $costStamina;

	    //不能保存太多天的
	    while (count($this->userModify['va_user']['spend_stamina']) > UserConf::SPEND_STAMINA_DATE_NUM)
	    {
	        $first = key($this->userModify['va_user']['spend_stamina']);
	        unset($this->userModify['va_user']['spend_stamina'][$first]);
	    }
	}

	public function levelUp($num)
	{
	    if($num <= 0)
	    {
	        Logger::warning('levelUp %s.',$num);
	        return;
	    }
	    $this->userModify['level'] += $num;
	    $this->userModify['upgrade_time'] = Util::getTime();
	    $this->getHeroManager()->getMasterHeroObj()->setLevel($this->getLevel());
	    EnSwitch::checkSwitchOnLevelUp();
	    UserLogic::getLevelUpReward($num);
	    EnAchieve::updateUserLevel($this->getUid(), $this->getLevel());
	    EnNewServerActivity::updateUserLevel($this->getUid(), $this->getLevel());
	    if(EnSwitch::isSwitchOpen(SwitchDef::ROBTREASURE))
	    {
	        $this->resetStaminaToFull();
	    }
	}
	/**
	 * 恢复耐力到耐力上限值
	 */
	private function resetStaminaToFull()
	{
        $this->addStamina(UserConf::LEVEL_UP_ADD_STAMINA);
        $this->userModify['stamina_time'] = Util::getTime();
	}

	public function antiWallow()
	{
		if( isset( $this->userModify['va_user']['wallow'] ) )
		{
			$wallowInfo = $this->userModify['va_user']['wallow'];
		}
		else
		{
			$wallowInfo = array(
					'accum' => 0,	//当天累计在线时间
			);
		}

		//更新一下当天在线时间
		$loginTime = $this->userModify['last_login_time'];
		if (!Util::isSameDay($loginTime))
		{
			$loginTime = strtotime(strftime("%Y%m%d 00::00:00", Util::getTime()));
			$wallowInfo['accum'] = Util::getTime() - $loginTime;
		}
		else
		{
			$wallowInfo['accum'] += (Util::getTime() - $loginTime);
		}

		$this->userModify['va_user']['wallow'] = $wallowInfo;
	}



	/**
	 * 封号
	 */
	public function ban($time, $msg)
	{
		if ( strlen($msg) > UserConf::BAN_MSG_MAX_LEN )
		{
			$msg = substr($msg, 0, UserConf::BAN_MSG_MAX_LEN);
		}
		$this->userModify['va_user']['ban'] = array('time' => $time, 'msg'=>$msg);
	}

	public function unsetBan()
	{
		unset( $this->userModify['va_user']['ban'] );
	}


	protected function addExtData($key, $value)
	{
		return;
	}

	protected function goldStat($arrStat)
	{
		foreach ($arrStat as $type=>$num)
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
	}

	public function setMute($isMute)
	{
		$this->userModify['mute'] = $isMute;
	}


	public function addUnusedHero($hid, $htid,$level=1)
	{
		if( isset($this->userModify['va_hero']['unused'][$hid]) )
		{
			Logger::fatal('hid:%d already exist', $hid);
			throw new Exception('inter');
		}

		$this->userModify['va_hero']['unused'][$hid][UserDef::UNUSED_HERO_HTID] = $htid;
		if($level > 1)
		{
			$this->userModify['va_hero']['unused'][$hid][UserDef::UNUSED_HERO_LEVEL] = $level;
		}

		Logger::info('add unused hero. hid:%d, htid:%d, level:%s', $hid, $htid, $level );
	}



	/**
	 * 从未使用过的武将中取出一个来初始化
	 * @param int $hid
	 */
	public function initHero($hid, $setHeroAttr = array() )
	{
		$uid = $this->userModify['uid'];

		//1. 检查这个用户有没有这个武将
		$heroInfo = $this->getUnusedHero($hid);
		if( empty($heroInfo) )
		{
			throw new FakeException('has no hid:%d', $hid);
		}
		$htid = $heroInfo['htid'];
		$level = $heroInfo['level'];

		//2. 批量写数据库
		$heroAttr = HeroLogic::getInitData($uid, $hid, $htid, $level);
		foreach( $setHeroAttr as $key => $value )
		{
			if( ($key == 'uid' && $value != $uid ) ||
					($key == 'hid' && $value != $hid ) ||
					($key == 'htid' && $value != $htid ) )
			{
				throw new InterException('invalid param:%s, uid:%d, hid:%d, htid:%d', $setHeroAttr, $uid, $hid, $htid);
			}
			if( isset($heroAttr[$key]) )
			{
				$heroAttr[$key] = $value;
			}
			else
			{
				throw new InterException('invalid field:%s', $key);
			}
		}

		$batchData = new BatchData();

		$heroData = $batchData->newData();
		$heroData->insertInto(HeroDao::TBL_HERO)->values($heroAttr)->query();

		unset( $this->userModify['va_hero']['unused'][$hid] );
		$info = array(
				'va_hero' => $this->userModify['va_hero']
		);

		$userData = $batchData->newData();
		$userData->update(UserDao::tblUser)->set($info)->where( array('uid', '=', $uid) )->query();
		$ret = $batchData->query();

		Logger::info('init hero. hid:%d, htid:%d', $hid, $htid);

		//3. 处理结果
		$this->user['va_hero'] = $this->userModify['va_hero'];

		$this->updateSession();
		return $heroAttr;
	}


	public function delUnusedHero( $hid )
	{
		if( ! isset( $this->userModify['va_hero']['unused'][$hid] ))
		{
			throw new FakeException('has no hid:%d', $hid);
		}

		Logger::info('del unused hero. hid:%d, htid:%d', $hid, $this->getUnusedHeroHtid($hid) );

		unset( $this->userModify['va_hero']['unused'][$hid] );

	}



	public function setVaConfig($vaConfig)
	{
		if (count($vaConfig) > UserConf::VA_CONFIG_SIZE)
		{
			Logger::warning('va config %d is more than max size', count($vaConfig));
			throw new Exception('fake');
		}

		foreach ($vaConfig as $config)
		{
			if (is_array($config))
			{
				Logger::warning('va config type err');
				throw new Exception('fake');
			}
			else if (is_string($config))
			{
				if (strlen($config) > 100)
				{
					Logger::warning('string too long for arr config');
					throw new Exception('fake');
				}
			}
		}

		$this->userModify['va_user']['va_config'] = $vaConfig;
	}

	public function setArrConfig($key, $value)
	{
		if (!isset($this->userModify['va_user']['arr_config']))
		{
			$this->userModify['va_user']['arr_config'] = array();
		}

		if (is_array($value))
		{
			if (count($value) > UserConf::ARR_CONFIG_SIZE)
			{
				Logger::warning('arr config is more than max size');
				throw new Exception('fake');
			}

			foreach ($value as $tmp)
			{
				if (is_array($tmp))
				{
					Logger::warning('arr config type err');
					throw new Exception('fake');
				}
				else if (is_string($tmp))
				{
					if (strlen($tmp) > 100)
					{
						Logger::warning('string too long for arr config');
						throw new Exception('fake');
					}
				}
			}
		}
		else if(is_string($value))
		{
			if (strlen($value) > 100)
			{
				Logger::warning('string too long for arr config');
				throw new Exception('fake');
			}
		}

		$this->userModify['va_user']['arr_config'][$key] = $value;
		if (count($this->userModify['va_user']['arr_config']) > UserConf::ARR_CONFIG_SIZE)
		{
			Logger::warning('arr config too large');
			throw new Exception('fake');
		}
	}

	public function getVaConfig()
	{
		if (isset($this->userModify['va_user']['va_config']))
		{
			return $this->userModify['va_user']['va_config'];
		}
		else
		{
			return array();
		}
	}

	public function getArrConfig()
	{
		if (isset($this->userModify['va_user']['arr_config']))
		{
			return $this->userModify['va_user']['arr_config'];
		}
		else
		{
			return array();
		}
	}


	public function openHeroGrid()
	{
	    $curLimit    =    $this->getHeroLimit();
	    $this->userModify['va_user'][VA_USER::HERO_LIMIT] = intval($curLimit)+HeroDef::PRE_HERO_LIMIT_ADD;
	}

	public function setHeroGrid($num)
	{
	    if($num < $this->getHeroLimit())
	    {
	        throw new FakeException('set hero limit num %s is little than pre num %s.',$num,$this->getHeroLimit());
	    }
	    $this->userModify['va_user'][VA_USER::HERO_LIMIT] = $num;
	}

	public function getFlopNum()
	{
	    if(isset($this->userModify['va_user'][VA_USER::FLOP_NUM]))
	    {
	        return $this->userModify['va_user'][VA_USER::FLOP_NUM];
	    }
	    return UserConf::MAX_FLOP_NUM;
	}

	public function addFlopNum($num)
	{
	    if(!isset($this->userModify['va_user'][VA_USER::FLOP_NUM]))
	    {
	        return;
	    }
	    $this->userModify['va_user'][VA_USER::FLOP_NUM] += $num;
	    if($this->userModify['va_user'][VA_USER::FLOP_NUM] >=  UserConf::MAX_FLOP_NUM)
	    {
	        unset($this->userModify['va_user'][VA_USER::FLOP_NUM]);
	    }
	}

	public function fixFlopNum()
	{
	    if(!isset($this->userModify['va_user'][VA_USER::FLOP_NUM]))
	    {
	        $this->userModify['va_user'][VA_USER::FLOP_NUM] = 0;
	    }
	}

	public function setDressInfo($dressTmplId,$pos)
	{
	    if(!isset($this->userModify['va_user'][VA_USER::DRESSINFO]))
	    {
	        $this->userModify['va_user'][VA_USER::DRESSINFO] = array();
	    }
	    if(isset($this->userModify['va_user'][VA_USER::DRESSINFO][$pos])
	            && ($this->userModify['va_user'][VA_USER::DRESSINFO][$pos] == $dressTmplId))
	    {
	        return FALSE;
	    }
	    $this->userModify['va_user'][VA_USER::DRESSINFO][$pos] = $dressTmplId;
	    return TRUE;
	}

	public function setUname($uname)
	{
	    $this->userModify['uname'] = $uname;
	}

	/**
	 * 设置性别
	 * @author jinyang
	 */
	public function setUtid($utid)
	{
	    if ($utid != 1 && $utid != 2)
	        throw new FakeException('utid must be 1 or 2');
	    $this->userModify['utid'] = $utid;
	}

	/**
	 * 因代充，已经扣除的金币
	 */
	public function getSubNumByBadOrder()
	{
		if ( empty( $this->userModify['va_user']['badOrder']['sub'] ) )
		{
			return 0;
		}
		return $this->userModify['va_user']['badOrder']['sub'];
	}

	public function addSubNumByBadOrder($addNum)
	{
		if ( empty( $this->userModify['va_user']['badOrder']['sub'] ) )
		{
			$this->userModify['va_user']['badOrder']['sub'] = 0;
		}

		$this->userModify['va_user']['badOrder']['sub'] += $addNum;

		Logger::debug('addSubNumByBadOrder. uid:%d, add:%d,  now:%d',
				$this->getUid(), $addNum, $this->userModify['va_user']['badOrder']['sub']);
	}


	public function learnMasterSkill($skillType,$skillId,$learnSource)
	{
	    $skillInfo = $this->getMasterSkill();
	    Logger::info('learnMasterSkill skilltype %s skillid %d source %d,pre info is %s',
	            $skillType,$skillId,$learnSource,$skillInfo);
	    if(empty($skillId) || empty($skillType) || empty($learnSource))
	    {
	        Logger::fatal('invalid parameters');
	        return;
	    }
	    $skillInfo[$skillType] = array(
	            $skillId,
	            $learnSource,
	            );
	    $this->userModify['va_user']['master_skill'] = $skillInfo;
	    $this->modifyBattleData();
	}

	public function getMasterSkill()
	{
	    $skillInfo = parent::getMasterSkill();
	    $this->userModify['va_user']['master_skill'] = $skillInfo;
	    return $skillInfo;
	}

	public function removeSkill($skillType)
	{
	    if(!isset($this->userModify['va_user']['master_skill'][$skillType]))
	    {
	        return FALSE;
	    }
	    unset($this->userModify['va_user']['master_skill'][$skillType]);
	    $this->modifyBattleData();
	    return TRUE;
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
