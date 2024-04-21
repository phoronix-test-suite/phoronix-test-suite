#!/bin/sh
unzip -o FurMark_2.1.0.2_win64.zip
echo "#!/bin/sh
cd FurMark_win64/
./furmark.exe \$@ > \$LOG_FILE" > furmark
chmod +x furmark
