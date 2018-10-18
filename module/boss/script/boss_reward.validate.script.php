<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: boss_reward.validate.script.php 178506 2015-06-12 04:01:33Z wuqilin $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/boss/script/boss_reward.validate.script.php $
 * @author $Author: wuqilin $(jhd@babeltime.com)
 * @date $Date: 2015-06-12 04:01:33 +0000 (Fri, 12 Jun 2015) $
 * @version $Revision: 178506 $
 * @brief
 *
 **/

require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/def/Boss.def.php";
//require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Dummy.class.php";


if (! function_exists ( 'btstore_get' ))
{
	require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/conf/Script.cfg.php";
	require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/Logger.class.php";
	require_once dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) . "/lib/SimpleBtstore.php";
	ScriptConf::$ARR_PRELOAD_BTSTORE = array();
}


$boss_rewards = btstore_get()->BOSS_REWARD->toArray();

foreach ( $boss_rewards as $reward_id => $reward_info )
{
	//validate order list num
	if ( $reward_info[BossDef::REWARD_ORDER_LIST_NUM] !=
		count($reward_info[BossDef::REWARD_ORDER_LIST]) )
	{
		echo "BOSS REWARD:$reward_id order list num != count(order list)\n";
	}

	//validate order list
	$orders = array();
	for ( $i = 0; $i < count($reward_info[BossDef::REWARD_ORDER_LIST]); $i++ )
	{
		$list = $reward_info[BossDef::REWARD_ORDER_LIST][$i];

		//validate order low and order up
		if ( $list[BossDef::REWARD_ORDER_LOW] > $list[BossDef::REWARD_ORDER_UP] )
		{
			echo "BOSS REWARD:$reward_id index:$i order low:" . $list[BossDef::REWARD_ORDER_LOW]
			. " > " . $list[BossDef::REWARD_ORDER_UP] . " is invalid\n";
		}

		foreach ( range($list[BossDef::REWARD_ORDER_LOW], $list[BossDef::REWARD_ORDER_UP]) as $order )
		{
			if ( in_array($order, $orders) )
			{
				echo "BOSS REWARD:$reward_id index:$i order range is invalid\n";
			}
		}

	 	$orders = array_merge($orders,array(array($list[BossDef::REWARD_ORDER_LOW],
        $list[BossDef::REWARD_ORDER_UP])));
    }

    for ( $i = 0 ; $i <= 3; $i++ )
    {
        $exists = false;
        foreach ( $orders as $k => $v )
        {
            if ( $v[0] >= $i && $v[1] <=$i )
            {
                $exists = true;
                continue;
            }
        }
        if ( $exists == false )
        {
            echo "BOSS REWARD:$reward_id order $i is not isset\n";
        }
    }

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */