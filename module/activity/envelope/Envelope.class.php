<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Envelope.class.php 221532 2016-01-13 03:48:54Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/envelope/Envelope.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2016-01-13 03:48:54 +0000 (Wed, 13 Jan 2016) $
 * @version $Revision: 221532 $
 * @brief 
 *  
 **/
class Envelope implements IEnvelope
{
    private $uid = 0;
    
    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
    }
    
    public function getInfo($type)
    {
        $type = intval($type);
        
        if ( !in_array($type, EnvelopeDef::$ENVELOPE_LIST_TYPE) )
        {
            throw new FakeException('param err. type:%d.', $type);
        }
        
        $ret = EnvelopeLogic::getInfo($this->uid, $type);
        
        return $ret;
    }
    
    public function getSingleInfo($eid)
    {
        $eid = intval($eid);
        
        if ($eid < 0)
        {
            throw new FakeException('param err. eid:%d.', $eid);
        }
        
        $ret = EnvelopeLogic::getSingleInfo($this->uid, $eid);
        
        return $ret;
    }
    
    public function send($scale, $goldNum, $divNum, $msg = '')
    {
        $scale = intval($scale);
        $goldNum = intval($goldNum);
        $divNum = intval($divNum);
        
        if (!in_array($scale, EnvelopeDef::$ENVELOPE_SCALE))
        {
            throw new FakeException('param err. wrong envelope scale:%d.',$scale);
        }
        
        if ($goldNum <= 0 || $divNum <= 0)
        {
            throw new FakeException('param err. invaild goldNum:%d or divNum:%d', $goldNum, $divNum);
        }
        
        $ret = EnvelopeLogic::send($this->uid, $scale, $goldNum, $divNum, $msg);
        
        return $ret;
    }
    
    public function open($eid)
    {
        $eid = intval($eid);
        
        if ($eid < 0)
        {
            throw new FakeException('param err. eid:%d.', $eid);
        }
        
        $ret = EnvelopeLogic::open($this->uid, $eid);
        
        return $ret;
    }
    
    public function rewardUser($uid)
    {
        EnvelopeLogic::rewardUser($uid);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */