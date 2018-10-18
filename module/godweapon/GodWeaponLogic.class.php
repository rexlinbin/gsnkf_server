<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeaponLogic.class.php 242270 2016-05-12 05:49:51Z DuoLi $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/godweapon/GodWeaponLogic.class.php $$
 * @author $$Author: DuoLi $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-05-12 05:49:51 +0000 (Thu, 12 May 2016) $$
 * @version $$Revision: 242270 $$
 * @brief 
 *  
 **/
class GodWeaponLogic
{

    public static function reinForce($uid, $itemId, $arrItemId, $arrItemNum)
    {
        if(empty($itemId) || empty($arrItemId))
        {
            return array();
        }
        if(in_array($itemId, $arrItemId))
        {
            throw new FakeException("can not eat self itemId:%d, arrItemId:%s", $itemId, $arrItemId);
        }
        //检查神兵是否属于该用户
        if(EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_GODWEAPON) == FALSE)
        {
            throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
        }
        $item = ItemManager::getInstance()->getItem($itemId);
        //检查物品是否为神兵
        if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
        {
            throw new FakeException('itemId:%d is not a godWeapon!', $itemId);
        }
        $isStrengthen = ItemAttr::getItemAttr($item->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_IS_STRENGTHEN);
        if($isStrengthen == GodWeaponDef::CAN_NOT_STRENGTHEN)
        {
            throw new FakeException('itemId:%d can not strengthen', $itemId);
        }

        $arrAfterCom = array_combine($arrItemId, $arrItemNum);
        $arrMaterial = ItemManager::getInstance()->getItems($arrItemId);
        foreach($arrMaterial as $materialId => $material)
        {
            if(empty($material))
            {
                throw new FakeException("materialObj is empty");
            }
            $isMaterialGodExp = ItemAttr::getItemAttr($material->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP);
            if(empty($isMaterialGodExp))
            {
                throw new FakeException('not godExp item:%d can not use to reinForce material', $material->getItemTemplateID());
            }
            if($material->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
            {
                throw new FakeException('materialId:%d is not a godWeapon!', $materialId);
            }
        }

        $reinForceLevel = $item->getReinForcelevel();
        //强化等级上限
        $reinForceLevelLimit = $item->getReinForceLevelLimit();
        if($reinForceLevel >= $reinForceLevelLimit)
        {
            throw new FakeException('uid:%d itemId:%d reinForceLevel:%d has reached limit%d', $uid, $itemId, $reinForceLevel, $reinForceLevelLimit);
        }

        //得到用户对象
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);

        //强化材料提供的总经验和需要的总花费
        $totalExp = 0;
        $totalCost = 0;
        foreach($arrMaterial as $materialId => $material)
        {
            $totalExp += $material->getTotalExp() * $arrAfterCom[$materialId];
            //删除材料
            if($bag->decreaseItem($materialId, $arrAfterCom[$materialId]) == false)
            {
                throw new FakeException('itemId:%d num:%d delete from bag failed!', $materialId, $arrAfterCom[$materialId]);
            }
        }

        $totalCost = $totalExp *
            ItemAttr::getItemAttr($item->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_CONSUME_RATIO)
            / UNIT_BASE;
        //扣除银币
        if($userObj->subSilver($totalCost) == false)
        {
            throw new FakeException('godWeapon reinForce sub silver failed');
        }

        //记录本次消耗的经验和银币
        $item->addReinForceExp($totalExp);
        $item->addReinForceCost($totalCost);
        //刷新神兵等级
        $item->rfrReinForceLv();

        $userObj->update();
        $bag->update();

