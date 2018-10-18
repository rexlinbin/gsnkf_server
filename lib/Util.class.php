<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: Util.class.php 255510 2016-08-11 02:33:28Z BaoguoMeng $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/Util.class.php $
 * @author $Author: BaoguoMeng $(hoping@babeltime.com)
 * @date $Date: 2016-08-11 02:33:28 +0000 (Thu, 11 Aug 2016) $
 * @version $Revision: 255510 $
 * @brief
 *
 **/

class Util
{

    /**
     * 检查参数
     * @param string $method for example:Hero::sell
     * @param array $args
     */    
    public static function checkParam($method,$args)
    {
        Logger::trace('checkParam method %s args %s.',$method,$args);
        if(empty($args))
        {
            return $args;
        }
        foreach($args as $index => $value)
        {
            if(!isset(ParamCheckConf::$ARR_METHOD[$method][$index]))
            {
                $checkType = ParamCheckConf::CHECKTYPE_INT_LARGGER_THAN_ZERO;
            }
            else
            {
                $checkType = ParamCheckConf::$ARR_METHOD[$method][$index];
            }
            $errorType = 0;
            switch($checkType)
            {
                case ParamCheckConf::CHECKTYPE_INT_LARGGER_THAN_ZERO:
                    if((intval($value) <= 0))
                    {
                        $errorType = $checkType;
                    }
                    $args[$index] = intval($value);
                    break;
                case ParamCheckConf::CHECKTYPE_INT_NOT_LESS_THAN_ZERO:
                    if((intval($value) < 0))
                    {
                        $errorType = $checkType;
                    }
                    $args[$index] = intval($value);
                    break;
                case ParamCheckConf::CHECKTYPE_ARRAY_NOT_NULL:
                    if(!is_array($value) || (empty($value)))
                    {
                        $errorType = $checkType;
                    }
                    break;
                case ParamCheckConf::CHECKTYPE_ARRAY_CAN_NULL:
                    if(!is_array($value))
                    {
                        $errorType = $checkType;
                    }
                    break;
                case ParamCheckConf::CHECKTYPE_STRING:
                    if(!is_string($value))
                    {
                        $errorType = $checkType;
                    }
                    break;
                default:
                    throw new FakeException('no such check type %s.',$checkType);
            }
            if(!empty($errorType))
            {
                throw new FakeException('check param for %s error index %s value %s error type is %s.',$method,$index,$value,$errorType);
            }
        }
        return $args;
    }
	/**
	 * 将以Object格式存储的array转换成相应格式
	 * @param mixed $data
	 */
	static function object2array($data, $preseve = true)
	{

		if (is_object ( $data ))
		{
			if ($data instanceof Object)
			{

				$clazz = get_class ( $data );
				$data = $data->getData ();
				if ($preseve)
				{
					$data = array ('__class' => $clazz,
							'__data' => self::object2array ( $data, $preseve ) );
				}
				else
				{
					$data = self::object2array ( $data, $preseve );
				}
			}
			else
			{
				throw new Exception ( "unsupported object type" );
			}
		}
		else if (is_array ( $data ))
		{
			foreach ( $data as $key => $value )
			{
				$data [$key] = self::object2array ( $value, $preseve );
			}
		}
		return $data;
	}

	/**
	 * 将array转换成对象
	 * @param mixed $data
	 * @throws Exception
	 */
	static function array2object($data)
	{

		if (is_object ( $data ))
		{
			throw new Exception ( "object not supported" );
		}
		else if (is_array ( $data ))
		{
			$clazz = null;
			if (isset ( $data ['__class'] ) && isset ( $data ['__data'] ))
			{
				$clazz = $data ['__class'];
				$data = $data ['__data'];
			}
			foreach ( $data as $key => $value )
			{
				$data [$key] = self::array2object ( $value );
			}
			if (! empty ( $clazz ))
			{
				$obj = new $clazz ();
				$obj->setData ( $data );
				$data = $obj;
			}
		}
		return $data;
	}
	
	

	/**
	 * 用于解析csv文件。将数组内的值转成int
	 */
	static function array2Int($array, $reportError = TRUE)
	{
		foreach ( $array as $key => $value )
		{
			if(intval($value) != $value && $reportError)
			{
				Logger::fatal("invalid conf.key:%s, value:%s", $key, $value);
			}
			$array[$key] = intval($value);
		}
		return $array;
	}
	
	
	/**
	 * 用于解析csv文件。将一个字符串按照指定分隔符分割成对应的字符串
	 * @param string $str
	 * @param string $delimiter
	 */
	static function str2Array($str, $delimiter = ',')
	{
		if(  trim($str) == '' )
		{
			return array();
		}
		return explode($delimiter, $str);
	}
	


	

	/**
	 * 将结果按指定的key进行索引
	 * @param array $arrData
	 * @param mixed $keyIndex
	 */
	static function arrayIndex($arrData, $keyIndex)
	{

		$arrRet = array ();
		foreach ( $arrData as $arrRow )
		{
			$arrRet [$arrRow [$keyIndex]] = $arrRow;
		}
		return $arrRet;
	}

	/**
	 * 将结果按指定的key/value进行索引
	 * @param array $arrData
	 * @param mixed $keyIndex
	 * @param mixed $valueIndex
	 */
	static function arrayIndexCol($arrData, $keyIndex, $valueIndex)
	{

		$arrRet = array ();
		foreach ( $arrData as $arrRow )
		{
			$arrRet [$arrRow [$keyIndex]] = $arrRow [$valueIndex];
		}
		return $arrRet;
	}

	/**
	 * 从结果中抽取一列出来形成新array
	 * @param array $arrData
	 * @param mixed $keyIndex
	 */
	static function arrayExtract($arrData, $keyIndex)
	{

		$arrRet = array ();
		foreach ( $arrData as $arrRow )
		{
			$arrRet [] = $arrRow [$keyIndex];
		}
		return $arrRet;
	}
	
	/**
	 * 合并多个数组，后面的覆盖前面的键值，无论是数字索引还是字符串索引
	 * 
	 * @param array $arrays
	 * eg:
	 * {
	 * 		{
	 * 			[1]=>1
	 * 			[2]=>2
	 *      }
	 *      {
	 *      	[1]=>2
	 *      	[2]=>1
	 * 		}
	 * }
	 * @return array $arrRet
	 * eg:
	 * {
	 * 		[1]=>2
	 *      [2]=>1
	 * }
	 */
	static function arrayMerge($arrays)
	{
		$arrRet = array();
		foreach ($arrays as $array)
		{
			foreach ($array as $key => $value)
			{
				$arrRet[$key] = $value;
			}
		}
		return $arrRet;
	}
	
	/**
	 * 二维数组相同键名的值加起来
	 *
	 * @param array $arrays
	 * eg:
	 * {
	 * 		{
	 * 			[1]=>1
	 * 			[2]=>2
	 *      }
	 *      {
	 *      	[2]=>1
	 * 			[3]=>2
	 * 		}
	 * }
	 * @return array $arrRet
	 * eg:
	 * {
	 * 		[1]=>1
	 * 		[2]=>3
	 * 		[3]=>2
	 * }
	 */
	public static function arrayAdd2V($arrays)
	{
		$arrRet = array();
		foreach ($arrays as $array)
		{
			foreach ($array as $key => $value)
			{
				if (!isset($arrRet[$key]))
				{
					$arrRet[$key] = 0;
				}
				$arrRet[$key] += $value;
			}
		}
		return $arrRet;
	}
	
	/**
	 * 三维数组相同键名的值加起来
	 *
	 * @param array $arrays
	 * eg:
	 * {
	 * 		{
	 * 			1=>
	 * 			{
	 * 				[1]=>1
	 * 				[2]=>2
	 *      	}
	 *      	2=>
	 *      	{
	 *      		[11]=>1
	 * 				[22]=>2
	 * 			}
	 * 		}
	 * 		{
	 * 			1=>
	 * 			{
	 * 				[1]=>1
	 * 				[2]=>2
	 *      	}
	 *      	2=>
	 *      	{
	 *      		[11]=>1
	 * 				[22]=>2
	 * 			}
	 * 		}
	 * }
	 * @return array $arrRet
	 * eg:
	 * {
	 * 		1=>
	 * 		{
	 * 			[1]=>2
	 * 			[2]=>4
	 *      }
	 *      2=>
	 *      {
	 *      	[11]=>2
	 * 			[22]=>4
	 * 		}
	 * }
	 */
	public static function arrayAdd3V($arrays)
	{
		$arrRet = array();
		foreach ($arrays as $array)
		{
			foreach ($array as $key => $value)
			{
				if(!isset($arrRet[$key]))
				{
					$arrRet[$key] = array();
				}
				$arrRet[$key] = self::arrayAdd2V(array($arrRet[$key], $value));
			}
		}
		return $arrRet;
	}

	/**
	 *
	 * 得到当前时间
	 */
	static function getTime()
	{

		return RPCContext::getInstance ()->getFramework ()->getRequestTime ();
	}

	/**
	 * 获取当天是星期几
	 */
	static function getTodayWeek()
	{

		// 获取当前时间
		$curTime = self::getTime ();
		// 返回
		return intval ( date ( 'w', $curTime ) );
	}

	/**
	 * 判断两个时间是否是同一天
	 *
	 * @param int $checkTime					需要检查的时刻
	 * @param int $offset						策划们配置的偏移量，哪个时刻算新的 一天
	 */
	static function isSameDay($checkTime, $offset = FrameworkConfig::DAY_OFFSET_SECOND)
	{

		// 获取当前时间
		$referenceTime = self::getTime ();
		// 两者都减去偏移量
		$referenceTime -= $offset;
		$checkTime -= $offset;
		// 如果检查时间小于判定时刻
		if (date ( "Y-m-d ", $checkTime ) === date ( "Y-m-d ", $referenceTime ))
		{
			// 尚未通过这个时刻，返回 TRUE
			return true;
		}
		// 已经通过这个时刻，返回 FALSE
		return false;
	}

	/**
	 * 查看两个时间段之间间隔了多少天
	 *
	 * @param int $checkTime					需要检查的时刻
	 * @param int $offset						策划们配置的偏移量，哪个时刻算新的 一天
	 */
	static function getDaysBetween($checkTime, $offset = FrameworkConfig::DAY_OFFSET_SECOND)
	{
		// 获取当前时间
		$referenceTime = self::getTime ();
		// 两者都减去偏移量
		$referenceTime -= $offset;
		$checkTime -= $offset;
		// 一天的秒数
		$SECONDS_OF_DAY = 86400;

		$ret = intval (
				(strtotime ( date ( "Y-m-d ", $referenceTime ) ) -
						 strtotime ( date ( "Y-m-d ", $checkTime ) )) / $SECONDS_OF_DAY );

		Logger::debug ( "getDaysBetween check time is %d, now is %d, ret is %d.", $checkTime,
				$referenceTime, $ret );
		return $ret;
	}

	/**
	 * 判断两个时间是否在同一周
	 * Enter description here ...
	 * @param unknown_type $checkTime
	 * @param unknown_type $offset
	 */
	static function isSameWeek($checkTime, $offset = FrameworkConfig::WEEK_SECOND)
	{
		$curTime = self::getTime ();
		$SECONDS_OF_WEEK = 604800;

		//这个时间为周日的晚上
		//$s = "1970-3-1 23:59:59";
		$s = "1970-3-2 00:00:00"; //2014.10.23 wuqilin
		$BASE_TIME = strtotime($s);
		//$BASE_TIME = 5155199;
		$checkTime -= ($BASE_TIME + $offset);
		$curTime -= ($BASE_TIME + $offset);

		$checkWeek = intval ( $checkTime / $SECONDS_OF_WEEK );
		$curWeek = intval ( $curTime / $SECONDS_OF_WEEK );

		return $checkWeek == $curWeek;
	}

	/**
	 * 判断两个时间是否是同一个月
	 * Enter description here ...
	 * @param unknown_type $checkTime
	 * @param unknown_type $offset
	 */
	static function isSameMonth($checkTime, $offset = FrameworkConfig::MONTH_SECOND)
	{

		$curTime = self::getTime ();
		//32*24*3600
		$SECONDS_OF_32DAY = 2764800;

		//大于32天，肯定不是同一个月
		if (abs ( $curTime - $checkTime ) > $SECONDS_OF_32DAY)
		{
			return false;
		}

		//减去时间差得到当前是第几月
		$month = strftime ( "%m", $checkTime - $offset );
		$curMonth = strftime ( "%m", $curTime - $offset );

		if ($month == $curMonth)
		{
			return true;
		}
		return false;
	}

	/**
	 * 返回年月日
	 * 格式：20111015
	 */
	static function todayDate($offset = FrameworkConfig::DAY_OFFSET_SECOND)
	{

		$curTime = self::getTime ();
		return strftime ( "%Y%m%d", $curTime - $offset );
	}

	/**
	 *
	 * 生成logid
	 */
	static function genLogId()
	{

		$code = microtime ( true ) . '#' . rand ( 0, 9999 );
		$ret = md5 ( $code );
		$high = hexdec ( substr ( $ret, 0, 16 ) );
		$low = hexdec ( substr ( $ret, 16 ) );
		$ret = (($high ^ $low) & 0xFFFFFFFFFFFFFF) * 100;
		$ret %= 10000000000;
		if ($ret < 1000000000)
		{
			$ret += 1000000000;
		}
		return $ret;
	}

