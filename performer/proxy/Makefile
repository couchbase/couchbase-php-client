# protocol/ is generated (not committed); the Dockerfile runs this target too.
PROTO_DIR := ../proto/operational
GO_MODULE := github.com/couchbaselabs/transactions-fit-performer

proto:
	rm -rf protocol
	protoc --proto_path=$(PROTO_DIR) \
		--go_opt=module=$(GO_MODULE) --go-grpc_opt=module=$(GO_MODULE) \
		--go_out=./ --go-grpc_out=./ \
		$(PROTO_DIR)/*.proto

.PHONY: proto
