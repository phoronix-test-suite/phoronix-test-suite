#!/bin/sh
mkdir CinebenchR23
cd CinebenchR23
unzip -o ../CinebenchR23.zip

echo "#!/bin/sh
cd \"CinebenchR23\"
cmd /c Cinebench.exe g_acceptDisclaimer=true \$@ > \$LOG_FILE" > ~/cinebench