	/**
	 * 整个战斗系统中使用的随机模型
	 * @param array $arrWeight
	 * @return mixed 随机到的key值
	 */
	static function randWeight($arrWeight)
	{

		$offset = 0;
		$mapKey2Range = array ();
		foreach ( $arrWeight as $key => $value )
		{
			$value = intval ( $value );
			if (empty ( $value ))
			{
				continue;
			}
			if ($value < 0)
			{
				throw new Exception ( "invalid value $value" );
			}
			$arrWeightRange [] = array ($key, $offset, $offset += $value );
		}

		if (empty ( $arrWeightRange ))
		{
			Logger::trace ( "invalid input, empty weight array found" );
			throw new Exception ( "empty weight array" );
		}

		$weight = rand ( 0, $offset );
		return self::binaryRangeSearch ( $arrWeightRange, $weight );
	}

	/**
	 * 二分范围查找
	 * @param array $arrWeightRange 要查找的权重区间
	 * @param number $weight 要查找的权重
	 * @return 查找到的key值
	 * @throws Exception
	 */
	private static function binaryRangeSearch($arrWeightRange, $weight)
	{

		$leftIndex = 0;
		$rightIndex = count ( $arrWeightRange ) - 1;
		while ( $leftIndex <= $rightIndex )
		{
			$currIndex = intval ( ($leftIndex + $rightIndex) / 2 );

			if ($arrWeightRange [$currIndex] [1] > $weight)
			{
				$rightIndex = $currIndex - 1;
			}
			else if ($arrWeightRange [$currIndex] [2] < $weight)
			{
				$leftIndex = $currIndex + 1;
			}
			else
			{
				return $arrWeightRange [$currIndex] [0];
			}
		}
		Logger::fatal ( "can't %d in array:%s", $weight, $arrWeightRange );
		throw new Exception ( 'sys' );
	}

	/**
	 * 检查名字是否合法。
	 * 只支持utf-8中文、大小写字母、数字、下划线
	 * 修改为不支持一些特殊字符，支持"~"
	 * @param string $name
	 * @return string 返回如下：
	 * ok
	 * invalid_char  有无效字符
	 * sensitive_word 有敏感词
	 *
	 */
	public static function checkName($name)
	{

		//utf-8中文、大小写字母、数字、下划线
		//$reg = "/[^\x{4e00}-\x{9fa5}a-zA-Z0-9_]/u";
		$reg = "/[`!@#\$%^&*()\\-+={}\\[\\]\\\\|:;'\"<>,.\\/\\? \t]/u";
		if (preg_match ( $reg, $name ) || self::has4BytesCharacter($name))
		{
			//非法字符
			return 'invalid_char';
		}
		else
		{
			$ret = TrieFilter::search ( $name );
			if (empty ( $ret ))
			{
				return 'ok';
			}
			else
			{
				//敏感字符
				return 'sensitive_word';
			}
		}
	}
	
	/**
	 * 检查一个utf8字符串中是否包含4个字节（及大于4个字节）的字符
	 * @param string $str
	 * @return boolean
	 */
	public static function has4BytesCharacter($str)
	{
		$len = strlen($str);
		for($i = 0; $i < $len; $i++)
		{
			$byte = ord($str[$i]);
			if( $byte >= 240 )
			{
				return true;
			}
		}
		return false;
	}

	/**
	 *
	 * 放回抽样
	 * @param array $samples			抽样样本数组
	 * @param int $number				抽样次数
	 * @param string $name				抽样时所用权重的元素名称
	 *
	 * @return array(key)	 			抽样结果key的数组
	 */
	public static function backSample($samples, $number, $name = 'weight')
	{

		return self::_sample ( $samples, $number, $name, TRUE );
	}

	/**
	 *
	 * 不放回抽样
	 * @param array $samples			抽样样本数组
	 * @param int $number				抽样次数
	 * @param string $name				抽样时所用权重的元素名称
	 *
	 * @return array(key)	 			抽样结果key的数组
	 */
	public static function noBackSample($samples, $number, $name = 'weight')
	{

		return self::_sample ( $samples, $number, $name, FALSE );
	}

