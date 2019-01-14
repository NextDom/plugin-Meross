#!/bin/bash

# Get current directory
set_root() {
    local this=`readlink -n -f $1`
    root=`dirname $this`
}
set_root $0

export PYTHONPATH=${root}/meross_iot/

python3 $root/meross.py $*


# install prerequisites : pip3 install meross_iot -t /root/plugin-Meross/3rdparty/meross_iot
