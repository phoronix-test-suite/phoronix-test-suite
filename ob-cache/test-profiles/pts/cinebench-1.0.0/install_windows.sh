#!/bin/sh

unzip -o CINEBENCHR15.038.zip

echo "#!/bin/sh
cd \"CINEBENCH R15.038_RC184115\"
cmd /c '.\CINEBENCH Windows 64 Bit.exe' \$@ > \$LOG_FILE" > cinebench