	/**
	 *
	 * 抽样
	 * @param array $samples			抽样样本数组
	 * @param int $number				抽样次数
	 * @param string $name				抽样时所用权重的元素名称
	 * @param boolean $back				是否为放回抽样, TRUE表示放回抽样，FALSE表示不放回抽样
	 *
	 * @return array(key)	 			抽样结果key的数组
	 */
	private static function _sample($samples, $number, $name, $back)
	{

		if (count ( $samples ) < $number)
		{
			Logger::FATAL ( 'number must > count(sample)!' );
			throw new Exception ( 'inter' );
		}
		$keys = array ();
		$weight_max = 0;
		foreach ( $samples as $value )
		{
			$weight_max += $value [$name];
		}
		for($i = 0; $i < $number; $i ++)
		{
			$rand = rand ( 0, $weight_max - 1 );
			foreach ( $samples as $key => $value )
			{
				if ($rand < $value [$name])
				{
					$keys [] = $key;
					//不放回抽样,则将已经掉落的物品移除可掉落列表
					if ($back == FALSE)
					{
						$weight_max -= $value [$name];
						unset ( $samples [$key] );
					}
					break;
				}
				else
				{
					$rand -= $value [$name];
				}
			}
		}
		return $keys;
	}

	static function signVa($vaData)
	{

		$ret = sha1 ( $vaData, true );
		return $ret;
	}

	static function amfDecode($data, $uncompress = false, $flag = FrameworkConfig::AMF_DECODE_FLAGS)
	{

		if ($uncompress)
		{
			$data = gzuncompress ( $data );
			if (false === $data)
			{
				Logger::fatal ( "uncompress data failed" );
				throw "inter";
			}
		}

		if ($flag & AMF_AMF3)
		{
			if ($data [0] != FrameworkDef::AMF_AMF3)
			{
				$data = FrameworkDef::AMF_AMF3 . $data;
			}
		}
		return amf_decode ( $data, $flag );
	}

	static function amfEncode($data, &$compressed = false, $threshold = false,
			$flag = FrameworkConfig::AMF_ENCODE_FLAGS)
	{

		$data = amf_encode ( $data, $flag );
		if (false === $data)
		{
			Logger::fatal ( "amf_encode failed" );
			throw "inter";
		}
		if ($flag & AMF_AMF3)
		{
			if ($data [0] == FrameworkDef::AMF_AMF3)
			{
				$data = substr ( $data, 1 );
			}
		}
		if ($compressed || ($threshold > 0 && strlen ( $data ) > $threshold))
		{
			$data = gzcompress ( $data );
			$compressed = true;
		}
		return $data;
	}

	/**
	 * 批量得到用户名
	 * @param array $arrUid uid数组
	 * @return array
	 * <code>
	 * uid=>uname
	 * </code>
	 */
	static function getUnameByUid($arrUid)
	{

		if (empty ( $arrUid ))
		{
			return array ();
		}
		$arrUid = array_merge ( $arrUid );
		if (count ( $arrUid ) == 1 && $arrUid [0] == RPCContext::getInstance ()->getUid ())
		{
			return array ($arrUid [0] => RPCContext::getInstance ()->getSession ( 'global.uname' ) );
		}
		$tblName = 't_user';

		$arrUname = array ();
		$data = new CData ();
		$ret = $data->select ( array ('uid', 'uname' ) )->from ( $tblName )->where ( 'uid', 'IN',
				$arrUid )->where ( 'status', '!=', 0 )->query ();
		return self::arrayIndexCol ( $ret, 'uid', 'uname' );
	}

	

	/**
	 * 异步执行需要比较长时间的方法
	 * NOTICE 这个方法必须在config里注册为private的
	 * @param string $method 需要执行的方法
	 * @param array $arrData 调用该方法所需要的参数
	 * @param int $executeTimeout 任务执行超时时间，单位：秒
	 * @param int $retry 重试次数，重试条件：连接失败等网络错误
	 */
	public static function asyncExecute($method, $arrArg, $executeTimeout = 1000, $retry = 10)
	{

		//根据配置判断：是把任务转移到main机器上运行，还是在本地起个进程运行
		if (FrameworkConfig::ASYNC_TASK_ON_MAIN)
		{
			RPCContext::getInstance ()->asyncExecuteTask ( $method, $arrArg, $executeTimeout,
					$retry );
		}
		else
		{
			Logger::debug ( "async execute method:%s in with data:%s", $method, $arrArg );
			$framework = RPCContext::getInstance ()->getFramework ();
			$arrRequest = array ('method' => $method, 'args' => $arrArg,
					'serverIp' => $framework->getServerIp (), 'group' => $framework->getGroup (),
					'logid' => $framework->getLogid (), 'db' => $framework->getDb (),
					'time' => Util::getTime (), 'serverId' => Util::getServerId () );
			$compress = false;
			$request = Util::amfEncode ( $arrRequest, $compress );
			$cmd = sprintf ( FrameworkConfig::ASYNC_CMD_TPL, base64_encode ( $request ) );
			Logger::info ( "asyncExecute:%s", $cmd );
			popen ( $cmd, 'r' );
		}
	}

