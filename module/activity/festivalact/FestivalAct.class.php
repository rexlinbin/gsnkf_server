<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: ITopupReward.class.php 122072 2014-07-22 08:05:51Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/module/activity/topupreward/ITopupReward.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2014-07-22 16:05:51 +0800 (星期二, 22 七月 2014) $$
 * @version $$Revision: 122072 $$
 * @brief 
 *  
 **/

class FestivalAct implements IFestivalAct
{
    private $uid = 0;

    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
        // 检查活动是否开启
        if (!EnActivity::isOpen(ActivityName::FESTIVAL_ACT))
        {
            throw new FakeException('Act festivalAct is not open.');
        }
    }

    public function getInfo()
    {
        Logger::trace('FestivalAct::getInfo Start.');

        $festivalInfo = FestivalActLogic::getInfo($this->uid);

        Logger::trace('Festival::getInfo End.');

        return $festivalInfo;
    }

    public function taskReward($id)
    {
        Logger::trace('FestivalAct::taskReward Start.');

        $id = intval($id);
        
        if ($id <= 0)
        {
            throw new FakeException('param err. id:%d.', $id);
        }

        $ret = FestivalActLogic::taskReward($this->uid, $id);

        Logger::info('Festival::taskReward End id:%s.', $id);

        return $ret;
    }

    public function buy($id, $num)
    {
        Logger::trace('FestivalAct::buy Start.');

        $id = intval($id);
        $num = intval($num);

        if ($id <= 0 || $num <= 0)
        {
            throw new FakeException('param err. id:%d, num:%d.', $id, $num);
        }

        $ret = FestivalActLogic::buy($this->uid, $id, $num);

        Logger::info('Festival::buy End id:%d, num:%d.', $id, $num);

        return $ret;
    }

    public function exchange($id, $num = 1)
    {
        Logger::trace('FestivalAct::exchange Start.');

        $id = intval($id);
        $num = intval($num);

        if ($id <= 0 || $num <= 0)
        {
            throw new FakeException('param err. id:%d, num:%d.', $id, $num);
        }

        $ret = FestivalActLogic::exchange($this->uid, $id, $num);

        Logger::info('Festival::exchange End id:%s, num:%s.', $id, $num);

        return $ret;
    }

    public function signReward($id)
    {
        Logger::trace('FestivalAct::signReward Start.');

        $id = intval($id);

        if ($id <= 0)
        {
            throw new FakeException('param err. id:%d.', $id);
        }

        $ret = FestivalActLogic::signReward($this->uid, $id);

        Logger::info('Festival::signReward End id:%s.', $id);

        return $ret;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */