#!/bin/bash
set -e

ORT_VERSION="1.16.3"
INSTALL_DIR="${HOME}/.local/lib"
mkdir -p "$INSTALL_DIR"

echo "Installing ONNX Runtime ${ORT_VERSION}..."

# Detect platform
if [[ "$OSTYPE" == "linux-gnu"* ]]; then
    PLATFORM="linux"
    ARCH="x64"
    EXT="so"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    PLATFORM="osx"
    ARCH="x86_64"
    EXT="dylib"
else
    echo "Unsupported platform: $OSTYPE"
    exit 1
fi

URL="https://github.com/microsoft/onnxruntime/releases/download/v${ORT_VERSION}/onnxruntime-${PLATFORM}-${ARCH}-${ORT_VERSION}.tgz"

echo "Downloading from ${URL}..."
curl -L "$URL" -o /tmp/onnxruntime.tgz

echo "Extracting..."
tar -xzf /tmp/onnxruntime.tgz -C /tmp/

echo "Installing library..."
cp "/tmp/onnxruntime-${PLATFORM}-${ARCH}-${ORT_VERSION}/lib/libonnxruntime.${EXT}" "$INSTALL_DIR/"

echo "Cleaning up..."
rm -rf /tmp/onnxruntime.tgz "/tmp/onnxruntime-${PLATFORM}-${ARCH}-${ORT_VERSION}"

echo "ONNX Runtime installed to ${INSTALL_DIR}/libonnxruntime.${EXT}"
echo "Add to your LD_LIBRARY_PATH: export LD_LIBRARY_PATH=${INSTALL_DIR}:\$LD_LIBRARY_PATH"
