#!/bin/bash

LOCKFILE=$0
PATH_TO_CLX=$1

if [[ ! -d "$PATH_TO_CLX" ]]; then
    echo "Usage:"
    echo "cronjob.sh <pathToMainClx>"
    exit 0
fi

# make sure script is only running once at a time
(
    flock -e --timeout 300 200

    if [[ "$?" == 1 ]]
    then
        echo "ERROR: Lock could not be acquired"
        exit 1
    fi

    WEBSITE_LIST_FILE=`mktemp`
    cd $PATH_TO_CLX

    ./cx MultiSite list > $WEBSITE_LIST_FILE

    while read website; do
        OUTPUT=`./cx MultiSite pass $website Cron -s`
        if [[ $? != 0 ]] || [[ "$OUTPUT" != "" ]]; then
            echo "Executing Cronjobs for Website '$website'"
            echo $OUTPUT
        fi
    done < $WEBSITE_LIST_FILE

    #echo "Script terminated"
    rm "$WEBSITE_LIST_FILE"

) 200<$LOCKFILE

