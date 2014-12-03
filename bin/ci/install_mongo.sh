#!/bin/sh -e
#
# install ext-mongo

if (php --version | grep -i HipHop > /dev/null); then
    wget https://github.com/mongofill/mongofill-hhvm/archive/master.tar.gz || exit 1
    tar xzf master.tar.gz || exit 1
    cd mongofill-hhvm-master
    ./build.sh || exit 1

    echo "hhvm.dynamic_extension_path = `pwd`" >> /etc/hhvm/php.ini
    echo "hhvm.dynamic_extensions[mongo] = mongo.so" >> /etc/hhvm/php.ini
else
    echo "extension = mongo.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi

# show mongo PHP extension version
echo "ext-mongo version: `php -r 'echo phpversion(\"mongo\");'`"
