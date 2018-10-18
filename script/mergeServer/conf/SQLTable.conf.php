<?php
/***************************************************************************
 *
 * Copyright (c) 2011 babeltime.com, Inc. All Rights Reserved
 * $Id: SQLTable.conf.php 112423 2014-06-04 05:53:36Z HaidongJia $
 *
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/pirate/rpcfw/script/mergeServer/conf/SQLTable.conf.php $
 * @author $Author: HaidongJia $(jhd@babeltime.com)
 * @date $Date: 2014-06-04 13:53:36 +0800 (星期三, 04 六月 2014) $
 * @version $Revision: 112423 $
 * @brief
 *
 **/

class SQLTableConf
{
	/**
	 *
	 * 需要处理的ID
	 *
	 * @var array(id => table_name)
	 *
	 */
	public static $SQLMODIFYID = array(
		'uid' => 't_user',
		'item_id' => 't_item',
		'guild_id' => 't_guild',
		'hid' => 't_hero',
		//'kid' => 't_task', 三国没有
		'star_id' => 't_star',
		'petid' => 't_pet',
		'rid' => 't_reward',
		'eid' => 't_envelope',
	);
	
	/**
	 * 需要从初始值开始重新生成的id，而不是按照偏移生成
	 */
	public static $SQLMODIFY_ID_REARRANGE = array(
		'item_id' => array(
				'default' => 1000000,
				'step' => 1,
			),
	);

	/**
	 *
	 * 需要处理的主要表
	 *
	 * @var array
	 */
	public static $SQLMODIFYMAINTABLE = array(
		't_guild',
		't_user',
	);

	/**
	 *
	 * 需要处理的主要表的相关表列表
	 *
	 * @var array
	 * <code>
	 * 		'table column id' => 'relative id'
	 * </code>
	 */
	public static $SQLMODIFYTABLE = array(
		't_guild'			=> array(
			't_guild' => array(
				'guild_id' => 'guild_id',
				'create_uid' => 'uid',
			),
			't_guild_copy' => array(
				'guild_id' => 'guild_id',
			),
		),
			
		't_slim_user'		=> array(
			't_slim_user' => array(
				'uid' => 'uid',
			),
			't_bbpay_gold' => array(
				'uid' => 'uid',
			),
			't_bbpay_item' => array(
				'uid' => 'uid',
			),
		),

		't_user' 			=> array(
			't_user' => array (
				'uid' => 'uid',
				'master_hid' => 'hid',
				'guild_id' => 'guild_id',
			),
			't_guild_member' => array(
				'uid' => 'uid',
				'guild_id' => 'guild_id',
			),
	
			't_bag' => array(
				'uid' => 'uid',
				'item_id' => 'item_id',
			),
			
			't_copy' => array(
				'uid' => 'uid',
			),
			
			't_hero' => array(
				'hid' => 'hid',
				'uid' => 'uid',
			),
			't_friend' => array(
				'uid' => 'uid',
				'fuid' => 'uid',
			),

			't_pet' => array(
				'uid' => 'uid',
				'petid' => 'petid'
			),

			't_switch' => array(
				'uid' => 'uid',
			),

			't_bbpay_gold' => array(
				'uid' => 'uid',
			),
			't_bbpay_item' => array(
				'uid' => 'uid',
			),
			't_bad_order' => array(
				'uid' => 'uid',
			),

			't_acc_sign' => array(
				'uid' => 'uid',
			),
			't_normal_sign' => array(
				'uid' => 'uid',
			),
	
			't_achieve' => array(
				'uid' => 'uid',
			),
				
			't_active' => array(
				'uid' => 'uid',
			),
			
			't_activity_copy' => array(
				'uid' => 'uid',
			),
			
			't_all_star' => array(
				'uid' => 'uid',
			),
			't_star' => array(
				'uid' => 'uid',
				'star_id' => 'star_id',
			),
			
			't_arm_book' => array(
				'uid' => 'uid',
			),
			't_treas_book' => array(
				'uid' => 'uid',
			),
			
			't_charge_raffle' => array(
				'uid' => 'uid',
			),
			
			't_compete' => array(
				'uid' => 'uid',
			),
			
			't_copy_team' => array(
				'uid' => 'uid',
			),
			
			't_destiny' => array(
				'uid' => 'uid',
			),
			
			't_discount_card' => array(
				'uid' => 'uid',
			),
			
			't_divine' => array(
				'uid' => 'uid',
			),
			
			't_dragon' => array(
				'uid' => 'uid',
			),
				
			't_elite_copy' => array(
				'uid' => 'uid',
			),
			
			't_fragseize' => array(
				'uid' => 'uid',
			),
			't_seizer' => array(
				'uid' => 'uid',
			),
	
			't_friendlove' => array(
				'uid' => 'uid',
			),
			
			't_growup' => array(
				'uid' => 'uid',
			),
			
			't_guildtask' => array(
				'uid' => 'uid',
			),
			
			't_hcopy' => array(
				'uid' => 'uid',
			),
			
			't_hero_book' => array(
				'uid' => 'uid',
			),
			
			't_hero_formation' => array(
				'uid' => 'uid',
			),
			
			't_hero_shop' => array(
				'uid' => 'uid',
			),
			
			't_hunt' => array(
				'uid' => 'uid',
			),
			
			't_ka' => array(
				'uid' => 'uid',
			),
			
			't_keeper' => array(
				'uid' => 'uid',
			),
			
			't_mall' => array(
				'uid' => 'uid',
			),
			
			't_online' => array(
				'uid' => 'uid',
			),
			
			't_reward' => array(
				'rid' => 'rid',
				'uid' => 'uid',
			),
			
			't_rob_tomb' => array(
				'uid' => 'uid',
			),
			
			't_shop' => array(
				'uid' => 'uid',
			),
			
			't_sign_activity' => array(
				'uid' => 'uid',
			),
			
			't_tower' => array(
				'uid' => 'uid',
			),
			
			't_user_copy' => array(
				'uid' => 'uid',
			),
			
			't_user_extra' => array(
				'uid' => 'uid',
			),
			
			't_vipbonus' => array(
				'uid' => 'uid',
			),
				
			't_lordwar_inner_user' => array(
				'uid' => 'uid',
			),
			't_topup_reward' => array(
				'uid' => 'uid',
			),
			't_user_olympic' => array(
				'uid' => 'uid',
			),
			't_month_sign' => array(
				'uid' => 'uid',
			),
			't_dress_room' => array(
				'uid' => 'uid',
			),
			't_roulette' => array(
				'uid' => 'uid',
			),
			't_god_weapon_book' => array(
				'uid' => 'uid',
			),
			't_pass' => array(
				'uid' => 'uid',
			),
			't_retrieve' => array(
				'uid' => 'uid',
			),
			't_bowl' => array(
				'uid' => 'uid',
			),
			't_festival' => array(
				'uid' => 'uid',
			),
			't_trust_device' => array(
				'uid' => 'uid',
			),
			't_athena' => array(
				'uid' => 'uid',
			),
			't_guild_copy_user' => array(
				'uid' => 'uid',
			),
			't_moon' => array(
				'uid' => 'uid',
			),
			't_world_pass_inner_user' => array(
				'uid' => 'uid',
			),
			't_world_arena_inner_user' => array(
				'uid' => 'uid',
			),
			't_union' => array(
				'uid' => 'uid',
			),
			't_world_groupon_inner_user' => array(
				'uid' => 'uid',
			),
			't_mission_inner_user' => array(
				'uid' => 'uid',
			),
			't_travel_shop_user' => array(
				'uid' => 'uid',
			),
			't_fs_reborn' => array(
				'uid' => 'uid',
			),
			't_happy_sign' => array(
				'uid' => 'uid',
			),
			't_desact' => array(
				'uid' => 'uid',
			),
			't_world_compete_inner_user' => array(
				'uid' => 'uid',
			),
			't_recharge_gift' => array(
				'uid' => 'uid',
			),
			't_tally_book' => array(
				'uid' => 'uid',
			),
			't_envelope_user' => array(
				'uid' => 'uid',
				'eid' => 'eid',
			),
			't_envelope' => array(
				'uid' => 'uid',
				'eid' => 'eid',
			),
			't_one_recharge' => array(
				'uid' => 'uid',
			),
			't_pay_back' => array(
				'uid' => 'uid',
			),
			't_stylish' => array(
				'uid' => 'uid',
			),
			't_festivalact' => array(
				'uid' => 'uid',
			),
			't_chariot_book' => array(
					'uid' => 'uid',
			),
			't_sevens_lottery' => array(
				'uid' => 'uid',
			),
			't_welcomeback' => array(
				'uid' => 'uid',
			),
		),
	);

	/**
	 *
	 * 需要删除的表
	 *
	 * @var array
	 */
	public static $SQLDELETE = array(
		't_guild_record',
		't_guild_apply',
		't_arena_msg',
		't_battle_record',
		't_boss_atk',
		't_boss',
		't_activity_conf',
		't_arena',
		't_arena_history',
		't_arena_lucky',
		't_base_fdrank',
		't_black',
		't_city_war',
		't_city_war_attack',
		't_city_war_user',
		't_copy_fdrank',
		't_defeat_replay',
		't_forge',
		't_mail',
		't_mineral',
		't_mineral_guards',
		't_pay_back_info',
		't_pay_back_user',
		't_random_name',
		't_timer',
		't_groupon',
		't_groupon_user',
		't_lordwar_procedure',
		't_lordwar_temple',
		't_olympic_global',
		't_olympic_log',
		't_olympic_rank',
		't_mergeserver_reward',
		't_mineral_roblog',
		't_guild_rob',
		't_guild_rob_user',
		't_arena_fmt',
		't_guild_war_inner_user',
		't_guild_war_inner_temple',
		't_mission_inner_config',
		't_travel_shop',
		't_countrywar_inner_user',
		't_countrywar_inner_worship',
		't_charge_dart_record',
		't_charge_dart_road',
		't_charge_dart_user',
		't_mineral_elves',
		't_new_server_activity',
		't_new_server_goods',
	);

	/**
	 *
	 * 需要处理va字段的表
	 *
	 * @var array
	 */
	public static $SQLMODIFYVA = array(
		't_item'		=>		array(
			'va_item_text'	=>	array (
				'callback' => 'item',
			),
		),

		't_hero'		=>		array(
			'va_hero'	=>	array (
				'callback' => 'hero',
				'fieldInfo' => array(
					'convert_from' => array(),
					'arming' => array(),
					'skillBook' => array(),
					'dress' => array(),
					'treasure' => array(),
					'fightSoul' => array(),
					'godWeapon' => array(),
					'talent' => array(),
					'lock' => false, 
					'transfer' => 0,
					'pill' => array(),
					'dxtrans' => 0,
					'pocket' => array(),
					'tally' => array(),
					'masterTalent' => array(),
					'chariot' => array(),
				)
			),
		),

		't_user'		=>		array(
			'va_hero'	=>	array (
				'callback' => 'userHero',
				'fieldInfo' => array(
					'unused' => array(),
				),
			),
		),
			
		't_compete'		=>		array(
			'va_compete'	=>	array (
				'callback' => 'compete',
			),
		),
		
		't_friendlove'	=>		array(
			'va_love'	=>	array (
				'callback' => 'friendlove',
			),
		),
			
		't_hero_formation'	=>		array(
			'va_formation'	=>	array (
				'callback' => 'formation',
				'fieldInfo' => array(
					'formation' => array(),
					'extra' => array(),
					'extopen' => array(),
					'warcraft' => array(),
					'attr_extra' => array(),
					'attr_extra_open' => array(),
					'attr_extra_lv' => array(),
				),
			),
		),
			
		't_reward'		=>		array(
			'va_reward'	=>	array (
				'callback' => 'reward',
			),
		),
		
		't_lordwar_inner_user' => array(
			'va_lord' => array(
				'callback' => 'lordwar'
			),
			'va_lord_extra' => array(
				'callback' => 'lordwarExtra'
			)
		),
		
		't_keeper' => array(
			'va_keeper' => array(
				'callback' => 'petKeeper'
			),
		),
		
		't_all_star' => array(
			'va_act_info' => array(
				'callback' => 'allStar'
			),
		),
		
		't_dragon' => array(
			'va_bf' => array(
				'callback' => 'dragonBattleFormation'
			),
		),
		
		't_activity_copy' => array(
		    'va_copy_info' => array(
		        'callback' => 'resetGoldTreeBattleFmt'
		    ),
		),
		
		't_guild_copy' => array(
			'va_extra' => array(
				'callback' => 'guildCopyExtra'
			),
			
			'va_last_box' => array(
				'callback' => 'guildCopyLastBox'
			),
	
		),
		
	);

	/**
	 *
	 * 需要处理物品的表
	 *
	 * @var array
	 */
	public static $SQLMODIFYITEM = array (
		't_bag'		=>		'bag2item',
		't_hero'	=>		'hero2item',
		't_item'	=>		'item2item',
		't_reward' 	=>		'reward2item',
	);

	/**
	 *
	 * 需要处理名字的表
	 *
	 * @var array
	 */
	public static $SQLMODIFYNAME = array (
		't_user'	=> 		'uname',
		't_guild'	=>		'guild_name',
	);
	
	/**
	 * 除了t_user表，其他需要修改名字的表，需要配置关联的uid
	 * 通过该关联的uid找到原始的服id，用于产生后缀
	 * 
	 * @var array
	 */
	public static $SQLMODIFYNAMEBELONGUID = array (
		't_guild' 	=>		'create_uid', 
	);

	/**
	 *
	 * 需要增加game_id的字段
	 * @var array
	 */
	public static $SQLADDGAMEID = array (
		't_user'
	);

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */