.PHONY: build-piper1 clean-piper1 init-submodules build-libpiper build-libs examples

PIPER_DIR := piper1-gpl
BUILD_DIR := $(PIPER_DIR)/build
LIBPIPER_BUILD_DIR := $(PIPER_DIR)/libpiper/build
LIBPIPER_INSTALL_DIR := $(LIBPIPER_BUILD_DIR)/piper1-gpl/libpiper/install
LIBS_DIR := libs

init-submodules:
	@echo "Initializing git submodules..."
	git submodule update --init --recursive
	@echo "Submodules initialized"

build-piper1: init-submodules build-libpiper
	@echo "Building piper1-gpl espeakbridge..."
	mkdir -p $(BUILD_DIR)
	cd $(BUILD_DIR) && cmake ..
	cd $(BUILD_DIR) && $(MAKE)
	@echo "Build complete. Output in $(BUILD_DIR)/"

build-libpiper:
	@echo "Building libpiper..."
	mkdir -p $(LIBPIPER_BUILD_DIR)
	cd $(LIBPIPER_BUILD_DIR) && cmake .. -DCMAKE_INSTALL_PREFIX=$(LIBPIPER_INSTALL_DIR)
	cd $(LIBPIPER_BUILD_DIR) && $(MAKE)
	cd $(LIBPIPER_BUILD_DIR) && $(MAKE) install
	@echo "libpiper installed to $(LIBPIPER_INSTALL_DIR)/"

build-libs: build-libpiper
	@echo "Copying libraries to $(LIBS_DIR)/..."
	mkdir -p $(LIBS_DIR)
	cp $(LIBPIPER_BUILD_DIR)/libpiper.so $(LIBS_DIR)/
	cp $(LIBPIPER_INSTALL_DIR)/lib/libonnxruntime.so* $(LIBS_DIR)/ 2>/dev/null || cp $(LIBPIPER_INSTALL_DIR)/lib/libonnxruntime*.so* $(LIBS_DIR)/
	cp -r $(LIBPIPER_INSTALL_DIR)/espeak-ng-data $(LIBS_DIR)/
	@echo "Libraries ready in $(LIBS_DIR)/"
	@echo "  - libpiper.so"
	@echo "  - libonnxruntime.so (and versioned files)"
	@echo "  - espeak-ng-data/"

examples: build-libs
	@echo "Checking for voice model files..."
	@if [ ! -f models/en_US-lessac-medium.onnx ]; then \
		echo "Downloading en_US-lessac-medium.onnx..."; \
		./bin/piper-tts download en_US-lessac-medium ./models; \
	else \
		echo "✓ en_US-lessac-medium.onnx exists"; \
	fi
	@if [ ! -f models/en_US-lessac-medium.onnx.json ]; then \
		echo "Downloading en_US-lessac-medium.onnx.json..."; \
		./bin/piper-tts download en_US-lessac-medium ./models; \
	else \
		echo "✓ en_US-lessac-medium.onnx.json exists"; \
	fi
	@echo ""
	@echo "Libraries and voice model ready!"
	@echo "You can now run: php examples/speak.php"

clean-piper1:
	@echo "Cleaning piper1-gpl build..."
	rm -rf $(BUILD_DIR) $(LIBPIPER_BUILD_DIR) $(LIBPIPER_INSTALL_DIR) $(LIBS_DIR)
	@echo "Clean complete"
