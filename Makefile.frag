$(phplibdir)/libcouchbase_php_core.${SHLIB_SUFFIX_NAME}: 
	$(CMAKE) --build $(COUCHBASE_CMAKE_BUILD_DIRECTORY) --parallel 4 --verbose

.PHONY: build-core
build-core: $(phplibdir)/libcouchbase.${SHLIB_SUFFIX_NAME}
