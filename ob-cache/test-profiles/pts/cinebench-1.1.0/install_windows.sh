#!/bin/sh
mkdir CinebenchR20
cd CinebenchR20
unzip -o ../CinebenchR20.zip

echo "#!/bin/sh
cd \"CinebenchR20\"
cmd /c Cinebench.exe g_acceptDisclaimer=true \$@ > \$LOG_FILE" > ~/cinebench

