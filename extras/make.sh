#!/bin/bash

srcdir=`pwd`
phplibdir=${srcdir}/modules
php_config=`which php-config`

_sed=/usr/bin/sed
_copy=/usr/bin/cp
_make=/usr/bin/make
_grep=/usr/bin/grep

if [ -f ${srcdir}/Makefile ]; then
    ${_make} clean
else
    if [ -f configure ]; then
        ./configure --enable-coverage
    else
        phpize
        ./configure --enable-coverage
    fi
fi

${_make}

extension_dir=`$php_config --extension-dir 2>/dev/null`
if [ -f "${extension_dir}/posix.so" ]; then
    ${_copy} ${extension_dir}/posix.so ${phplibdir}/
    make_posix=`${_grep} posix ${srcdir}/Makefile`
    if [ -z "$make_posix" ]; then
        ${_sed} -i -e 's/\(\-d extension_dir=[^ ]*\) /\1 \-d extension=posix\.so /' ${srcdir}/Makefile
    fi
fi
