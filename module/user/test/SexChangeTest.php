<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id$
 *
 **************************************************************************/

 /**
 * @file $HeadURL$
 * @author $Author$(jinyang@babeltime.com)
 * @date $Date$
 * @version $Revision$
 * @brief
 *
 **/
class OneRechargeTest extends PHPUnit_Framework_TestCase
{
    private static $uid = 20027;
    private static $pid = 20004;
    private static $tname = 'action3015';

    public static function setUpBeforeClass()
    {
        /*
        self::$pid = IdGenerator::nextId('uid');
        $utid = 1;
        $uname = 'action'.rand(0, 9999);
        $ret = UserLogic::createUser(self::$pid, $utid, $uname);

        if ($ret['ret'] != 'ok')
        {
            echo "create user failed \n";
            exit();
        }

        self::$uid = $ret['uid'];
        */

    }

    protected function setUp()
    {
        RPCContext::getInstance()->setSession('global.uid', self::$uid);
        $userObj = EnUser::getUserObj(self::$uid);
        echo "================================\n";
        echo "user original sex is: ";
        echo $userObj->getUtid();
        echo "\n";
        echo "user original master htid is: ";
        echo $userObj->getHeroManager()->getMasterHeroObj()->getHtid();
        echo "\n";
        echo "user origianl masterskill is:\n";
   //     $athena = new Athena();
  //      $athena->changeSkill(AthenaDef::TYPE_NORMAL, 181051);
        var_dump($userObj->getMasterSkill());
        echo "--------------------------------\n";
    }

    protected function tearDown()
    {
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->unsetSession('global.uid');

    }
    public static function tearDownAfterClass()
    {
    }


    public function test_changeSex()
    {
        $user =  new User();
        $ret = $user->changeSex();
        var_dump($ret);
        $userObj = EnUser::getUserObj(self::$uid);

        echo "user current sex is: ";
        echo $userObj->getUtid();
        echo "\n";
        echo "user current master htid is: ";
        echo $userObj->getHeroManager()->getMasterHeroObj()->getHtid();
        echo "\n";
        echo "user current masterskill is:\n";
        var_dump($userObj->getMasterSkill());
        echo "================================\n";
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */