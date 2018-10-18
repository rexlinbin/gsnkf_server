<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: Athena.class.php 230386 2016-03-02 02:33:25Z ShijieHan $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/Athena.class.php $$
 * @author $$Author: ShijieHan $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-03-02 02:33:25 +0000 (Wed, 02 Mar 2016) $$
 * @version $$Revision: 230386 $$
 * @brief 
 *  
 **/
class Athena implements IAthena
{

    private $uid;

    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
        if(EnSwitch::isSwitchOpen(SwitchDef::ATHENA, $this->uid) == false)
        {
            throw new FakeException('user:%d does not open the athena', $this->uid);
        }
    }

    public function getAthenaInfo()
    {
        return AthenaLogic::getAthenaInfo($this->uid);
    }

    public function upGrade($index, $attrId)
    {
        if($index <= 0 || empty($attrId))
        {
            throw new FakeException("error param index:%d attrId:%d", $index, $attrId);
        }
        return AthenaLogic::upGrade($this->uid, $index, $attrId);
    }

    public function synthesis($amount)
    {
        if($amount <= 0)
        {
            throw new FakeException("invalid param amount:%d", $amount);
        }
        return AthenaLogic::synthesis($this->uid, $amount);
    }

    public function buy($itemTplId, $num)
    {
        if(empty($itemTplId) || $num <= 0)
        {
            throw new FakeException("invalid param itemTplId:%d num:%d", $itemTplId, $num);
        }
        return AthenaLogic::buy($this->uid, $itemTplId, $num);
    }

    public function changeSkill($skillType, $skillId)
    {
        if($skillType != AthenaDef::TYPE_NORMAL && $skillType != AthenaDef::TYPE_RAGE)
        {
            throw new FakeException("invalid param skillType:%d", $skillType);
        }
        return AthenaLogic::changeSkill($this->uid, $skillType, $skillId);
    }

    public function getArrMasterTalent()
    {
        return AthenaLogic::getArrMasterTalent($this->uid);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */