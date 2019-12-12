#!/bin/sh

unzip -o nexuiz-252.zip
mv Nexuiz Nexuiz_

echo "#!/bin/sh
cd Nexuiz_
./nexuiz.exe +exec effects-high.cfg \$@ > \$LOG_FILE" > nexuiz

