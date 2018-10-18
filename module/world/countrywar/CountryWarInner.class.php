<?php
/***************************************************************************
 * 
 * Copyright (c) 2010 babeltime.com, Inc. All Rights Reserved
 * $Id: CountryWarInner.class.php 216904 2015-12-22 07:17:12Z ShiyuZhang $
 * 
 **************************************************************************/

 /**
 * @file $HeadURL: svn://192.168.1.80:3698/C/tags/card/rpcfw/rpcfw_1-0-41-55/module/world/countrywar/CountryWarInner.class.php $
 * @author $Author: ShiyuZhang $(zhangshiyu@babeltime.com)
 * @date $Date: 2015-12-22 07:17:12 +0000 (Tue, 22 Dec 2015) $
 * @version $Revision: 216904 $
 * @brief 
 * 
 *  服内接口入口，构造函数里做场景判定
 *  
 **/
class CountryWarInner implements ICountryWarInner
{
	private $uid = null;
	function __construct()
	{
		if( !CountryWarUtil::isInnerScene() )
		{
			throw new FakeException( 'call inner method, not in inner scene' );
		}
		
		$this->uid = RPCContext::getInstance()->getUid();
	}
	
	/* (non-PHPdoc)
	 * @see ICountryWarInner::getCoutrywarInfo()
	 */
	public function getCoutrywarInfo() 
	{
		return CountryWarLogic::getCoutrywarInfo();
	}
	
	/* (non-PHPdoc)
	 * @see ICountryWarInner::getCoutrywarInfoWhenLogin()
	*/
	public function getCoutrywarInfoWhenLogin() 
	{
		return CountryWarLogic::getCoutrywarInfoWhenLogin();
	}
	
	/* (non-PHPdoc)
	 * @see ICountryWarInner::getFinalMembers()
	 */
	public function getFinalMembers() 
	{
		CountryWarUtil::checkInnerBasicInfo();
		return CountryWarLogic::getFinalMembers();
	}
 
	/* (non-PHPdoc)
	 * @see ICountryWarInner::getMySupport()
	 */
	public function getMySupport() 
	{
		CountryWarUtil::checkInnerBasicInfo();
		return CountryWarLogic::getMySupport();
	} 

	/* (non-PHPdoc)
	 * @see ICountryWarInner::signForOneCountry()
	 */
	public function signForOneCountry($countryId) 
	{
		CountryWarUtil::checkInnerBasicInfo();
		if( !in_array( $countryId , CountryWarCountryId::$ALL) )
		{
			throw new FakeException( 'invalid countryId:%s', $countryId );
		}
		$ret = CountryWarLogic::signForOneCountry( $countryId);
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see ICountryWarInner::getLoginInfo()
	 */
	public function getLoginInfo() 
	{
		CountryWarUtil::checkInnerBasicInfo();
		$ret = CountryWarLogic::getLoginInfo( $this->uid );
		
		return $ret;
	}

	/* (non-PHPdoc)
	 * @see ICountryWarInner::supportOneUser()
	 */
	public function supportOneUser($pid, $serverId) 
	{
		CountryWarUtil::checkInnerBasicInfo();
		return CountryWarLogic::supportOneUser($pid, $serverId);
	}

	/* (non-PHPdoc)
	 * @see ICountryWarInner::supportFinalSide()
	 */
	public function supportFinalSide($side) 
	{
		CountryWarUtil::checkInnerBasicInfo();
		CountryWarUtil::checkSide($side);
		return CountryWarLogic::supportFinalSide($side);
	}

	/* (non-PHPdoc)
	 * @see ICountryWarInner::worship()
	 */
	public function worship() 
	{
		return CountryWarLogic::worship();
	}

	/* (non-PHPdoc)
	 * @see ICountryWarInner::exchangeCocoin()
	 */
	public function exchangeCocoin($amount) 
	{
		CountryWarUtil::checkInnerBasicInfo();
		return CountryWarLogic::exchangeCocoin($amount);
	}
	
}
/* vim: set ts=4 sw=4 sts=4 tw=100 noet: */