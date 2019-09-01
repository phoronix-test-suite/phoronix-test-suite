#!/bin/sh

HOME=$DEBUG_REAL_HOME steam steam://install/286690

# Early 2016 fix as outlined @ https://bugs.freedesktop.org/show_bug.cgi?id=95329
echo "#include <errno.h>
#include <malloc.h>
#include <stdlib.h>
#include <strings.h>

int posix_memalign(void **memptr, size_t alignment, size_t size) {
        if(alignment < 32) {
                alignment = 32;  // Optional. Might boost memcpy().
        }
        size *= 2;       // Required
        void *p = memalign(alignment, size);
        if(!p && size) {
                return ENOMEM;
        }
        bzero(p, size);  // Optional
        *memptr = p;
        return 0;
}" > posix_memalign.c
cc -m32 -shared -fPIC -O2 -g -Wall -Werror -std=c99 -o posix_memalign32.so posix_memalign.c
cc -m64 -shared -fPIC -O2 -g -Wall -Werror -std=c99 -o posix_memalign64.so posix_memalign.c

echo '#!/bin/sh
export LD_PRELOAD="$HOME/posix_memalign32.so:$HOME/posix_memalign64.so:$LD_PRELOAD"
cd $DEBUG_REAL_HOME/.steam/steam/steamapps/common/Metro\ 2033\ Redux

HOME=$DEBUG_REAL_HOME ./metro -benchmark benchmarks\\\\benchmark33 -bench_runs 1 -output_file $LOG_FILE -close_on_finish' > metro2033-redux
chmod +x metro2033-redux
