#!/bin/sh

unzip -o apitrace-apitrace-3.0-0-gde9f3e5.zip
7za -y x redeclipse-1-trace.7z

rm -rf apitrace_
mv -T apitrace-apitrace-de9f3e5 apitrace_
cd apitrace_/
cmake -H. -Bbuild
make -C build
echo $? > ~/install-exit-status

# extend this test profile for image quality comparison, i.e.
# glretrace -s dump/ -S 1600000-3613127/frame ../../redeclipse-1.trace 

cd ~/
echo "#!/bin/sh
cd apitrace_/build/
./glretrace -b ~/\$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > apitrace
chmod +x apitrace