        $ret = array();
        $ret[GodWeaponDef::REINFORCE_LEVEL] = $item->getReinForcelevel();
        $ret[GodWeaponDef::REINFORCE_COST] = $totalCost;
        $ret[GodWeaponDef::REINFORCE_EXP] = $item->getReinForceExp();
        return $ret;
    }

    public static function evolve($uid, $itemId, $arrGodMaterialId)
    {
        if(empty($itemId))
        {
            return array();
        }
        if(in_array($itemId, $arrGodMaterialId))
        {
            throw new FakeException("can not eat self itemId:%d, arrGodMaterialId:%s", $itemId, $arrGodMaterialId);
        }
        //检查神兵是否属于该用户
        if(EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_GODWEAPON) == FALSE)
        {
            throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
        }
        $item = ItemManager::getInstance()->getItem($itemId);
        //检查物品是否为神兵
        if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
        {
            throw new FakeException('uid:%d itemId:%d is not a godWeapon!', $uid, $itemId);
        }
        $itemTplId = $item->getItemTemplateID();
        //经验物品，不能进化
        $isGodExp = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP);
        if(!empty($isGodExp))
        {
            throw new FakeException('godExp item:%d can not evolve', $itemTplId);
        }

        //当前进化次数
        $evolveNum = $item->getEvolveNum();
        //最大进化次数
        $evolveLimit = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_LIMIT);
        if($evolveNum >= $evolveLimit)
        {
            throw new FakeException("evolve:evolveNum:%d is greater than evolveLimit:%d", $evolveNum, $evolveLimit);
        }
        $arrEvolveId = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID);
        //进化表id
        $evolveId = $arrEvolveId[$evolveNum];
        //读取对应的进化表
        $transferInfo = btstore_get()->GOD_WEAPON_TRANSFER[$evolveId];
        if($item->getReinForcelevel() < $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_NEED_RESOLVE_GOD_LEVEL])
        {
            throw new FakeException('reinForceLevel%d has not reached needresolvegodlv:%d',
                $item->getReinForcelevel(), $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_NEED_RESOLVE_GOD_LEVEL]);
        }

        $oldQuality = $item->getItemQuality();
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        if($userObj->getLevel() < $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_NEED_ACTOR_LV])
        {
            throw new FakeException('uid:%d user level:%d has not reached needavaterlv:%d', $uid,
                    $userObj->getLevel(), $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_NEED_ACTOR_LV]);
        }
        $needSilver = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_SILVER];
        //扣除银币
        if($userObj->subSilver($needSilver) == FALSE)
        {
            throw new FakeException('godWeapon evolve sub silver failed');
        }

        //检查前端传的神兵够不够
        $arrNeedGodMaterialTplId = array();
        $arrNeedGodAmy = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY];
        foreach($arrNeedGodAmy as $needGodAmy)
        {
            if(empty($arrNeedGodMaterialTplId[$needGodAmy[0]]))
            {
                $arrNeedGodMaterialTplId[$needGodAmy[0]] = 1;
            }
            else
            {
                $arrNeedGodMaterialTplId[$needGodAmy[0]] += 1;
            }
        }
        if(!empty($arrGodMaterialId))
        {
            ItemManager::getInstance()->getItems($arrGodMaterialId);
            foreach($arrGodMaterialId as $godMaterialId)
            {
                $godMaterial = ItemManager::getInstance()->getItem($godMaterialId);
                //检查消耗的神兵材料是否为神兵
                if($godMaterial === NULL || $godMaterial->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
                {
                    throw new FakeException('uid:%d godMaterialId:%d is not a godWeapon!', $uid, $godMaterialId);
                }
                //前端传的id，不在要删除的map内
                if(empty($arrNeedGodMaterialTplId[$godMaterial->getItemTemplateID()]))
                {
                    throw new FakeException("error param itemTplId:%d not equal need", $godMaterial->getItemTemplateID());
                }
                //新加的规则,神兵材料必须是强化等级为0
                if($godMaterial->getReinForcelevel() > GodWeaponDef::INIT_REINFORCE_LEVEL)
                {
                    throw new FakeException("reinForceLevel:%d bigger than zero", $godMaterial->getReinForcelevel());
                }
                //神兵材料进化次数必须等于初始进化次数
                if($godMaterial->getEvolveNum() > ItemAttr::getItemAttr($godMaterial->getItemTemplateID(),
                    GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM))
                {
                    throw new FakeException("evolveNum:%d bigger than initNum:%d", $godMaterial->getEvolveNum(),
                        ItemAttr::getItemAttr($godMaterial->getItemTemplateID(),
                            GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM)
                    );
                }
                //删除神兵材料
                if($bag->deleteItem($godMaterialId) == false)
                {
                    throw new FakeException('itemId:%d delete from bag failed!', $godMaterialId);
                }

                if($arrNeedGodMaterialTplId[$godMaterial->getItemTemplateID()] > 1)
                {
                    $arrNeedGodMaterialTplId[$godMaterial->getItemTemplateID()]--;
                }
                else
                {
                    unset($arrNeedGodMaterialTplId[$godMaterial->getItemTemplateID()]);
                }
            }

            Logger::info('uid:%d godWeapon evolve delete arrGodMaterialId:%s', $uid, $arrGodMaterialId);
        }
        if(!empty($arrNeedGodMaterialTplId))
        {
            throw new FakeException('qianduan param wrong arrNeedGodAmy:%s should contain arrNeedGodMaterialTplId:%s', $arrNeedGodAmy, $arrNeedGodMaterialTplId);
        }

        //删除进化消耗材料
        $resolveItem = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID]->toArray();
        if($bag->deleteItemsByTemplateID($resolveItem) == false)
        {
            throw new FakeException('resolveItem:%s delete from bag failed!', $resolveItem);
        }

        $item->addEvolveNum();
        //记录本次消耗的银币
        $item->addReinForceCost($needSilver);

        //自动刷新神兵等级（有些物品身上的经验，大于当前强化等级对应的经验--当吞吃经验石头时候会出现）
        $item->rfrReinForceLv();

        $userObj->update();
        $bag->update();

        $newQuality = $item->getItemQuality();
        if($newQuality > $oldQuality)
        {
            EnAchieve::updateGodWeaponNum($uid, $newQuality, 1);
            EnAchieve::updateGodWeaponQuality($uid, $newQuality);
        }

        $ret = array();
        $ret[GodWeaponDef::EVOLVE_NUM] = $item->getEvolveNum();
        $ret[GodWeaponDef::REINFORCE_LEVEL] = $item->getReinForcelevel();
        $ret[GodWeaponDef::REINFORCE_EXP] = $item->getReinForceExp();
        return $ret;
    }

    public static function resolve($uid, $arrItemId, $preview = false)
    {
        if(empty($arrItemId))
        {
            return array();
        }
        if(count($arrItemId) > 5)
        {
            throw new FakeException('uid:%d resolve arrItemId size > 5', $uid);
        }
        $silver = 0;
        $items = array();
        $extra = array();
        $dropRet = array();
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $arrItem = ItemManager::getInstance()->getItems($arrItemId);
        //最多是5个
        $isFirst = 1;
        $isTmpBag = false;
        foreach($arrItem as $itemId => $item)
        {
            if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
            {
                throw new FakeException('itemId:%d is not godWeapon', $itemId);
            }
            if($bag->isItemExist($itemId) == false)
            {
                throw new FakeException('itemId:%d is not in bag!', $itemId);
            }
            $itemTplId = $item->getItemTemplateID();
            //经验物品，不能炼化
            $isGodExp = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP);
            if(!empty($isGodExp))
            {
                throw new FakeException('godExp item:%d can not resolve', $itemTplId);
            }
            //进化过 不能炼化
            if($item->getEvolveNum() > ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM))
            {
                throw new FakeException('itemId:%d evolveNum:%d greater than originalEvolveNum:%d', $itemId, $item->getEvolveNum(),
                    ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM));
            }

            $sellInfo = $item->sellInfo();
            $silver += $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE];
            $isTmpBag = ($isFirst == 1) ? false : true;
            //炼化返还Id组(对应掉落表id)
            $resolveId = $item->getResolveId();
            $dropRet = Util::arrayAdd3V(array($dropRet, EnUser::drop($uid, array($resolveId), false, $isTmpBag)));
            $isFirst++;
            //物品的经验要转化成经验石头返回
            $exp = $item->getTotalExp();
            if($exp > 0)
            {
                $arrRebornItemId = ItemManager::getInstance()->addItem(GodWeaponDef::REBORN_RETURN_ITEM_ID);
                if(empty($arrRebornItemId))
                {
                    throw new FakeException('addItem itemId:%d error', GodWeaponDef::REBORN_RETURN_ITEM_ID);
                }
                $isTmpBag = ($isFirst == 1) ? false : true;
                if($bag->addItems($arrRebornItemId, $isTmpBag) == false)
                {
                    throw new FakeException('resolve: full bag. add arrRebornItemId:%s failed', $arrRebornItemId);
                }
                $isFirst++;
                $rebornReturnItemId = $arrRebornItemId[0];
                //石头初始经验
                $stoneExp = ItemAttr::getItemAttr(GodWeaponDef::REBORN_RETURN_ITEM_ID ,GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GIVE_EXP);
                if($exp - $stoneExp >= 0)
                {
                    ItemManager::getInstance()->getItem($rebornReturnItemId)->addReinForceExp($exp - $stoneExp);
                }
                else 
                {
                    throw new ConfigException("error itemid:%d config@cehua stoneExp:%d is zero", $itemId, $stoneExp);
                }
                $extra = Util::arrayAdd2V(array($extra, array(GodWeaponDef::REBORN_RETURN_ITEM_ID => 1)));
            }

            if($bag->deleteItem($itemId) == false)
            {
                throw new FakeException('itemId:%d delete from bag failed!', $itemId);
            }
        }

        if(!empty($silver))
        {
            $userObj->addSilver($silver);
        }

        if (! $preview )
        {
        	$bag->update();
        	$userObj->update();
        }
        $items = Util::arrayAdd2V(array($items, $extra));

        return array(
            'silver' => $silver,
            'item' => $items,
            'drop' => $dropRet,
        );
    }

    public static function reborn($uid, $itemId, $preview = false)
    {
        if(empty($itemId))
        {
            return array();
        }
        //检查神兵是否属于该用户
        if(EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_GODWEAPON) == FALSE)
        {
            throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
        }
        $item = ItemManager::getInstance()->getItem($itemId);
        //检查物品是否为神兵
        if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
        {
            throw new FakeException('itemId:%d is not a godWeapon!', $itemId);
        }
        $itemTplId = $item->getItemTemplateID();
        //经验物品，不能重生
        $isGodExp = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP);
        if(!empty($isGodExp))
        {
            throw new FakeException('godExp item:%d can not reborn', $itemTplId);
        }
        //强化等级必须大于初始等级
        $reinForceLevel = $item->getReinForcelevel();
        if($reinForceLevel <= GodWeaponDef::INIT_REINFORCE_LEVEL)
        {
            throw new FakeException('uid:%d itemId:%d reinForceLevel:%d less than initReinForceLevel:%d', $uid, $itemId,
                $reinForceLevel, GodWeaponDef::INIT_REINFORCE_LEVEL);
        }

        $items = array();
        $extra = array();
        $silver = $item->getReinForceCost();
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);

        //神兵进化次数
        $evolveNum = $item->getEvolveNum();
        //初始化进化次数
        $initEvolveNum = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM);
        //进化表Id组
        $arrEvolveId = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_ID);
        $rebornCost = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REBORN_COST);
        if(empty($rebornCost[$evolveNum]))
        {
            throw new ConfigException("config error @cehua evolveNum:%d, rebornCost:%s", $evolveNum, $rebornCost);
        }
        $needGold = $rebornCost[$evolveNum];
        
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GOD_WEAPON_REBORN) == false)
        {
            throw new FakeException('GroupOn::bugGood subGold failed');
        }

        $isFirst = 0;
        for($i = $initEvolveNum; $i < $evolveNum; $i++)
        {
            if(!isset($arrEvolveId[$i]))
            {
                throw new ConfigException("error config @cehua, no evolveNum:%d", $i);
            }
            //进化表id
            $evolveId = $arrEvolveId[$i];
            //读取对应的进化表
            $transferInfo = btstore_get()->GOD_WEAPON_TRANSFER[$evolveId];
            //返还进化消耗的银币(物品进化时候身上已经记录了消耗银币，所以这里不用加了)
            //$silver += $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_SILVER];
            //返还进化消耗神兵
            if(isset($transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY]))
            {
                $arrNeedGodAmy = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_COST_GOD_AMY];
                foreach($arrNeedGodAmy as $needGodAmy)
                {
//                     $arrReturnGodWeapon = ItemManager::getInstance()->addItem($needGodAmy[0]);
//                     if(empty($arrReturnGodWeapon))
//                     {
//                         throw new InterException('addItem itemId:%d error', $needGodAmy[0]);
//                     }
                    $items = Util::arrayAdd2V(array($items, array($needGodAmy[0] => 1)));
                }
            }

            //返还进化消耗材料
            if(isset($transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID]))
            {
                $resolveItem = $transferInfo[GodWeaponDef::GOD_WEAPON_TRANSFER_RESOLVE_ITEM_ID];
                $items = Util::arrayAdd2V(array($items, $resolveItem));
            }
        }

        $isFirst = 1;
        $isTmpBag = false;
        //重生返还的经验物品
        $exp = $item->getReinForceExp();
        $splitNum = 0;
        while($exp > 0)
        {
            $arrRebornItemId = ItemManager::getInstance()->addItem(GodWeaponDef::REBORN_RETURN_ITEM_ID);
            if(empty($arrRebornItemId))
            {
                throw new FakeException('addItem itemId:%d error', GodWeaponDef::REBORN_RETURN_ITEM_ID);
            }
            $isTmpBag = ($isFirst == 1) ? false : true;
            if($bag->addItems($arrRebornItemId, $isTmpBag) == false)
            {
                throw new FakeException('reborn: full bag. add arrRebornItemId:%s failed', $arrRebornItemId);
            }
            $isFirst++;
            $rebornReturnItemId = $arrRebornItemId[0];
            //石头初始经验
            $stoneExp = ItemAttr::getItemAttr(GodWeaponDef::REBORN_RETURN_ITEM_ID ,GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GIVE_EXP);

            //计算当前经验石头要堆叠的经验值
            if($splitNum >= ItemDef::UPPER_LIMIT_NUM_FOR_EXP_ITEM - 1)
            {
                $expForThis = $exp;
            }
            else
            {
                $expForThis = $exp >= ItemDef::UPPER_LIMIT_EXP_FOR_GOD_WEAPON ? ItemDef::UPPER_LIMIT_EXP_FOR_GOD_WEAPON : $exp;
            }

            if($expForThis - $stoneExp >= 0)
            {
                ItemManager::getInstance()->getItem($rebornReturnItemId)->addReinForceExp($expForThis - $stoneExp);
            }
            else
            {
                throw new ConfigException("error itemid:%d config@cehua stoneExp:%d is zero", $itemId, $stoneExp);
            }
            if(isset($extra[GodWeaponDef::REBORN_RETURN_ITEM_ID]))
            {
                $extra[GodWeaponDef::REBORN_RETURN_ITEM_ID] += 1;
            }
            else
            {
                $extra[GodWeaponDef::REBORN_RETURN_ITEM_ID] = 1;
            }

            //扣除经验值
            $exp -= $expForThis;
            $splitNum++;
        }

        if(!empty($silver))
        {
            $userObj->addSilver($silver);
        }
        if(!empty($items))
        {
            $isTmpBag = ($isFirst == 1) ? false : true;
            if($bag->addItemsByTemplateID($items, $isTmpBag) == false)
            {
                throw new FakeException('full bag. add items:%s failed', $items);
            }
        }

        //物品重生
        $item->reset();

        if(! $preview)
        {
       		$userObj->update();
        	$bag->update();
        }
        $items = Util::arrayAdd2V(array($items, $extra));

        return array(
            'silver' => $silver,
            'item' => $items,
        );

    }

    public static function wash($uid, $itemId, $type, $index)
    {
        self::checkWash($uid, $itemId);
        $item = ItemManager::getInstance()->getItem($itemId);
        $item->checkWash($index);
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);

        if($type == 0)
        {
            $normalNeedCost = ItemAttr::getItemAttr($item->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_NORMAL_WASH_COST);
            if($bag->deleteItemsByTemplateID($normalNeedCost[GodWeaponDef::NEED_ITEM][$index]) == false)
            {
                throw new FakeException('normal wash:%s delete from bag failed!', $normalNeedCost[GodWeaponDef::NEED_ITEM][$index]);
            }
            if($userObj->subSilver($normalNeedCost[GodWeaponDef::NEED_SILVER][$index]) == false)
            {
                throw new FakeException('normal wash:%d subSilver failed', $normalNeedCost[GodWeaponDef::NEED_SILVER][$index]);
            }
        }
        else if($type == 1)
        {
            $goldNeedCost = ItemAttr::getItemAttr($item->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOLD_WASH_COST);
            if(!empty($goldNeedCost[GodWeaponDef::NEED_ITEM]) && !empty($goldNeedCost[GodWeaponDef::NEED_ITEM][$index]))
            {
                if($bag->deleteItemsByTemplateID($goldNeedCost[GodWeaponDef::NEED_ITEM][$index]) == false)
                {
                    throw new FakeException('gold wash:%s delete from bag failed!', $goldNeedCost[GodWeaponDef::NEED_ITEM][$index]);
                }
            }
            if($userObj->subGold($goldNeedCost[GodWeaponDef::NEED_GOLD][$index], StatisticsDef::ST_FUNCKEY_GOD_WEAPON_WASH) == false)
            {
                throw new FakeException('gold wash:%d subSilver failed', $goldNeedCost[GodWeaponDef::NEED_GOLD][$index]);
            }
        }
        else
        {
            throw new FakeException("error param:%d", $type);
        }

        $item->wash($index);
        $bag->update();
        $userObj->update();

        $ret = $item->getToConfirmAttr($index);
        return $ret;
    }

    public static function replace($uid, $itemId, $index)
    {
        self::checkWash($uid, $itemId);
        $item = ItemManager::getInstance()->getItem($itemId);
        $bag = BagManager::getInstance()->getBag($uid);
        $item->replaceAttr($index);

        $bag->update();
        return 'ok';
    }

    public static function batchWash($uid, $itemId, $type, $index)
    {
        self::checkWash($uid, $itemId);
        $item = ItemManager::getInstance()->getItem($itemId);
        $item->checkWash($index);
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $num = GodWeaponDef::BATCH_WASH_NUM;

        if($type == 0)
        {
            $normalNeedCost = ItemAttr::getItemAttr($item->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_NORMAL_WASH_COST);
            foreach($normalNeedCost[GodWeaponDef::NEED_ITEM][$index] as $materItemTplId => $materNum)
            {
                $materNumInBag = $bag->getItemNumByTemplateID($materItemTplId);
                if(0 == $materNum)
                {
                    continue;
                }
                $num = floor($materNumInBag / $materNum) >= $num ? $num : floor($materNumInBag / $materNum);
            }

            $onceNeedSilver = $normalNeedCost[GodWeaponDef::NEED_SILVER][$index];
            if($onceNeedSilver > 0)
            {
                $silverOfUser = $userObj->getSilver();
                $num = floor($silverOfUser / $onceNeedSilver) >= $num ? $num : floor($silverOfUser / $onceNeedSilver);
            }

            if($num <= 0)
            {
                throw new FakeException('short of material');
            }
            $materToDel = array();
            foreach($normalNeedCost[GodWeaponDef::NEED_ITEM][$index] as $materItemTplId => $materNum)
            {
                $materToDel[$materItemTplId] = $materNum * $num;
            }

            if($bag->deleteItemsByTemplateID($materToDel) == false)
            {
                throw new FakeException('batch normal wash:%s delete from bag failed!', $materToDel);
            }
            if($userObj->subSilver($onceNeedSilver * $num) == false)
            {
                throw new FakeException('batch normal wash:%d subSilver failed', $onceNeedSilver * $num);
            }
        }
        else if($type == 1)
        {
            $goldNeedCost = ItemAttr::getItemAttr($item->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOLD_WASH_COST);
            $onceNeedGold = $goldNeedCost[GodWeaponDef::NEED_GOLD][$index];
            if($onceNeedGold > 0)
            {
                $goldOfUser = $userObj->getGold();
                $num = floor($goldOfUser / $onceNeedGold) >= $num ? $num : floor($goldOfUser / $onceNeedGold);
            }

            if(!empty($goldNeedCost[GodWeaponDef::NEED_ITEM]) && !empty($goldNeedCost[GodWeaponDef::NEED_ITEM][$index]))
            {
                foreach($goldNeedCost[GodWeaponDef::NEED_ITEM][$index] as $materItemTplId => $materNum)
                {
                    $materNumInBag = $bag->getItemNumByTemplateID($materItemTplId);
                    $num = floor($materNumInBag / $materNum) >= $num ? $num : floor($materNumInBag / $materNum);
                }

                if($num <= 0)
                {
                    throw new FakeException('short of material');
                }
                $materToDel = array();
                foreach($goldNeedCost[GodWeaponDef::NEED_ITEM][$index] as $materItemTplId => $materNum)
                {
                    $materToDel[$materItemTplId] = $materNum * $num;
                }

                if($bag->deleteItemsByTemplateID($materToDel) == false)
                {
                    throw new FakeException('batch gold wash:%s delete from bag failed!', $materToDel);
                }
            }

            if($userObj->subGold($onceNeedGold * $num, StatisticsDef::ST_FUNCKEY_GOD_WEAPON_WASH) == false)
            {
                throw new FakeException('batch gold wash:%d subSilver failed', $onceNeedGold * $num);
            }
        }
        else
        {
            throw new FakeException("error param:%d", $type);
        }

        $item->batchWash($index, $num);
        $bag->update();
        $userObj->update();

        $ret = array(
            'arrAttrId' => $item->getBatchToConfirmAttr($index),
            'num' => $num,
        );
        return $ret;
    }

    public static function ensure($uid, $itemId, $index, $attrId)
    {
        self::checkWash($uid, $itemId);
        $item = ItemManager::getInstance()->getItem($itemId);
        $bag = BagManager::getInstance()->getBag($uid);
        $item->ensure($index, $attrId);

        $bag->update();
        return 'ok';
    }

    public static function checkWash($uid, $itemId)
    {
        //检查神兵是否属于该用户
        if(EnUser::isCurUserOwnItem($itemId, ItemDef::ITEM_TYPE_GODWEAPON) == FALSE)
        {
            throw new FakeException('itemId:%d is not belong to user:%d!', $itemId, $uid);
        }
        $item = ItemManager::getInstance()->getItem($itemId);
        //检查物品是否为神兵
        if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
        {
            throw new FakeException('itemId:%d is not a godWeapon!', $itemId);
        }
        $itemTplId = $item->getItemTemplateID();
        //经验物品，不能洗练
        $isGodExp = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP);
        if(!empty($isGodExp))
        {
            throw new FakeException('godExp item:%d can not wash', $itemTplId);
        }
    }

    public static function legend($uid, $arrItemId, $arrIndex)
    {
        $userObj = EnUser::getUserObj($uid);
        $bag = BagManager::getInstance()->getBag($uid);

        $aimItemId = $arrItemId[1];
        $items = ItemManager::getInstance()->getItems($arrItemId);
        foreach($items as $itemId => $item)
        {
            self::checkWash($uid, $itemId);
            //神兵互换时若有未激活的洗练层扔让互换
            /*if($aimItemId == $itemId)
            {
                foreach($arrIndex as $index)
                {
                    $item->checkWash($index);
                }
            }*/
        }
        $item1 = ItemManager::getInstance()->getItem($arrItemId[0]);
        $item2 = ItemManager::getInstance()->getItem($arrItemId[1]);
        $type1 = ItemAttr::getItemAttr($item1->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_TYPE);
        $type2 = ItemAttr::getItemAttr($item2->getItemTemplateID(), GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_TYPE);
        if($type1 != $type2)
        {
            throw new FakeException('invalid type type1:%d, type2:%d', $type1, $type2);
        }

        $conf = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GODWEAPON_LEGEND];
        $needGold = 0;
        foreach($conf as $index => $gold)
        {
            if(in_array($index, $arrIndex))
            {
                $needGold += $gold;
            }
        }
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_GOD_WEAPON_LEGEND) == false)
        {
            throw new FakeException('uid:%d god weapon legend subGold:%d failed', $uid, $needGold);
        }

        $confirmedAttr = $item1->getConfirmedAttr();
        $confirmedAttr2 = $item2->getConfirmedAttr();
        if(empty($confirmedAttr) && empty($confirmedAttr2))
        {
            throw new FakeException("confirmedAttr and confirmedAttr2 is empty");
        }
        $item1CanWashCount = $item1->getCanWashCount();
        $item2CanWashCount = $item2->getCanWashCount();
        foreach($arrIndex as $index)
        {
            if($index > $item1CanWashCount || $index > $item2CanWashCount)
            {
                throw new FakeException("index:%d cant legend", $index);
            }
            if(empty($confirmedAttr[$index]) && empty($confirmedAttr2[$index]))
            {
                throw new FakeException("index:%d of confirmedAttr:%s and confirmedAttr2:%s is empty", $index, $confirmedAttr, $confirmedAttr2);
            }

            $item2->delAttr($index);
            if(!empty($confirmedAttr[$index]))
            {
                $item2->setAttr($index, $confirmedAttr[$index]);
            }

            $item1->delAttr($index);
            if(!empty($confirmedAttr2[$index]))
            {
                $item1->setAttr($index, $confirmedAttr2[$index]);
            }
        }
        //$item1->delArrAttr($arrIndex);

        $bag->update();
        $userObj->update();

        return 'ok';
    }

    public static function cancel($uid, $itemId, $index)
    {
        self::checkWash($uid, $itemId);
        $bag = BagManager::getInstance()->getBag($uid);
        $item = ItemManager::getInstance()->getItem($itemId);
        $item->delBatchToConfirmAttr($index);
        $bag->update();
        return 'ok';
    }

    public static function lock($uid, $itemId)
    {
        self::checkWash($uid, $itemId);
        $bag = BagManager::getInstance()->getBag($uid);
        $item = ItemManager::getInstance()->getItem($itemId);
        if($item->isLock())
        {
            throw new FakeException("item itemId:%d has locked", $itemId);
        }
        $item->lock();
        $bag->update();
        return 'ok';
    }

    public static function unLock($uid, $itemId)
    {
        self::checkWash($uid, $itemId);
        $bag = BagManager::getInstance()->getBag($uid);
        $item = ItemManager::getInstance()->getItem($itemId);
        if(!$item->isLock())
        {
            throw new FakeException("item itemId:%d is not locked", $itemId);
        }
        $item->unlock();
        $bag->update();
        return 'ok';
    }
    
    public static function transfer($uid, $itemId, $itemTplId)
    {
    	//检查用户等级
    	$user = EnUser::getUserObj($uid);
    	$userLevel = $user->getLevel();
    	$needLevel = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GODWEAPON_TRANSFER_NEED_LEVEL];
    	if ($userLevel < $needLevel) 
    	{
    		 throw new FakeException("Can not transfer godWeapon, user level:%d, need level:%d", $userLevel, $needLevel);
    	}
    	
    	//检查物品模板id
    	if (ItemManager::getInstance()->getItemType($itemTplId) != ItemDef::ITEM_TYPE_GODWEAPON) 
    	{
    		throw new FakeException('itemTplId:%d is not a godWeapon', $itemTplId);
    	}
    	
    	//检查物品是否神兵
    	$item = ItemManager::getInstance()->getItem($itemId);
    	if($item === NULL || $item->getItemType() != ItemDef::ITEM_TYPE_GODWEAPON)
    	{
    		throw new FakeException('itemId:%d is not a godWeapon', $itemId);
    	}
    	
    	//检查物品是否锁定
    	if($item->isLock())
    	{
    		throw new FakeException("itemId:%d is locked", $itemId);
    	}
    	
    	//检查神兵转换规则
    	if ($item->getItemTemplateID() == $itemTplId) 
    	{
    		throw new FakeException('itemId:%d can not transfer to self', $itemId);
    	}
    	
    	//检查是否同属性神兵（配置保证）
    	$index = -1;
    	$arrTrans = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GODWEAPON_TRANSFER_ARR]->toArray();
    	foreach ($arrTrans as $key => $value)
    	{
    		if (in_array($item->getItemTemplateID(), $value)) 
    		{
    			$index = $key;
    			break;
    		}
    	}
    	if (!isset($arrTrans[$index]) || !in_array($itemTplId, $arrTrans[$index])) 
    	{
    		throw new FakeException('itemId:%d can not transfer to itemTplId:%d', $itemId, $itemTplId);
    	}
    	
    	//检查转换花费
    	$cost = btstore_get()->NORMAL_CONFIG[NormalConfigDef::CONFIG_ID_GODWEAPON_TRANSFER_COST][$item->getEvolveNum()];
    	if (!$user->subGold($cost, StatisticsDef::ST_FUNCKEY_GOD_WEAPON_TRANSFER)) 
    	{
    		throw new FakeException('uid:%d has no enough gold:%d for transfer itemId:%d', $uid, $cost, $itemId);
    	}
    	
    	//检查物品是否在背包中
    	$bag = BagManager::getInstance()->getBag($uid);
    	if (!$bag->deleteItem($itemId)) 
    	{
    		throw new FakeException('itemId:%d is not in bag', $itemId);
    	}
    	
    	//物品继承属性
    	$arrItemId = ItemManager::getInstance()->addItem($itemTplId);
    	$newItemId = $arrItemId[0];
    	$newItem = ItemManager::getInstance()->getItem($newItemId);
    	$newItem->setReinForceLevel($item->getReinForcelevel());
    	$newItem->addReinForceCost($item->getReinForceCost());
    	$newItem->addReinForceExp($item->getReinForceExp());
    	$newItem->setEvolveNum($item->getEvolveNum());
    	$newItem->setToConfirmAttrInfo($item->getToConfirmAttrInfo());
    	$newItem->setConfirmedAttr($item->getConfirmedAttr());
    	$newItem->setBatchToConfirmAttrInfo($item->getBatchToConfirmAttrInfo());
    	
    	//加到背包
    	if (!$bag->addItem($newItemId))
    	{
    		throw new FakeException('bag is full!');
    	}
    	
    	//更新
    	$user->update();
    	$bag->update();
    	
    	return $newItemId;
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
