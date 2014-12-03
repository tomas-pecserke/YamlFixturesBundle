#!/bin/sh -e
#
# install g++ 4.8
#
# Run as sudo

sudo add-apt-repository -y ppa:ubuntu-toolchain-r/test
sudo apt-get update -qq
sudo apt-get install -qq hhvm-dev g++-4.8 git-core automake autoconf libtool gcc
sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 90

# compile libbson
wget https://github.com/mongodb/libbson/archive/master.tar.gz
tar xzf master.tar.gz
rm master.tar.gz
cd libbson-master
./configure
make
sudo make install
cd ..
rm libbson-master -r
