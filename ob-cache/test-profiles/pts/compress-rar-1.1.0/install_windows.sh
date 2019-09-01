#!/bin/sh
./winrar-x64-561.exe

echo "#!/bin/sh
Rar.exe a r to-compress.rar to-compress\\*" > compress-rar
chmod +x compress-rar 


