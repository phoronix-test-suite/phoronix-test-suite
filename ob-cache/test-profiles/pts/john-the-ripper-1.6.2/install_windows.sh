#!/bin/sh

unzip -o john180j1w.zip

echo "#!/bin/sh
cd john180j1w/run/
./john.exe \$@ > \$LOG_FILE 2>&1" > john-the-ripper
chmod +x john-the-ripper
