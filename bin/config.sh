#!/bin/bash

LOGINNAME=ec2-user
#SERVER=web02
SERVER=54.187.56.99

USERNAME=binbir
PASSWORD=bin1
DBNAME=$USERNAME
LOCAL_PATH=~/Projects/praytime.local
REMOTE_PATH=/www/praytime.binbir.net
HOSTNAME_LOCAL=praytime.local
HOSTNAME_REMOTE=praytime.binbir.net

DB_LOCAL="-u $USERNAME -p$PASSWORD $DBNAME"
DB_REMOTE="-h $SERVER -u $USERNAME -p$PASSWORD $DBNAME"

DB_LOCAL_ADMIN="-u root"
DB_REMOTE_ADMIN="-h $SERVER -u root -pEbruNews01"

# -e "ssh -i /Users/cavitkeskin/cavitkeskin.pem"
RSYNC_OPTIONS="--archive --verbose --human-readable --progress --recursive --copy-links --delete"

MYSQL=$(which mysql)
FIND=$(which find)
TEXTUTIL=$(which textutil)
PANDOC=$(which pandoc)
BASENAME=$(which basename)
SED=$(which sed)
TR=$(which tr)
CURL=$(which curl)
PERL=$(which perl)
DATE=$(which date)
LS=$(which ls)
SORT=$(which sort)
HEAD=$(which head)
GREP=$(which grep)
CAT=$(which cat)
STAT=$(which stat)
CHMOD=$(which chmod)
