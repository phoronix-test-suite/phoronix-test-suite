#!/bin/sh

unzip -o john-1.9.0-jumbo-1-win64.zip

echo "#!/bin/sh
cd john-1.9.0-jumbo-1-win64/run/
rm -f cygwin1.dll
./john.exe \$@ > \$LOG_FILE 2>&1" > john-the-ripper
chmod +x john-the-ripper
