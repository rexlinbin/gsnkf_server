<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildRob.test.php 145187 2014-12-10 10:11:39Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guildrob/test/GuildRob.test.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2014-12-10 10:11:39 +0000 (Wed, 10 Dec 2014) $
 * @version $Revision: 145187 $
 * @brief 
 *  
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/test/UserClient.php";

class MyGuildRobRobot extends UserClient
{
	protected $err = false;
	protected $errTryNum = 1;

	function __construct($server, $port, $pid)
	{
		parent::__construct($server, $port, $pid);
		printf('pid:%d login ok\n', $pid);
		$this->setClass('guildrob');
	}
}
 
class GuildBarnTest extends PHPUnit_Framework_TestCase
{
	private $uid = 0;
	protected function setUp()
	{
		parent::setUp ();
		$this->uid = 21000;
		RPCContext::getInstance()->setSession('global.uid', $this->uid);
	}

	protected function tearDown()
	{
		parent::tearDown ();
		RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->unsetSession('global.uid');
	}

	protected static function getPrivateMethod($className, $methodName)
	{
		$class = new ReflectionClass($className);
		$method = $class->getMethod($methodName);
		$method->setAccessible(true);
		return $method;
	}

	public function test_getGuildRobAreaInfo()
	{
		$ret = GuildRobLogic::getGuildRobAreaInfo($this->uid, 1, '');
		var_dump($ret);
		$ret = GuildRobLogic::getGuildRobAreaInfo($this->uid, 1, '0');
		var_dump($ret);
		$ret = GuildRobLogic::getGuildRobAreaInfo($this->uid, 1, '默');
		var_dump($ret);
	}
	
	public function test_canRob()
	{
		$attackGuildId = 1801;
		$defendGuildId = 1802;
		
		$defendGuildObj = GuildObj::getInstance($defendGuildId);
		$attackGuildObj = GuildObj::getInstance($attackGuildId);
		
		// 验证被抢军团粮草不足的情况
		$grainUpperLimit = $defendGuildObj->getGrainLimit();
		$magic = intval($grainUpperLimit * GuildRobConf::CAN_ROB_MIN_PERCENT / UNIT_BASE);
		$defendGuildObj->subGrainNum($defendGuildObj->getGrainNum());
		$defendGuildObj->addGrainNum($magic - 1);
		$defendGuildObj->update();
		
		$canRobGrain = 0;
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFEND_LOW_GRAIN, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		// 验证抢粮军团攻击次数超过限制的情况
		$defendGuildObj->subGrainNum($defendGuildObj->getGrainNum());
		$defendGuildObj->addGrainNum($magic + 1);
		$defendGuildObj->update();
		
		$attackLimit = intval(btstore_get()->GUILD_ROB['attack_limit']);
		$attackGuildObj->setAttackNum($attackLimit);
		$attackGuildObj->update();
		
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACK_TOO_MUCH, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		// 验证抢粮军团缺少战书，无法抢粮
		$attackGuildObj->setAttackNum(0);
		$attackGuildObj->setFightBook(0);
		$attackGuildObj->update();
		
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_LACK_FIGHT_BOOK, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		$attackGuildObj->setFightBook(1);
		$attackGuildObj->update();
		
		// 验证抢粮军团正在抢夺另一个军团，无法抢夺当前军团
		$robObj = GuildRobObj::getInstance($attackGuildId);
		$robObj->start(100, 1703, 100, 100);
		$robObj->update();
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACKER_ATTACKING, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		$robObj->resetAllField();
		$robObj->update();
		
		// 验证被抢军团正在抢夺另一个军团，不能被当前军团抢夺
		$robObj = GuildRobObj::getInstance($defendGuildId);
		$robObj->start(100, 1703, 100, 100);
		$robObj->update();
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFENDER_ATTACKING, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		$robObj->resetAllField();
		$robObj->update();
		
		// 验证抢粮军团正在被其他军团抢粮，无法抢夺当前军团
		$robObj = GuildRobObj::getInstance(1703);
		$robObj->start(100, $attackGuildId, 100, 100);
		$robObj->update();
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_ATTACKER_DEFENDING, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		$robObj->resetAllField();
		$robObj->update();
		
		// 验证被抢军团正在被另一个军团抢夺，不能被当前军团抢劫
		$robObj = GuildRobObj::getInstance(1703);
		$robObj->start(100, $defendGuildId, 100, 100);
		$robObj->update();
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_DEFENDER_DEFENDING, $ret);
		$this->assertEquals(0, $canRobGrain);
		
