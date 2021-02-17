#!/bin/sh
/System/Library/CoreServices/DiskImageMounter.app/Contents/MacOS/DiskImageMounter ParaView-5.9.0-MPI-OSX10.13-Python3.8-64bit.dmg

echo "#!/bin/sh

/Applications/ParaView-5.9.0.app/Contents/bin/pvpython /Applications/ParaView-5.9.0.app/Contents/Python/paraview/benchmark/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > paraview
chmod +x paraview
