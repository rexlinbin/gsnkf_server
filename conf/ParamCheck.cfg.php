<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ParamCheck.cfg.php 219508 2016-01-05 11:40:25Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/ParamCheck.cfg.php $
 * @author $Author: MingTian $(zhangtiantian@babeltime.com)
 * @date $Date: 2016-01-05 11:40:25 +0000 (Tue, 05 Jan 2016) $
 * @version $Revision: 219508 $
 * @brief 
 *  
 **/
class ParamCheckConf
{
    const CHECKTYPE_INT_LARGGER_THAN_ZERO = 1;//整型 >0
    const CHECKTYPE_INT_NOT_LESS_THAN_ZERO = 2;//整型 >=0
    const CHECKTYPE_ARRAY_NOT_NULL = 3;//非空数组
    const CHECKTYPE_ARRAY_CAN_NULL = 4;//数组，可以为空
    const CHECKTYPE_STRING = 5;//字符串
    
    //默认参数的类型是int，并且>0    
    //如果有其他情况需要在此设置(如整型>=0，数组等)    
    public static $ARR_METHOD = array(
            'Hero::enforceByHero' => array(1=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'Hero::sell'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'Hero::evolve'=>array(1=>self::CHECKTYPE_ARRAY_CAN_NULL,2=>self::CHECKTYPE_ARRAY_CAN_NULL),
            'Hero::develop'=>array(1=>self::CHECKTYPE_ARRAY_CAN_NULL,2=>self::CHECKTYPE_ARRAY_CAN_NULL),
    		'Hero::develop2red'=>array(1=>self::CHECKTYPE_ARRAY_CAN_NULL,2=>self::CHECKTYPE_ARRAY_CAN_NULL),
            'Hero::removeSkillBook'=>array(2=>self::CHECKTYPE_ARRAY_CAN_NULL),
            'Hero::inheritTalent' => array(2=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::resolveItem'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::rebornItem'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
    		'MysteryShop::resolveTreasure'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'CopyTeam::startTeamAtk'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'CopyTeam::getAllInviteInfo'=>array(1=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::resolveDress'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::resolveHeroJH'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::rebornDress'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::rebornTreasure'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::resolveRune'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::rebornPocket'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::rebornFightSoul'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'CopyTeam::setInviteStatus'=>array(0=>self::CHECKTYPE_INT_NOT_LESS_THAN_ZERO),
            'Hero::activateTalent'=>array(3=>self::CHECKTYPE_INT_NOT_LESS_THAN_ZERO),
            'MysteryShop::resolveTally'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            'MysteryShop::rebornTally'=>array(0=>self::CHECKTYPE_ARRAY_NOT_NULL),
            );
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */