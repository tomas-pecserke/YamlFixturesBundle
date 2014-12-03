#!/bin/sh -e
#
# install ext-mongo

if (php --version | grep -i HipHop > /dev/null); then
    sudo apt-get install -qq hhvm-dev g++-4.8 libboost-dev
    sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 90

    # install libgoogle.log-dev
    wget http://launchpadlibrarian.net/80433359/libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
    sudo dpkg -i libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
    rm libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
    wget http://launchpadlibrarian.net/80433361/libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb
    sudo dpkg -i libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb
    rm libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb

    # install libjemalloc
    wget http://ubuntu.mirrors.tds.net/ubuntu/pool/universe/j/jemalloc/libjemalloc1_3.6.0-2_amd64.deb
    sudo dpkg -i libjemalloc1_3.6.0-2_amd64.deb
    rm libjemalloc1_3.6.0-2_amd64.deb
    wget http://ubuntu.mirrors.tds.net/ubuntu/pool/universe/j/jemalloc/libjemalloc-dev_3.6.0-2_amd64.deb
    sudo dpkg -i libjemalloc-dev_3.6.0-2_amd64.deb
    rm libjemalloc-dev_3.6.0-2_amd64.deb

    # compile libbson
    wget https://github.com/mongodb/libbson/archive/master.tar.gz
    tar xzf master.tar.gz
    rm master.tar.gz
    cd libbson-master
    ./autogen.sh
    ./configure
    make
    sudo make install
    cd ..
    rm libbson-master -r

    # compile mongofill-hhvm
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
