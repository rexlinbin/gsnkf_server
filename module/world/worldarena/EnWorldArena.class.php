<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: EnWorldArena.class.php 244613 2016-05-30 06:49:52Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/worldarena/EnWorldArena.class.php $
 * @author $Author: BaoguoMeng $(mengbaoguo@babeltime.com)
 * @date $Date: 2016-05-30 06:49:52 +0000 (Mon, 30 May 2016) $
 * @version $Revision: 244613 $
 * @brief 
 *  
 **/
 
class EnWorldArena
{
	/**
	 * 读取跨服竞技场活动配置
	 *
	 * @param array $arrData
	 * @return array
	 */
	public static function readWorldArenaCsv($arrData)
	{
		$incre = 0;
		$tag = array
		(
				'id' => $incre++,
				'timeConfig' => $incre++,
				'need_level' => $incre++,
				'update_cold_time' => $incre++,
				'protect_time' => $incre++,
				'free_atk_num' => $incre++,
				'buy_cost' => $incre++,
				'gold_reset_cost' => $incre++,
				'silver_reset_cost' => $incre++,
				'win_reward' => $incre++,
				'desc' => $incre++,
				'pos_rank_reward' => $incre++,
				'kill_rank_reward' => $incre++,
				'conti_rank_reward' => $incre++,
				'conti_reward' => $incre++,
				'termianl_conti_reward' => $incre++,
				'room_user_count' => $incre++,
				'room_min_user_count' => $incre++,
				'target_coef' => $incre++,
				'need_open_days' => $incre++,
				'lose_reward' => $incre++,
				'attack_cd' => $incre++,
				'king_reward' => $incre++,
		);

		$conf = array();
		foreach ($arrData as $data)
		{
			if (empty($data) || empty($data[0]))
			{
				break;
			}
				
			// id
			$id = intval($data[$tag['id']]);
			$conf['id'] = $id;
			
			// 时间配置
			$arrTimeConfig = explode(',', $data[$tag['timeConfig']]);
			if (count($arrTimeConfig) != 4) 
			{
				throw new ConfigException('time config count is error, config[%s]', $arrTimeConfig);
			}
			$index = 0;
			for ($i = 0; $i < count($arrTimeConfig); ++$i)
			{
				$detail = array_map('intval', explode('|', $arrTimeConfig[$i]));
				if (count($detail) != 2) 
				{
					throw new ConfigException('time config count is error, config[%s]', $arrTimeConfig);
				}
				$detail[1] = sprintf('%06d', $detail[1]);
				$detail[1] = strtotime(date('Ymd', time()) . $detail[1]) - strtotime(date('Ymd', time()) . '000000');
				$second = $detail[0] * 86400 + $detail[1];
				if ($second >= 7 * 86400) 
				{
					throw new ConfigException('time exceed 7 * 86400, config[%s]', $arrTimeConfig);
				}
				$conf['timeConfig'][] = $second;
			}
			
			// 报名需要的等级
			$needLevel = intval($data[$tag['need_level']]);
			$conf['need_level'] = $needLevel;
			
			// 更新冷却时间
			$updateColdTime = intval($data[$tag['update_cold_time']]);
			$conf['update_cold_time'] = $updateColdTime;
			
			// 保护时间
			$protectTime = intval($data[$tag['protect_time']]);
			$conf['protect_time'] = $protectTime;
			
			// 免费的攻击次数
			$freeAtkNum = intval($data[$tag['free_atk_num']]);
			$conf['free_atk_num'] = $freeAtkNum;
			
			// 购买次数花费
			$arrCost = explode(',', $data[$tag['buy_cost']]);
			foreach ($arrCost as $aCost)
			{
				$detail = array_map('intval', explode('|', $aCost));
				if (count($detail) != 2)
				{
					throw new ConfigException('cost count is error, config[%s]', $arrCost);
				}
				$conf['buy_cost'][$detail[0]] = $detail[1]; 
			}
			
			// 金币重置次数
			$arrCost = explode(',', $data[$tag['gold_reset_cost']]);
			foreach ($arrCost as $aCost)
			{
				$detail = array_map('intval', explode('|', $aCost));
				if (count($detail) != 2)
				{
					throw new ConfigException('cost count is error, config[%s]', $arrCost);
				}
				$conf['gold_reset_cost'][$detail[0]] = $detail[1];
			}
			
			// 银币重置次数
			$arrCost = explode(',', $data[$tag['silver_reset_cost']]);
			foreach ($arrCost as $aCost)
			{
				$detail = array_map('intval', explode('|', $aCost));
				if (count($detail) != 2)
				{
					throw new ConfigException('cost count is error, config[%s]', $arrCost);
				}
				$conf['silver_reset_cost'][$detail[0]] = $detail[1];
			}
			
			// 击杀奖励
			$arrReward = explode(',', $data[$tag['win_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = array_map('intval', explode('|', $aReward));
				if (count($detail) != 3)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$conf['win_reward'][] = $detail;
			}
			
			// 位置排名奖励
			$arrReward = explode(';', $data[$tag['pos_rank_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = explode(',', $aReward);
				if (count($detail) < 2)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$rank = intval($detail[0]);
				$reward = array();
				for ($i = 1; $i < count($detail); ++$i)
				{
					$reward[] = array_map('intval', explode('|', $detail[$i]));
				}
				$conf['pos_rank_reward'][$rank] = $reward;
			}
			
			// 击杀排名奖励
			$arrReward = explode(';', $data[$tag['kill_rank_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = explode(',', $aReward);
				if (count($detail) < 2)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$rank = intval($detail[0]);
				$reward = array();
				for ($i = 1; $i < count($detail); ++$i)
				{
					$reward[] = array_map('intval', explode('|', $detail[$i]));
				}
				$conf['kill_rank_reward'][$rank] = $reward;
			}
			
			// 连杀排名奖励
			$arrReward = explode(';', $data[$tag['conti_rank_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = explode(',', $aReward);
				if (count($detail) < 2)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$rank = intval($detail[0]);
				$reward = array();
				for ($i = 1; $i < count($detail); ++$i)
				{
					$reward[] = array_map('intval', explode('|', $detail[$i]));
				}
				$conf['conti_rank_reward'][$rank] = $reward;
			}
			
			// 连杀奖励
			$arrReward = explode(';', $data[$tag['conti_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = explode(',', $aReward);
				if (count($detail) < 2)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$rank = intval($detail[0]);
				$reward = array();
				for ($i = 1; $i < count($detail); ++$i)
				{
					$reward[] = array_map('intval', explode('|', $detail[$i]));
				}
				$conf['conti_reward'][$rank] = $reward;
			}
			
			// 终结连杀奖励
			$arrReward = explode(';', $data[$tag['termianl_conti_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = explode(',', $aReward);
				if (count($detail) < 2)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$rank = intval($detail[0]);
				$reward = array();
				for ($i = 1; $i < count($detail); ++$i)
				{
					$reward[] = array_map('intval', explode('|', $detail[$i]));
				}
				$conf['termianl_conti_reward'][$rank] = $reward;
			}
			
			// 房间期望人数
			$roomUserCount = intval($data[$tag['room_user_count']]);
			$conf['room_user_count'] = $roomUserCount;
			
			// 房间最少人数
			$roomMinUserCount = intval($data[$tag['room_min_user_count']]);
			$conf['room_min_user_count'] = $roomMinUserCount;
			
			// 判断房间配置的有效性
			if (empty($roomUserCount) || empty($roomMinUserCount) || $roomMinUserCount >= $roomUserCount) 
			{
				throw new ConfigException('invalid room user count[%d] or room min count[%d]', $roomUserCount, $roomMinUserCount);
			}
			
			// 目标系数
			$targetCoef = intval($data[$tag['target_coef']]);
			$conf['target_coef'] = $targetCoef;
			
			// 自动分组时候，服务器需要开启的天数
			$needOpenDays = intval($data[$tag['need_open_days']]);
			$conf['need_open_days'] = $needOpenDays;
			
			// 失败奖励
			$arrReward = explode(',', $data[$tag['lose_reward']]);
			foreach ($arrReward as $aReward)
			{
				$detail = array_map('intval', explode('|', $aReward));
				if (count($detail) != 3)
				{
					throw new ConfigException('reward count is error, config[%s]', $detail);
				}
				$conf['lose_reward'][] = $detail;
			}
			
			// 挑战cd 20151105添加，兼容老版本
			if (empty($data[$tag['attack_cd']])) 
			{
				$conf['attack_cd'] = 0;
			}
			else 
			{
				$conf['attack_cd'] = intval($data[$tag['attack_cd']]);
			}
			
			// 击杀奖励  20160530添加，兼容老版本
			if (empty($data[$tag['king_reward']])) 
			{
				$conf['king_reward'] = array();
			}
			else 
			{
				$arrReward = explode(',', $data[$tag['king_reward']]);
				foreach ($arrReward as $aReward)
				{
					$detail = array_map('intval', explode('|', $aReward));
					if (count($detail) != 3)
					{
						throw new ConfigException('reward count is error, config[%s]', $detail);
					}
					$conf['king_reward'][] = $detail;
				}
			}

			break;
		}
		return $conf;
	}
}


/*$csvFile = './script/world_arena.csv';
$file = fopen($csvFile, 'r');
if (FALSE == $file)
{
	echo $argv[1] . "{$csvFile} open failed! exit!\n";
	exit;
}

$arrCsv = array();
fgetcsv($file);
fgetcsv($file);
while (TRUE)
{
	$data = fgetcsv($file);
	if (empty($data))
		break;
	$arrCsv[] = $data;
}

$ret = EnWorldArena::readWorldArenaCsv($arrCsv);
var_dump($ret);*/

/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */