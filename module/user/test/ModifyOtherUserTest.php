<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ModifyOtherUserTest.php 67060 2013-09-29 07:47:16Z TiantianZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/user/test/ModifyOtherUserTest.php $
 * @author $Author: TiantianZhang $(zhangtiantian@babeltime.com)
 * @date $Date: 2013-09-29 07:47:16 +0000 (Sun, 29 Sep 2013) $
 * @version $Revision: 67060 $
 * @brief 
 *  
 **/
/**
 * 添加函数RPCContext::getInstance()->delAllCallBack();
 * @author dell
 *
 */
class ModifyOtherUserTest extends BaseScript
{
    private static $uid;
    private static $otherUid;
    
    /* (non-PHPdoc)
     * @see BaseScript::executeScript()
    */
    protected function executeScript ($arrOption)
    {
        // TODO Auto-generated method stub
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
        
        $pid = time()+1;
        $str = strval($pid);
        $uname = substr($str, strlen($str) - UserConf::MAX_USER_NAME_LEN);
        $ret = UserLogic::createUser($pid, 1, $uname);
        if($ret['ret'] != 'ok')
        {
            echo "create other use failed\n";
            exit();
        }
        Logger::trace('create other user ret %s.',$ret);
        self::$otherUid = $ret['uid'];
        $this->test();
    }
    
    public function test()
    {
        $this->addGold();
        $this->addSilver();
        $this->addSoul();
        $this->chargeGold();
        $this->setFightForce();
        $this->banChat();
        $this->addExecution();
        $this->addStamina();
    }
    
    
    private function addGold()
    {
        $addGold = 10;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $preGold = $otherUser->getGold();
        $otherUser->addGold($addGold, StatisticsDef::ST_FUNCKEY_COPY_GETPRIZE);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $afterGold = $otherUser->getGold();
        $this->assertTrue(($preGold+$addGold == $afterGold),
                'add gold pre '.$preGold.' add gold:'.$addGold." after gold:".$afterGold);
    }
    
    private function assertTrue($condition,$msg)
    {
        if($condition)
        {
            echo $msg." passed\n";
        }
        else
        {
            echo $msg." not passed\n";
        }
    }
    
    private function addSilver()
    {
        $addSilver = 100;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $preSilver = $otherUser->getSilver();
        $otherUser->addSilver($addSilver);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $afterSilver = $otherUser->getSilver();
        $this->assertTrue(($preSilver+$addSilver == $afterSilver),
                'add Silver pre '.$preSilver." add ".$addSilver." after ".$afterSilver);
    }
    
    private function addSoul()
    {
        $add = 100;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $pre = $otherUser->getSoul();
        $otherUser->addSoul($add);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $after = $otherUser->getSoul();
        $this->assertTrue(($pre+$add == $after),
                'add soul pre '.$pre." add ".$add." after ".$after);
    }
    
    private function addExp()
    {
        $add = 100;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $pre = $otherUser->getExp();
        $otherUser->addExp($add);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $after = $otherUser->getExp();
        $this->assertTrue(($pre+$add == $after),
                'add exp pre '.$pre." add ".$add." after ".$after);
    }
    
    private function addExecution()
    {
        $add = 10;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $pre = $otherUser->getCurExecution();
        $otherUser->addExecution($add);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $after = $otherUser->getCurExecution();
        $this->assertTrue(($pre+$add <= $after),
                'add execution pre '.$pre." add ".$add." after ".$after);
    }
    
    private function addStamina()
    {
        $add = 10;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $pre = $otherUser->getStamina();
        $otherUser->addStamina($add);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $after = $otherUser->getStamina();
        $this->assertTrue(($pre+$add <= $after),
                'add stamina pre '.$pre." add ".$add." after ".$after);
    }
    
    private function chargeGold()
    {
        $preVip = 0;
        $afterVip = 2;
        $chargeGold = intval(btstore_get()->VIP[$afterVip]['totalRecharge']);
        $payBacks = btstore_get()->FIRSTPAY_REWARD['pay_back']->toArray();
        foreach($payBacks as $charge => $payBack)
        {
            if($charge > $chargeGold)
            {
                $chargeGold = $charge;
                $backGold = intval($payBack);
                break;
            }
            continue;
        }
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $preGold = $otherUser->getGold();
        $user = new User();
        $preCharge = UserLogic::getSumGoldByUid(self::$otherUid);
        $user->addGold4BBpay(self::$otherUid, time(), $chargeGold);
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $getVip = $otherUser->getVip();
        $afterGold = $otherUser->getGold();
        $afterCharge = UserLogic::getSumGoldByUid(self::$otherUid);
        //vip
        $this->assertTrue(($afterVip == $getVip),'charge gold vip is wrongly.now vip is '.$getVip.' should be '.$afterVip);
        //gold
        $this->assertTrue(($preGold + $chargeGold + $backGold == $afterGold),'charge gold gold num is wrong.'.$preGold." ".$chargeGold." ".$backGold." ".$afterGold);
        //chargeGold
        $this->assertTrue(($preCharge + $chargeGold == $afterCharge),'charge gold charge num is wrong.'.$preCharge." ".$chargeGold." ".$afterCharge);
    }
    
    private function setFightForce()
    {
        $fightForce = 10000;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $otherUser->setFightForce($fightForce);
        $otherUser->update();
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $getfight = $otherUser->getFightForce();
        $this->assertTrue(($fightForce == $getfight),'set fightforce fightforce is wrong.'.$fightForce." ".$getfight);
    }
    
    private function banChat()
    {
        $banTime = Util::getTime() + 1000;
        $gm = new Gm();
        $gm->silentUser(self::$otherUid, $banTime);
        $arrCallbackList = RPCContext::getInstance ()->getCallback ();
        Util::sendCallback ( $arrCallbackList, ScriptConf::CALLBACK_INTERVAL );
        RPCContext::getInstance()->delAllCallBack();
        sleep(3);
        EnUser::release(self::$otherUid);
        CData::$QUERY_CACHE = NULL;
        $otherUser = EnUser::getUserObj(self::$otherUid);
        $getTime = $otherUser->getBanChatTime();
        $this->assertTrue(($banTime == $getTime),'banchat time wrong.'.$banTime." ".$getTime);
    }


}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */