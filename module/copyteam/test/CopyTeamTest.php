<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CopyTeamTest.php 92251 2014-03-05 06:19:40Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/copyteam/test/CopyTeamTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2014-03-05 06:19:40 +0000 (Wed, 05 Mar 2014) $
 * @version $Revision: 92251 $
 * @brief 
 *  
 **/
class CopyTeamTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $pid;
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        self::$pid = time();
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
    
    }
    
    private function createUser()
    {
        self::$pid++;
        $pid = self::$pid;
        $str = strval($pid);
        $uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
        $ret = UserLogic::createUser($pid, 1, $uname);
        if($ret['ret'] != 'ok')
        {
            echo "create use failed\n";
            exit();
        }
        Logger::trace('create user ret %s.',$ret);
        self::$uid = $ret['uid'];
        EnUser::release();
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
    }
    
    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    
    }
    
    /**
     * This method is called after the last test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function tearDownAfterClass()
    {
    
    }
    
    public function testRfrGuildAtkNum()
    {
        echo "testRfrGuildAtkNum start\n";
        self::createUser();
        RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, self::$uid);
        $uid = self::$uid;
        $copyTeam = new CopyTeam();
        $userTeamInfo = $copyTeam->getCopyTeamInfo(CopyTeamDef::COPYTEAM_TYPE_GUILDTEAM);
        $dailyAtkNum = CopyTeamLogic::getGuildDailyAddAtkNum();
        //新创建的用户  攻击次数是每天增加攻击次数
        $this->assertTrue(($userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] == $dailyAtkNum),
                'the init atk num is '.$userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM].
                '.the daily add atk num in conf is '.$dailyAtkNum.'.not equal');
        echo "init atk num. test done\n";
        $now = Util::getTime();
        $threeDaysAgo = strtotime('-3 day',$now);
        $userTeamInfo = array(CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME => $threeDaysAgo);
        CopyTeamDao::updateCopyTeamInfo(self::$uid, $userTeamInfo);
        CData::$QUERY_CACHE = NULL;
        $userTeamInfo = $copyTeam->getCopyTeamInfo(CopyTeamDef::COPYTEAM_TYPE_GUILDTEAM);
        $maxAtkNum = CopyTeamLogic::getGuildAtkNumLimit();
        $atkNum = 4 * $dailyAtkNum;
        if($atkNum > $maxAtkNum)
        {
            $atkNum = $maxAtkNum;
        }
        $this->assertTrue(($userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] == 4*$dailyAtkNum),
                'atk num error.should be '.$atkNum.'.but now atk num is '.
                $userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM]);
        echo "add atk num daily. test done.\n";
        $userTeamInfo = array(
                CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM => $maxAtkNum,
                CopyTeamDef::COPYTEAM_SQLFIELD_GUILDRFRTIME => strtotime('-2 day',$now)
                );
        CopyTeamDao::updateCopyTeamInfo(self::$uid, $userTeamInfo);
        CData::$QUERY_CACHE = NULL;
        $userTeamInfo = $copyTeam->getCopyTeamInfo(CopyTeamDef::COPYTEAM_TYPE_GUILDTEAM);
        $this->assertTrue(($userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM] == $maxAtkNum),
                'atk num error.should be '.$maxAtkNum.'.but now atk num is '.
                $userTeamInfo[CopyTeamDef::COPYTEAM_SQLFIELD_GUILDATKNUM]);
        echo "add atk num daily.but can not max than maxatknum.test done.\n";
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */