<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: MysMerchant.test.php 99250 2014-04-11 08:16:55Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/mysmerchant/test/MysMerchant.test.php $$
 * @author $$Author: ShijieHan $$(hoping@babeltime.com)
 * @date $$Date: 2014-04-11 08:16:55 +0000 (Fri, 11 Apr 2014) $$
 * @version $$Revision: 99250 $$
 * @brief 
 *  
 **/

class MysMerchantTest extends PHPUnit_Framework_TestCase
{
    private $user;
    private $uid;
    private $utid;
    private $pid;
    private $uname;

    protected function setUp()
    {
        parent::setUp ();
        $this->pid = 40000 + rand(0,9999);
        $this->utid = 1;
        $this->uname = 't' . $this->pid;
        $ret = UserLogic::createUser($this->pid, $this->utid, $this->uname);
        $users = UserLogic::getUsers( $this->pid );
        $this->uid = $users[0]['uid'];
        RPCContext::getInstance()->setSession('global.uid', $this->uid);
    }

    protected function tearDown()
    {
        parent::tearDown ();
        EnUser::release();
        RPCContext::getInstance()->resetSession();
        RPCContext::getInstance()->unsetSession('global.uid');
    }

    public function test_getShopInfo()
    {
        $mysMerchant = new MysMerchant();
        $res = $mysMerchant->getShopInfo();
        var_dump($res);
    }

    /*public function test_buyGoods()
    {
        $mysMerchant = new MysMerchant();
        $ret = $mysMerchant->buyGoods("11");
        var_dump($ret);
    }*/

    /*public function test_playerRfrGoodsList()
    {
        $mysMerchant = new MysMerchant();
        $ret = $mysMerchant->playerRfrGoodsList(2); //使用金币刷新
        var_dump($ret);
    }*/

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */