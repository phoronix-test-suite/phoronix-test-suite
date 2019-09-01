#!/bin/sh

#Reset Zapcc cache if required
[ "$CC" = "zapcc" ] && zapcc -cc1 -reset-server
