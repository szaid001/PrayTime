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
rsync $RSYNC_OPTIONS -e "ssh -v -i ${IDENTITY_FILE}" --exclude .git $LOCAL_PATH/ $SERVER_USER@$SERVER_ADDR:$SERVER_PATH/

echo " --- end ---"

