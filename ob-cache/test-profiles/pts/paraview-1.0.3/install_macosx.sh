#!/bin/sh
/System/Library/CoreServices/DiskImageMounter.app/Contents/MacOS/DiskImageMounter ParaView-5.4.1-Qt5-OpenGL2-MPI-OSX10.8-64bit.dmg

echo "#!/bin/sh

/Applications/ParaView-5.4.1.app/Contents/bin/pvpython /Applications/ParaView-5.4.1.app/Contents/Python/paraview/benchmark/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > paraview
chmod +x paraview
