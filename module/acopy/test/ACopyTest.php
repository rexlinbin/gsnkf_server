<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ACopyTest.php 76789 2013-11-26 03:11:43Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/acopy/test/ACopyTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-11-26 03:11:43 +0000 (Tue, 26 Nov 2013) $
 * @version $Revision: 76789 $
 * @brief 
 *  
 **/
class ACopyTest extends PHPUnit_Framework_TestCase
{
    private static $uid;
    private static $goldTreeId;
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
        //开启活动副本的功能节点
        $switchObj = EnSwitch::getSwitchObj();
        $switchObj->addNewSwitch(SwitchDef::ACTCOPY);
        $switchObj->save();
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
     * 开启条件限制
     */
    public function testGoldTreeAtk1()
    {
        //找出摇钱树活动副本的开启条件
        $acopyCnf = btstore_get()->ACTIVITYCOPY;
        $goldTreeId = 0;
        $openCondition = array();
        foreach($acopyCnf as $acopyId => $conf)
        {
            if($conf['type'] == ACT_COPY_TYPE::GOLDTREE)
            {
                $goldTreeId = $acopyId;
                if(!empty($conf['pre_pass_ncopy']))
                {
                    $openCondition['ncopy'] = $conf['pre_pass_ncopy'];
                }
                if(!empty($conf['need_level']))
                {
                    $openCondition['level'] = $conf['need_level'];
                }
                break;
            }
        }
        if(empty($goldTreeId))
        {
            throw new FakeException('no gold tree in conf table.');
        }
        self::$goldTreeId = $goldTreeId;
        if(empty($openCondition))
        {
            $copyList = MyACopy::getInstance()->getActivityCopyList();
            $this->assertTrue((isset($copyList[$goldTreeId])),'no open condition.gold tree should open');
            echo self::$goldTreeId.' has no open condition.has been open'."\n";
        }
        else
        {
            $copyList = MyACopy::getInstance()->getActivityCopyList();
            $this->assertTrue((!isset($copyList[self::$goldTreeId])),'has open condition.should have no goldtree actcopy');
            echo self::$goldTreeId.' has open condition.has not been open'."\n";
            $console = new Console();
            if(isset($openCondition['ncopy']))
            {
                $console->passNCopies($openCondition['ncopy']);
            }
            if(isset($openCondition['level']))
            {
                $console->level($openCondition['level']);
            }
            $copyList = MyACopy::getInstance()->getActivityCopyList();
            $this->assertTrue((isset($copyList[$goldTreeId])),'open condition has been satified.gold tree should open');
            echo self::$goldTreeId.' has open condition.condition be satified.has been open'."\n";
        }
        
    }
    
    public function decodeBattle($data)
    {
        $data = str_replace(array("\n", "\t", " "), "", $data);
        $data = base64_decode($data);
        $data = gzuncompress($data);
        $data = chr(0x11) . $data;
        $arrData = amf_decode($data, 7);
        return $arrData;
    }
    /**
     * 攻击次数扣除
     * 攻击摇钱树的奖励和伤害
     */
    public function testGoldTreeAtk2()
    {
        $user = EnUser::getUserObj();
        $level = $user->getLevel();
        $preSilver = $user->getSilver();
        $preExec = $user->getCurExecution();
        $acopyObj = MyACopy::getInstance()->getActivityCopyObj(self::$goldTreeId);
        if(empty($acopyObj))
        {
            throw new FakeException('no gold tree with id %s.',self::$goldTreeId);
        }
        $preAtkNum = $acopyObj->getCanDefeatNum();
        $acopy = new ACopy();
        $ret = $acopy->atkGoldTree(self::$goldTreeId);
        MyACopy::release();
        EnUser::release();
        CData::$QUERY_CACHE = NULL;
        RPCContext::getInstance()->unsetSession(UserDef::SESSION_KEY_USER);
        $fightRet = $this->decodeBattle($ret['fightRet']);
        $costHp = $fightRet['team2']['totalHpCost'];
        $getSilver = intval(min(100000,500*max($level,50)) + $costHp * 0.05);
        $this->assertTrue(($getSilver == $ret['reward']['silver']),'got silver should be equal');
        $this->assertTrue(($ret['hurt'] == $costHp),'costHP should be equal to hurt ret');
        $baseId = btstore_get()->ACTIVITYCOPY[self::$goldTreeId]['base_id'];
        $lvName = 'simple';
        $needExec = intval(btstore_get()->BASE[$baseId][$lvName][$lvName.'_need_power']);
        $userObj = EnUser::getUserObj();
        $afterSilver = $userObj->getSilver();
        $afterExec = $userObj->getCurExecution();
        $acopyObj = MyACopy::getInstance()->getActivityCopyObj(self::$goldTreeId);
        $afterAtkNum = $acopyObj->getCanDefeatNum();
        $this->assertTrue(($preSilver+$getSilver == $afterSilver),'get silver '.$getSilver." pre is ".$preSilver.' after should be '.$afterSilver);
        $this->assertTrue(($preAtkNum-1 == $afterAtkNum),'atkgold tree need 1 atknum');
        $this->assertTrue(($preExec-$needExec == $afterExec),'atkGoldtree need execution '.$needExec.' pre '.$preExec.' after '.$afterExec);
        echo 'after atkgoldtree '.self::$goldTreeId." get silver ".$getSilver." ,hurt ".$costHp.",sub atknum and exec successfully\n";
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */