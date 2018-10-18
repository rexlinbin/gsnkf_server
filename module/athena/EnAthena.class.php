<?php
/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id: EnAthena.class.php 251380 2016-07-13 07:13:41Z YangJin $$
 *
 **************************************************************************/

 /**
 * @file $$HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/athena/EnAthena.class.php $$
 * @author $$Author: YangJin $$(ShijieHan@babeltime.com)
 * @date $$Date: 2016-07-13 07:13:41 +0000 (Wed, 13 Jul 2016) $$
 * @version $$Revision: 251380 $$
 * @brief
 *
 **/
class EnAthena
{
    public static function getAddAddrByAthena($uid)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::ATHENA, $uid) == false)
        {
            return array();
        }
        $athena = AthenaManager::getInstance($uid);
        $arrAddAttr = array();

        $detail = $athena->getDetail();
        foreach($detail as $index => $indexInfo)
        {
            foreach($indexInfo as $attrId => $level)
            {
                $attrValue = self::getAttrValue($attrId);
                foreach($attrValue as $tmp)
                {
                    if(isset($arrAddAttr[$tmp[0]]))
                    {
                        $arrAddAttr[$tmp[0]] += $tmp[1] * $level;
                    }
                    else
                    {
                        $arrAddAttr[$tmp[0]] = $tmp[1] * $level;
                    }
                }

            }
        }

        $arrRet = HeroUtil::adaptAttr($arrAddAttr);
        Logger::trace("getAddAddrByAthena. uid:%d, arr:%s", $uid, $arrRet);
        return $arrRet;
    }

    public static function getAttrValue($attrId)
    {
        $treeSkillConf = AthenaLogic::getTreeSkillConf();
        if(empty($treeSkillConf[$attrId][AthenaCsvDef::AFFIX_GROW]))
        {
            throw new ConfigException("config error for attrId:%d", $attrId);
        }
        return $treeSkillConf[$attrId][AthenaCsvDef::AFFIX_GROW];
    }

    /**
     * 星魂系统可装备的技能列表
     * @param $uid
     * @return array
     * [
     *  'normal' => {skillId, ...},
     *  'anger' => {skillId, ...},
     * ]
     */
    public static function getSkillList($uid)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::ATHENA, $uid) == false)
        {
            return array();
        }
        return AthenaLogic::getSkillList($uid);
    }

    public static function getArrMasterTalent($uid)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::ATHENA, $uid) == false)
        {
            return array();
        }
        return AthenaLogic::getArrMasterTalent($uid);
    }

    /**
     * 获取一个星魂技能在相反性别下的等价技能
     * @param int $skillId
     * @param int $newUtid 1 or 2
     * @return int
     * @author jinyang
     */
    public static function getCrspSkill($skillId, $newUtid)
    {
        //原先星魂技能比较蛋疼弄了两个虚拟技能，特殊处理一下
        //另外之前由于技能表配置错误，可能有虚拟技能与utid不对应的情况，这里就不按性别走了
 	
        if ($skillId == AthenaDef::$DonateMale[0])
            return AthenaDef::$DonateFemale[0];
        
        if ($skillId == AthenaDef::$DonateMale[1])
        	return AthenaDef::$DonateFemale[1];

        if ($skillId == AthenaDef::$DonateFemale[0])
            return AthenaDef::$DonateMale[0];
        
        if ($skillId == AthenaDef::$DonateFemale[1])
        	return AthenaDef::$DonateMale[1];

        //不是这两个虚拟技能

        $oldUtid = ($newUtid == 1)?2:1;
        $treeConf = AthenaLogic::getTreeConf();
        for ($index =1; $index <= count($treeConf); ++$index)
        {
            if (count($treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID]) == 0)
                return 0;
            
            if ($skillId == $treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID][$oldUtid][0])
            {
            	return $treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID][$newUtid][0];
            }
            else if ($skillId == $treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID][$oldUtid][1]) 
            {
            	return $treeConf[$index][AthenaCsvDef::SPECIAL_ATTR_ID][$newUtid][1];
            }
        }
        return 0;
    }

    /**
     * 主角变性后重置t_athena的va_data里的special
     * @param int $uid
     * @author jinyang
     */
    public static function rebuildDBVaData($uid)
    {
        if(EnSwitch::isSwitchOpen(SwitchDef::ATHENA, $uid) == false)
        {
            return;
        }
        $athena = AthenaManager::getInstance($uid);
        $athena->setArrSpecialAttr(array());//清空原有va的special
        AthenaLogic::getAthenaInfo($uid);//该方法会重建玩家星魂的所有数据
    }
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */