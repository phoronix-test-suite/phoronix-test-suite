#!/bin/sh

echo "#!/bin/sh
rm *.png
cd \$TEST_XPLANE9
./xplane9 \$@
mv -f xplane_945_timedemo/*.png ~/" > xplane9-iqc
chmod +x xplane9-iqc
