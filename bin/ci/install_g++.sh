#!/bin/sh -e
#
# install g++ 4.8
#
# Run as sudo

add-apt-repository -y ppa:ubuntu-toolchain-r/test
apt-get update -qq
apt-get install -qq hhvm-dev g++-4.8 libbson
update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 90
