.PHONY: build-piper1 clean-piper1 init-submodules build-libpiper

PIPER_DIR := piper1-gpl
BUILD_DIR := $(PIPER_DIR)/build
LIBPIPER_BUILD_DIR := $(PIPER_DIR)/libpiper/build
LIBPIPER_INSTALL_DIR := $(PIPER_DIR)/libpiper/install

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

clean-piper1:
	@echo "Cleaning piper1-gpl build..."
	rm -rf $(BUILD_DIR) $(LIBPIPER_BUILD_DIR) $(LIBPIPER_INSTALL_DIR)
	@echo "Clean complete"
