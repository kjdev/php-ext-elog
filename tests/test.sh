#!/bin/sh
if [ ! -t 0 ]; then
    BUFFER=`cat -`
fi

DIR=$(cd $(dirname $0); pwd)
LOG=${DIR}/test.log

echo $@ >> ${LOG} 2>&1
echo ${BUFFER} >> ${LOG} 2>&1

echo "test.sh"
