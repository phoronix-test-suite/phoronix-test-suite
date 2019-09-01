#!/bin/sh
cd Radiance-Benchmark4-master
RAYPATH=.:$HOME/radiance-5.0.0-Linux/usr/local/radiance/lib PATH=$HOME/radiance-5.0.0-Linux/usr/local/radiance/bin:$PATH make clean
