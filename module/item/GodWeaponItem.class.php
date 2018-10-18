<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: GodWeaponItem.class.php 230235 2016-03-01 10:27:49Z MingTian $$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/item/GodWeaponItem.class.php $$
 * @author $$Author: MingTian $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-03-01 10:27:49 +0000 (Tue, 01 Mar 2016) $$
 * @version $$Revision: 230235 $$
 * @brief 
 *  
 **/

/**
 * Class GodWeaponItem
 * va_item_text:array   物品扩展信息
 * [
 *      'reinForceLevel':int 强化等级
 *      'reinForceCost':int 强化费用银币(炼化返还用)
 *      'reinForceExp':int  强化经验(炼化返还用)
 *      'evolveNum':int 进化次数
 *      'confirmed':array 当前洗练的属性(提供加成)
 *      [
 *          "1" => id0, "2" => id1, id2, id3
 *      ]
 *      'toConfirm':array 洗练出来的属性(寻求理想)
 *      [
 *          id0, id1, id2, id3
 *      ]
 * ]
 */
class GodWeaponItem extends Item
{
    /**
     * 产生物品
     *
     * @param int $itemTplId    物品模板ID
     * @return array|void   等级和花费银币
     */
    public static function createItem($itemTplId)
    {
        $itemText = array();

        //初始化物品强化等级
        $itemText[GodWeaponDef::REINFORCE_LEVEL] = GodWeaponDef::INIT_REINFORCE_LEVEL;

        //初始化进化次数
        $initEvolveNum = ItemAttr::getItemAttr($itemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM);
        if(!empty($initEvolveNum))
        {
            $itemText[GodWeaponDef::EVOLVE_NUM] = $initEvolveNum;
        }
        else
        {
            $itemText[GodWeaponDef::EVOLVE_NUM] = GodWeaponDef::INIT_EVOLVE_NUM;
        }

        //强化费用和强化经验
        $itemText[GodWeaponDef::REINFORCE_COST] = 0;
        $itemText[GodWeaponDef::REINFORCE_EXP] = 0;

        return $itemText;
    }

    /**
     * 得到物品的出售信息
     * @throws ConfigException
     * @return array|void sell_pirce表示出售的价格, sell_type表示出售的类型
     */
    public function sellInfo()
    {
        $sellInfo = parent::sellInfo();

        if($sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_TYPE] != ItemDef::ITEM_SELL_TYPE_SILVER)
        {
            throw new ConfigException('godWeapon template id:%d item sell type is wrong!', $this->getItemTemplateID());
        }

        $reinForceCost = $this->getReinForceCost();
        $sellInfo[ItemDef::ITEM_ATTR_NAME_SELL_PRICE] += intval($reinForceCost);
        return $sellInfo;
    }

    /**
     * 计算属性加成
     * 属性加成由进化次数决定
     */
    public function info()
    {
        $info = array();

        $evolveNum = $this->getEvolveNum();
        //神兵等级
        $reinForceLevel = $this->mItemText[GodWeaponDef::REINFORCE_LEVEL];

        for($i = 1; $i <= count(GodWeaponDef::$arrBaseAbility); $i++)
        {
            $tmpBaseAbility = ItemAttr::getItemAttr($this->mItemTplId, "baseAbilityId" . "$i");
            $tmpGrowAbility = ItemAttr::getItemAttr($this->mItemTplId, "growAbilityId" . "$i");
            if(isset($tmpBaseAbility[$evolveNum]))
            {
                if(isset($info[$tmpBaseAbility[$evolveNum][0]]))
                {
                    $info[$tmpBaseAbility[$evolveNum][0]] += $tmpBaseAbility[$evolveNum][1];
                }
                else
                {
                    $info[$tmpBaseAbility[$evolveNum][0]] = $tmpBaseAbility[$evolveNum][1];
                }
            }
            if(isset($tmpGrowAbility[$evolveNum]))
            {
                if(isset($info[$tmpGrowAbility[$evolveNum][0]]))
                {
                    $info[$tmpGrowAbility[$evolveNum][0]] += $reinForceLevel * $tmpGrowAbility[$evolveNum][1];
                }
                else
                {
                    $info[$tmpGrowAbility[$evolveNum][0]] = $reinForceLevel * $tmpGrowAbility[$evolveNum][1];
                }
            }
        }

        //神兵洗练属性加成
        $confirmedAttr = $this->getConfirmedAttr();
        foreach($confirmedAttr as $index => $attrId)
        {
            if($this->ifWashAttrEffect($index))
            {
                $arrAttr = btstore_get()->GOD_WEAPON_AFFIX[$attrId][GodWeaponDef::GOD_WEAPON_WASH_ATTR];
                foreach($arrAttr as $attrId => $attrValue)
                {
                    if(isset($info[$attrId]))
                    {
                        $info[$attrId] += $attrValue;
                    }
                    else
                    {
                        $info[$attrId] = $attrValue;
                    }
                }
            }
        }

        $arrRet = HeroUtil::adaptAttr($info);
        Logger::trace("getAddAttrByGodWeapon. itemId:%d arr:%s.", $this->mItemId, $arrRet);
        return $arrRet;
    }

    /**
     * 重置神兵
     */
    public function reset()
    {
        //初始化物品强化等级
        $this->setReinForceLevel(GodWeaponDef::INIT_REINFORCE_LEVEL);

        //初始化进化次数
        $initEvolveNum = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_ORIGINAL_EVOLVE_NUM);
        if(empty($initEvolveNum))
        {
            $initEvolveNum = GodWeaponDef::INIT_EVOLVE_NUM;
        }
        $this->setEvolveNum($initEvolveNum);

        //强化费用和强化经验
        $this->mItemText[GodWeaponDef::REINFORCE_COST] = 0;
        $this->mItemText[GodWeaponDef::REINFORCE_EXP] = 0;
    }

    /**
     * 得到神兵的强化费用
     */
    public function getReinForceCost()
    {
        if(!isset($this->mItemText[GodWeaponDef::REINFORCE_COST]))
        {
            $this->mItemText[GodWeaponDef::REINFORCE_COST] = 0;
        }
        return $this->mItemText[GodWeaponDef::REINFORCE_COST];
    }

    /**
     * 增加神兵强化费用
     */
    public function addReinForceCost($cost)
    {
        $this->mItemText[GodWeaponDef::REINFORCE_COST] += $cost;
    }

    /**
     * 得到神兵强化经验
     */
    public function getReinForceExp()
    {
        if(empty($this->mItemText[GodWeaponDef::REINFORCE_EXP]))
        {
            return 0;
        }
        return $this->mItemText[GodWeaponDef::REINFORCE_EXP];
    }

    /**
     * 被当作强化材料的时候，能够提供的经验
     */
    public function getTotalExp()
    {
        $tmpGiveExp = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_GIVE_EXP);
        $totalExp = $tmpGiveExp + $this->getReinForceExp();
        return $totalExp;
    }

    /**
     * 增加神兵强化经验
     * @param $exp int 经验
     */
    public function addReinForceExp($exp)
    {
        $this->mItemText[GodWeaponDef::REINFORCE_EXP] += $exp;
    }

    /**
     * 计算神兵品质和阶别，根据进化次数
     * $return array
     * [
     *  0 => 对应品质, 1 => 对应阶别
     * ]
     */
    public function calQualityAndStage()
    {
        $ret = array();
        $evolveNum = $this->mItemText[GodWeaponDef::EVOLVE_NUM];
        $evolveQuality = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_QUALITY);
        if(isset($evolveQuality[$evolveNum]))
        {
            $ret = $evolveQuality[$evolveNum];
        }
        else
        {
            throw new ConfigException("error config evolveQuality, not contain evolveNum:%d", $evolveNum);
        }
        return $ret;
    }

    /**
     * 获得神兵品质
     * @return int
     */
    public function getItemQuality()
    {
        $qualityAndStage = $this->calQualityAndStage();
        return $qualityAndStage[0];
    }

    /**
     * 得到神兵强化等级
     */
    public function getReinForcelevel()
    {
        if(empty($this->mItemText[GodWeaponDef::REINFORCE_LEVEL]))
        {
            return 0;
        }
        return $this->mItemText[GodWeaponDef::REINFORCE_LEVEL];
    }

    /**
     * 设置神兵强化等级
     * @param $level int 强化等级
     */
    public function setReinForceLevel($level)
    {
        $this->mItemText[GodWeaponDef::REINFORCE_LEVEL] = $level;
    }

    /**
     * 得到当前神兵强化等级上限
     */
    public function getReinForceLevelLimit()
    {
        $evolveNum = $this->getEvolveNum();
        $reinForceLevelLimit = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_EVOLVE_REINFORCE_LEVEL_LIMIT)->toArray();
        if(!isset($reinForceLevelLimit[$evolveNum]))
        {
            throw new FakeException('error config @cehua, reinForceLevelLimit:%s have no evolveNum:%d', $reinForceLevelLimit, $evolveNum);
        }
        return $reinForceLevelLimit[$evolveNum];
    }

    /**
     * 得到神兵进化次数
     */
    public function getEvolveNum()
    {
        if(empty($this->mItemText[GodWeaponDef::EVOLVE_NUM]))
        {
            return 0;
        }
        return $this->mItemText[GodWeaponDef::EVOLVE_NUM];
    }

    /**
     * 设置神兵进化次数
     * @param $num int 进化次数
     */
    public function setEvolveNum($num)
    {
        $this->mItemText[GodWeaponDef::EVOLVE_NUM] = $num;
    }

    /**
     * 增加神兵进化次数
     */
    public function addEvolveNum()
    {
        $this->mItemText[GodWeaponDef::EVOLVE_NUM]++;
    }

    /**
     * 得到神兵的类型（金木水火土）
     */
    public function getType()
    {
        return ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_TYPE);
    }

    /**
     * 得到神兵羁绊ID组
     */
    public function getFriendIds()
    {
        return ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_FRIEND_ID);
    }

    /**
     * 根据当前强化经验计算强化等级,并刷新赋值
     */
    public function rfrReinForceLv()
    {
        //升级经验表ID
        $reinForceExpId = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_REINFORCE_EXP_ID);
        $confExpTbl = btstore_get()->EXP_TBL[$reinForceExpId];
        //当前的强化等级
        $reinForceLevel = $this->getReinForcelevel();
        //当前强化经验
        $reinForceExp = $this->getReinForceExp();
        //当前强化等级上限
        $reinForceLevelLimit = $this->getReinForceLevelLimit();
        while( isset($confExpTbl[$reinForceLevel + 1])
            && $reinForceExp >= $confExpTbl[$reinForceLevel + 1]
            && $reinForceLevel < $reinForceLevelLimit
        )
        {
            $reinForceLevel++;
        }
        $this->setReinForceLevel($reinForceLevel);
    }

    /**
     * 炼化返还Id(对应掉落表Id)
     * @return mixed
     */
    public function getResolveId()
    {
        return ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_RESOLVE_ID);
    }

    /**
     * 羁绊生效进化次数要求
     * @return mixed
     */
    public function getFriendOpen()
    {
        return ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_FRIEND_OPEN);
    }

    /**
     * 是否是经验石头
     */
    public function isExpStone()
    {
        $isExpStone = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_IS_GOD_EXP);
        if(empty($isExpStone))
        {
            return false;
        }
        return true;
    }

    private function addToConfirmAttr($washIndex, $attrId)
    {
        $this->mItemText[GodWeaponDef::TOCONFIRM][$washIndex] = $attrId;
        Logger::trace("addToConfirmed washIndex:%d attrId:%d", $washIndex, $attrId);
    }

    private function addBatchToConfirmAttr($washIndex, $arrAttr)
    {
        $this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex] = $arrAttr;
        Logger::trace("addBatchToConfirmAttr washIndex:%d arrAttr:%s", $washIndex, $arrAttr);
    }

    public function delBatchToConfirmAttr($washIndex)
    {
        unset($this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex]);
    }

    public function getBatchToConfirmAttr($washIndex)
    {
        if(isset($this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex]))
        {
            return $this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex];
        }
        return array();
    }

    public function getToConfirmAttr($washIndex)
    {
        if(isset($this->mItemText[GodWeaponDef::TOCONFIRM][$washIndex]))
        {
            return $this->mItemText[GodWeaponDef::TOCONFIRM][$washIndex];
        }
        return array();
    }
    
    public function getToConfirmAttrInfo()
    {
    	if(isset($this->mItemText[GodWeaponDef::TOCONFIRM]))
    	{
    		return $this->mItemText[GodWeaponDef::TOCONFIRM];
    	}
    	return array();
    }
    
    public function setToConfirmAttrInfo($info)
    {
    	$this->mItemText[GodWeaponDef::TOCONFIRM] = $info;
    }

    public function getConfirmedAttr()
    {
        if(isset($this->mItemText[GodWeaponDef::CONFIREMED]))
        {
            return $this->mItemText[GodWeaponDef::CONFIREMED];
        }
        return array();
    }
    
	public function setConfirmedAttr($info)
    {
        $this->mItemText[GodWeaponDef::CONFIREMED] = $info;
    } 
    
    public function getBatchToConfirmAttrInfo()
    {
    	if(isset($this->mItemText[GodWeaponDef::BATCHTOCONFIRM]))
    	{
    		return $this->mItemText[GodWeaponDef::BATCHTOCONFIRM];
    	}
    	return array();
    }
    
    public function setBatchToConfirmAttrInfo($info)
    {
    	$this->mItemText[GodWeaponDef::BATCHTOCONFIRM] = $info;
    }

    /**
     * 随机洗练出一个属性
     * 洗练的时候
     * 先用神兵上的组权重选出用哪一个组
     * 然后在同一个组中的不同属性用该权重选出哪个属性ID
     * @param $washIndex int 洗练第几个属性
     * @throws
     */
    public function wash($washIndex)
    {
        $attrId = self::doWash($washIndex);
        $this->addToConfirmAttr($washIndex, $attrId);
        return $attrId;
    }

    public function batchWash($washIndex, $num)
    {
        $arrAttrId = array();
        for($i = 0; $i < $num; $i++)
        {
            $arrAttrId[] = self::doWash($washIndex);
        }
        $this->addBatchToConfirmAttr($washIndex, $arrAttrId);
        return $arrAttrId;
    }

    public function doWash($washIndex)
    {
        $arrWashWeight = ItemAttr::getItemAttr($this->mItemTplId, "washWeight" . $washIndex)->toArray();
        $arrWashAttr = ItemAttr::getItemAttr($this->mItemTplId, "washAbility" . $washIndex)->toArray();
        if(count($arrWashAttr) != count($arrWashWeight))
        {
            throw new ConfigException("error config for item:%d count arrWashWeight:%d, count arrWashAttr:%d",
                $this->mItemTplId, count($arrWashWeight), count($arrWashAttr));
        }

        foreach($arrWashWeight as $id => $washWeight)
        {
            $arrWashWeight[$id] = array('weight' => $washWeight);
        }
        $arrGroupId = Util::noBackSample($arrWashWeight, 1);
        if(count($arrGroupId) != 1)
        {
            throw new FakeException("rand wash group failed. rand result:%s", $arrGroupId);
        }
        $groupId = $arrGroupId[0];
        Logger::trace("rand group:%d", $groupId);
        $arrAttrId = $arrWashAttr[$groupId];
        $arrAttrSample = array();
        foreach($arrAttrId as $attrId)
        {
            if(empty(btstore_get()->GOD_WEAPON_AFFIX[$attrId]))
            {
                throw new ConfigException("godarm_affix.csv not contain attrId:%d", $attrId);
            }
            $attrIdWeight = btstore_get()->GOD_WEAPON_AFFIX[$attrId]['weight'];
            $arrAttrSample[$attrId] = array('weight' => $attrIdWeight);
        }
        $arrRandAttr = Util::noBackSample($arrAttrSample, 1);
        if(count($arrRandAttr) != 1)
        {
            throw new FakeException("rand attr from arrRankAttr:%s failed, result:%s", $arrAttrSample, $arrRandAttr);
        }
        $attrId = $arrRandAttr[0];
        Logger::trace("rand attr:%d", $attrId);

        return $attrId;
    }

    public function checkWash($washIndex)
    {
        $awakeOpenQuality = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_AWAKE_OPEN_QUALITY);
        $canWashCount = count($awakeOpenQuality);
        if($washIndex > $canWashCount)
        {
            throw new FakeException("error param washIndex:%d canWashCount:%d", $washIndex, $canWashCount);
        }
        $quality = $this->getItemQuality();
        if($this->ifWashAttrEffect($washIndex) == false)
        {
            throw new FakeException("washIndex:%d quality:%d need quality:%d cant wash", $washIndex, $quality, $awakeOpenQuality[$washIndex-1]);
        }
    }

    /**
     * 获得神兵总的可洗练层数
     * @return int
     */
    public function getCanWashCount()
    {
        $awakeOpenQuality = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_AWAKE_OPEN_QUALITY);
        $canWashCount = count($awakeOpenQuality);
        return $canWashCount;
    }

    private function ifWashAttrEffect($index)
    {
        $awakeOpenQuality = ItemAttr::getItemAttr($this->mItemTplId, GodWeaponDef::ITEM_ATTR_NAME_GOD_WEAPON_AWAKE_OPEN_QUALITY);
        $quality = $this->getItemQuality();
        if($quality < $awakeOpenQuality[$index - 1])
        {
            return false;
        }
        return true;
    }

    public function replaceAttr($washIndex)
    {
        if(empty($this->mItemText[GodWeaponDef::TOCONFIRM])
            || empty($this->mItemText[GodWeaponDef::TOCONFIRM][$washIndex]))
        {
            throw new FakeException("no attr to confirm mItemText:%s", $this->mItemText[GodWeaponDef::TOCONFIRM]);
        }

        $this->mItemText[GodWeaponDef::CONFIREMED][$washIndex] = $this->mItemText[GodWeaponDef::TOCONFIRM][$washIndex];
        unset($this->mItemText[GodWeaponDef::TOCONFIRM][$washIndex]);
    }

    public function ensure($washIndex, $attrId)
    {
        if(empty($this->mItemText[GodWeaponDef::BATCHTOCONFIRM])
            || empty($this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex]))
        {
            throw new FakeException("no attr batch to confirm mItemText:%s", $this->mItemText[GodWeaponDef::BATCHTOCONFIRM]);
        }
        if(!in_array($attrId, $this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex]))
        {
            throw new FakeException("error attrId:%d, not in array:%s", $attrId, $this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex]);
        }

        $this->setAttr($washIndex, $attrId);
        unset($this->mItemText[GodWeaponDef::BATCHTOCONFIRM][$washIndex]);
    }

    public function setAttr($index, $attrId)
    {
        $this->mItemText[GodWeaponDef::CONFIREMED][$index] = $attrId;
    }

    public function delArrAttr($arrIndex)
    {
        foreach($arrIndex as $index)
        {
            $this->delAttr($index);
        }
    }

    public function delAttr($index)
    {
        unset($this->mItemText[GodWeaponDef::CONFIREMED][$index]);
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */