package main

import (
	"flag"
	"fmt"
	frontend "github.com/couchbaselabs/transactions-fit-performer/performer"
	"github.com/couchbaselabs/transactions-fit-performer/protocol"
	"github.com/sirupsen/logrus"
	"google.golang.org/grpc"
	"google.golang.org/grpc/reflection"
	"log"
	"net"
	"os"
	"strconv"
)

func main() {
	phpBackendURL := flag.String("php-backend-url", envOrDefault("PHP_BACKEND_URL", "http://localhost:8000"),
		"base URL of the PHP backend")
	port := flag.Int("port", envOrDefaultInt("TXNPERFORMERPORT", 8060), "the port to listen on")
	version := flag.String("library-version", envOrDefault("LIBRARY_VERSION", ""),
		"couchbase extension version to report to the driver, e.g. 4.5.0")
	flag.Parse()

	logger := logrus.New()
	logger.SetFormatter(&logrus.TextFormatter{
		FullTimestamp:   true,
		TimestampFormat: "2006-01-02T15:04:05.999999999Z07:00",
	})
	logger.SetOutput(os.Stdout)
	logger.SetLevel(logrus.InfoLevel)

	// Empty is reported as-is: the driver treats it as unknown rather than comparing against it.
	if *version == "" {
		logger.Warn("no -library-version given; the driver will skip version-gated checks")
	}

	lis, err := net.Listen("tcp", fmt.Sprintf(":%d", *port))
	if err != nil {
		log.Fatalf("Failed to start listener: %v", err)
	}

	var performer protocol.PerformerServiceServer

	performer = frontend.NewPerformer(logger, *version, *phpBackendURL)

	grpcSrv := grpc.NewServer()
	protocol.RegisterPerformerServiceServer(grpcSrv, performer)
	reflection.Register(grpcSrv)

	logger.Logf(logrus.InfoLevel, "Starting grpc server at %d", *port)
	logger.Logf(logrus.InfoLevel, "Library version: %s", *version)
	err = grpcSrv.Serve(lis)
	if err != nil {
		log.Fatalf("Failed to start grpc server: %v", err)
	}
}

func envOrDefault(key, def string) string {
	if v := os.Getenv(key); v != "" {
		return v
	}
	return def
}

func envOrDefaultInt(key string, def int) int {
	v := os.Getenv(key)
	if v == "" {
		return def
	}
	parsed, err := strconv.Atoi(v)
	if err != nil {
		log.Printf("Invalid value %q for %s, using %d\n", v, key, def)
		return def
	}
	return parsed
}
