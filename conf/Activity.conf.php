<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Activity.conf.php 259932 2016-09-01 09:35:15Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/conf/Activity.conf.php $
 * @author $Author: GuohaoZheng $(wuqilin@babeltime.com)
 * @date $Date: 2016-09-01 09:35:15 +0000 (Thu, 01 Sep 2016) $
 * @version $Revision: 259932 $
 * @brief 
 *  
 **/


class ActivityConf
{
	const VALIDITY							= 3600;				//数据的有效期，检测是否有新配置的周期
	
	const VALIDITY_RAND						= 60;				//数据的有效期随机范围

	const REFRESH_RETRY_INTERVAL			= 300;				//刷新失败时，重试的间隔
	
	public static $SUPPLY_TIME_ARR = array(
			1 => array("11:59:00", "14:00:00"),
			2 => array("17:59:00", "20:00:00"),
			3 => array("20:59:00", "23:00:00"),
	);

	const SUPPLY_NUM = 50;


	public static $ARR_READ_CONF_FUNC = array(
			
			//ActivityName::SALE 			=> 'EnSale::readSaleCSV',
			
			//ActivityName::GROWUP		=> 'EnGrowUp::readGrowupCSV',
			
			ActivityName::SPEND			=> 'EnSpend::readSpendCSV',
			
			//ActivityName::BARTER		=> 'EnBarter::readBarterCSV',
			
			//ActivityName::BARTER_FRONT	=> 'EnBarter::readBarterFrontCSV',
			
			//ActivityName::LEVELUP_FUND	=> 'EnLevelfund::readLevelfundCSV',
			
			ActivityName::TOPUP_FUND	=> 'EnTopupFund::readTopupFundCSV',
			
			//ActivityName::CARD_BIG_RUN	=> 'EnCardbigrun::readCardbigrunCSV',
			
			ActivityName::ARENA_REWARD 	=> 'EnArena::readRewardActivityCSV',
	        
	        ActivityName::HERO_SHOP    => 'EnHeroShop::readHeroShopCSV',
	        
	        ActivityName::HEROSHOP_REWARD    => 'EnHeroShop::readRewardCSV',
	        
	        ActivityName::ROB_TOMB => 'EnRobTomb::readRobTombCSV',
			
			ActivityName::SIGN_ACTIVITY => 'EnSignactivity::readSignactivityCSV',
			
			ActivityName::WEAL => 'EnWeal::readWealCSV',
			
			ActivityName::ACT_EXCHANGE => 'EnActExchange::readActExchangeCSV',

            ActivityName::GROUPON => 'EnGroupOn::readGroupOnCsv',
	        
	        ActivityName::CHARGERAFFLE => 'EnChargeRaffle::readChargeRaffleCSV',

            ActivityName::TOPUPREWARD => 'EnTopupReward::readContinuePayCsv',
	        
	        ActivityName::MONTHLYCARDGIFT => 'EnMonthlyCard::readMonthlyCardCSV',
			
			ActivityName::LORDWAR => 'EnLordwar::readLordwarCSV',

            ActivityName::REGRESS => 'EnRegress::readRegressCSV',

            ActivityName::STEPCOUNTER => 'EnStepCounter::readStepRewardCsv',
			
			ActivityName::ROULETTE => 'EnRoulette::readRouletteCSV',
			
			ActivityName::LIMITSHOP => 'EnLimitShop::readLimitShopCSV',
			
			ActivityName::BOWL => 'EnBowl::readBowlCSV',
			
			ActivityName::GUILDWAR => 'EnGuildWar::readGuildWarCsv',
			
			ActivityName::FESTIVAL => 'EnFestival::readFestivalCSV',
			
			ActivityName::FRONTSHOW => 'EnFrontshow::readFrontShowCSV',
			
			ActivityName::SCORESHOP => 'EnScoreShop::readScoreShopCSV',
			
			ActivityName::SUPPLY => 'EnSupply::readSupplyCSV',
			
			ActivityName::WORLDARENA => 'EnWorldArena::readWorldArenaCsv',

            ActivityName::WORLDGROUPON => 'EnWorldGroupon::readWorldGrouponCsv',
			
			ActivityName::TRAVELSHOP => 'EnTravelShop::readTravelShopCSV',
			
			ActivityName::VALIDITY => 'EnValidity::readValidityCSV',
			
			ActivityName::MISSION => 'EnMission::readMissionCSV',
			
			ActivityName::WORLDCARNIVAL => 'EnWorldCarnival::readWorldCarnivalCsv',
			
			ActivityName::BLACKSHOP => 'EnBlackshop::readblackshopCSV',
			
			ActivityName::HAPPYSIGN => 'EnHappySign::readHappySignCsv',
			
			ActivityName::FSREBORN => 'EnFsReborn::readFsRebornCSV',
	    
	        ActivityName::DESACT => 'EnDesact::readDesactCSV',
			
			ActivityName::RECHARGEGIFT => 'EnRechargeGift::readRechargeGiftCsv',
	    
	        ActivityName::ENVELOPE => 'EnEnvelope::readEnvelopeCSV',
			
			ActivityName::ONERECHARGE => 'EnOneRecharge::readOneRechargeCSV',
	    
	        ActivityName::ACTPAYBACK => 'EnActPayBack::readActPayBackCSV',
			
			ActivityName::MINERALELVES=>'EnMineralElves::readMineralElvesCSV',

	        ActivityName::FESTIVAL_ACT => 'EnFestivalAct::readFestivalActCSV',

	        ActivityName::FESTIVALACT_REWARD => 'EnFestivalAct::readRewardCSV',
	);
	
	public static $ARR_CANT_CHANGE_WHEN_OPEN = array(
		ActivityName::SPEND,
		ActivityName::TOPUP_FUND,
	    ActivityName::HERO_SHOP,
		ActivityName::TRAVELSHOP,	
	);
	
	const KA_SAMPLE_NUM = 6;
	
	const NS_DURATION = 999;//含第7天
	
	public static $STRICT_CHECK_CONF = true;  //严格检查活动配置，例如：活动开始后不能修改活动时间之类的
	
	public static $NS_ACTIVITY = array(	

			ActivityName::ROB_TOMB =>array(
				NSActivityDef::BTS_NAME => 'ERNIE',
				NSActivityDef::NEED_TOARRAY => true,
				NSActivityDef::TIME_ARR => array(
				array(1,999),
						
			),
			),

			ActivityName::SPEND => array(
				NSActivityDef::BTS_NAME => 'SPENDACC',
				NSActivityDef::NEED_TOARRAY => true,
				NSActivityDef::TIME_ARR => array(
				array(1,999),
			),
			),
			ActivityName::WEAL => array(
				NSActivityDef::BTS_NAME => 'NSWEAL',
				NSActivityDef::NEED_TOARRAY => true,
				NSActivityDef::TIME_ARR => array(
						array(1,999),
				),
			),
	);
	
	public static $MULCONF_ACTIVITY = array(
		ActivityName::WEAL,
	);
	
	
	public static $MUST_VALID_FOR_VALIDITY = array(
			ActivityName::LORDWAR,
			ActivityName::WORLDARENA,
			ActivityName::GUILDWAR,
			ActivityName::MISSION,
	);
	
	public static $FRONT_NEVER_NEED_DATA = array(
		    ActivityName::DESACT,
	);
	
}





/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */