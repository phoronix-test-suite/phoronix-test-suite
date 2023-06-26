#!/bin/sh
rm -rf Xonotic_
unzip -o xonotic-0.8.6.zip
mv Xonotic Xonotic_
echo "#!/bin/sh
cd Xonotic_
./xonotic.exe \$@ > \$LOG_FILE" > xonotic
