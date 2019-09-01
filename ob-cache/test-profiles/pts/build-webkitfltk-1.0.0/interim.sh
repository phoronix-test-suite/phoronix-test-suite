#!/bin/sh

cd webkitfltk-0.1.1/
make -C Source/WTF/wtf clean
make -C Source/JavaScriptCore clean
make -C Source/WebCore clean
make -C Source/WebKit/fltk clean
