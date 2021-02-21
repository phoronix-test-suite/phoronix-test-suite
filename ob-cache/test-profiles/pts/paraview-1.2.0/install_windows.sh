#!/bin/sh
unzip -o ParaView-5.9.0-Windows-Python3.8-msvc2017-64bit.zip
echo "#!/bin/sh

cd ParaView-5.9.0-Windows-Python3.8-msvc2017-64bit/bin

./pvpython Lib/site-packages/paraview/benchmark/\$@ > \$LOG_FILE" > paraview
chmod +x paraview

# echo "You may need to manually install Microsoft MPI if not already done so for this test to run: http://go.microsoft.com/FWLink/p/?LinkID=389556" > ~/install-message
