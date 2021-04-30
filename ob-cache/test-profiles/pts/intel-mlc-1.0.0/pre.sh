#!/bin/bash

if [ -w /proc/sys/vm/nr_hugepages ] 
then
   cat /proc/sys/vm/nr_hugepages > nr_hugepages_archive
   echo 4000 > /proc/sys/vm/nr_hugepages
   sleep 1
fi
