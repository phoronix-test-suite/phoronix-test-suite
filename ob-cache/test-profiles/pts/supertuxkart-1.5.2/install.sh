#!/bin/sh

tar xvfJ supertuxkart-0.9.3-linux.tar.xz

echo "#!/bin/sh

original_config=~/.config/supertuxkart/0.8.2/config.xml
if [ -f \$original_config ]; then
	original_config_found=true
else
	original_config_found=false
fi

if \$original_config_found; then mv \$original_config config.xml.back; fi    # make sure to start with the default config
cd supertuxkart-0.9.3-linux
LD_LIBRARY_PATH=\"./lib-64:\$LD_LIBRARY_PATH\" ./bin-64/supertuxkart \$@ 2>&1
cp ~/.config/supertuxkart/0.8.2/stdout.log \$LOG_FILE
cd ..
if \$original_config_found; then mv config.xml.back \$original_config; fi    # restore the original config file
" > supertuxkart
chmod +x supertuxkart

rm supertuxkart-0.9.3-linux.tar.xz
