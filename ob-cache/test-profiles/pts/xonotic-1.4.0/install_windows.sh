#!/bin/sh

unzip -o xonotic-0.8.0.zip
mv Xonotic Xonotic_

echo "#!/bin/sh
cd Xonotic_
xonotic.exe \$@ > \$LOG_FILE" > xonotic

