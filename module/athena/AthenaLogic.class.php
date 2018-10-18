<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: AthenaLogic.class.php 251240 2016-07-12 08:52:24Z YangJin $$
 *
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/AthenaLogic.class.php $$
 * @author $$Author: YangJin $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-07-12 08:52:24 +0000 (Tue, 12 Jul 2016) $$
 * @version $$Revision: 251240 $$
 * @brief
 *
 **/
class AthenaLogic
{
    public static function getAthenaInfo($uid)
    {
        $ret = array();
        $athena = AthenaManager::getInstance($uid);

        $index = $athena->getTreeNum();
        for($i = AthenaDef::INIT_TREE_INDEX; $i <= $index; $i++)
        {
            self::refreshTree($uid, $athena, $i);
        }
        $ret[AthenaSql::DETAIL] = $athena->getDetail();
        $ret[AthenaSql::TREE_NUM] = $athena->getTreeNum();
        $ret[AthenaSql::BUY_NUM] = $athena->getWholeBuyNum();
        $athena->update();
        return $ret;
    }

    public static function upGrade($uid, $index, $attrId)
    {
        $treeConf = self::getTreeConf();
        $treeSkillConf = self::getTreeSkillConf();
        $arrAffix = $treeConf[$index][AthenaCsvDef::AFFIX]->toArray();
        if(!in_array($attrId, $arrAffix))
        {
            throw new FakeException("invalid attrId:%d not in arrAffix:%s", $attrId, $arrAffix);
        }
        $athena = AthenaManager::getInstance($uid);
        $exSkill = $treeSkillConf[$attrId][AthenaCsvDef::EX_SKILL]->toArray();
        if(!empty($exSkill))
        {
            if($athena->getAttrLv($index, $exSkill[0]) < $exSkill[1])
            {
                throw new FakeException("ex_skill level:%d not reach:%d", $athena->getAttrLv($index, $exSkill[0]), $exSkill[1]);
            }
        }
        $maxLevel = $treeSkillConf[$attrId][AthenaCsvDef::MAX_LEVEL];
        $curLv = $athena->getAttrLv($index, $attrId);
        if($curLv >= $maxLevel)
        {
            throw new FakeException("level:%d bigger than max:%d", $curLv, $maxLevel);
        }

        $needMaterial = $treeSkillConf[$attrId][AthenaCsvDef::UP_COST][$curLv];
        self::delMaterial($uid, $needMaterial, StatisticsDef::ST_FUNCKEY_ATHENA_SKILL_UPGRADE);

        $athena->attrLvUp($index, $attrId);
        self::refreshTree($uid, $athena, $index);

        $athena->update();

        EnUser::getUserObj($uid)->modifyBattleData();
        EnActive::addTask(ActiveDef::ATHENA);
        return 'ok';
    }

    public static function delMaterial($uid, $material, $statistics, $amount=1)
    {
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj = EnUser::getUserObj($uid);
        foreach($material as $oneM)
        {
            list($type, $mid, $num) = $oneM;
            switch($type)
            {
                case AthenaDef::TYPE_SILVER:
                    if($userObj->subSilver($num * $amount) == false)
                    {
                        throw new FakeException("subSilver %d failed", $num * $amount);
                    }
                    break;
                case AthenaDef::TYPE_GOLD:
                    if($userObj->subGold($num * $amount, $statistics) == false)
                    {
                        throw new FakeException("subGold %d failed", $num * $amount);
                    }
                    break;
                case AthenaDef::TYPE_ITEMS:
                    if($bag->deleteItembyTemplateID($mid, $num * $amount) == false)
                    {
                        throw new FakeException("delete item failed itemId:%d num:%d", $mid, $num * $amount);
                    }
                    break;
                default:
                    throw new FakeException("invalid type:%d", $type);
            }
        }
        $bag->update();
        $userObj->update();
    }

    public static function getTreeConf()
    {
        return btstore_get()->ATHENA_TREE;
    }

    public static function getTreeSkillConf()
    {
        return btstore_get()->ATHENA_TREE_SKILL;
    }

    public static function getNormalConf()
    {
        return btstore_get()->NORMAL_CONFIG;
    }

