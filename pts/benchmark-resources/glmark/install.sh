#!/bin/sh

cd $1

tar -xvf GLMark-0.1.tar.gz
gcc *.c -o glmark -lSDL -lGL -lGLU

