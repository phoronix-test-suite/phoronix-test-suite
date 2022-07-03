#!/bin/sh
tar -xf ParaView-5.10.1-MPI-Linux-Python3.9-x86_64.tar.gz

echo "#!/bin/sh
cd ParaView-5.10.1-MPI-Linux-Python3.9-x86_64/bin/
./pvpython \$HOME/ParaView-5.10.1-MPI-Linux-Python3.9-x86_64/lib/python3.9/site-packages/paraview/benchmark/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > paraview
chmod +x paraview
