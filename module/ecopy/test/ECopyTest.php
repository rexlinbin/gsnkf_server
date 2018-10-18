<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ECopyTest.php 74854 2013-11-14 09:27:41Z TiantianZhang $
 * 
 **************************************************************************/
require_once '/home/pirate/rpcfw/module/ecopy/test/MyBattleUtil.php';
 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/ecopy/test/ECopyTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-14 09:27:41 +0000 (Thu, 14 Nov 2013) $
 * @version $Revision: 74854 $
 * @brief 
 *  
 **/
class ECopyTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $arrECopy;
    /**
     * This method is called before the first test of this test class is run.
     *
     * @since Method available since Release 3.4.0
     */
    public static function setUpBeforeClass()
    {
        $pid = time();
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
        RPCContext::getInstance ()->setSession ( UserDef::SESSION_KEY_UID, self::$uid );
        //开启精英副本的功能节点
        $switchObj = EnSwitch::getSwitchObj();
        $switchObj->addNewSwitch(SwitchDef::ELITECOPY);
        $switchObj->addNewSwitch(SwitchDef::SQUAD);
        $switchObj->save();
        EnUser::getUserObj()->addSilver(20000000);
        EnUser::getUserObj()->addSoul(2000000);
        EnUser::getUserObj()->addExecution(20000000);
        EnUser::getUserObj()->update();
        //提高此玩家的战斗力
        MyBattleUtil::upFightForce();
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->resetSession();
    }
    
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     */
    protected function setUp()
    {
    
    }
    /**
     * 1.开启显示状态：1.通关前置普通副本   或者   2.前置精英副本可攻击
     * 2.开启攻击状态：1.通关前置普通副本   并且   2.前置精英副本已通关
     */
    public function testEcopyOpen()
    {
        $conf  = btstore_get()->ELITECOPY;
        foreach($conf as $copyId => $copyConf)
        {
            self::$arrECopy[] = $copyId;
        }
        $firstECopy = CopyConf::$FIRST_ELITE_COPY_ID;
        $needNCopy = $conf[$firstECopy]['pre_open_copy'];
        MyBattleUtil::doNCopyBattle($needNCopy);
        $check = self::checkCopyStatus();
        Logger::trace('passNcopy %s check result %s.',$needNCopy,$check);
        $this->assertTrue((empty($check)),'checkCopyStatus1 no empty,please check log');
        MyBattleUtil::doECopyBattle($firstECopy);
        $check = self::checkCopyStatus();
        Logger::trace('attack ecopy %s check result %s.',$firstECopy,$check);
        $this->assertTrue((empty($check)),'checkCopyStatus2 no empty,please check log');
        
        //取出精英副本数组中的中间哪一个副本
        $ecopyId = self::$arrECopy[intval(count(self::$arrECopy)/4)];    
        $needNCopy = $conf[$ecopyId]['pre_open_copy'];    
        MyBattleUtil::doNCopyBattle($needNCopy);
        $check = self::checkCopyStatus();
        Logger::trace('passNcopy %s check result %s.',$needNCopy,$check);
        $this->assertTrue((empty($check)),'checkCopyStatus3 no empty,please check log');
    }
    /**
     * 验证攻击次数、奖励等
     */
    public function testAtkSecondEcopy()
    {
        $firstECopy = CopyConf::$FIRST_ELITE_COPY_ID;
        $user = EnUser::getUserObj();
        $preSilver = $user->getSilver();
        $preExp = $user->getAllExp();
        $preSoul = $user->getSoul();
        $preCanAtkNum = MyECopy::getInstance()->getCanDefeatNum();
        $ret = MyBattleUtil::doECopyBattle($firstECopy);
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        CData::$QUERY_CACHE = NULL;
        $user = EnUser::getUserObj();
        $afterSilver = $user->getSilver();
        $afterExp = $user->getAllExp();
        $afterSoul = $user->getSoul();
        $afterCanAtkNum = MyECopy::getInstance()->getCanDefeatNum();
        $this->assertTrue(($preCanAtkNum-1==$afterCanAtkNum),'defeat ecopy,sub one can defeat num');
        $this->assertTrue(($preSilver+$ret['reward']['silver'] == $afterSilver),'reward silver fail');
        $this->assertTrue(($preSoul+$ret['reward']['soul'] == $afterSoul),'reward soul fail');
        $this->assertTrue(($preExp+$ret['reward']['exp'] == $afterExp),'reward exp fail');
    }
    /**
     * 随时都可以调用此函数   确认精英副本开启状态是否正确
     */
    private function checkCopyStatus()
    {
        $conf  = btstore_get()->ELITECOPY;
        $errorCopyStatus = array();
        foreach($conf as $copyId => $copyConf)
        {
            $preNcopy = $copyConf['pre_open_copy'];
            $preEcopy = $copyConf['pre_copy'];
            $ncopyPassed = FALSE;
            if(MyNCopy::getInstance()->isCopyPassed($preNcopy))
            {
                $ncopyPassed = TRUE;
            }
            $preEcopyStatus = MyECopy::getInstance()->getStatusofCopy($preEcopy);
            if($ncopyPassed && ($preEcopyStatus == EliteCopyStatus::PASS))
            {
                if(MyECopy::getInstance()->getStatusofCopy($copyId) < EliteCopyStatus::CANATTACK)
                {
                    $errorCopyStatus[$copyId] = array(
                            'error'=>MyECopy::getInstance()->getStatusofCopy($copyId),
                            'right'=>EliteCopyStatus::CANATTACK,
                            );
                }
            }
            else if($ncopyPassed || ($preEcopyStatus == EliteCopyStatus::CANATTACK))
            {
                if(MyECopy::getInstance()->getStatusofCopy($copyId) < EliteCopyStatus::CANSHOW)
                {
                    $errorCopyStatus[$copyId] = array(
                            'error'=>MyECopy::getInstance()->getStatusofCopy($copyId),
                            'right'=>EliteCopyStatus::CANSHOW,
                    );
                }
            }
        }
        return $errorCopyStatus;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */