#!/bin/bash
if [ -w /proc/sys/vm/nr_hugepages ] 
then
  cat nr_hugepages_archive > /proc/sys/vm/nr_hugepages
fi
