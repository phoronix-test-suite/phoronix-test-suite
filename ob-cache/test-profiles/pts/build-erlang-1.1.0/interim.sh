#!/bin/sh

rm -rf otp-OTP-23.2.6
tar -xf otp-OTP-23.2.6.tar.gz
cd otp-OTP-23.2.6
./otp_build autoconf
./configure