    public static function refreshTree($uid, $athena, $index)
    {
        $treeConf = self::getTreeConf();
        $needOpen = $treeConf[$index][AthenaCsvDef::OPEN_NEED];
        $isOpen = false;
        foreach($needOpen as $each)
        {
            if($athena->getAttrLv($index, $each[0]) >= $each[1])
            {
                $isOpen = true;
                break;
            }
        }
        if($isOpen == false)
        {
            return;
        }

        $utid = EnUser::getUserObj($uid)->getUtid();//性别 1男 2女
        $type = $treeConf[$index][AthenaCsvDef::TYPE];
        if( !empty($treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID][$utid]) )
        {
            foreach($treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID][$utid] as $specialAttrId)
            {
                if($athena->isSpecialAttrExist($type, $specialAttrId) == false)
                {
                    $athena->addSpecialAttr($type, $specialAttrId);
                }
            }
        }
        else if( !empty($treeConf[$index][AthenaCsvDef::AWAKE_ABILITY_ID]) )
        {
            $awakeAbilityId = $treeConf[$index][AthenaCsvDef::AWAKE_ABILITY_ID];
            if($athena->ifTalentExist($awakeAbilityId) == false)
            {
                $athena->addTalent($awakeAbilityId);
            }
        }
        else
        {
            throw new ConfigException("error config for ability");
        }

        /**
         * 条件：
         * 1 配置下一页不是0，并且下一页大于当前最大页数
         * 2 玩家等级大于下一页开启等级
         */
        $userLv = EnUser::getUserObj($uid)->getLevel();
        if(!empty($treeConf[$index][AthenaCsvDef::NEXT_TREE]) &&
            $treeConf[$index][AthenaCsvDef::NEXT_TREE] > $athena->getTreeNum() &&
            !empty($treeConf[$index][AthenaCsvDef::NEXT_NEED_LEVEL]) &&
            $userLv >= $treeConf[$index][AthenaCsvDef::NEXT_NEED_LEVEL]
        )
        {
            $athena->addTreeNum();
        }
    }

    public static function synthesis($uid, $amount)
    {
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj = EnUser::getUserObj($uid);
        $normalConf = self::getNormalConf();
        $material = $normalConf[NormalConfigDef::CONFIG_ID_FORMULA];
        self::delMaterial($uid, $material, StatisticsDef::ST_FUNCKEY_ATHENA_SYNTHESIS, $amount);
        $formulaItem = $normalConf[NormalConfigDef::CONFIG_ID_FORMULA_ITEM];
        if(count($formulaItem) != 2)
        {
            throw new ConfigException("error config@cehua, count of formulaItem:%s not 2", $formulaItem);
        }
        $bag->addItemByTemplateID($formulaItem[0], $amount * $formulaItem[1]);
        $bag->update();
        $userObj->update();
        return 'ok';
    }

    public static function buy($uid, $itemTplId, $num)
    {
        $athena = AthenaManager::getInstance($uid);
        $bag = BagManager::getInstance()->getBag($uid);
        $userObj = EnUser::getUserObj($uid);
        $normalConf = self::getNormalConf();
        $material = $normalConf[NormalConfigDef::CONFIG_ID_FORMULA];
        $isitemTplIdValid = false;
        foreach($material as $oneM)
        {
            list($type, $mid, $numX) = $oneM;
            if($mid == $itemTplId)
            {
                $isitemTplIdValid = true;
                break;
            }
        }
        if($isitemTplIdValid == false)
        {
            throw new FakeException('invalid itemTplId:%d', $itemTplId);
        }
        $vip = $userObj->getVip();
        $numLimit = btstore_get()->VIP[$vip]['athenaLimitNum'][$itemTplId];
        $curBuyNum = $athena->getBuyNum($itemTplId);
        if($curBuyNum + $num > $numLimit)
        {
            throw new FakeException("you buy to many numLimit:%d, curBuyNum:%d, num:%d", $numLimit, $curBuyNum, $num);
        }
        $needGold = $normalConf[NormalConfigDef::CONFIG_ID_FORMULA_GOLD][$itemTplId] * $num;
        if($userObj->subGold($needGold, StatisticsDef::ST_FUNCKEY_ATHENA_SYNTHESIS_BUY_MATERIAL) == false)
        {
            throw new FakeException("athenaLogic::buy subGold failed needGold:%d", $needGold);
        }
        if($bag->addItemsByTemplateID(array($itemTplId => $num)) == false)
        {
            throw new FakeException("full bag. add items failed itemTplId:%d, num:%d", $itemTplId, $num);
        }
        $athena->addBuyNum($itemTplId, $num);
        $athena->rfrBuyTime();

        $athena->update();
        $bag->update();
        $userObj->update();
        return 'ok';
    }

    public static function getSkillList($uid)
    {
        $userObj = EnUser::getUserObj($uid);
        $athena = AthenaManager::getInstance($uid);
        $skillList = $athena->getArrSpecialAttr();
        $utid = $userObj->getUtid();
        $arrNormalSkill = array();
        if(!empty($skillList[AthenaSql::NORMAL]))
        {
            $arrNormalSkill = $skillList[AthenaSql::NORMAL];
        }
        if($utid == 1)
        {
            $arrNormalSkill = array_merge($arrNormalSkill, AthenaDef::$DonateFemale);
        }else if($utid == 2)
        {
            $arrNormalSkill = array_merge($arrNormalSkill, AthenaDef::$DonateMale);
        }

        $skillList[AthenaSql::NORMAL] = $arrNormalSkill;
        return $skillList;
    }

    public static function changeSkill($uid, $skillType, $skillId)
    {
        $userObj = EnUser::getUserObj($uid);
        $athena = AthenaManager::getInstance($uid);
        if(!in_array($skillId, AthenaDef::$DonateFemale) && !in_array($skillId, AthenaDef::$DonateMale))
        {
            if($athena->isSpecialAttrExist($skillType, $skillId) == false)
            {
                throw new FakeException("skillId:%d not exist", $skillId);
            }
        }
        else
        {
            Logger::trace("changeSkill donate skill:%d", $skillId);
        }
        if($skillType == AthenaDef::TYPE_NORMAL)
        {
            $userObj->learnMasterSkill(PropertyKey::ATTACK_SKILL, $skillId, MASTERSKILL_SOURCE::ATHENA);
        }
        else if($skillType == AthenaDef::TYPE_RAGE)
        {
            $userObj->learnMasterSkill(PropertyKey::RAGE_SKILL, $skillId, MASTERSKILL_SOURCE::ATHENA);
        }
        $athena->update();
        $userObj->update();
        return 'ok';
    }

    public static function getArrMasterTalent($uid)
    {
        $athena = AthenaManager::getInstance($uid);
        return $athena->getArrTalentInfo();
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */