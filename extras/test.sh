#!/bin/sh

title="php-ext-elog"

srcdir=`pwd`
phplibdir=${srcdir}/modules
php_config=`which php-config`

SED=/usr/bin/sed
COPY=/usr/bin/cp
MAKE=/usr/bin/make
GREP=/usr/bin/grep

if [ -f ${srcdir}/Makefile ]; then
    ${MAKE} clean
else
    if [ -f configure ]; then
        ./configure
    else
        phpize
        ./configure
    fi
fi

${MAKE}

extension_dir=`$php_config --extension-dir 2>/dev/null`
if [ -f "${extension_dir}/posix.so" ]; then
    ${COPY} ${extension_dir}/posix.so ${phplibdir}/
    make_posix=`${GREP} posix ${srcdir}/Makefile`
    if [ -z "$make_posix" ]; then
        ${SED} -i -e 's/\(\-d extension_dir=[^ ]*\) /\1 \-d extension=posix\.so /' ${srcdir}/Makefile
    fi
fi
