#!/bin/sh

sudo apt-get install openssh-server git-core php5-cli

echo "GRUB_RECORDFAIL_TIMEOUT=0" >> /etc/default/grub
update-grub
