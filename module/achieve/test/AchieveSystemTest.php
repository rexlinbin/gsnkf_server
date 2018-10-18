<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class AchieveSystemTest extends PHPUnit_Framework_TestCase
{
    protected static $uid = 0;

    protected static $pid = 0;

    protected static $uname = 0;

    public static function setUpBeforeClass()
    {
        self::$pid = IdGenerator::nextId('uid');
        self::$uname = strval(self::$pid);
        $ret = UserLogic::createUser(self::$pid, 1, self::$uname);
        if($ret['ret'] != 'ok')
        {
            echo "create user failed\n";
            exit();
        }
        self::$uid = $ret['uid'];
        echo "uid"." ". self::$uid;
    }

    protected function setUp()
    {
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
    }

    protected function tearDown()
    {
    }

    public function testGetInfo()
    {
        $obj = AchieveObj::getObj(self::$uid);
        $conf = $obj->getConf();
        $info = $obj->getInfos();
        $this->assertEquals(count($info), count($conf["ids"]));
        foreach($conf["ids"] as $aid => $aidConf)
        {
            $this->assertContains($aid, array_keys($info));
        }
    }

    public function testPassNCopy()
    {
        $obj = AchieveObj::getObj(self::$uid);

        $this->assertEquals($obj->checkObtain(101003), 'unfinished');
        EnAchieve::updatePassNCopy(self::$uid, 4);
        $status = $obj->checkObtain(101003);
        echo "status " . $status;
        $obj->commit();
        $this->assertEquals($obj->obtainReward(101003), 'ok');
        $obj->commit();
        $this->assertEquals($obj->checkObtain(101003), 'obtained');
    }

    public function testNCopyScore()
    {
        $obj = AchieveObj::getObj(self::$uid);

        $this->assertEquals($obj->checkObtain(102002), 'unfinished');
        EnAchieve::updateNCopyScore(self::$uid, 100);
        $status = $obj->checkObtain(102002);
        echo "status " . $status;
        $obj->commit();
        $this->assertEquals($obj->obtainReward(102002), 'ok');
        $obj->commit();
        $this->assertEquals($obj->checkObtain(102002), 'obtained');
    }

    public function testFightSoul()
    {
        $obj = AchieveObj::getObj(self::$uid);

        $this->assertEquals($obj->checkObtain(203001), 'unfinished');
        EnAchieve::updateFightSoul(self::$uid, 4);
        $status = $obj->checkObtain(203001);
        echo "status " . $status;
        $obj->commit();
        $this->assertEquals($obj->obtainReward(203001), 'ok');
        $obj->commit();
        $this->assertEquals($obj->checkObtain(203001), 'obtained');
    }

    public function testPetTypes()
    {
        $obj = AchieveObj::getObj(self::$uid);

        $this->assertEquals($obj->checkObtain(212002), 'unfinished');
        EnAchieve::updatePetTypes(self::$uid, 1);
        EnAchieve::updatePetTypes(self::$uid, 2);
        $status = $obj->checkObtain(212002);
        echo "status " . $status;
        $obj->commit();
        $this->assertEquals($obj->obtainReward(212002), 'ok');
        $obj->commit();
        $this->assertEquals($obj->checkObtain(212002), 'obtained');
    }


}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */