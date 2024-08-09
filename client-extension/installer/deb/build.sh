#!/bin/bash
set -e

# cd to working dir
cd "$(dirname "$0")"


# build client .deb package
BUILDDIR=oco-client-extension-linux

# empty / create necessary directories
if [ -d "$BUILDDIR/usr" ]; then
	rm -r $BUILDDIR/usr
fi

# copy files in place
install -D -m 755 ../../oco-client-extension-linux.py       -t $BUILDDIR/usr/bin
install -D -m 644 ../../oco-client-extension-linux.desktop  -t $BUILDDIR/usr/share/applications


# build debs
dpkg-deb -Zxz --root-owner-group --build $BUILDDIR

echo "Build finished"
