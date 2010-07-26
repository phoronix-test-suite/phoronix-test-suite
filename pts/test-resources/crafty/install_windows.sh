#!/bin/sh

unzip -o crafty-23.3-win64.exe.zip

echo "#!/bin/sh
crafty-23.3-win64.exe \$@ > \$LOG_FILE" > crafty
