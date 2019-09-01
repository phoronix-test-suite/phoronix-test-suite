#!/bin/sh

tar -xf php-7.2.12.tar.bz2
mv php-7.2.12 php_


echo "#!/bin/sh

\$PHP_BIN \$@ > \$LOG_FILE 2> /dev/null" > php
chmod +x php
