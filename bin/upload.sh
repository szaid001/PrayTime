#!/bin/bash

source $(dirname $0)/config.sh

# --- MYSQL ---
#echo " ---> uploading database..."
# --upload whole database --- 
#mysqldump $DB_LOCAL | mysql $DB_REMOTE
#mysqldump $DB_LOCAL media_source | mysql $DB_REMOTE
#echo " --- end ---"

# --- RSYNC ---
echo " --> uploading files..."
set -x 
rsync $RSYNC_OPTIONS -e "ssh -i /Users/cavitkeskin/cavitkeskin.pem" $LOCAL_PATH/ $LOGINNAME@$SERVER:$REMOTE_PATH/

echo " --- end ---"

