#!/bin/sh
tar -xf ParaView-5.4.1-Qt5-OpenGL2-MPI-Linux-64bit.tar.gz

echo "#!/bin/sh

cd ParaView-5.4.1-Qt5-OpenGL2-MPI-Linux-64bit/bin

./pvpython \$HOME/ParaView-5.4.1-Qt5-OpenGL2-MPI-Linux-64bit/lib/python2.7/site-packages/paraview/benchmark/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > paraview
chmod +x paraview
