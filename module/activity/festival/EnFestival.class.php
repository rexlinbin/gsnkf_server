<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnFestival.class.php 175207 2015-05-28 02:39:18Z GuohaoZheng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/festival/EnFestival.class.php $
 * @author $Author: GuohaoZheng $(zhengguohao@babeltime.com)
 * @date $Date: 2015-05-28 02:39:18 +0000 (Thu, 28 May 2015) $
 * @version $Revision: 175207 $
 * @brief 
 *  
 **/
class EnFestival
{
	public static function readFestivalCSV($arrData)
	{
		$csvIndex = 0;
		$confIndex = array(
				FestivalDef::ID => $csvIndex,
				FestivalDef::ACT_TYPE => $csvIndex+=2,
				FestivalDef::FORMULA_NUM => $csvIndex+=6,
				FestivalDef::EXTRA_DROP => ++$csvIndex,
				FestivalDef::FORMULA => ++$csvIndex,
		);

		$firstFormula = $confIndex[FestivalDef::FORMULA];

		$confList  = array();

		foreach ( $arrData as $data )
		{
			if ( empty($data) || empty($data[0]) )
			{
				break;
			}
				
			if ( !isset( $data[$confIndex[FestivalDef::ACT_TYPE]] ) )
			{
				throw new ConfigException('no act type %d.',$data[$confIndex[FestivalDef::ACT_TYPE]]);
			}
				
			$conf = array();
			
			foreach ( $confIndex as $key => $index )
			{
				switch ($key)
				{
					case FestivalDef::EXTRA_DROP:
						$conf[$key] = array();
						$arrExtraDropConf = Util::str2Array($data[$index],',');
						foreach ( $arrExtraDropConf as $extraDropConf )
						{
							$exDropInfo = array_map('intval', Util::str2Array($extraDropConf,'|'));
							$conf[$key][$exDropInfo[0]] = $exDropInfo[1];
						}
						break;
					case FestivalDef::FORMULA:
						$conf[$key] = array();
							
						$formulaNum = intval($data[$confIndex[FestivalDef::FORMULA_NUM]]);
						$eachFormula = FestivalDef::EACH_FORMULA;
							
						for ( $i = $firstFormula; $i < $firstFormula + $eachFormula * $formulaNum; $i+= $eachFormula)
						{
							$need = array();
							$target = array();

							$reqConf = Util::str2Array($data[$i],',');
							$acqConf = Util::str2Array($data[$i+1],',');
							$max = intval( $data[$i+2] );

							foreach ( $reqConf as $req )
							{
								$need[] = array_map('intval', Util::str2Array($req,'|'));  
							}
							foreach ( $acqConf as $acq )
							{
								$target[] = array_map('intval', Util::str2Array($acq,'|'));
							}

							$conf[$key][] = array(
									'req' => $need,
									'acq' => $target,
									'maxNum' => $max
							);
						}
						break;
					default:
						$conf[$key] = intval($data[$index]);
				}
			}
		}
		
		unset($conf[FestivalDef::ID]);
		unset($conf[FestivalDef::FORMULA_NUM]);
		
		return $conf;
	}
	
	public static function getFestival($type)
	{
		$dropId = 0;
		
		if ( FALSE == EnActivity::isOpen(ActivityName::FESTIVAL) )
		{
			return $dropId;
		}
		
		if (FALSE == FestivalLogic::isDropTime())
		{
			return $dropId;
		}
		
		$conf = EnActivity::getConfByName(ActivityName::FESTIVAL);
		$arrExtraDrop = $conf['data'][FestivalDef::EXTRA_DROP];
		
		$dropId = $arrExtraDrop[$type];
		
		return $dropId;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */