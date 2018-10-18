<?php

/***************************************************************************
 *
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: TableGenerator.class.php 80342 2013-12-11 10:41:23Z wuqilin $
 *
 **************************************************************************/

/**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/lib/TableGenerator.class.php $
 * @author $Author: wuqilin $(hoping@babeltime.com)
 * @date $Date: 2013-12-11 10:41:23 +0000 (Wed, 11 Dec 2013) $
 * @version $Revision: 80342 $
 * @brief
 *
 **/

/**
 * 用于生成表格的库
 * @author Hoping
 *
 */
class TableGenerator
{

	private $arrConfig;

	/**
	 * 构造函数
	 * @param array $arrConfig 格式如下
	 * <code>
	 * {
	 * key:{
	 * colName:列名
	 * threshold:界限
	 * format:{
	 * lt:小于
	 * gt:大于
	 * }
	 * }
	 * }
	 * </code>
	 * 其中format目前 支持如下两种red和green
	 */
	public function __construct($arrConfig)
	{

		$this->arrConfig = $arrConfig;
	}

	public function generateCsv($arrRowList)
	{

		$name = md5 ( uniqid ( time () ) );
		$name = "/tmp/$name";
		$handle = fopen ( $name, "w" );
		$arrRow = array ();
		$arrField = array ();
		foreach ( $this->arrConfig as $key => $arrRowConfig )
		{
			$arrKey [] = $key;
			$arrField [] = $arrRowConfig ['colName'];
		}
		fputcsv ( $handle, $arrField );

		foreach ( $arrRowList as $arrRow )
		{
			$arrField = array ();
			foreach ( $arrKey as $key )
			{
				$arrField [] = $arrRow [$key];
			}
			fputcsv ( $handle, $arrField );
		}

		fclose ( $handle );
		$content = file_get_contents ( $name );
		unlink ( $name );
		return mb_convert_encoding ( $content, 'GBK', 'UTF-8' );
	}

	public function generate($arrRowList)
	{

		$table = "<table border='1'>\n<tr>";
		foreach ( $this->arrConfig as $arrRowConfig )
		{
			$table .= sprintf ( '<td>%s</td>', htmlspecialchars ( $arrRowConfig ['colName'] ) );
		}
		$table .= "</tr>\n";

		foreach ( $arrRowList as $arrRow )
		{
			$table .= '<tr>';
			foreach ( $this->arrConfig as $key => $arrRowConfig )
			{
				$value = htmlspecialchars ( $arrRow [$key] );
				if (! isset ( $arrRowConfig ['threshold'] ))
				{
					$table .= sprintf ( '<td>%s</td>', $value );
					continue;
				}

				$threshold = $arrRowConfig ['threshold'];
				foreach ( $arrRowConfig ['format'] as $operand => $format )
				{
					switch ($operand)
					{
						case 'lt' :
							if ($value < $threshold)
							{
								$value = $this->applyFormat ( $format, $value );
							}
							break;
						case 'gt' :
							if ($value > $threshold)
							{
								$value = $this->applyFormat ( $format, $value );
							}
							break;

					}
				}
				$table .= sprintf ( '<td>%s</td>', $value );
			}
			$table .= "\n";
		}
		$table .= '</table>';
		return $table;
	}

	private function applyFormat($format, $value)
	{

		switch ($format)
		{
			case 'red' :
				return sprintf ( '<span style="color:#FF1122">%s</span>', $value );
			case 'green' :
				return sprintf ( '<span style="color:#11FF22">%s</span>', $value );
			default :
				return $value;
		}
	}

}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */