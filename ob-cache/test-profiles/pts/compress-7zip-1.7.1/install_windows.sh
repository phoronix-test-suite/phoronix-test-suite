#!/bin/sh

unzip -o 7z-win64-1602-files.zip

echo "#!/bin/sh
cd 7z-win64-1602-files/
./7z.exe b > \$LOG_FILE" > compress-7zip
chmod +x compress-7zip
