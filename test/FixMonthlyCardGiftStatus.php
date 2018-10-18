<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: FixMonthlyCardGiftStatus.php 240769 2016-04-29 12:05:09Z MingTian $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/test/FixMonthlyCardGiftStatus.php $
 * @author $Author: MingTian $(wuqilin@babeltime.com)
 * @date $Date: 2016-04-29 12:05:09 +0000 (Fri, 29 Apr 2016) $
 * @version $Revision: 240769 $
 * @brief 
 *  
 **/

class FixMonthlyCardGiftStatus extends BaseScript
{
	protected function executeScript($arrOption)
	{
		$fix = $arrOption[0] == 'fix' ? true : false;
		
		$arrUid = $this->getArrUid();
		foreach ($arrUid as $uid)
		{
			if ($fix)
			{
				Util::kickOffUser($uid);
			}
			RPCContext::getInstance()->resetSession();
			RPCContext::getInstance()->setSession(UserDef::SESSION_KEY_UID, $uid);
			
			//修复合服前领取月卡1的大礼包后，合服后购买月卡2没有大礼包的问题(3,2)
			$arrInst = array();
			foreach (DiscountCardDef::$VAILD_MONTHLYCATD_IDS as $cardId)
			{
				$arrInst[$cardId] = MonthlyCardObj::getInstance($uid, $cardId);
			}
			
			//月卡1的购买时间是合服前，而且月卡1大礼包已经领取
			//月卡2的购买时间是合服后，而且月卡2大礼包没有领取
			if ($arrInst[1]->getGiftStatus() == MONTHCARD_GIFTSTATUS::GOTGIFT
			&& !EnMergeServer::isMonthCardEffect($arrInst[1]->getBuyTime())
			&& $arrInst[2]->getGiftStatus() == MONTHCARD_GIFTSTATUS::HASGIFT
			&& EnMergeServer::isMonthCardEffect($arrInst[2]->getBuyTime()))
			{
				Logger::info('FixMonthlyCardGiftStatus, uid:%d card1 buyTime:%s card2 buyTime:%s do flag:%s', $uid, $arrInst[1]->getBuyTime(),$arrInst[2]->getBuyTime(), $fix?'fix':'check');
				$arrInst[1]->setGiftStatus(MONTHCARD_GIFTSTATUS::HASGIFT);
				if ($fix)
				{
					printf("fix uid:%d\n", $uid);
					$arrInst[1]->save();;
				}
			}
		}
			
		printf("done\n");
	}
	
	public static function getArrUid()
	{
		$data = new CData();
		
		$offset = 0;
		$arrRet = array();
		$limit = CData::MAX_FETCH_SIZE;
		while ($limit >= CData::MAX_FETCH_SIZE)
		{
			$ret = $data->select(array(DiscountCardDef::TBL_SQLFIELD_UID))
						->from(DiscountCardDef::DISCOUNTCARD_TBLNAME)
						->where(DiscountCardDef::TBL_SQLFIELD_UID,'>',0)
						->where(DiscountCardDef::TBL_SQLFIELD_CARDID,'=',2)
						->limit($offset, $limit)
						->query();
			$ret = Util::arrayExtract($ret, DiscountCardDef::TBL_SQLFIELD_UID);
			$arrRet = array_merge($arrRet, $ret);
			$offset += $limit;
			$limit = count($ret);
		}
		return $arrRet;
	}
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */