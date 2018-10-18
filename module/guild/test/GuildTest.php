<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: GuildTest.php 228946 2016-02-23 06:55:12Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/guild/test/GuildTest.php $
 * @author $Author: MingTian $(tianming@babeltime.com)
 * @date $Date: 2016-02-23 06:55:12 +0000 (Tue, 23 Feb 2016) $
 * @version $Revision: 228946 $
 * @brief 
 *  
 **/
class GuildTest extends PHPUnit_Framework_TestCase
{
	protected static $uid1 = 0;
	protected static $uid2 = 0;
	protected static $guildId1 = 0;
	protected static $guildId2 = 0;
	
	public static function setUpBeforeClass()
	{
		self::$uid1 = self::newUser();
		self::$uid2 = self::newUser();
	}
	
	protected function setUp()
	{
	}

	protected function tearDown()
	{
	}
	
	public static function newUser()
	{	
		$uid = self::createUser();
		self::openSwitch($uid);
		return $uid;
	}

	public static function createUser()
	{
		$pid = IdGenerator::nextId('uid');
		$uname = strval($pid);
		$ret = UserLogic::createUser($pid, 1, $uname);
		return $ret['uid'];
	}
	
	public static function openSwitch($uid)
	{
		EnSwitch::getSwitchObj($uid)->addNewSwitch(SwitchDef::GUILD);
		EnSwitch::getSwitchObj($uid)->save();
	}
	
	public static function setSession($uid)
	{
	    EnUser::release();
	    GuildObj::release(0);
	    GuildMemberObj::release(0);
	    RPCContext::getInstance()->resetSession();
		RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
		$memberInfo = GuildDao::selectMember($uid);
		if (!empty($memberInfo))
		{
			$guildId = $memberInfo[GuildDef::GUILD_ID];
			RPCContext::getInstance()->setSession(GuildDef::SESSION_GUILD_ID, $guildId);
		}
		else 
		{
			RPCContext::getInstance()->unsetSession(GuildDef::SESSION_GUILD_ID);
		}
	}
	
