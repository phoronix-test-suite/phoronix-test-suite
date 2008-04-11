#!/bin/sh

cd $1

if [ ! -f GLMark-0.1.tar.gz ]
  then
     wget http://internap.dl.sourceforge.net/sourceforge/glmark/GLMark-0.1.tar.gz -O GLMark-0.1.tar.gz
fi

tar -xvf GLMark-0.1.tar.gz
gcc *.c -o glmark -lSDL -lGL -lGLU