	/**
	 * 发送邮件
	 * @param string $to 收件人
	 * @param string $from 发件人
	 * @param string $subject 标题
	 * @param string $message 消息内容
	 * @param bool $html 是否为html邮件
	 * @param array $arrAttachment 附件信息，格式如下
	 * <code>
	 * [{
	 * name:文件名
	 * type:文件类型
	 * content:文件内容
	 * }]
	 * </code>
	 */
	public static function sendMail($to, $from, $subject, $message, $html = true,
			$arrAttachment = array())
	{

		$header = '';
		if (! empty ( $arrAttachment ))
		{
			$boundary = md5 ( uniqid ( time () ) );
			$header .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n\r\n";
			$header .= "--" . $boundary . "\r\n";
		}

		foreach ( $arrAttachment as $attachment )
		{
			$header .= sprintf ( "Content-Type: %s; name=\"%s\"\r\n", $attachment ['type'],
					$attachment ['name'] );
			$header .= "Content-Transfer-Encoding: base64\r\n";
			$header .= sprintf ( "Content-Disposition: attachment; filename=\"%s\"\r\n\r\n",
					$attachment ['name'] );
			$header .= chunk_split ( base64_encode ( $attachment ['content'] ) ) . "\r\n\r\n";
			$header .= "--" . $boundary . "\r\n";
		}

		if ($html)
		{
			$header .= "Content-type: text/html; charset=UTF-8\r\n\r\n";
		}
		else
		{
			$header .= "Content-type: text/plain; charset=UTF-8\r\n\r\n";
		}

		$header .= $message;
		$subject = '=?UTF-8?B?' . base64_encode ( $subject ) . '?=';
		mail ( $to, $subject, '', $header, "-f$from" );
	}

	/**
	 *
	 * 得到服务器ID
	 *
	 * @return int
	 */
	public static function getServerId()
	{

		$server_name = RPCContext::getInstance ()->getSession ( UserDef::SESSION_KEY_SERVER_ID );
		//如果session中不存在,则从服务器传送过来的group中查询
		if (empty ( $server_name ))
		{
			Logger::debug ( 'serverId not found in session' );
			$server_name = RPCContext::getInstance ()->getFramework ()->getGroup ();
			return self::getServerIdByGroup ( $server_name );
		}
		else
		{
			Logger::debug ( 'serverId found in session' );
			return intval ( $server_name );
		}
	}

	static public function getServerIdByGroup($server_name)
	{

		$arr_server_name = explode ( '_', $server_name );

		if (preg_match ( '/\d+/', $server_name, $matches ) == TRUE)
		{
			$base_id = intval ( $matches [0] );
			if (isset ( $arr_server_name [1] ))
			{
				$server_index = intval ( $arr_server_name [1] );
			}
			else
			{
				$server_index = 0;
			}
			return $base_id + $server_index;
		}
		else
		{
			Logger::WARNING ( 'invalied server id!' );
			return 0;
		}
	}
	

	/**
	 * 运行在用户连接中时，返回用户对应的serverId
	 *
	 * @return int
	 */
	public static function getServerIdOfConnection()
	{
		$serverId = RPCContext::getInstance()->getSession ( UserDef::SESSION_KEY_SERVER_ID );
		if( empty($serverId) )
		{
			$group = RPCContext::getInstance()->getFramework()->getGroup();
			if( empty($group) )
			{
				Logger::fatal('getServerIdOfConnection failed. maybe in cross group');
				return 0;
			}
			$GAME_STR = 'game';
			$serverId = substr ( $group, strlen ( $GAME_STR ) );
			if( !is_numeric($serverId) )
			{
				Logger::fatal('getServerIdOfConnection. maybe not in user connection');
				return 0;
			}
		}
		return intval($serverId);
	}
	
	/**
	 * 获取当前服的第一个serverId
	 *
	 * @return int
	 */
	public static function getFirstServerIdOfGroup()
	{
		$group = RPCContext::getInstance()->getFramework()->getGroup ();
		if( empty($group) )
		{
			Logger::fatal('getServerIdOfConnection failed. maybe in cross group');
			return 0;
		}
		return self::getServerIdByGroup ( $group );
	}
	
	public static function getGroupByServerId($serverId)
	{
		return sprintf('game%03d', $serverId);
	}
	
	/**
	 * 是否在跨服机器上。
	 * 在跨服机器上有些业务要做特殊处理。
	 * 	比如活动解析配置时，如果在跨服机器上，就不需要做和开服时间相关的检查了
	 */
	public static function isInCross()
	{
		$group = RPCContext::getInstance()->getFramework()->getGroup ();
		
		return empty($group);
	}

	/**
	 * 检查两个时间是否相同
	 * @param int $time1
	 * @param int $time2
	 * @return bool 时间相同则返回true
	 */
	public static function isSameTime($time1, $time2)
	{

		return abs ( $time1 - $time2 ) <= FrameworkConfig::SAME_TIME_OFFSET;
	}

	/**
	 * 判断time1是否小于time2
	 * @param int $time1
	 * @param int $time2
	 * @return bool
	 */
	public static function isLessTime($time1, $time2)
	{

		if ($time1 <= $time2)
		{
			return true;
		}
		else
		{
			return $time1 - $time2 <= FrameworkConfig::SAME_TIME_OFFSET;
		}
	}

	/**
	 * 判断time2是否大于time1
	 * @param int $time1
	 * @param int $time2
	 * @return bool
	 */
	public static function isGreaterTime($time1, $time2)
	{

		return self::isLessTime ( $time2, $time1 );
	}

	/**
	 * 判断一个ip是否在ip队列里
	 * @param array $arrIpRange
	 * @param int $ip
	 */
	public static function ipContains($arrIpRange, $ip)
	{

		$left = 0;
		$right = count ( $arrIpRange ) - 1;
		while ( $left <= $right )
		{
			$middle = intval ( ($left + $right) / 2 );

			if ($ip < $arrIpRange [$middle] [0])
			{
				$right = $middle - 1;
				continue;
			}

			if ($ip > $arrIpRange [$middle] [1])
			{
				$left = $middle + 1;
				continue;
			}

			return true;
		}

		return false;
	}

	/**
	 * 返回此服上所有的server id， 如果合服了serverid 有多个
	 * Enter description here ...
	 * @return array
	 */
	public static function getAllServerId()
	{
		//合服
		if (class_exists ( "GameConf", false ) && defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ))
		{
			$groupName = RPCContext::getInstance ()->getFramework ()->getGroup ();

			//去掉game字符串
			$GAME_STR = 'game';
			$groupName = substr ( $groupName, strlen ( $GAME_STR ) );
			$arrTmp = explode ( '_', $groupName );

			$arrGroup = array ();
			$groupBase = $arrTmp [0];

			for($i = 1; $i < count ( $arrTmp ); $i ++)
			{
				$arrGroup [] = $groupBase + $arrTmp [$i];
			}

			return $arrGroup;
		}
		else
		{
			return array (self::getServerId () );
		}
	}
	
	/**
	 * 从phpproxy中获取某个模块（某个服）的信息
	 * @param string $module（lcserver,battle,data) 
	 * @param string $group(game001)
	 * @return 
	 * 	
	 * 	array
	 * 	{
	 * 		host:192.168.1.1
	 * 		port:1111
	 * 		ret: ok/ retry/ mod_not_found
	 * 	}
	 */
	public static function getModuleInfo($module, $group)
	{
		$proxy = new PHPProxy ( $module );
		
		$returnData = array();
		try
		{
			$ret = $proxy->getModuleInfo ( $module, $group );
			$returnData = $ret;
			$returnData['ret'] = 'ok';
		}
		catch(Exception $e)
		{
			Logger::fatal('getModuleInfo failed. module:%s, group:%s, err:%s', $module, $group, $e->getMessage());
			$returnData['ret'] = $e->getMessage();
		}
		return $returnData;
	}
	
	public static function getZkInfo($path)
	{
		$proxy = new PHPProxy ( '' );
		$returnData = array('ret'=>'ok');
		try
		{
			$ret = $proxy->getZkInfo( $path);
			$returnData = $ret;
			$returnData['ret'] = 'ok';
		}
		catch(Exception $e)
		{
			Logger::fatal('getZkInfo failed. path:%s, err:%s', $path, $e->getMessage());
			$returnData['ret'] = $e->getMessage();
		}
		return $returnData;
	}

	public static function getSuffixName()
	{

		if (! defined ( 'GameConf::MERGE_SERVER_OPEN_DATE' ))
		{
			return '';
		}

		$groupName = RPCContext::getInstance ()->getFramework ()->getGroup ();
		//去掉game字符串
		$GAME_STR = 'game';
		$groupName = substr ( $groupName, strlen ( $GAME_STR ) );
		$arrTmp = explode ( '_', $groupName );
		$groupBase = $arrTmp [0];

		$suffixNum = 0;
		$serverId = self::getServerId();

		$suffixNum = $serverId - intval ( $groupBase );

		return MergeServerConf::CONCAT_NAME . $suffixNum;
	}

	static function sendCallback($arrCallbackList, $usleep)
	{

		$proxy = new ServerProxy ();
		foreach ( $arrCallbackList as $arrCallback )
		{
			$method = $arrCallback ['method'];
			$arrArg = $arrCallback ['args'];
			Logger::info ( "send callbackend method:%s", $method );
			try
			{
				switch ($arrCallback ['method'])
				{
					case 'sendMsg' :
						$proxy->sendMessage ( $arrArg [0],
								$arrArg [1] ['callback'] ['callbackName'], $arrArg [1] ['ret'] );
						break;
					case 'asyncExecuteRequest' :
						$proxy->asyncExecuteRequest ( $arrArg [0], $arrArg [1] ['method'],
								$arrArg [1] ['args'], $arrArg [2],
								$arrArg [1] ['callback'] ['callbackName'] );
						break;
					case 'freeGuildBattle' :
						$proxy->freeGuildBattle ( $arrArg [0] );
						break;
					case 'sendFilterMessage' :
						$proxy->sendFilterMessage ( $arrArg [0], $arrArg [1], $arrArg [2] );
						break;
					default :
						Logger::fatal ( "method:%s is not supported by script send callback",
								$method );
				}
			}
			catch ( Exception $e )
			{
				Logger::fatal ( "method:%s, args:%s", $method, $arrArg );
				Logger::info ( "%s", $e->getTraceAsString () );
			}
			usleep ( $usleep );
		}
	}

	public static function kickOffUser($uid)
	{
	    $proxy = new ServerProxy();
	    $proxy->closeUser($uid);
	    sleep(1);
	}
	
	public static function kickOffUserIfNeed($uid)
	{
		$proxy = new ServerProxy();
	
		//$proxy->sendMessage(array($uid), PushInterfaceDef::BACKEND_CLOSE, array());
	
		usleep(100);
	
		$ret = $proxy->checkUser($uid, true);
		 
		if( $ret )
		{
			sleep(1);
		}
	}
	
	public static function isSpecialDrop($args, $num)
	{
		if (count($args) != 2) 
		{
			throw new FakeException('invalid para! args num is not equal 2');
		}
		
		list($a, $b) = $args;
		if ($num%($a+$b)-$a+$b < 0)
		{
			return false;
		}
		$rate = exp((max($num%($a+$b)-$a+$b+1,0)-2*$b)/5)*10000;
		$rand = rand(1, UNIT_BASE);
		return $rand <= $rate ? true : false;
	}
	
	/**
	 *
	 * 伪随机序列
	 *
	 * @param int $seed				种子
	 * @param int $number			数量
	 *
	 * @return array
	 */
	public static function pseudoRand($seed, $number)
	{
		$array = array();
		//使用需要被设置的种子
		srand($seed);
		for ( $i = 0; $i < $number; $i++ )
		{
			$array[$i] = rand();
		}
		//恢复原始的srand种子
		srand();
		return $array;
	}
	
	/**
	 * 合并多个3元组奖励
	 * @param array
	 * eg  array((7,60003,2),(3,0,100)), array((7,60009,2),(3,0,100),(1,0,200))
	 * @return array
	 * eg  array((7,60003,2),(3,0,200),(7,60009,2),(1,0,200))
	 */
	public static function arrayAdd3D()
	{
	    $arrArgs = func_get_args();
	
	    if ( empty( $arrArgs ) )
	    {
	        return array();
	    }
	
	    $merge = array();
	    foreach ( $arrArgs as $args )
	    {
	        if ( empty( $args ) )
	        {
	            continue;
	        }
	
	        foreach ( $args as $arg )
	        {
	            if ( !isset( $merge[$arg[0]] ) )
	            {
	                $merge[$arg[0]] = array();
	            }
	
	            if ( !isset( $merge[$arg[0]][$arg[1]] ) )
	            {
	                $merge[$arg[0]][$arg[1]] = 0;
	            }
	
	            $merge[$arg[0]][$arg[1]] += $arg[2];
	        }
	    }
	
	    $arrMerge = array();
	    foreach ( $merge as $itemType => $arrItem )
	    {
	        foreach ( $arrItem as $itemTplId => $itemNum )
	        {
	            $arrMerge[] = array( $itemType, $itemTplId, $itemNum );
	        }
	    }
	
	    return $arrMerge;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */
