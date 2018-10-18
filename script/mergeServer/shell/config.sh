#!/bin/bash

#zookeepr hosts
ZKHOSTS="192.168.1.91:2181"

HOMEDIR=/home/pirate
#third programes path
MYSQLPATH=$HOMEDIR/programs/mysql/
PHPPATH=$HOMEDIR/programs/php/
DATAPROXYPATH=$HOMEDIR/dataproxy
BINPATH=$HOMEDIR/bin

#developed programes path
LCSERVERPATH=$HOMEDIR/lcserver
RPCFWPATH=$HOMEDIR/rpcfw

#merge path
MERGEOPBAK=$HOMEDIR/opbak/merge_server
MERGEPATH=$HOMEDIR/sanguo/mergeServer

#mysql user and password
MYSQLUSER=admin
MYSQLPASSWD=BabelTime
MYSQLROOTPASSWD=
DBPRE=pirate

#zookeeper data path
ZKPATH=/card
ZKLOGICPATH=/card/logic
ZKLCSERVERPATH=/card/lcserver
ZKLCSERVERPREFIX=/lcserver#game
ZKDATAPATH=/card/dataproxy
ZKDATAPREFIX=/data#pirate

#game 
SERVERPRE=game