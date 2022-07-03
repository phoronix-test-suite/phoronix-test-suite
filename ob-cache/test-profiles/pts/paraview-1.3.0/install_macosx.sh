#!/bin/sh
/System/Library/CoreServices/DiskImageMounter.app/Contents/MacOS/DiskImageMounter ParaView-5.10.1-MPI-OSX10.13-Python3.9-x86_64.dmg

echo "#!/bin/sh
/Applications/ParaView-5.10.1.app/Contents/bin/pvpython /Applications/ParaView-5.10.1.app/Contents/Python/paraview/benchmark/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > paraview
chmod +x paraview
