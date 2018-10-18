<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeapon.class.php 242520 2016-05-13 02:48:57Z DuoLi $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/godweapon/GodWeapon.class.php $$
 * @author $$Author: DuoLi $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-05-13 02:48:57 +0000 (Fri, 13 May 2016) $$
 * @version $$Revision: 242520 $$
 * @brief 
 *  
 **/
class GodWeapon implements IGodWeapon
{
    /**
     * 用户id
     * @var $uid
     */
    private $uid;

    public function __construct()
    {
        $this->uid = RPCContext::getInstance()->getUid();
        //todo 功能节点
    }

    public function reinForce($itemId, $arrItemId, $arrItemNum)
    {
        Logger::trace('GodWeapon:reinForce start. itemId:%d, arrItemId:%s, arrItemNum:%s', $itemId, $arrItemId, $arrItemNum);
        if(count($arrItemId) != count($arrItemNum))
        {
            throw new FakeException("count of arrItemNum:%d and arrItemId:%d not equal", $arrItemNum, $arrItemId);
        }
        foreach($arrItemNum as $itemNum)
        {
            if($itemNum <= 0 || $itemNum > 50)
            {
                throw new FakeException("error param arrItemNum:%s", $arrItemNum);
            }
        }
        $reinForceTenNeedLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_REINFORCE_GOD_WEAPON_LEVEL];
        $level = EnUser::getUserObj($this->uid)->getLevel();
        if(count($arrItemId) > 5 && $level < $reinForceTenNeedLevel)
        {
            throw new FakeException("your level:[%d] not reach:[%d]", $level, $reinForceTenNeedLevel);
        }
        $ret = GodWeaponLogic::reinForce($this->uid, $itemId, $arrItemId, $arrItemNum);
        Logger::trace('GodWeapon:reinForce end. itemId:%d, arrItem:%s, arrItemNum:%s', $itemId, $arrItemId, $arrItemNum);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return $ret;
    }

    public function evolve($itemId, $arrGodMaterialId=array())
    {
        Logger::trace('GodWeapon:evolve start. itemId:%d, $arrGodMaterialId:%s', $itemId, $arrGodMaterialId);
        $ret = GodWeaponLogic::evolve($this->uid, $itemId, $arrGodMaterialId);
        Logger::trace('GodWeapon:evolve end. itemId:%d, $arrGodMaterialId:%s', $itemId, $arrGodMaterialId);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return $ret;
    }

    public function resolve($arrItemId)
    {
        Logger::trace('GodWeapon:resolve start. arrItemId:%s', $arrItemId);
        $ret = GodWeaponLogic::resolve($this->uid, $arrItemId, false);
        Logger::trace('GodWeapon:resolve end. arrItemId:%s', $arrItemId);
        return $ret;
    }
    
	public function previewResolve($arrItemId)
    {
        Logger::trace('GodWeapon:resolve start. arrItemId:%s', $arrItemId);
        $ret = GodWeaponLogic::resolve($this->uid, $arrItemId, true);
        Logger::trace('GodWeapon:resolve end. arrItemId:%s', $arrItemId);
        return $ret;
    }

    public function reborn($itemId)
    {
        Logger::trace('GodWeapon:reborn start, $itemId');
        $ret = GodWeaponLogic::reborn($this->uid, $itemId, false);
        Logger::trace('GodWeapon:reborn end, $itemId');
        return $ret;
    }

    public function previewReborn($itemId)
    {
    	Logger::trace('GodWeapon:previewReborn start, $itemId');
        $ret = GodWeaponLogic::reborn($this->uid, $itemId, true);
        Logger::trace('GodWeapon:previewReborn end, $itemId');
        return $ret;
    }
    
    public function wash($itemId, $type, $index)
    {
        Logger::trace('GodWeapon:wash start, itemId:%d, type:%d, index:%d', $itemId, $type, $index);
        if(empty($itemId) || !in_array($type, array(0, 1)) || $index < 1 || $index > 4)
        {
            throw new FakeException("error param itemId:%d, type:%d, index:%d", $itemId, $type, $index);
        }
        Logger::trace('GodWeapon:wash end, itemId:%d, type:%d, index:%d', $itemId, $type, $index);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return GodWeaponLogic::wash($this->uid, $itemId, $type, $index);
    }

    public function replace($itemId, $index)
    {
        Logger::trace('GodWeapon:replace start, itemId:%d, index:%d', $itemId, $index);
        if(empty($itemId) || $index < 1 || $index > 4)
        {
            throw new FakeException('error param itemId:%d, index:%d', $itemId, $index);
        }
        Logger::trace('GodWeapon:replace end, itemId:%d, index:%d', $itemId, $index);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return GodWeaponLogic::replace($this->uid, $itemId, $index);
    }

    public function batchWash($itemId, $type, $index)
    {
        Logger::trace('GodWeapon:batchWash start, itemId:%d, type:%d, index:%d', $itemId, $type, $index);
        if(empty($itemId) || !in_array($type, array(0, 1)) || $index < 1 || $index > 4)
        {
            throw new FakeException("error param itemId:%d, type:%d, index:%d", $itemId, $type, $index);
        }
        Logger::trace('GodWeapon:batchWash end, itemId:%d, type:%d, index:%d', $itemId, $type, $index);
        return GodWeaponLogic::batchWash($this->uid, $itemId, $type, $index);
    }

    public function ensure($itemId, $index, $attrId)
    {
        Logger::trace('GodWeapon:ensure start, itemId:%d, index:%d, attrId:%d', $itemId, $index, $attrId);
        if(empty($itemId) || $index < 1 || $index > 4 || empty($attrId))
        {
            throw new FakeException('error param itemId:%d, index:%d, attrId:%d', $itemId, $index, $attrId);
        }
        Logger::trace('GodWeapon:ensure end, itemId:%d, index:%d, attrId:%d', $itemId, $index, $attrId);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return GodWeaponLogic::ensure($this->uid, $itemId, $index, $attrId);
    }

    public function legend($arrItemId, $arrIndex)
    {
        Logger::trace('GodWeapon:legend start, arrItemId:%s, arrIndex:%s', $arrItemId, $arrIndex);
        if(count($arrItemId) != 2 || count($arrIndex) > 4 || $arrItemId[0] == $arrItemId[1])
        {
            throw new FakeException('error param arrItemId:%s, arrIndex:%s', $arrItemId, $arrIndex);
        }
        foreach($arrIndex as $index)
        {
            if($index > 4 || $index < 1)
            {
                throw new FakeException('error param arrItemId:%s, arrIndex:%s', $arrItemId, $arrIndex);
            }
        }
        Logger::trace('GodWeapon:legend end, arrItemId:%s, arrIndex:%s', $arrItemId, $arrIndex);
        //清一下战斗缓存
        Enuser::getUserObj()->modifyBattleData();
        return GodWeaponLogic::legend($this->uid, $arrItemId, $arrIndex);
    }

    public function cancel($itemId, $index)
    {
        if(empty($itemId) || $index < 1 || $index > 4)
        {
            throw new FakeException('error param itemId:%d, index:%d', $itemId, $index);
        }
        return GodWeaponLogic::cancel($this->uid, $itemId, $index);
    }

    public function lock($itemId)
    {
        if(empty($itemId))
        {
            throw new FakeException("error param itemId:%d", $itemId);
        }
        return GodWeaponLogic::lock($this->uid, $itemId);
    }

    public function unLock($itemId)
    {
        if(empty($itemId))
        {
            throw new FakeException("error param itemId:%d", $itemId);
        }
        return GodWeaponLogic::unLock($this->uid, $itemId);
    }
    
    public function transfer($itemId, $itemTplId)
    {
    	if (empty($itemId) || empty($itemTplId)) 
    	{
    		throw new FakeException("error param itemId:%d, itemTplId:%d", $itemId, $itemTplId);
    	}
    	return GodWeaponLogic::transfer($this->uid, $itemId, $itemTplId);
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */