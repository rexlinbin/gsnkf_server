	<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: BattleRecordRestore.php 201209 2015-10-09 09:23:04Z wuqilin $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/trunk/card/rpcfw/script/BattleRecordRestore.php $
 * @author $Author: wuqilin $(wuqilin@babeltime.com)
 * @date $Date: 2015-10-09 17:23:04 +0800 (星期五, 09 十月 2015) $
 * @version $Revision: 201209 $
 * @brief 
 *  
 **/

/**
 * @example btscript gamexxx BattleRecordRestore.php uid uname op[search|check|fix] [brid|date]...
 * 
 * @see help()
 * @author wuqilin
 *
 */
/**
 * 重生的小伙伴无法恢复不了属性
 * @author wuqilin
 *
 */
class ShowBattleRecordInfo extends BaseScript
{

	private static $arrConfPath = array(
			'./config',
			'/home/pirate/static/config'
	);
	
	
	protected function executeScript ($arrOption)
	{
		
		$recordStrHex = trim(file_get_contents($arrOption[0]));

		$recordStr = pack("H".strlen($recordStrHex), $recordStrHex);
		$recordStr = gzuncompress($recordStr);
		$battleData = amf_decode(chr(0x11) . $recordStr, 7);
		$ret = $this->getFormationInfo($battleData['team2']);
		
		self::info('%s', $ret['msg']);
	}
	
	public function getFormationInfo($formationInfo)
	{

		$arrHero = $formationInfo['arrHero'];
		$arrPet = array();
		if( isset($formationInfo['arrPet']) )
		{
			$arrPet = Util::arrayIndex($formationInfo['arrPet'], 'petid');
		}
	
		$msg = sprintf('infomation in uid:%d, uname:%s', $formationInfo['uid'], $formationInfo['name']);
		$arrItemInfo = array();
		$arrLittleFriend = array();
		if( !empty( $formationInfo['littleFriend'] ) )
		{
			/**
			 * 小伙伴的数据结构改变过一次
			 * 之前是：{hid=>htid}
			 * 改成：[ {hid=>int, htid=>int, position=>int} ]
			 */
			if( isset($formationInfo['littleFriend'][0]) )
			{
				$arrLittleFriend = Util::arrayIndex( $formationInfo['littleFriend'], 'hid');
			}
			else
			{
				foreach( $formationInfo['littleFriend'] as $hid => $htid )
				{
					$arrLittleFriend[ $hid ] = array(
							'hid' => $hid,
							'htid' => $htid,
					);
				}
	
			}
		}
		$arrAttrFriend = array();
		if (  !empty( $formationInfo['attrFriend'] ) )
		{
			$arrAttrFriend = Util::arrayIndex($formationInfo['attrFriend'], 'hid');
		}
		$arrHero = Util::arrayIndex($arrHero, 'hid');
		$msg .= sprintf("hero:\n");
		foreach( $arrHero as $hero )
		{
			$msg .= sprintf("hid:%d, htid:%d, name:%s, evolve:%d, pos:%d\n",
					$hero['hid'], $hero['htid'], self::getHeroName($hero['htid']), $hero['evolve_level'], $hero['position']);
			foreach(  $hero['equipInfo'] as $equipName => $equipInfo )
			{
				$msg .= sprintf( "%s\n", $equipName );
				foreach( $equipInfo as $pos => $itemInfo )
				{
					if( empty( $itemInfo  ) )
					{
						continue;
					}
						
					switch($equipName)
					{
						case 'arming':
							//潜能在战报中和数据库中不太一样
							if (  !empty($itemInfo['va_item_text']['armPotence']) )
							{
								foreach ( $itemInfo['va_item_text']['armPotence'] as $attrId => $attrValue )
								{
									$itemInfo['va_item_text']['armPotence'][$attrId] = $attrValue *
									Potence::getPotenceAttrValue( ItemAttr::getItemAttr($itemInfo['item_template_id'], ArmDef::ITEM_ATTR_NAME_ARM_FIXED_POTENCE), $attrId);
								}
							}
							$msg .= sprintf("[arm] pos:%d, id:%d, tpl:%d, name:%s, level:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']),
									$itemInfo['va_item_text']['armReinforceLevel'] );
								
							break;
								
						case 'treasure':
							if( !isset($itemInfo['va_item_text']['treasureEvolve']) )
							{
								$itemInfo['va_item_text']['treasureEvolve'] = 0;
							}
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d, evovle:%d\n",
									$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
									self::getItemName($itemInfo['item_template_id']),
									$itemInfo['va_item_text']['treasureLevel'],
									$itemInfo['va_item_text']['treasureEvolve'] );
							if ( isset( $itemInfo['va_item_text']['treasureInlay'] ) )
							{
								foreach( $itemInfo['va_item_text']['treasureInlay'] as $inlyInfo  )
								{
									$arrItemInfo['rune'][$inlyInfo['item_id']] = $inlyInfo;
									$msg .= sprintf("\trune: tpl:%d, name:%s", $inlyInfo['item_template_id'],
											self::getItemName($inlyInfo['item_template_id']) );
								}
							}
							break;
	
						case 'dress':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s\n",
							$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
							self::getItemName($itemInfo['item_template_id']) );
							break;
								
						case 'fightSoul':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d\n",
							$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
							self::getItemName($itemInfo['item_template_id']), $itemInfo['va_item_text']['fsLevel'] );
							break;
	
						case 'skillBook':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s\n",
							$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
							self::getItemName($itemInfo['item_template_id']) );
							break;
						case 'godWeapon':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s\n",
							$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
							self::getItemName($itemInfo['item_template_id']) );
							break;
								
						case 'pocket':
							$msg .= sprintf("pos:%d, id:%d, tpl:%d, name:%s, level:%d, exp:%d\n",
							$pos, $itemInfo['item_id'], $itemInfo['item_template_id'],
							self::getItemName($itemInfo['item_template_id']),
							$itemInfo['va_item_text']['pocketLevel'],
							$itemInfo['va_item_text']['pocketExp']  );
							break;
								
						default:
							self::fatal('invalid equipName:%s', $equipName);
							break;
					}
					if( !empty($itemInfo) )
					{
						$arrItemInfo[$equipName][$itemInfo['item_id']] = $itemInfo;
					}
				}
			}
				
		}
	
		$msg .= sprintf("little friend:\n");
		foreach($arrLittleFriend as $hero)
		{
			$msg .= sprintf("hid:%d, htid:%d, name:%s\n", $hero['hid'], $hero['htid'], self::getHeroName($hero['htid']) );
		}
	
		$msg .= sprintf("attr friend:\n");
		foreach($arrAttrFriend as $hero)
		{
			$msg .= sprintf("hid:%d, htid:%d, name:%s, lv:%d, ev:%d\n",
					$hero['hid'], $hero['htid'], self::getHeroName($hero['htid']),$hero['level'], $hero['evolve_level']);
		}
	
		$msg .= sprintf("pet:\n");
		foreach($arrPet as $pet)
		{
			$msg .= sprintf("pet_id:%d, tplId:%d, name:%s\n", $pet['petid'], $pet['pet_tmpl'], self::getPetName($pet['pet_tmpl']));
		}
	
		return array(
				'msg' => $msg,
				'arrItem' => $arrItemInfo,
				'arrHero' => $arrHero,
				'arrLittleFriend' => $arrLittleFriend,
				'attrFriend' => $arrAttrFriend,
				'arrPet' => $arrPet,
				'level' => $formationInfo['level'],
		);
	}
	

	public static function getConfPath()
	{
		foreach( self::$arrConfPath as $confPath )
		{
			if( is_dir($confPath) )
			{
				return $confPath;
			}
		}
		return '';
	}
	
	public static function getItemName($itemTplId)
	{
		$confPath = self::getConfPath();
		if( empty( $confPath ) )
		{
			return '物品XXX';
		}
		$ret = exec("grep -E '^$itemTplId,' $confPath/item_* | awk  -F ',' '{print $3}' ");
	
		if( empty($ret) )
		{
			return '物品XXX';
		}
	
		$name = iconv('GBK', 'UTF-8', $ret);
		return $name;
	}
	public static function getHeroName($htid)
	{
		$confPath = self::getConfPath();
		if( empty( $confPath ) )
		{
			return '武将XXX';
		}
		$ret = exec("grep -E '^$htid,' $confPath/heroes.csv | awk  -F ',' '{print $3}' ");
		if( empty($ret) )
		{
			return '武将XXX';
		}
	
		$name = iconv('GBK', 'UTF-8', $ret);
		return $name;
	}
	public static function getPetName($tplId)
	{
		$confPath = self::getConfPath();
		if( empty( $confPath ) )
		{
			return '宠物XXX';
		}
		$ret = exec("grep -E '^$tplId,' $confPath/pet.csv | awk  -F ',' '{print $3}' ");
		if( empty($ret) )
		{
			return '宠物XXX';
		}
	
		$name = iconv('GBK', 'UTF-8', $ret);
		return $name;
	}
	
	public static function baseLog($level, $arrArg)
	{
	
		foreach ( $arrArg as $idx => $arg )
		{
			if ($arg instanceof BtstoreElement)
			{
				$arg = $arg->toArray ();
			}
			if (is_array ( $arg ))
			{
				$arrArg [$idx] = var_export ( $arg, true );
			}
		}
		$msg = call_user_func_array ( 'sprintf', $arrArg );
	
		switch ($level)
		{
			case 'info':
				printf("%s\n", $msg);
				Logger::log(Logger::L_INFO, $arrArg, 2);
				break;
			case 'warn':
				printf("[WARN]%s\n", $msg);
				Logger::log(Logger::L_WARNING, $arrArg, 2);
				break;
			case 'fatal':
				printf("[ERROR]%s\n", $msg);
				Logger::log(Logger::L_WARNING, $arrArg, 2);
				break;
		}
	
	}
	

	public static function info()
	{
		$arrArg = func_get_args ();
		self::baseLog('info', $arrArg);
	}
	public static function warn()
	{
		$arrArg = func_get_args ();
		self::baseLog('warn', $arrArg);
		self::$foundWarn = true;
	}
	public static function fatal()
	{
		$arrArg = func_get_args ();
		self::baseLog('fatal', $arrArg);
		
		self::$foundErro = true;
	}
}

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */