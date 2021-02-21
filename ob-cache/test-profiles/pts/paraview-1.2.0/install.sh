#!/bin/sh
tar -xf ParaView-5.9.0-MPI-Linux-Python3.8-64bit.tar.gz

echo "#!/bin/sh

cd ParaView-5.9.0-MPI-Linux-Python3.8-64bit/bin

./pvpython \$HOME/ParaView-5.9.0-MPI-Linux-Python3.8-64bit/lib/python3.8/site-packages/paraview/benchmark/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > paraview
chmod +x paraview
