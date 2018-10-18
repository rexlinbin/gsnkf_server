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
class TopupRewardTest extends PHPUnit_Framework_TestCase
{
    private $uid;
    private $user;
    private $pid;
    private $utid;
    private $uname;

    protected function setUp()
    {
        parent::setUp();
        $this->pid = 40000 + rand(0,9999);
        $this->utid = 1;
        $this->uname = 'he' . $this->pid;
        UserLogic::createUser($this->pid, $this->utid, $this->uname);
        $users = UserLogic::getUsers($this->pid);
        $this->uid = $users[0]['uid'];
        RPCContext::getInstance()->setSession('global.uid', $this->uid);
        EnUser::release($this->uid);
    }

    protected function tearDown()
    {
        parent::tearDown();
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->unsetSession('global.uid');
    }

    /**
     *
     */
    public function testRewardUser()
    {

        Logger::debug('==========%s==========', __METHOD__);
        //1 准备测试环境(活动已开 充钱 活动时间推一天)
        if(!EnActivity::isOpen(ActivityName::TOPUPREWARD))
        {
            Logger::debug('not open');
            return;
        }
        $orderId = 'AAAA_00_' . strftime("%Y%m%d%H%M%S") . rand(10000, 99999);
        $user = new User();
        $user->addGold4BBpay($this->uid, $orderId, 1000);

        $actConf = ActivityConfDao::getCurConfByName(ActivityName::TOPUPREWARD, ActivityDef::$ARR_CONF_FIELD);
        $actConf['version'] = Util::getTime();
        $actConf['start_time'] += 86400;
        ActivityConfDao::insertOrUpdate($actConf);
        ActivityConfLogic::updateMem();

        //2 调用被测试的接口
        TopupRewardLogic::rewardUserOnLogin($this->uid);

        //3 检查结果
        $arrField = array(
            RewardDef::SQL_UID ,
            RewardDef::SQL_SOURCE ,
            RewardDef::SQL_SEND_TIME,
            RewardDef::SQL_RECV_TIME,
            RewardDef::SQL_DELETE_TIME,
            RewardDef::SQL_VA_REWARD
        );
        $ret = RewardDao::getRewardByUidTime($this->uid, RewardSource::TOPUP_REWARD, Util::getTime()-100, $arrField);
        var_dump($ret);
        $this->assertEmpty($ret);
    }


}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */