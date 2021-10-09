#!/bin/sh
echo "clean up "
rm -rf Install license.txt pts-install.xml readme.txt Setup.exe Splash yquake2-QUAKE2_8_00

echo "unpack yquake"
unzip -o QUAKE2_8_00.zip

echo "compile yquake"
cd yquake2-QUAKE2_8_00
make
echo $? > ~/install-exit-status
cd ..

echo "unpack yquake vulkan"
unzip -o v1.0.1.zip

echo "compile yquake"
cd ref_vk-1.0.1
make
cp -v release/ref_vk.so ../yquake2-QUAKE2_8_00/release/
cd ..

echo "unpack quake 2 demo"
unzip -o q2-314-demo-x86.exe
cp -rv Install/Data/baseq2/pak0.pak yquake2-QUAKE2_8_00/release/baseq2/
cp -rv Install/Data/baseq2/players yquake2-QUAKE2_8_00/release/baseq2/

echo "add test profile"
cat > yquake2-QUAKE2_8_00/release/baseq2/pts.cfg << EOF
unbindall
timedemo 1
set nextdemo quit
set demoloop "demomap q2demo1.dm2"
vstr demoloop
EOF

echo "create run script"
echo "#!/bin/sh
cd yquake2-QUAKE2_8_00/release
case \$OS_TYPE in
	*)
		./quake2 \$@ > \$LOG_FILE 2>&1
		echo \$? > ~/test-exit-status
	;;
esac" > yquake2
chmod +x yquake2