		$robObj->resetAllField();
		$robObj->update();
		
		// 一切正常，返回ok，和可以被抢的粮草数
		$ret = GuildRobUtil::canRob($attackGuildId, $defendGuildId, $canRobGrain);
		$this->assertEquals(GuildRobCreateRet::GUILD_ROB_CREATE_RET_OK, $ret);
		$expectGrain = $defendGuildObj->getGrainNum() - intval($grainUpperLimit * GuildRobConf::CAN_ROB_MIN_PERCENT / UNIT_BASE);
		$this->assertEquals($expectGrain, $canRobGrain);
	}
	
	public function test_checkEffectTime()
	{
		$config = btstore_get()->GUILD_ROB['effect_time'];
		$arrNotConfigWeekday = array();
		$arrConfigWeekday = array();
		for ($i = 1; $i <= 7; ++$i)
		{
			if (!isset($config[$i])) 
			{
				$arrNotConfigWeekday[] = $i;
			}
			else 
			{
				$arrConfigWeekday[] = $i;
			}
		}
		
		// 如果存在没有配置的日期，则验证这种情况
		if (!empty($arrNotConfigWeekday)) 
		{
			printf("have not config weekday, need assert\n");
			var_dump($arrNotConfigWeekday);
			foreach ($arrNotConfigWeekday as $checkWeekDay)
			{
				$currTime = Util::getTime();
				$currWeekday = intval(date('w', $currTime));
				$currWeekday = $currWeekday ? $currWeekday : 7;
				$checkTime = $currTime + 86400 * (7 - $currWeekday + $checkWeekDay);
				$ret = GuildRobUtil::checkEffectTime($checkTime);
				$this->assertEquals(FALSE, $ret);
			}
		}
		
		//循环验证配置的时间范围
		if (!empty($arrConfigWeekday)) 
		{
			printf("have config weekday, need assert\n");
			var_dump($arrConfigWeekday);

			foreach ($arrConfigWeekday as $checkWeekDay)
			{
				$start = $config[$checkWeekDay][0];
				$end = $config[$checkWeekDay][1];
				
				if (strlen($start) == 5) 
				{
					$start = '0' . $start;
				}
				
				if (strlen($end) == 5)
				{
					$end = '0' . $end;
				}
				
				//var_dump($start);
				//var_dump($end);
				
				$startTime = intval(substr($start, 0, 2)) * 3600 + intval(substr($start, 2, 2)) * 60 + intval(substr($start, 4, 2));
				$endTime = intval(substr($end, 0, 2)) * 3600 + intval(substr($end, 2, 2)) * 60 + intval(substr($end, 4, 2));
				
				//var_dump($startTime);
				//var_dump($endTime);
				
				$currTime = Util::getTime();
				$currWeekday = intval(date('w', $currTime));
				$currWeekday = $currWeekday ? $currWeekday : 7;
				$checkTime = $currTime + 86400 * (7 - $currWeekday + $checkWeekDay);
				
				$checkStr = strftime("%Y%m%d", $checkTime);
				//var_dump($checkStr);
				$checkTime = strtotime($checkStr . "000000");
				//var_dump($checkTime);
				
				$this->assertEquals(FALSE, GuildRobUtil::checkEffectTime($checkTime + $startTime - 1));
				$this->assertEquals(TRUE, GuildRobUtil::checkEffectTime($checkTime + $startTime));
				$this->assertEquals(TRUE, GuildRobUtil::checkEffectTime($checkTime + $startTime + 1));
				$this->assertEquals(TRUE, GuildRobUtil::checkEffectTime($checkTime + $endTime));
				$this->assertEquals(TRUE, GuildRobUtil::checkEffectTime($checkTime + $endTime - 1));
				$this->assertEquals(FALSE, GuildRobUtil::checkEffectTime($checkTime + $endTime + 1));
			}
		}
	}
	
	public function test_checkGuildMember()
	{
		$arrField  = array(GuildDef::USER_ID);
		$arrCond = array(array(GuildDef::GUILD_ID, '=', 0));
		
		$data = new CData();
		$arrRet = $data->select($arrField)
						->from(GuildDef::TABLE_GUILD_MEMBER)
						->where(array(GuildDef::GUILD_ID, '=', 0))
						->query();
		if (!empty($arrRet))
		{
			printf("assert not in any guild,user num:%d\n", count($arrRet));
			
			$count = 0;
			foreach ($arrRet as $aRet)
			{
				if (++$count > 10) // 只验证10个
				{
					break;
				}
				$aUid = $aRet[GuildDef::USER_ID];
				$this->assertEquals(FALSE, GuildRobUtil::checkGuildMember($aUid));
			}
		}
		
		$arrField  = array(GuildDef::USER_ID, GuildDef::GUILD_ID, GuildDef::MEMBER_TYPE);
		
		$data = new CData();
		$arrRet = $data->select($arrField)
						->from(GuildDef::TABLE_GUILD_MEMBER)
						->where(array(GuildDef::GUILD_ID, '!=', 0))
						->query();
		
		if (!empty($arrRet))
		{
			printf("assert in guild,user num:%d\n", count($arrRet));
				
			$count = 0;
			foreach ($arrRet as $aRet)
			{
				if (++$count > 10) // 只验证10个
				{
					break;
				}
				$aUid = $aRet[GuildDef::USER_ID];
				$aGuildId = $aRet[GuildDef::GUILD_ID];
				$aMemberType = $aRet[GuildDef::MEMBER_TYPE];
				$this->assertEquals(array($aGuildId, $aMemberType), GuildRobUtil::checkGuildMember($aUid));
			}
		}
	}
	
	public function test_checkPrivilege()
	{
		$operationTypeCreate = GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_CREATE;
		$operationTypeEnter = GuildRobOperationType::GUILD_ROB_OPERATION_TYPE_ENTER;
		$memberType = GuildMemberType::PRESIDENT;
		$this->assertEquals(TRUE, GuildRobUtil::checkPrivilege($memberType, $operationTypeCreate));
		$this->assertEquals(TRUE, GuildRobUtil::checkPrivilege($memberType, $operationTypeEnter));
		
		$memberType = GuildMemberType::VICE_PRESIDENT;
		$this->assertEquals(TRUE, GuildRobUtil::checkPrivilege($memberType, $operationTypeCreate));
		$this->assertEquals(TRUE, GuildRobUtil::checkPrivilege($memberType, $operationTypeEnter));
		
		$memberType = GuildMemberType::NONE;
		$this->assertEquals(FALSE, GuildRobUtil::checkPrivilege($memberType, $operationTypeCreate));
		$this->assertEquals(TRUE, GuildRobUtil::checkPrivilege($memberType, $operationTypeEnter));
	}
	
	public function test_checkRobTime()
	{
		$robId = 1801;
		$startTime = Util::getTime();
		$robObj = GuildRobObj::getInstance($robId);
		$robObj->start($startTime, 1802, 100, 100);
		$robObj->update();
		$readyTime = intval(btstore_get()->GUILD_ROB['ready_time']);
		$duration = intval(btstore_get()->GUILD_ROB['battle_time']);
		
		$this->assertEquals(TRUE, GuildRobUtil::checkRobTime($robId));
		
		$startTime = Util::getTime() - $readyTime - $duration - 1;
		$robObj = GuildRobObj::getInstance($robId);
		$robObj->start($startTime, 1802, 100, 100);
		$robObj->update();
		$this->assertEquals(FALSE, GuildRobUtil::checkRobTime($robId));
		
		$startTime = Util::getTime() - $readyTime;
		$robObj = GuildRobObj::getInstance($robId);
		$robObj->start($startTime, 1802, 100, 100);
		$robObj->update();
		$this->assertEquals(TRUE, GuildRobUtil::checkRobTime($robId));
	}
	
	/*public function test_Console()
	{
		$guildObj = GuildObj::getInstance(363);
		$guildObj->setAttackNum(0);
		$guildObj->update();
	}
	
	public function test_checkBasic()
	{
		
	}*/
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */