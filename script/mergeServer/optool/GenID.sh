#!/bin/bash

#get base path
BASEPATH=`readlink -f $0`
BASEPATH=`dirname $BASEPATH`
BASEPATH=`dirname $BASEPATH`

#include format functions
source $BASEPATH/shell/config.sh
source $BASEPATH/optool/formatOutput.sh
source $BASEPATH/optool/dbLcserverInfo.sh

function usage()
{
	echo_usage "Usage:"
	echo_usage "	generate id on slave db with group id"
	echo_usage "	sh $0 groupid id.xml data.xml"
}

if [ $# -lt 3 ]; then
	usage;
	exit 1;
fi

GROUP=$1
ID_XML_PATH=$2
DATA_XML_PATH=$3

# valid input group id
if [ `validGroupId $GROUP` -ne 1 ]; then
	echo_error "$GROUP is invalid";
	usage;
	exit 1;
fi

# get slave database ip
SIMPLEDBNAME=`getSimpleDBName $GROUP`
SLAVEDB=`getDataproxyIP $GROUP`
MASTERDB=`getMasterDBIP $GROUP`
if [ -z $SLAVEDB ]; then
	echo_error "$GROUP slave db not exist!exit!";
	exit 1;
fi

# check dataproxy/data/ directory is exist
datadir=`ssh $SLAVEDB "ls -l /home/pirate/dataproxy/data/ | grep 'pirate$SIMPLEDBNAME\$'" | wc -l`
if [ $datadir -ne 0 ]; then
	echo_notice "$GROUP data dir exist in slave db $SLAVEDB! exit!";
	#exit 0;
fi

mkdir -p $MERGEPATH/$GROUP/IDS/pirate$SIMPLEDBNAME
cd $MERGEPATH/$GROUP/IDS/pirate$SIMPLEDBNAME

# generate id files
$PHPPATH/bin/php $BASEPATH/script/DataproxyGenIdFetch.php $MASTERDB pirate$SIMPLEDBNAME $MYSQLUSER $MYSQLPASSWD $ID_XML_PATH $DATA_XML_PATH

# check data file number 
if [ `ls -l $MERGEPATH/$GROUP/IDS/pirate$SIMPLEDBNAME | wc -l` -lt 2 ]; then
	echo_error "$GROUP data generator error! exit!";
	exit 1;
fi

# scp id files to target server
scp -r $MERGEPATH/$GROUP/IDS/pirate$SIMPLEDBNAME pirate@$SLAVEDB:$DATAPROXYPATH/data/

# check data file on slave db
datadir=`ssh $SLAVEDB "ls -l /home/pirate/dataproxy/data/ | grep 'pirate$SIMPLEDBNAME\$'" | wc -l`
if [ $datadir -eq 0 ]; then
	echo_error "$GROUP data dir not exist in slave db $SLAVEDB! exit!";
	exit 1;
fi

exit 0;
