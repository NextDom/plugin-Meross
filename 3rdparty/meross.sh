#!/bin/bash

# Timeout in secondes
timeout=30

# Get current directory
set_root() {
    local this=`readlink -n -f $1`
    root=`dirname $this`
}
set_root $0

# install prerequisites : pip3 install meross_iot -t /root/plugin-Meross/3rdparty/meross_iot

export PYTHONPATH=${root}/meross_iot/

timeout --signal=SIGINT ${timeout} python3 $root/meross.py $*
codret=$?

# Kill ghosts processes
nbprocess=$(pgrep -u www-data -f "python3 $root/meross.py" -c)
if [[ ! $nbprocess -eq 0 ]]; then
    pkill -u www-data -f "python3 $root/meross.py"
fi

exit ${codret}
