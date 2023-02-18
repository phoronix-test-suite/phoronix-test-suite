#!/bin/sh
tar -xf jfk-long-audio-sample-wav-1.tar.xz
pip3 install --user encodec==0.1.1
echo $? > ~/install-exit-status

# Run it first as on first run it needs to download things...
~/.local/bin/encodec -f jfk.wav out.ecdc

echo "#!/bin/sh
~/.local/bin/encodec \$@ out.ecdc
echo \$? > ~/test-exit-status" > encodec
chmod +x encodec
