<?php
/**
 * Created by PhpStorm.
 * User: hanshijie
 * Date: 15/9/25
 * Time: 19:13
 */
class CheckWashTime extends BaseScript
{

    /**
     * @var array
     * uid => $va_item_text 只存上一条的
     */
    private $arrUserData =array();


    protected function executeScript($arrOption)
    {
        if(count($arrOption) < 3)
        {
            printf("");
        }

        $filePath = $arrOption[0];

        $file = fopen($filePath, 'r');
        if(empty($file))
        {
            echo sprintf("open file:%s failed\n", $filePath);
            exit(0);
        }

        while(!feof($file))
        {
            $line = fgets($file);
            if( empty($line) )
            {
            	break;
            }
            $line = trim($line);
            $row = mb_split("\\s", $line);
            //var_dump($row);
           
            $pid = intval($row[5]);
            $uid = intval($row[6]);
            $time = $row[2];
            $group = $row[4];
            $vaData = $row[8];
            $key = $group.'_'.$uid;
           
            if( empty($row[11]) )
            {
            	Logger::warning('%s', $line);
            	
            	printf("uid:%d pid:%d group:%s time:%s index:miss\n", $uid, $pid, $group, $time);
            
            	continue;
            }
            $goldInfo = $row[9];
            
            $msg = sprintf("uid:%d pid:%d group:%s time:%s gold:%s ", $uid, $pid, $group, $time, $goldInfo);
            
            
            $itemId = intval($row[10]);
            $itemTplId = intval($row[11]);
            
            if($itemTplId != 630102)
            {
                continue;
            }

            $vaItemText = $this->decodeAmf($vaData);

            $toConfirm = array();
            $btc = array();
            if( !empty($vaItemText['toConfirm']) )
            {
            	$toConfirm = $vaItemText['toConfirm'];
            }
            if( !empty($vaItemText['btc']) )
            {
            	$btc = $vaItemText['btc'];
            }

            
            //如果有上一条
            if(isset($this->arrUserData[$key][$itemId]))
            {
            	$all = array();
            	$min = 99999;
            	$isBatch = false;
            	foreach( $toConfirm as $key => $value )
            	{
            		if(empty($this->arrUserData[$key][$itemId]['to'][$key])
            			||  $value != $this->arrUserData[$key][$itemId]['to'][$key] )
            		{
            			$all[] = $key;
            			if ( $min > $key )
            			{
            				$min = $key;
            				$isBatch = true;
            			}
            		}
            	}
            	foreach( $btc as $key => $value )
            	{
            		if(empty($this->arrUserData[$key][$itemId]['btc'][$key])
            		||  $value != $this->arrUserData[$key][$itemId]['btc'][$key] )
            		{
            			$all[] = $key;
            			if ( $min > $key )
            			{
            				$min = $key;
            				$isBatch = true;
            			}
            		}
            	}
            	
            	if(count($all) == 0 )
            	{
            		printf("%s error not first\n", $msg);
            		Logger::fatal("%s error", $msg);
            		continue;
            	}
            	else if( count($all) > 1 )
            	{
            		$msg .= sprintf('index:%s batch:%s mult:%s', $min, $isBatch?'yes':'no', implode(',', $all));
            	}
            	else
            	{
            		$msg .= sprintf('index:%s batch:%s', $min, $isBatch?'yes':'no');
            	}
                
            	$this->arrUserData[$key][$itemId] = array(
            			'to' => $toConfirm,
            			'btc' => $btc,
            	);
            }
            else
            {
                //首次洗练 一定是1条
                if(count($toConfirm) != 1 && count($btc) != 1)
                {
                    printf("%s error\n", $msg);
                    Logger::fatal("%s error", $msg);
                    var_dump($btc);
                    var_dump($toConfirm);
                    var_dump($row);
                    exit();
                    continue;
                }
                $this->arrUserData[$key][$itemId] = array(
                	'to' => $toConfirm,
                	'btc' => $btc,
                );
                $min = 999999;
                $all = array();
                $isBatch = false;
                foreach( $toConfirm as $key => $value )
                {
                	if( $min > $key )
                	{
                		$min = $key;
                		$isBatch = true;
                	}
                	$all[] = $key;
                }
                foreach( $btc as $key => $value )
                {
                	if( $min > $key )
                	{
                		$min = $key;
                		$isBatch = true;
                	}
                	$all[] = $key;
                }
                if( count($all) > 1 )
                {
                	$msg .= sprintf('index:%s batch:%s mult:%s', $min, $isBatch?'yes':'no', implode(',', $all));
                }
                else 
                {
                	$msg .= sprintf('index:%s batch:%s', $min, $isBatch?'yes':'no');
                }
	
            }

			printf("%s\n", $msg);
        }
    }

    public function decodeAmf($data)
    {
        $data = str_replace(array("\n", "\t", " "), "", $data);
        $data = pack('H' . strlen($data), $data);
        $data = chr(0x11) . $data;
        $arrData = amf_decode($data, 7);
        return $arrData;
     }
}