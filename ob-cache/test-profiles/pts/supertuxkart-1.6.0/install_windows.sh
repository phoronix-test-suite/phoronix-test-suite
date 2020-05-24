#!/bin/sh

unzip -o SuperTuxKart-1.1-win.zip

echo "@echo off
cd SuperTuxKart-1.1-win\stk-code\build-mingw64\bin
supertuxkart.exe %* 2>&1
copy /Y \"%APPDATA%\supertuxkart\config-0.10\stdout.log\" ..\stdout.log
cd ~
" > supertuxkart.bat
chmod +x supertuxkart.bat
echo "#!/bin/sh

supertuxkart.bat \$@
mv stdout.log \$LOG_FILE
" > supertuxkart
chmod +x supertuxkart
