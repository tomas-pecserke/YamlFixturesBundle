#!/bin/sh -e
#
# install g++ 4.8
#
# Run as sudo

sudo add-apt-repository -y ppa:ubuntu-toolchain-r/test
sudo apt-get update -qq
sudo apt-get install -qq hhvm-dev g++-4.8 git-core automake autoconf libtool gcc libboost-dev libjemalloc-dev
sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 90

# install libgoogle.log-dev
wget http://launchpadlibrarian.net/80433359/libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
sudo dpkg -i libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
rm libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
wget http://launchpadlibrarian.net/80433361/libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb
sudo dpkg -i libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb
rm libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb

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