	public function test_createGuild()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$conf = btstore_get()->GUILD;
		$level = $conf[GuildDef::GUILD_USER_LEVEL];
		$subGold = $conf[GuildDef::GUILD_GOLD_CREATE];
		$subSilver = $conf[GuildDef::GUILD_SILVER_CREATE];
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		
		self::setSession(self::$uid1);
		$user = EnUser::getUserObj(self::$uid1);
		$user->addExp($expTable[$level]);
		$user->addSilver($subSilver);
		$user->update();
		$silverBefore = $user->getSilver();
		$guild = new Guild();
		$name = 'sg' . rand(0000, 9999);
		$ret = $guild->createGuild($name);
		$this->assertTrue($ret['ret'] == 'ok');
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $subSilver, $silverAfter);
		$ret = GuildDao::selectMember(self::$uid1);
		$this->assertEquals(GuildMemberType::PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
		self::$guildId1 = $ret[GuildDef::GUILD_ID];
		
		self::setSession(self::$uid2);
		$user = EnUser::getUserObj(self::$uid2);
		$user->addExp($expTable[$level]);
		$user->addGold($subGold, StatisticsDef::ST_FUNCKEY_GUILD_CREATE_COST);
		$user->update();
		$goldBefore = $user->getGold();
		$guild = new Guild();
		$name = 'sg' . rand(0000, 9999);
		$ret = $guild->createGuild($name, 1);
		$this->assertTrue($ret['ret'] == 'ok');
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore - $subGold, $goldAfter);
		$ret = GuildDao::selectMember(self::$uid2);
		$this->assertEquals(GuildMemberType::PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
		self::$guildId2 = $ret[GuildDef::GUILD_ID];
	}
	
	public function test_applyGuild()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
	}
	
	public function test_cancelApply()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = $guild->cancelApply(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
	}
	
	public function test_agreeApply()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(self::$guildId1, $ret[GuildDef::GUILD_ID]);
		$this->assertEquals(GuildMemberType::NONE, $ret[GuildDef::MEMBER_TYPE]);
	}
	
	public function test_refuseApply()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->refuseApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
	}
	
	public function test_refuseAllApply()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->refuseAllApply();
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
	}
	
	public function test_quitGuild()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
		
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->quitGuild();
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(0, $ret[GuildDef::GUILD_ID]);
		$rejoinCd = Util::getTime() + btstore_get()->GUILD[GuildDef::GUILD_REJOIN_CD];
		$this->assertEquals($rejoinCd, $ret[GuildDef::REJOIN_CD]);
	}
	
	public function test_kickMember()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
		
		$ret = $guild->kickMember($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(0, $ret[GuildDef::GUILD_ID]);
		$rejoinCd = Util::getTime() + btstore_get()->GUILD[GuildDef::GUILD_REJOIN_CD];
		$this->assertEquals($rejoinCd, $ret[GuildDef::REJOIN_CD]);
	}
	
	public function test_modifySlogan()
	{
		Logger::debug('======%s======', __METHOD__);
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$slogan = 'hello sanguo';
		$ret = $guild->modifySlogan($slogan);
		$this->assertEquals('ok', $ret['ret']);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$this->assertEquals($slogan, $ret[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::SLOGAN]);
		
		$slogan = 999999;
		$ret = $guild->modifySlogan($slogan);
		$this->assertEquals('ok', $ret['ret']);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$this->assertEquals($slogan, $ret[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::SLOGAN]);
	}
	
	public function test_modifyPost()
	{
		Logger::debug('======%s======', __METHOD__);
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$post = 'hello sanguo';
		$ret = $guild->modifyPost($post);
		$this->assertEquals('ok', $ret['ret']);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$this->assertEquals($post, $ret[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::POST]);
	
		$post = 999999;
		$ret = $guild->modifyPost($post);
		$this->assertEquals('ok', $ret['ret']);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$this->assertEquals($post, $ret[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::POST]);
	}
	
	public function test_modifyPasswd()
	{
		Logger::debug('======%s======', __METHOD__);
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$old = '123456';
		$new = '456789';
		$ret = $guild->modifyPasswd($old, $new);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$this->assertEquals(md5($new), $ret[GuildDef::VA_INFO][GuildDef::ALL][GuildDef::PASSWD]);
	}
	
	public function test_setVicePresident()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
	
		$ret = $guild->setVicePresident($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::VICE_PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
	}
	
	public function test_unsetVicePresident()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId1);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(!empty($ret));
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId1);
		$this->assertTrue(empty($ret));
	
		$ret = $guild->setVicePresident($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::VICE_PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
	
		$ret = $guild->unsetVicePresident($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::NONE, $ret[GuildDef::MEMBER_TYPE]);
	}
	
	public function test_transPresident()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId2);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId2);
		$this->assertTrue(!empty($ret));
	
		self::setSession(self::$uid2);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId2);
		$this->assertTrue(empty($ret));
	
		$passwd = '123456';
		$ret = $guild->transPresident($uid, $passwd);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
		$ret = GuildDao::selectMember(self::$uid2);
		$this->assertEquals(GuildMemberType::NONE, $ret[GuildDef::MEMBER_TYPE]);
	
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->transPresident(self::$uid2, $passwd);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember(self::$uid2);
		$this->assertEquals(GuildMemberType::PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::NONE, $ret[GuildDef::MEMBER_TYPE]);
	}
	
	public function test_dismiss()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$conf = btstore_get()->GUILD;
		$level = $conf[GuildDef::GUILD_USER_LEVEL];
		$subSilver = $conf[GuildDef::GUILD_SILVER_CREATE];
		$expTable = btstore_get()->EXP_TBL[UserConf::EXP_TABLE_ID];
		
		$uid = self::newUser();
		self::setSession($uid);
		$user = EnUser::getUserObj($uid);
		$user->addExp($expTable[$level]);
		$user->addSilver($subSilver);
		$user->update();
		$guild = new Guild();
		$name = 'sg' . rand(0000, 9999);
		$ret = $guild->createGuild($name);
		$this->assertTrue($ret['ret'] == 'ok');
		$guildId = $ret['info'][GuildDef::GUILD_ID];
		$ret = $guild->dismiss("123456");
		$this->assertTrue($ret == 'ok');
		$ret = GuildDao::selectGuild($guildId);
		$this->assertTrue($ret == false);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(0, $ret[GuildDef::GUILD_ID]);
		$rejoinCd = Util::getTime() + btstore_get()->GUILD[GuildDef::GUILD_REJOIN_CD];
		$this->assertEquals($rejoinCd, $ret[GuildDef::REJOIN_CD]);
	}
	
	public function test_impeach()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		self::setSession($uid);
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId2);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId2);
		$this->assertTrue(!empty($ret));
	
		self::setSession(self::$uid2);
		$guild = new Guild();
		$ret = $guild->agreeApply($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectApply($uid, self::$guildId2);
		$this->assertTrue(empty($ret));
	
		$ret = $guild->setVicePresident($uid);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::VICE_PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
	
		$lastLoginTime = Util::getTime() - GuildConf::MAX_IMPEACHMENT_TIME * 2;
		UserDao::updateUser(self::$uid2, array('last_login_time'=>$lastLoginTime));
	
		self::setSession($uid);
		$impeachGold = btstore_get()->GUILD[GuildDef::GUILD_IMPEACH_GOLD];
		$user = EnUser::getUserObj($uid);
		$user->addGold($impeachGold, StatisticsDef::ST_FUNCKEY_GUILD_IMPEACH_COST);
		$goldBefore = $user->getGold();
		$guild = new Guild();
		$ret = $guild->impeach();
		$this->assertEquals('ok', $ret);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore - $impeachGold, $goldAfter);
		$ret = GuildDao::selectMember($uid);
		$this->assertEquals(GuildMemberType::PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
		$ret = GuildDao::selectMember(self::$uid2);
		$this->assertEquals(GuildMemberType::NONE, $ret[GuildDef::MEMBER_TYPE]);
	}
	
	public function test_contribute()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$type = 1;
		$conf = btstore_get()->GUILD;
		$contriArr = $conf[GuildDef::GUILD_CONTRI_ARR];
	
		$silver = $contriArr[$type]['silver'];
		$gold = $contriArr[$type]['gold'];
		$exp = $contriArr[$type]['exp'];
		$point = $contriArr[$type]['point'];
		$vip = $contriArr[$type]['vip'];
	
		self::setSession(self::$uid1);
		$user = EnUser::getUserObj(self::$uid1);
		$user->addSilver($silver);
		$user->addGold($gold, StatisticsDef::ST_FUNCKEY_GUILD_CONTRI_COST);
		$user->setVip($vip);
		$user->update();
	
		$silverBefore = $user->getSilver();
		$goldBefore = $user->getGold();
		$ret = GuildDao::selectGuild(self::$guildId1);
		$expBefore = $ret[GuildDef::CURR_EXP];
		$guildNumBefore = $ret[GuildDef::CONTRI_NUM];
		$ret = GuildDao::selectMember(self::$uid1);
		$pointBefore = $ret[GuildDef::CONTRI_POINT];
		$totalBefore = $ret[GuildDef::CONTRI_TOTAL];
		$weekBefore = $ret[GuildDef::CONTRI_WEEK];
		$NumBefore = $ret[GuildDef::CONTRI_NUM];
	
		$guild = new Guild();
		$ret = $guild->contribute($type);
		$this->assertEquals('ok', $ret);
	
		$silverAfter = $user->getSilver();
		$goldAfter = $user->getGold();
		$ret = GuildDao::selectGuild(self::$guildId1);
		$expAfter = $ret[GuildDef::CURR_EXP];
		$guildNumAfter = $ret[GuildDef::CONTRI_NUM];
		$ret = GuildDao::selectMember(self::$uid1);
		$pointAfter = $ret[GuildDef::CONTRI_POINT];
		$totalAfter = $ret[GuildDef::CONTRI_TOTAL];
		$weekAfter = $ret[GuildDef::CONTRI_WEEK];
		$NumAfter = $ret[GuildDef::CONTRI_NUM];
	
		$this->assertEquals($silverBefore - $silver, $silverAfter);
		$this->assertEquals($goldBefore - $gold, $goldAfter);
		$this->assertEquals($expBefore + $exp, $expAfter);
		$this->assertEquals($guildNumBefore + 1, $guildNumAfter);
		$this->assertEquals($pointBefore + $point, $pointAfter);
		$this->assertEquals($totalBefore + $point, $totalAfter);
		$this->assertEquals($weekBefore + $point, $weekAfter);
		$this->assertEquals($NumBefore + 1, $NumAfter);
	}
	
	public function test_upgradeGuild()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$expId = btstore_get()->GUILD[GuildDef::GUILD_EXP_ID];
		$needExp1 = btstore_get()->EXP_TBL[$expId][2] - btstore_get()->EXP_TBL[$expId][1];
		$arrField[GuildDef::CURR_EXP] = $needExp1;
		GuildDao::updateGuild(self::$guildId1, $arrField);
		self::setSession(self::$uid1);
		$guild = new Guild();
		$type = 1;
		$ret = $guild->upgradeGuild($type);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$currExp = $ret[GuildDef::CURR_EXP];
		$this->assertEquals(0, $currExp);
		$guildLevel = $ret[GuildDef::GUILD_LEVEL];
		$this->assertEquals(2, $guildLevel);
		$level =  $ret[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
		$this->assertEquals(2, $level);
		$allExp = $ret[GuildDef::VA_INFO][$type][GuildDef::ALLEXP];
		$this->assertEquals($needExp1, $allExp);
		
		$expId = btstore_get()->GUILD_TEMPLE[GuildDef::GUILD_EXP_ID];
		$needExp = btstore_get()->EXP_TBL[$expId][1];
		$arrField[GuildDef::CURR_EXP] = $needExp;
		GuildDao::updateGuild(self::$guildId1, $arrField);
		self::setSession(self::$uid1);
		$guild = new Guild(); 
		$type = 2;
		$ret = $guild->upgradeGuild($type);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$currExp = $ret[GuildDef::CURR_EXP];
		$this->assertEquals(0, $currExp);
		$guildLevel = $ret[GuildDef::GUILD_LEVEL];
		$this->assertEquals(2, $guildLevel);
		$level =  $ret[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
		$this->assertEquals(1, $level);
		$allExp = $ret[GuildDef::VA_INFO][$type][GuildDef::ALLEXP];
		$this->assertEquals($needExp, $allExp);
		
		$expId = btstore_get()->GUILD_BARN[GuildDef::GUILD_EXP_ID];
		$needExp = btstore_get()->EXP_TBL[$expId][1];
		$arrField[GuildDef::CURR_EXP] = $needExp;
		GuildDao::updateGuild(self::$guildId1, $arrField);
		self::setSession(self::$uid1);
		$guild = new Guild();
		$type = 6;
		$ret = $guild->upgradeGuild($type);
		$this->assertEquals('ok', $ret);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$currExp = $ret[GuildDef::CURR_EXP];
		$this->assertEquals(0, $currExp);
		$guildLevel = $ret[GuildDef::GUILD_LEVEL];
		$this->assertEquals(2, $guildLevel);
		$level =  $ret[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
		$this->assertEquals(1, $level);
		$allExp = $ret[GuildDef::VA_INFO][$type][GuildDef::ALLEXP];
		$this->assertEquals($needExp, $allExp);
	}
	
	public function test_reward()
	{
		Logger::debug('======%s======', __METHOD__);
	
		self::setSession(self::$uid1);
		$user = EnUser::getUserObj();
		$silverBefore = $user->getSilver();
	
		$guild = new Guild();
		$ret = GuildDao::selectMember(self::$uid1);
		$pointBefore = $ret[GuildDef::CONTRI_POINT];
		$guildInfo = $guild->getGuildInfo();
		$level = $guildInfo[GuildDef::VA_INFO][2][GuildDef::LEVEL];
		$conf = btstore_get()->GUILD_TEMPLE;
		$silver = floor($conf[GuildDef::GUILD_SILVER_BASE] + $conf[GuildDef::GUILD_SILVER_INCRE] * $level / 100);
		$point = $conf[GuildDef::GUILD_REWARD_COST];
	
		$ret = $guild->reward();
		$this->assertEquals('ok', $ret['ret']);
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore + $silver, $silverAfter);
		$ret = GuildDao::selectMember(self::$uid1);
		$pointAfter = $ret[GuildDef::CONTRI_POINT];
		$this->assertEquals($pointBefore - $point, $pointAfter);
	}
	
	public function test_lottery()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$ret = GuildDao::selectMember(self::$uid1);
		$meritBefore = $ret[GuildDef::MERIT_NUM];
		$lotteryBefore = $ret[GuildDef::LOTTERY_NUM];
		$cost = btstore_get()->GUILD_LOTTERY[GuildDef::GUILD_LOTTERY_COST];
		$arrCond = array(array(GuildDef::USER_ID, '=', self::$uid1));
		$arrField = array(GuildDef::MERIT_NUM => $meritBefore + $cost);
		GuildDao::updateMember($arrCond, $arrField);
		
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->lottery();
		$this->assertTrue(!empty($ret));
		$ret = GuildDao::selectMember(self::$uid1);
		$meritAfter = $ret[GuildDef::MERIT_NUM];
		$lotteryAfter = $ret[GuildDef::LOTTERY_NUM];
		$this->assertEquals($meritBefore, $meritAfter);
		$this->assertEquals($lotteryBefore + 1, $lotteryAfter);
	}
	
	public function test_harvest()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$fieldId = 1;
		$conf = btstore_get()->GUILD_BARN;
		$needLevel = $conf[GuildDef::GUILD_BARN_OPEN];
		$needSilver = $conf[GuildDef::GUILD_HARVEST_SILVER];
		$addExp = $conf[GuildDef::GUILD_HARVEST_EXP];
		list($memberMerit, $guildGrain) = $conf[GuildDef::GUILD_HARVEST_GRAIN][$fieldId][0]->toArray();
		$ret = GuildDao::selectGuild(self::$guildId1);
		foreach ($needLevel as $key => $value)
		{
			$ret[GuildDef::VA_INFO][$key][GuildDef::LEVEL] = $value;
		}
		GuildDao::updateGuild(self::$guildId1, $ret);
		$grainBefore = $ret[GuildDef::GRAIN_NUM];
		$expBefore = 0;
		
		self::setSession(self::$uid1);
		$user = EnUser::getUserObj(self::$uid1);
		$user->addSilver($needSilver);
		$user->update();
		$silverBefore = $user->getSilver();
		$ret = GuildDao::selectMember(self::$uid1);
		$meritBefore = $ret[GuildDef::MERIT_NUM];
		
		$guild = new Guild();
		$ret = $guild->harvest($fieldId);
		$this->assertEquals($grainBefore + $guildGrain, $ret);
		$silverAfter = $user->getSilver();
		$this->assertEquals($silverBefore - $needSilver, $silverAfter);
		$ret = GuildDao::selectMember(self::$uid1);
		$meritAfter = $ret[GuildDef::MERIT_NUM];
		$harvestNum = $ret[GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0];
		$ret = GuildDao::selectGuild(self::$guildId1);
		$grainAfter = $ret[GuildDef::GRAIN_NUM];
		$expAfter = $ret[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::FIELDS][$fieldId][1];
		$this->assertEquals($grainBefore + $guildGrain, $grainAfter);
		$this->assertEquals($meritBefore + $memberMerit, $meritAfter);
		$this->assertEquals($expBefore + $addExp, $expAfter);
		$this->assertEquals($harvestNum, $conf[GuildDef::GUILD_HARVEST_NUM][$fieldId] - 1);
	}
	
	public function test_refreshOwn()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$type = GuildDef::BARN;
		$ret = GuildDao::selectGuild(self::$guildId1);
		$barnLevel = $ret[GuildDef::VA_INFO][$type][GuildDef::LEVEL];
		$conf = btstore_get()->GUILD_BARN;
		$fieldNum = $conf[GuildDef::GUILD_FIELD_NUM];
		$ret = GuildDao::selectMember(self::$uid1);
		$refreshNumBefore = $ret[GuildDef::REFRESH_NUM];
		$fields = $ret[GuildDef::VA_MEMBER][GuildDef::FIELDS];
		$fieldCount = 0;
		foreach ($fields as $fieldId => $fieldInfo)
		{
			if ($fieldNum[$fieldId] <= $barnLevel)
			{
				$fieldCount++;
			}
		}
		$cost = $conf[GuildDef::GUILD_REFRESH_BASE][$fieldCount] + $conf[GuildDef::GUILD_REFRESH_ADD][$fieldCount] * $refreshNumBefore;
		$user = EnUser::getUserObj(self::$uid1);
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_REFRESH_OWN);
		$user->update();
		$goldBefore = $user->getGold();
		
		$guild = new Guild();
		$ret = $guild->refreshOwn();
		$this->assertEquals($ret, 'ok');
		$ret = GuildDao::selectMember(self::$uid1);
		$refreshNumAfter = $ret[GuildDef::REFRESH_NUM];
		$this->assertEquals($refreshNumBefore + 1, $refreshNumAfter);
		foreach ($ret[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
		{
			if ($fieldNum[$fieldId] <= $barnLevel)
			{
				$this->assertEquals($fieldInfo[0], $fields[$fieldId][0] + 1);
			}
		}
		$user = EnUser::getUserObj(self::$uid1);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore - $cost, $goldAfter);
	}
	
	public function test_refreshAll()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$type = RefreshAllType::GOLD;
		$vip = 8;
		list($can, $cost) = btstore_get()->VIP[$vip]['refreshFieldCost'];
		$user = EnUser::getUserObj(self::$uid1);
		$user->addGold($cost, StatisticsDef::ST_FUNCKEY_GUILD_REFRESH_ALL);
		$user->setVip($vip);
		$user->update();
		$goldBefore = $user->getGold();
		$ret = GuildDao::selectGuild(self::$guildId1);
		$refreshNumBefore = $ret[GuildDef::REFRESH_NUM];
		$refreshNumByExpBefore = $ret[GuildDef::REFRESH_NUM_BYGUILDEXP];
		$barnLevel = $ret[GuildDef::VA_INFO][GuildDef::BARN][GuildDef::LEVEL];
		$conf = btstore_get()->GUILD_BARN;
		$fieldNum = $conf[GuildDef::GUILD_FIELD_NUM];
		$maxHarvestNum = $conf[GuildDef::MAX_HARVEST_NUM];
		list($refreshNumLimit, $addFieldNum) = $conf[GuildDef::GUILD_REFRESH_ALL_BYGOLD];
		$memberList = GuildDao::getMemberList(self::$guildId1, array(GuildDef::USER_ID, GuildDef::VA_MEMBER));
		$arrMemberBefore = Util::arrayIndex($memberList, GuildDef::USER_ID);
		
		$guild = new Guild();
		$ret = $guild->refreshAll($type);
		$this->assertEquals($ret, 'ok');
		sleep(1);
		$user = EnUser::getUserObj(self::$uid1);
		$goldAfter = $user->getGold();
		$this->assertEquals($goldBefore - $cost, $goldAfter);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$refreshNumAfter = $ret[GuildDef::REFRESH_NUM];
		$refreshNumByExpAfter = $ret[GuildDef::REFRESH_NUM_BYGUILDEXP];
		$this->assertEquals($refreshNumBefore + 1, $refreshNumAfter);
		$this->assertEquals($refreshNumByExpBefore, $refreshNumByExpAfter);
		$memberList = GuildDao::getMemberList(self::$guildId1, array(GuildDef::USER_ID, GuildDef::VA_MEMBER));
		$arrMemberAfter = Util::arrayIndex($memberList, GuildDef::USER_ID);
		foreach ($arrMemberAfter as $uid => $memberInfo)
		{
			$count = 0;
			foreach ($memberInfo[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
			{
				if ($fieldNum[$fieldId] <= $barnLevel)
				{
					$count++;
					$num = min($arrMemberBefore[$uid][GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] + $addFieldNum, $maxHarvestNum);
					$this->assertEquals($fieldInfo[0], $num);
				}
			}
		}
		
		$type = RefreshAllType::GUILDEXP;
		list($refreshNumLimit, $addFieldNum) = $conf[GuildDef::GUILD_REFRESH_ALL_BYGUILDEXP];
		$needGuildExp = $conf[GuildDef::GUILD_RFRALL_BYEXP_COST][$refreshNumByExpBefore] * $count;
		$ret = GuildDao::selectGuild(self::$guildId1);
		$refreshNumBefore = $ret[GuildDef::REFRESH_NUM];
		$refreshNumByExpBefore = $ret[GuildDef::REFRESH_NUM_BYGUILDEXP];
		$expBefore = $ret[GuildDef::CURR_EXP] + $needGuildExp;
		GuildDao::updateGuild(self::$guildId1, array(GuildDef::CURR_EXP => $expBefore));
		$memberList = GuildDao::getMemberList(self::$guildId1, array(GuildDef::USER_ID, GuildDef::VA_MEMBER));
		$arrMemberBefore = Util::arrayIndex($memberList, GuildDef::USER_ID);
		
		$guild = new Guild();
		$ret = $guild->refreshAll($type);
		$this->assertEquals($ret, 'ok');
		sleep(1);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$expAfter = $ret[GuildDef::CURR_EXP];
		$refreshNumAfter = $ret[GuildDef::REFRESH_NUM];
		$refreshNumByExpAfter = $ret[GuildDef::REFRESH_NUM_BYGUILDEXP];
		$this->assertEquals($expBefore - $needGuildExp, $expAfter);
		$this->assertEquals($refreshNumBefore, $refreshNumAfter);
		$this->assertEquals($refreshNumByExpBefore + 1, $refreshNumByExpAfter);
		$memberList = GuildDao::getMemberList(self::$guildId1, array(GuildDef::USER_ID, GuildDef::VA_MEMBER));
		$arrMemberAfter = Util::arrayIndex($memberList, GuildDef::USER_ID);
		foreach ($arrMemberAfter as $uid => $memberInfo)
		{
			$count = 0;
			foreach ($memberInfo[GuildDef::VA_MEMBER][GuildDef::FIELDS] as $fieldId => $fieldInfo)
			{
				if ($fieldNum[$fieldId] <= $barnLevel)
				{
					$count++;
					$num = min($arrMemberBefore[$uid][GuildDef::VA_MEMBER][GuildDef::FIELDS][$fieldId][0] + $addFieldNum, $maxHarvestNum);
					$this->assertEquals($fieldInfo[0], $num);
				}
			}
		}
	}
	
	public function test_share()
	{
		Logger::debug('======%s======', __METHOD__);
		
		$ret = GuildDao::selectGuild(self::$guildId1);
		$grainNumBefore = $ret[GuildDef::GRAIN_NUM] + 2000;
		GuildDao::updateGuild(self::$guildId1, array(GuildDef::GRAIN_NUM => $grainNumBefore));
		
		$guild = new Guild();
		$ret = $guild->share();
		$this->assertTrue($ret > 0);
		$ret = GuildDao::selectGuild(self::$guildId1);
		$grainNumAfter = $ret[GuildDef::GRAIN_NUM];
		$this->assertTrue($grainNumAfter < 3);
	}
	
	public function test_buyFightBook()
	{
		Logger::debug('======%s======', __METHOD__);
		
		try 
		{
			$guild = new Guild();
			$ret = $guild->buyFightBook();
		}
		catch ( Exception $e )
		{						
			$this->assertEquals('fake',  $e->getMessage());
		}
		
		$needExp = btstore_get()->GUILD_BARN[GuildDef::GUILD_CHALLENGE_COST];
		$ret = GuildDao::selectGuild(self::$guildId1);
		$expBefore = $ret[GuildDef::CURR_EXP] + $needExp;
		$fightBookBefore = 0;
		GuildDao::updateGuild(self::$guildId1, array(GuildDef::FIGHT_BOOK => $fightBookBefore, GuildDef::CURR_EXP => $expBefore));
		
		$guild = new Guild();
		$ret = $guild->buyFightBook();
		$this->assertEquals($ret, 'ok');
		$ret = GuildDao::selectGuild(self::$guildId1);
		$expAfter = $ret[GuildDef::CURR_EXP];
		$fightBookAfter = $ret[GuildDef::FIGHT_BOOK];
		$this->assertEquals($expBefore - $needExp, $expAfter);
		$this->assertEquals($fightBookBefore + 1, $fightBookAfter);
	}
	
	public function test_getGuildApplyList()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId);
		$this->assertEquals('ok', $ret);
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->getGuildApplyList(0, 10);
		$this->assertEquals($uid, $ret['data'][$uid]['uid']);
	}
	/*
	public function test_getGuildList()
	{
		Logger::debug('======%s======', __METHOD__);
	
		$uid = self::newUser();
		$guild = new Guild();
		$ret = $guild->applyGuild(self::$guildId);
		$this->assertEquals('ok', $ret);
		
		$ret = $guild->getGuildList(0, 10);
		$this->assertTrue($ret['data'][0]['guild_id'] == self::$guildId);
		$ret = $guild->getGuildList(10, 10);
	}
	
	public function test_getGuildListByName()
	{
		Logger::debug('======%s======', __METHOD__);
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->getGuildListByName(0, 10, 'sg');
		$this->assertTrue(count($ret['data']) >= 1);
	}
	
	public function test_getGuildInfo()
	{
		Logger::debug('======%s======', __METHOD__);
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->getGuildInfo();
		$this->assertEquals($ret['guild_id'], self::$guildId);
	}
	
	public function test_getMemberInfo()
	{
		Logger::debug('======%s======', __METHOD__);
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->getMemberInfo();
		$this->assertEquals(GuildMemberType::PRESIDENT, $ret[GuildDef::MEMBER_TYPE]);
	}
	
	
	
	
	
	
	
	
	
	
	
	public function test_getMemberList()
	{
		Logger::debug('======%s======', __METHOD__);
	
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->getMemberList(0, 10);
		$this->assertTrue(!empty($ret['count']));
	}
	
	
	
	public function test_getDynamicList()
	{
		Logger::debug('======%s======', __METHOD__);
		self::setSession(self::$uid1);
		$guild = new Guild();
		$ret = $guild->getDynamicList();
	}

    public function test_fightEachOther()
    {
        Logger::debug('======%s======', __METHOD__);

        $uid = self::newUser();
        $guild = new Guild();

        $memberInfo = GuildDao::selectMember($uid);
        self::$guildId = $memberInfo[GuildDef::GUILD_ID];

        $ret = $guild->applyGuild(self::$guildId);
        $this->assertEquals('ok', $ret);
        $ret = GuildDao::selectApply($uid, self::$guildId);
        $this->assertTrue(!empty($ret));

        self::setSession(self::$uid1);
        $guild = new Guild();
        $ret = $guild->agreeApply($uid);
        $this->assertEquals('ok', $ret);
        $ret = GuildDao::selectApply($uid, self::$guildId);
        $this->assertTrue(empty($ret));

        $ret = $guild->fightEachOther(uid);
        var_dump($ret);
    }
	/*
	
	
	
	public function test_getUserApplyList()
	{
		Logger::debug('======%s======', __METHOD__);
		
		RPCContext::getInstance()->setSession('global.uid', self::$uid2);
		$guild = new Guild();
		$ret = $guild->getUserApplyList();
		$this->assertEquals(self::$guildId, $ret[0]);
	}
	
	public function test_getRandGoodsList()
	{
		Logger::debug('======%s======', __METHOD__);
		
		RPCContext::getInstance()->setSession('global.uid', self::$uid1);
		$guild = new Guild();
		$ret = $guild->getRandGoodsList();
		$this->assertEquals(4, count($ret));
	}
	
	public function test_buy()
	{
		Logger::debug('======%s======', __METHOD__);
		
		RPCContext::getInstance()->setSession('global.uid', self::$uid1);
		
		$goodsConf = btstore_get()->GUILD_GOODS[4];	
		$needPoint = $goodsConf[MallDef::MALL_EXCHANGE_REQ][MallDef::MALL_EXCHANGE_EXTRA];

		$guild = new Guild();
		$guild->contribute(3);
		$memberInfo = GuildDao::selectMember(self::$uid1);
		$pointBefore = $memberInfo[GuildDef::CONTRI_POINT];
		
		$ret = $guild->buy(4, 1);
		$this->assertEquals('ok', $ret);
		$memberInfo = GuildDao::selectMember(self::$uid1);
		$pointAfter = $memberInfo[GuildDef::CONTRI_POINT];
		$this->assertEquals($pointBefore - $needPoint, $pointAfter);
	}
	*/
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */