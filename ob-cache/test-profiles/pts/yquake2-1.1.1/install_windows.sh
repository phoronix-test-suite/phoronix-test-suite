#!/bin/sh
echo "clean up "
rm -rf Install license.txt pts-install.xml readme.txt Setup.exe Splash quake2-8.00

echo "unpack windows yquake"
unzip quake2-8.00.zip

echo "unpack windows yquake vulkan"
unzip ref_vk-1.0.1.zip
cp -v ref_vk-1.0.1/ref_vk.dll quake2-8.00/

echo "unpack quake 2 demo"
unzip q2-314-demo-x86.exe
cp -rv Install/Data/baseq2/pak0.pak quake2-8.00/baseq2/
cp -rv Install/Data/baseq2/players quake2-8.00/baseq2/

echo "add test profile"
cat > quake2-8.00/baseq2/pts.cfg << EOF
unbindall
timedemo 1
set nextdemo quit
set demoloop "demomap q2demo1.dm2"
vstr demoloop
EOF

echo "create run script"
echo "#!/bin/sh
cd quake2-8.00
./yquake2.exe \$@
mv \${USERPROFILE}\\\\Documents\\\\YamagiQ2\\\\stdout.txt \$LOG_FILE" > yquake2
chmod +x yquake2
