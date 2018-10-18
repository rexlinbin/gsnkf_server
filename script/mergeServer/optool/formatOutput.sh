#!/bin/bash



#
#  print error function
#
function echo_error() {
    local message=$1

    local caller=${FUNCNAME[1]}
    local line=${BASH_LINENO[0]}
    local file=${BASH_SOURCE[1]}

    file=${file/$BASE_PATH\//}
    echo -e "[ERROR][$file:$line][$caller]\033[49;31;5;1m${message}\033[49;31;0m" >&2
}

#
#  print notice function
#
function echo_notice()
{
    local message=$1

    local caller=${FUNCNAME[1]}
    local line=${BASH_LINENO[0]}
    local file=${BASH_SOURCE[1]}

    file=${file/$BASE_PATH\//}
    echo -e "[NOTICE][$file:$line][$caller]\033[49;36;1m${message}\033[49;34;0m\n"
}

#
#  print usage function
#
function echo_usage()
{
    local message=$1

    echo -e "\033[49;32;1m${message}\033[49;32;0m"
}

#
#  print info function
#
function echo_info()
{
    local message=$1

    local caller=${FUNCNAME[1]}
    local line=${BASH_LINENO[0]}
    local file=${BASH_SOURCE[1]}

    file=${file/$BASE_PATH\//}
    echo -e "[INFO][$file:$line][$caller]\033[49;32;1m${message}\033[49;32;0m"
}

#
#  print prompt function
#
function echo_prompt() {
    local message=$1

    echo -e "\033[49;34;1m${message}\033[49;34;0m" >&2
}