<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $$Id$$
 * 
 **************************************************************************/

 /**
 * @file $$HeadURL$$
 * @author $$Author$$(ShijieHan@babeltime.com)
 * @date $$Date$$
 * @version $$Revision$$
 * @brief 
 *  
 **/
class AthenaSql
{
    const TABLE = 't_athena';
    const UID = 'uid';
    const VA_DATA = 'va_data';
    const DETAIL = 'detail';
    const SPECIAL = 'special';
    const TREE_NUM = 'tree_num';
    const BUY_NUM = 'buy_num';
    const BUY_TIME = 'buy_time';
    const NORMAL = 'normal'; //普攻技能
    const RAGE = 'rage'; //怒攻技能
    const ARR_TALENT = 'arrTalent'; //觉醒能力

    public static $arrColumn = array(
        self::UID,
        self::VA_DATA,
    );
}

class AthenaDef
{
    const TYPE_SILVER = 1;
    const TYPE_GOLD = 3;
    const TYPE_ITEMS = 7;
    const INIT_TREE_INDEX = 1;
    const TYPE_NORMAL = 1;
    const TYPE_RAGE = 2;

    public static $DonateMale = array(420, 500);
    public static $DonateFemale = array(430, 510);
}

class AthenaCsvDef
{
    //tree.csv
    const ID = 'id';
    const AFFIX = 'affix';
    const SPECIAL_ATTR_ID = 'spe_attr_id';
    const OPEN_NEED = 'open_need';
    const TYPE = 'type';
    const NEXT_TREE = 'next_tree';
    const NEXT_NEED_LEVEL = 'next_need_level';  //开启下个等级需要玩家level
    const AWAKE_ABILITY_ID = 'awake_ability_id';    //觉醒能力ID

    //tree_skill.csv
    const AFFIX_GROW = 'affix_grow';
    const EX_SKILL = 'ex_skill';
    const MAX_LEVEL = 'max_level';
    const UP_COST1 = 'up_cost1';
    const UP_COST2 = 'up_cost2';
    const UP_COST3 = 'up_cost3';
    const UP_COST4 = 'up_cost4';
    const UP_COST5 = 'up_cost5';
    const UP_COST6 = 'up_cost6';
    const UP_COST7 = 'up_cost7';
    const UP_COST8 = 'up_cost8';
    const UP_COST9 = 'up_cost9';
    const UP_COST10 = 'up_cost10';
    const UP_COST11 = 'up_cost11';
    const UP_COST12 = 'up_cost12';
    const UP_COST13 = 'up_cost13';
    const UP_COST14 = 'up_cost14';
    const UP_COST15 = 'up_cost15';
    const UP_COST16 = 'up_cost16';
    const UP_COST17 = 'up_cost17';
    const UP_COST18 = 'up_cost18';
    const UP_COST19 = 'up_cost19';
    const UP_COST20 = 'up_cost20';
    const UP_COST = 'up_cost';

}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */