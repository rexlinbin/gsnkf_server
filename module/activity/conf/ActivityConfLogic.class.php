<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: ActivityConfLogic.class.php 248345 2016-06-27 09:38:36Z BaoguoMeng $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/activity/conf/ActivityConfLogic.class.php $
 * @author $Author: BaoguoMeng $(wuqilin@babeltime.com)
 * @date $Date: 2016-06-27 09:38:36 +0000 (Mon, 27 Jun 2016) $
 * @version $Revision: 248345 $
 * @brief 
 *  
 **/


/**
 * 关键性问题
 * 1）问题：如何触发更新配置
 * 	   方案：每次往mem中存数据时，加上一个有效期（当前时间的一个小时后）。当从mem获取数据时如果发现有效期到了，就触发一次更新。
 * 		而且，只有当前端获取数据时，才会触发更新
 *
 * 2）问题：同时有多处触发更新时，只有一个人在干活。
 *	    方案：将请求转到uid:0连接中同步执行。第一个人干完之后，后面的人能从mem中的数据判断出数据已经更新
 *
 * 3）问题：后端所用配置和前端的不一致
 * 	    方案：在session中记录用户当前使用的主干版本号（即所有配置的最大版本号），后端使用配置时直接根据根据session中的版本号取配置。
 * 			当前端获取配置时，返回最新配置，并更新session中的版本号
 *
 * 4）问题：（上面问题的衍生问题）如果一个请求被转到某个虚连接（如：uid=0）中执行，该虚连接的session中没有版本号记录
 * 	   方案：框架不支持，业务模块转出请求时，自己带版本号；或者直接使用最新配置，这种情况下及时前后端数据不一致也不会带来额外的影响（突然更新配置对业务的影响不在考虑范围内）
 *
 * 5）问题：如何处理增/删一个活动配置
 *   方案：不支持在线直接增/删某个活动的配置； 可以通过修改开始/结束时间把某个活动打开/关闭
 *
 *
 *
 * 在mem中的数据
 *
 * 	给后端使用的数据:每个活动有一个对应的key
 * 	 name1=>arary
 * 			{
 * 				version:int  	当前主干版本
 * 				start_time:int
 * 				end_time:int
 * 				need_open_time:int
 * 				data:array()
 * 			}
 *
 * 	 name2=>arary{...}
 *
 *  给前端的数据：
 *   config_front => array
 *   		{
 *   			validity:int  有效期
 *   			version:int 	当前的主干版本
 *   			arrData=>array
 *   			{
 *   				name=> array		//以配置名字为key
 *   				{
 *   					version:int		//此配置的版本号
 *   					start_time:int
 *   					end_time:int
 *   					need_open_time:int	
 *   					data:string		//配置文件内容
 *   				}
 *   			}
 *   		}
 *
 *
 * @author wuqilin
 *
 */

class ActivityConfLogic
{
	//当对应的配置找不到时，返回此数据。即活动未开启
	public  static $NULL_CONF_BACKEND = array(
				'version' => 0,
				'start_time' => 0,
				'end_time' => 0,
				'need_open_time' => 0,
				'data' => array(),
			);
	
	public static $NULL_CONF_FRONT = array(
				'validity' => 0,
				'version' => 0,
				'arrData' => array(),
			);
	
	
	public static $NULL_CONF_FRONT_ONE = array(
				'version' => 0,
				'start_time' => 0,
				'end_time' => 0,
				'need_open_time' => 0,
				'data' => '',
			);
	
	public static function getTrunkVersion()
	{
		$key = self::genMcKey4Front();
		$conf = McClient::get($key);
		
		if( empty($conf) )
		{
			return ActivityConfDao::getTrunkVersion();
		}
		return $conf['version'];
	}

	public static function getConf4Front()
	{
		$now = Util::getTime();
		
		$key = self::genMcKey4Front();
		$conf = McClient::get($key);

		//mc中没有数据时从db中获取数据，设置一下mem
		if( empty($conf) )
		{			
			$ret = self::updateMem();
			if( empty($ret[$key]) )
			{
				//db中没有配置时，会返回一个默认配置。所以理论上不会走到这里
				Logger::fatal('get front conf failed. maybe no any activity');
				return self::$NULL_CONF_FRONT;				
			}
			$conf = $ret[$key];
		}
				
		$version = $conf['version'];
		//如果有效期到了，就触发一下更新
		if( $conf['validity'] < $now )
		{			
			Logger::info('trigger refresh by front. curVersion:%d', $version);
			self::refreshConf($version);
		}

		Logger::trace('getConf4Front. version:%d, conf:%s',  $version, $conf);
		return $conf;
	}
	

	
	/**
	 * 获取指定版本的配置。
	 * @param string $name
	 * @param int $version
	 * @return array
	 */
	public static function getConf4Backend($name, $version)
	{
		/**
			一般情况下，mem中的配置就是所需要的配置。
			如果刚好遇到配置更新，mem中的配置版本会比要求的大
			如果发现需要的配置版本比mem中的大，就有错了
			
			如果mem中没有找到所需要的配置，就只好去数据库中取了
		 */
		$key = self::genMcKey($name);
		$conf = McClient::get($key);
		if( empty($conf) )
		{
			$ret = self::updateMem();
			if( empty($ret[$key]) )
			{
				//db中没有配置时，会返回一个默认配置。所以理论上不会走到这里
				Logger::fatal('get conf:%s failed. maybe not config it', $name);
				return self::$NULL_CONF_BACKEND;
			}
			$conf = $ret[$key];
		}
		
		if( $conf['version'] < $version)
		{
			Logger::fatal('version in mem:%d < need version:%d', $conf['version'], $version);
			return self::$NULL_CONF_BACKEND;
		}
		
		if( $version > 0 && $conf['version'] >  $version)
		{
			//这种情况出现在：刚好更新配置时，前端发了一个请求过来。
			Logger::fatal('need an old version:%d, curVersion:%d', $version, $conf['version']);
			/* 前后端版本保持一致的功能先关掉
			$conf = ActivityConfDao::getByNameAndVersion($name, $version, ActivityDef::$ARR_CONF_FIELD);
			if( empty($conf) )
			{
				Logger::fatal('not found version:%d of config:%s', version, $name);
				return array();
			}
			*/
		}
		
		Logger::trace('getConfByNameAndVersion. name:%s, version:%d, conf:%s', $name, $version, $conf);
		return $conf;		
	}


	public static function updateMem($arrConf = NULL)
	{
		if(empty($arrConf))
		{
			$arrConf = self::getAllConfInGame();
		}
				
		//mem中的记录主干版本号（即所有配置中最大的版本号）
		$trunkVersion = 0;
		foreach($arrConf as $conf)
		{
			if($trunkVersion < $conf['version'])
			{
				$trunkVersion = $conf['version'];
			}
		}
		
		$validity = Util::getTime() + ActivityConf::VALIDITY + rand(0, ActivityConf::VALIDITY_RAND);
		
		//前端数据中，所有的配置都在一起。外层记录一个主干版本号，每个配置中也记录了一个自己的版本号
		$frontKey = self::genMcKey4Front();
		$dataInMem = array(
				$frontKey => array(
						'version' => $trunkVersion,					
						'validity' => $validity,
						'arrData' => array(),
						),
				);
		
		foreach($arrConf as $conf)
		{
			$dataInMem[ $frontKey ][ 'arrData' ][$conf['name']] = array(
					'version' => $conf['version'],
					'start_time' => $conf['start_time'],
					'end_time' => $conf['end_time'],	
					'need_open_time' => $conf['need_open_time'],				
					'data' => $conf['str_data'],
					);
			
			//后端数据是分散开的， 每个配置都记录当前主干版本号
			$dataInMem[ self::genMcKey($conf['name']) ] = array(
					//'validity' => $validity,
					'version' => $trunkVersion,
					'start_time' => $conf['start_time'],
					'end_time' => $conf['end_time'],
					'need_open_time' => $conf['need_open_time'],
					'data' => $conf['va_data'],
					);
		}
		
		$frontConf = $dataInMem[$frontKey];
		unset($dataInMem[$frontKey]);
		$dataInMem[$frontKey] = $frontConf;
	
		foreach($dataInMem as $key => $value )
		{
			$ret = McClient::set($key, $value);
			if($ret != 'STORED')
			{
				Logger::fatal('mcset failed. ret:%s', $ret);
			}
			
		}		
		Logger::info('set conf data to mem. version:%d, arrName:%s ', 
					$trunkVersion, array_keys($dataInMem));
		
		return $dataInMem;
	}
	
	public static function addValidity($key, $value, $delt)
	{
		if(empty($value))
		{
			Logger::warning('addValidity failed');
			return ;
		}
		
		$value['validity'] = Util::getTime() + $delt;		
		$ret = McClient::set($key, $value);
		if($ret != 'STORED')
		{
			Logger::fatal('addValidity failed');
		}		
		Logger::info('addValidity. delt:%d, now:%d', $delt, $value['validity']);
	}
	
	public static function refreshConf($version, $force = false)
	{
		RPCContext::getInstance()->executeTask(0, 'activity.doRefreshConf', array($version, $force), false);
	}
	
	/**
	 * 
	 * @param int $version	触发更新时服务器的版本号
	 * @param bool $force 是否强制刷新
	 * 
	 * FIXME 现在这个函数，的返回值在不情况，返回的结构不一致
	 */
	public static function doRefreshConf($version, $force, $brodcast = true)
	{
		//version=0时说明db中都没有数据。是在初始化
		Logger::trace('doRefreshConf on version:%d, force:%d', $version, $force);
		
		//1. 如果已经更新完，就直接返回
		$mcKey = self::genMcKey4Front();

		$frontDataInMem = McClient::get($mcKey);
		if( !empty($frontDataInMem) 
				&& $frontDataInMem['version'] >= $version 
				&& $frontDataInMem['validity'] > Util::getTime()
				&& !$force)
		{
			Logger::info('already update to %d. validity:%d,  ignore:%d', 
						$frontDataInMem['version'], $frontDataInMem['validity'], $version);
			return $frontDataInMem;
		}			
	
				
		//2. 从平台取最新数据  
		//$arrNewConf = static::getConfFromPlatform($version);
		$arrNewConf = array();
		if( empty( $arrNewConf ))
		{			
			self::addValidity($mcKey, $frontDataInMem, ActivityConf::VALIDITY);
			Logger::info('no new conf. refresh done');
			return array();
		}
		
		//3. 取出当前数据
		$arrCurConf = self::getAllConfInGame(false);
		
		// 获得当前db中存储的最大的version，防止因前端主干版本号偏低，又从平台取到了db中已经有的配置，这样有可能导致解析失败
		// 所以这里在解析配置的时候，先找到db里面的最大版本号，如果发现新配置的版本号<=当前db的最大版本号，则忽略
		$curDbMaxVersion = 0;
		foreach ($arrCurConf as $aCurConf)
		{
			if($curDbMaxVersion < $aCurConf['version'])
			{
				$curDbMaxVersion = $aCurConf['version'];
			}
		}
		
		//4. 解析并检验数据，如果有错，放弃当前版本 。
		$arrNewConf = self::decodeConf($arrNewConf, $curDbMaxVersion);
		if( !empty($arrNewConf) )
		{
			list($arrMergeConf, $arrChangedConf) = self::mergeConf($arrCurConf, $arrNewConf);			
		}
		if(empty($arrNewConf) || empty($arrMergeConf) )
		{
			//到这里，说明平台有数据，但是数据错误			
			if( empty($frontDataInMem) )
			{
				throw new SysException('invalid conf from platform. And no data in mem');
			}
			else
			{
				Logger::fatal('invalid conf from platform. retry later');
				self::addValidity($mcKey, $frontDataInMem, ActivityConf::REFRESH_RETRY_INTERVAL);
				return array();
			}
		}
		
		if(empty($arrChangedConf))
		{
			self::addValidity($mcKey, $frontDataInMem, ActivityConf::VALIDITY);
			Logger::fatal('no change data from platform');
            return array();
		}
		
		foreach($arrChangedConf as $conf)
		{
			ActivityConfDao::insertOrUpdate($conf);
		}
		
		$ret = self::updateMem($arrMergeConf);
		
		if( $brodcast )
		{
			RPCContext::getInstance ()->broadcast ( ActivityDef::FRONT_CALLBACK_UPDATE, array( $ret[$mcKey]['version'] ) );
		}

		Logger::info('doRefreshConf done. oldVersion:%d, newVersion:%d', $version, $ret[$mcKey]['version']);
		
		return $ret;
	}
			
	

	public static function decodeConf($arrConf, $dealVersion = 0)
	{
		$arrRet = array();
		foreach($arrConf as $conf)
		{
			$name = $conf['name'];
			if( !isset(ActivityConf::$ARR_READ_CONF_FUNC[$name] ) )
			{
				Logger::fatal('invalid conf:%s', $name);
				continue;
			}
			
			if ($conf['version'] < $dealVersion) 
			{
				Logger::warning('activity name:%s, version:%d, deal version:%d, ignore', $name, $conf['version'], $dealVersion);
				continue;
			}
			
			try 
			{
				if( isset($arrRet[$name]) && $arrRet[$name]['version'] > $conf['version'])
				{
					Logger::warning('platform return multi conf for %s', $name);
					continue;
				}
				$arrLine = explode("\n", $conf['data']);
				$arrCsv = array();
				$strData = '';
				foreach($arrLine as $line)
				{					
					$ret = str_getcsv($line);
					//此处是想过滤调头两行，和后面的空行。依赖第一列是数字
					if(empty($ret[0]) || !is_numeric($ret[0]))
					{
						Logger::info('ignore one line. name:%s, line:%s', $name, $line);
						continue;
					}
					$strData = $strData.$line."\n";
					$arrCsv[] = $ret;
				}
		
				$ret = call_user_func(ActivityConf::$ARR_READ_CONF_FUNC[$name], 
						$arrCsv, $conf['version'], $conf['start_time'], $conf['end_time'], $conf['need_open_time']);
				if(empty($ret))
				{
					Logger::fatal('decode conf:%s failed:%s', $name, $arrCsv);
					return array();
				}
				Logger::debug('decode reuslt. %s:%s', $name, $ret);
				
				$arrRet[$name] = array(
						'name' => $conf['name'],
						'version' => $conf['version'],
						'start_time' => $conf['start_time'],
						'end_time' => $conf['end_time'],
						'need_open_time' => $conf['need_open_time'],
						'str_data' => $strData,
						'va_data' =>$ret,
						);
			}
			catch (Exception $e)
			{
				Logger::fatal('decode conf:%s failed:%s', $name, $e->getMessage());
				return array();
			}
		}
		
		return $arrRet;
	}
	
	public static function mergeConf($arrCurConf, $arrNewConf)
	{
		Logger::debug('curConf:%s, newConf:%s', $arrCurConf, $arrNewConf);
		
		$now = Util::getTime(); 
		$arrMergeConf = $arrCurConf;
		$arrChangedConf = array();
		foreach($arrNewConf as $name => $newConf)
		{
			if( !isset($arrCurConf[$name]) )
			{
				Logger::trace('add a new conf:%s', $name);
				$arrMergeConf[$name] = $newConf;
				$arrChangedConf[$name] = $newConf;
				continue;
			}
			
			$curConf = $arrCurConf[$name];
			
			//某些活动，在活动时间内，不能改配置
			if( in_array($name, ActivityConf::$ARR_CANT_CHANGE_WHEN_OPEN) && !FrameworkConfig::DEBUG )
			{
				if( $curConf['start_time'] - ActivityConf::VALIDITY <= $now
						&& $now <= $curConf['end_time'])
				{
					Logger::fatal('conf:%s in active period. cant change it', $name);
					return array( array(), array() );
				}
			}
			
			
			if($curConf != $newConf)
			{
				//配置有差异，但版本号没有改变，这是不允许的
				if( $curConf['version'] == $newConf['version'])
				{
					Logger::fatal('different conf with same version. name:%s, version:%d', $name, $curConf['version']);
					return array( array(), array() );
				}
				$arrChangedConf[$name] = $newConf;
				$arrMergeConf[$name] = $newConf;
			}			
			else
			{
				Logger::debug('conf:%s no change. version:%d', $name, $curConf['version']);
			}
		}
				
		Logger::debug('mergerConf:%s, change:%s', $arrMergeConf, $arrChangedConf);
		
		return array( $arrMergeConf, $arrChangedConf );
	}
	
	/**
	 * 获取本服所有配置。如果db内没有某个活动的配置。自动给一个默认配置。默认配置的活动处在未开启状态，活动配置内容为空
	 */
	public static function getAllConfInGame($refreshWhenEmpty = true)
	{
		$arrAllConfName = self::getAllConfName();
		$arrConf = ActivityConfDao::getArrCurConf($arrAllConfName, ActivityDef::$ARR_CONF_FIELD);
		if(empty($arrConf) && $refreshWhenEmpty)
		{
			//开新服时，如果数据库中没有数据，第一拉取的人，获得的数据可能是有问题的
			self::refreshConf(0, true);
			Logger::warning('no conf data in db');
		}
		
		foreach( $arrAllConfName as $name )
		{
			if( !isset($arrConf[$name]) )
			{
				$arrConf[$name] = array(
					'name' 				=> 	$name,
					'version' 			=> 0,
					'start_time' 		=> 0,
					'end_time'			=> 0,
					'need_open_time' 	=> 0,
					'str_data' 			=> '',
					'va_data' 			=> array(),	
				);
				Logger::info('no conf:%s in db. return default conf', $name);
			}
		}
		
		return $arrConf;
	}
	
	/**
	 * 这个arrConf和上边的arrConf不一样，data和va_vata的差距
	 * @param unknown $arrConf
	 * @return unknown
	 */
	public static function getRealConf($name,$conf)
	{
		$curTime = util::getTime();
		//注意，（要取上次配置的）某些活动，是不能支持多条配置的，现在没有TODO
		if ( !in_array( $name , ActivityConf::$MULCONF_ACTIVITY) ) 
		{
			return $conf;
		}
	
		$vaData = $conf['data'];
		usort($vaData, array('ActivityConfLogic','mySort'));
		Logger::debug('after sorted: %s',$vaData);
		$reCkeckEndTime = 0;
		foreach ( $vaData as $index => $oneVaData )
		{
			if (
			isset($oneVaData[ActivityDef::BEGIN_TIME] )
			&&isset($oneVaData[ActivityDef::END_TIME])
			&&isset($oneVaData[ActivityDef::NEED_OPEN_TIME])
			)
			{
				if($oneVaData[ActivityDef::END_TIME] <= $reCkeckEndTime)
				{
					throw new ConfigException( 'endtime err, last one is:%d nextOne is: %d',$reCkeckEndTime, $oneVaData[ActivityDef::END_TIME]);
				}
				$reCkeckEndTime = $oneVaData[ActivityDef::END_TIME];
		
				$nextBeginTime = strtotime($oneVaData[ActivityDef::BEGIN_TIME]);
				$nextEndTime = strtotime( $oneVaData[ActivityDef::END_TIME]);
				$needOpenTime = strtotime( $oneVaData[ActivityDef::NEED_OPEN_TIME]);
		
				$conf['start_time'] = $nextBeginTime;
				$conf['end_time'] = $nextEndTime;
				$conf['need_open_time'] = $needOpenTime;
				unset($vaData[$index][ActivityDef::BEGIN_TIME]);
				unset( $vaData[$index][ActivityDef::END_TIME]);
				unset( $vaData[$index][ActivityDef::NEED_OPEN_TIME]);
				$conf['data'] = array($vaData[$index]);
		
				//================================新服与sess刷新的冲突
				if( ActivityNSLogic::inNormalActivity( $conf['start_time'] ) )
				{
					if ( $curTime >= $nextBeginTime&&$curTime <= $nextEndTime )
					{
						break;
					}
					elseif ($curTime < $nextBeginTime)
					{
						break;
					}
					else
					{
						Logger::trace('gonna get next');
					}
				}
				else 
				{
					$conf = ActivityConfLogic::$NULL_CONF_BACKEND;
				}
				//================================新服与sess刷新的冲突
				
			}
		}				
		return $conf;
	}
	
	public static function isWholeServerActivity($name,$conf)
	{
		//返回给前后端的全服活动配置都不支持多条配置
		if( 
		!isset( ActivityConf::$NS_ACTIVITY[$name]) 
		&& $conf['start_time'] <= $conf['need_open_time'] 
		&& !in_array($name,  ActivityConf::$MULCONF_ACTIVITY)
		&& $conf['start_time'] >0 
		&& $conf['need_open_time'] >0 )
		{
			return true;
		}
		
		return false;
		
	}
	
