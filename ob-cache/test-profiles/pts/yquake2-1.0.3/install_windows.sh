#!/bin/sh
echo "clean up "
rm -rf Install license.txt pts-install.xml readme.txt Setup.exe Splash quake2-7.45a

echo "unpack windows yquake"
unzip quake2-7.45a.zip

echo "unpack quake 2 demo"
unzip q2-314-demo-x86.exe
cp -rv Install/Data/baseq2/pak0.pak quake2-7.45a/baseq2/
cp -rv Install/Data/baseq2/players quake2-7.45a/baseq2/

echo "add test profile"
cat > quake2-7.45a/baseq2/pts.cfg << EOF
unbindall
timedemo 1
set nextdemo quit
set demoloop "demomap q2demo1.dm2"
vstr demoloop
EOF

echo "create run script"
echo "#!/bin/sh
cd quake2-7.45a
./yquake2.exe \$@
mv \${USERPROFILE}\\\\Documents\\\\YamagiQ2\\\\stdout.txt \$LOG_FILE" > yquake2
chmod +x yquake2
