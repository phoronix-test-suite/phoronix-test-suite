#!/bin/sh

if which gegl>/dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: gegl is not found on the system! This test profile needs the 'gegl' commandline in the PATH"
	echo 2 > ~/install-exit-status
fi
unzip -o sample-photo-6000x4000-1.zip
tar -xf stock-photos-jpeg-2018-1.tar.xz
tar -xf pts-sample-photos-2.tar.bz2
cd ~
echo "#!/bin/sh
for i in *.JPG
do
	gegl -i \$i -o out.png -- \$@
done
echo \$? > ~/test-exit-status" > gegl
chmod +x gegl