	public static function getRealConfForFront($name, $conf)
	{
		//对于前端的getReal，外层判定了不是全服活动，并且不是处于新服期间才会走到这里
		
		if(!in_array( $name , ActivityConf::$MULCONF_ACTIVITY) )
		{
			if(ActivityNSLogic::inNormalActivity($conf['start_time']))
			{
				//这个活动对于这个服的开服时间是有效的
				return $conf;
			}
			else 
			{
				$conf['start_time'] = 0;
				$conf['end_time'] =0;
				$conf['data'] = '';//省一点，needopentime就不改了 还可以参考一下
				Logger::debug('fake one name %d', $name);
				return $conf;
			}
		}
		
		if( ActivityNSLogic::inNormalActivity($conf['start_time']) )
		{
			return $conf;
		}
		
		//否则的话重新解析出来，删掉不符合要求的之后返回去
		$strArr = explode( "\n", $conf['data']);
		$realStr = '';
		//依赖于多条配置文件的每一天的第2,3,4个字段(下标也就是 1,2,3)分别是开始时间，结束时间，服务器开启时间要求,解析之后的字段名字也要是一样的
		Logger::debug('str arr are: %s', $strArr);
		foreach ( $strArr as $index => $oneStr )
		{
			//不用管兼容了
			if( isset( $oneStr[1] ) )
			{
				$arr = str_getcsv($oneStr);
				$startTime = strtotime( $arr[1] );
				Logger::debug('str is : %s',$arr );
				if( ActivityNSLogic::inNormalActivity($startTime) )
				{
					Logger::debug('index: %d is normal activity', $index);
					$realStr .= $oneStr."\n";
				}
			}
			
		}
		
		if( empty( $realStr ) )
		{
			$conf['start_time'] = 0;
			$conf['end_time'] =0;
		}
		
		$conf['data'] = $realStr;
		
		return $conf;
		
	}
	
	public static function mySort($a,$b)
	{
		$sortKey = ActivityDef::BEGIN_TIME;
		if ($a[$sortKey] == $b[$sortKey]) 
		{
			throw new ConfigException( 'two conf same startTime' );
		}
		
		return ($a[$sortKey] > $b[$sortKey]) ? 1 : -1;
	}
	
	public static function getConfFromPlatform($version)
	{
		$platfrom = ApiManager::getApi ();
		
		//20140813 原本是根据serverId获取活动配置，但是在跨服机器上没有serverId。所以加了一个参数platName
		$serverKey = 0;
		$serverIdInSess = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_SERVER_ID );
		$group =  RPCContext::getInstance ()->getFramework ()->getGroup ();
		if( !empty( $serverIdInSess ) || !empty( $group )  )
		{
			$serverKey = Util::getServerId();
		}
		$argv = array (
					'serverKey' => $serverKey,
					'platName' => PlatformConfig::PLAT_NAME,
					'ip' => RPCContext::getInstance ()->getFramework ()->getServerIp (),
					'version' => $version	
				 );
		
		$trynum = 0;
		while (true)
		{
			try
			{
				$arrNewConf = $platfrom->users ( 'getActivityData', $argv );
				break;
			}
			catch ( Exception $e )
			{
				Logger::fatal('try get activity from plat failed in times:%s', $trynum);
				if( !empty( $group ) || $trynum >= 2 )
				{ 
					throw new SysException('try get activity from plat failed finally,message:%s', $e);
					break;
				}
				sleep( 1 );
			}
			$trynum++;
		}
		
		if(!is_array($arrNewConf))
		{
			Logger::fatal('get data from platform failed:%s', $arrNewConf);
			return array();
		}
		
		Logger::info('get data from platform:%s', $arrNewConf);
		return $arrNewConf;		
	}
	
	public static function getAllConfName()
	{
		return array_keys(ActivityConf::$ARR_READ_CONF_FUNC);
	}

	public static function genMcKey4Front()
	{
		return ActivityDef::MC_KEY_FRONT;
	}
	
	public static function genMcKey($name)
	{
		return ActivityDef::MC_KEY_PRE.'.'.$name;
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */