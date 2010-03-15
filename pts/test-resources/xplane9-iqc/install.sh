#!/bin/sh

echo "#!/bin/sh
rm -f *.png
cd \$TEST_XPLANE9
rm -f xplane_945_timedemo/*.png
./xplane9 \$@
mv -f xplane_945_timedemo/*.png ~/" > xplane9-iqc
chmod +x xplane9-iqc
