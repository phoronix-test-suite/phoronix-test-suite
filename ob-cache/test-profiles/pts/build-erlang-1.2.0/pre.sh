#!/bin/sh
rm -rf otp-OTP-25.0.4
tar -xf otp-OTP-25.0.4.tar.gz
cd otp-OTP-25.0.4
./otp_build autoconf
./configure
