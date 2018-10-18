<?php
class CDBInterface {

	// 私有变量 mysqli 实例
	private static $m_DBInterface = null;

	/**
	 * 关闭数据库连接
	 */
    public static function destroyConnection() {

        self::$m_DBInterface->kill(self::$m_DBInterface->thread_id);
        self::$m_DBInterface->close();
    }

    /**
     * 执行SQL
     * @param string $strSql					将要执行的SQL语句
     * @return 返回 $stmt
     */
    public static function execute($strSql) {

        // 准备SQL语句
        $stmt = self::$m_DBInterface->prepare($strSql);
        // 错误处理
        if (!$stmt) {
        	echo self::$m_DBInterface->error;
        	exit();
        }
        // 执行SQL
        $return = $stmt->execute();
        if ( $return == FALSE )
        {
        	echo self::$m_DBInterface->error;
        	exit();
        }
        // 返回 stmt
        return  $stmt;
    }

    /**
     * 连接数据库
     */
     public static function connect($ip, $dbName, $user, $pass) {

        self::$m_DBInterface = new mysqli($ip, $user, $pass);
        if (self::$m_DBInterface->connect_error != null) {
            printf("Could not connect to MySQL: %s\n", self::$m_DBInterface->error);
            exit();
        }
        // 选择需要连接的实例
        self::$m_DBInterface->select_db($dbName);
        // 更改字符集
        self::$m_DBInterface->query("SET NAMES utf8;");
        // 关闭自动提交
        self::$m_DBInterface->autocommit(false);

     }

	public static function getAllTblNames()
	{
		$arr = array();

		$result = self::$m_DBInterface->query("show tables;");

		$row = null;
		while (1) {
			$row = $result->fetch_array(3);
			if ($row != null)
				$arr[] = $row[0];
			else
				break;
		}

		return $arr;
	}
} // CDBInterface




function getIdTableMap($idXmlPath, $dataXmlPath)
{
	$arrIdNoTable = array(
		//abyss_id
	);
	$arrIdIgnore = array(
		'worldarena_pos_id',
		'uuid',
	);
	if ( !file_exists($idXmlPath)  )
	{
		throw new Exception("$idXmlPath not exist");
	}
	if ( !file_exists($dataXmlPath)  )
	{
		throw new Exception("$dataXmlPath not exist");
	}
	$idXmlRoot = simplexml_load_file($idXmlPath);
	$dataXmlRoot = simplexml_load_file($dataXmlPath);

	$idTableMap = array();
	foreach ( $idXmlRoot->id as $node )
	{
		if( empty($node->name) )
		{
			throw new Exception('invalid id.xml. no name');
		}
		$idName = strval($node->name);
		if ( in_array( $idName, $arrIdIgnore ) )
		{
			printf("ignore id:%s\n", $idName );
			continue;
		}
		if( !in_array($node->name, $arrIdNoTable) && empty($node->table) )
		{
			throw new Exception('invalid id.xml. no table');
		}

		$idTableMap[$idName] = empty($node->table) ? 'notable' : strval($node->table);
	}

	$idTableMapNoPart = $idTableMap;
	$idTableMapPart = array();
	foreach( $dataXmlRoot->table as $node )
	{
		if( empty($node->name) )
		{
			throw new Exception('invalid data.xml. no name');
		}
		$tblName = strval($node->name);

		$idName = array_search($tblName, $idTableMap);
		if( !empty($idName) )
		{
			if( !empty($node->partition) )
			{
				$idTableMapPart[$idName] = $tblName;
				unset( $idTableMapNoPart[$idName] );
			}
		}
	}

	return array(
		'idTableMapNoPart' => $idTableMapNoPart,
		'idTableMapPart' => $idTableMapPart,
	);
}


function getMaxIDNoPartition($idName, $tblName)
{
	$id = 0;

	if( empty($tblName) )
	{
		throw new Exception('invalid tableName');
	}
	
	if( $tblName == 'notable' )
	{
		return $id;
	}

	$sql = "select max(".$idName.") from ".$tblName.";";
	
	$stmt = CDBInterface::execute($sql);
	$stmt->bind_result($id);
	$stmt->fetch();
	$stmt->close();

	// 最大的下一个id
	return intval(($id + 99) / 100) * 100 + 100;
}

function getMaxIDPartition($idName, $tblName, $allTbls)
{
	$max = 0;
	$id = 0;
	$tblName = $tblName . '_';
	$strLength = strlen($tblName);
	foreach ($allTbls as $tbl)
	{
		// 如果前缀相等, 并且后面的是数字
		if (substr($tbl, 0, $strLength) == $tblName && is_numeric(substr($tbl, $strLength)) )
		{
			$sql = "select max(".$idName.") from ".$tbl.";";
			$stmt = CDBInterface::execute($sql);
			$stmt->bind_result($id);
			$stmt->fetch();
			$stmt->close();

			if ($id > $max)
			{
				$max = $id;
			}
		}
	}

	// 最大的下一个id
	return intval(($max + 99) / 100) * 100 + 100;
}

$help = "param: ip dbname user pass id.xml table.xml\n";
if ($argc != 7)
{
	exit($help);
}

// 链接数据库  ip, dbname, user, pass
CDBInterface::connect($argv[1], $argv[2], $argv[3], $argv[4]);

$arrRet = getIdTableMap($argv[5], $argv[6]);
$idTableMapNoPart = $arrRet['idTableMapNoPart'];
$idTableMapPart = $arrRet['idTableMapPart'];

$arrId = array();

foreach ($idTableMapNoPart as $idName => $tblName)
{
	$arrId[$idName] = getMaxIDNoPartition($idName, $tblName);
}

$allTbls = CDBInterface::getAllTblNames();

foreach ($idTableMapPart as $idName => $tblName)
{
	$arrId[$idName] = getMaxIDPartition($idName, $tblName, $allTbls);
}

// 断开数据库
CDBInterface::destroyConnection();


printf("idTableMapNoPart:%s\n", var_export($idTableMapNoPart,true));
printf("idTableMapPart:%s\n", var_export($idTableMapPart,true));

printf("arrId:%s\n", var_export($arrId,true));

foreach ($arrId as $key => $v)
{
	if ( ! empty($v) )
	{
		file_put_contents($key, pack("I", $v));
	}
	else
	{
		file_put_contents($key, "");
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */