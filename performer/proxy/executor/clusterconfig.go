package executor

import (
	"errors"
	"fmt"

	"github.com/couchbaselabs/transactions-fit-performer/phpbackend"
	"github.com/couchbaselabs/transactions-fit-performer/protocol/shared"
)

// ConnectionParams are sent with every request made against the connection.
func ConnectionParams(req *shared.ClusterConnectionCreateRequest) (phpbackend.ConnectionParams, error) {
	auth, err := authenticator(req)
	if err != nil {
		return phpbackend.ConnectionParams{}, err
	}

	config, err := clusterConfig(req.GetClusterConfig())
	if err != nil {
		return phpbackend.ConnectionParams{}, err
	}

	return phpbackend.ConnectionParams{
		Connstr: req.GetClusterHostname(),
		Auth:    auth,
		Config:  config,
	}, nil
}

// Drivers without SUPPORTS_AUTHENTICATOR (e.g. perf) send cluster_username/password instead.
func authenticator(req *shared.ClusterConnectionCreateRequest) (phpbackend.Authenticator, error) {
	passwordAuth := phpbackend.Authenticator{
		Kind:     "password",
		Username: req.GetClusterUsername(),
		Password: req.GetClusterPassword(),
	}

	switch a := req.GetAuthenticator().GetAuthenticator().(type) {
	case nil:
		return passwordAuth, nil
	case *shared.Authenticator_PasswordAuth:
		return phpbackend.Authenticator{
			Kind:     "password",
			Username: a.PasswordAuth.GetUsername(),
			Password: a.PasswordAuth.GetPassword(),
		}, nil
	case *shared.Authenticator_CertificateAuth:
		return phpbackend.Authenticator{
			Kind: "certificate",
			Cert: a.CertificateAuth.GetCert(),
			Key:  a.CertificateAuth.GetKey(),
		}, nil
	case *shared.Authenticator_JwtAuth:
		return phpbackend.Authenticator{
			Kind: "jwt",
			Jwt:  a.JwtAuth.GetJwt(),
		}, nil
	default:
		return phpbackend.Authenticator{}, fmt.Errorf("unsupported authenticator type %T", a)
	}
}

func clusterConfig(cfg *shared.ClusterConfig) (*phpbackend.ClusterConfig, error) {
	if cfg == nil {
		return nil, nil
	}

	// transactions_config is ignored, not rejected: the driver sets it on every connection regardless
	// of capabilities. The other two are capability-gated, so receiving one is a driver/caps mismatch.
	if cfg.ObservabilityConfig != nil {
		return nil, errors.New("cluster_config.observability_config was set but the performer " +
			"declares no OBSERVABILITY_1 capability (SpanCreate/SpanFinish are unimplemented)")
	}
	if cfg.CircuitBreakerConfig != nil {
		return nil, errors.New("cluster_config.circuit_breaker_config was set but the PHP SDK " +
			"exposes no circuit breaker options")
	}

	out := &phpbackend.ClusterConfig{
		UseTls:              cfg.GetUseTls(),
		UseCustomSerializer: cfg.GetUseCustomSerializer(),

		CertPath: cfg.CertPath,
		Cert:     cfg.Cert,
		Insecure: cfg.Insecure,

		KvConnectTimeoutSecs:   cfg.KvConnectTimeoutSecs,
		KvTimeoutMillis:        cfg.KvTimeoutMillis,
		KvDurableTimeoutMillis: cfg.KvDurableTimeoutMillis,
		KvScanTimeoutSecs:      cfg.KvScanTimeoutSecs,
		ViewTimeoutSecs:        cfg.ViewTimeoutSecs,
		QueryTimeoutSecs:       cfg.QueryTimeoutSecs,
		AnalyticsTimeoutSecs:   cfg.AnalyticsTimeoutSecs,
		SearchTimeoutSecs:      cfg.SearchTimeoutSecs,
		ManagementTimeoutSecs:  cfg.ManagementTimeoutSecs,

		EnableMutationTokens:          cfg.EnableMutationTokens,
		TcpKeepAliveTimeMillis:        cfg.TcpKeepAliveTimeMillis,
		EnableTcpKeepAlives:           cfg.EnableTcpKeepAlives,
		ForceIpv4:                     cfg.ForceIPV4,
		ConfigPollIntervalSecs:        cfg.ConfigPollIntervalSecs,
		ConfigPollFloorIntervalSecs:   cfg.ConfigPollFloorIntervalSecs,
		ConfigIdleRedialTimeoutSecs:   cfg.ConfigIdleRedialTimeoutSecs,
		NumKvConnections:              cfg.NumKvConnections,
		MaxHttpConnections:            cfg.MaxHttpConnections,
		IdleHttpConnectionTimeoutSecs: cfg.IdleHttpConnectionTimeoutSecs,
		PreferredServerGroup:          cfg.PreferredServerGroup,

		EnableAppTelemetry:           cfg.EnableAppTelemetry,
		AppTelemetryEndpoint:         cfg.AppTelemetryEndpoint,
		AppTelemetryBackoffSecs:      cfg.AppTelemetryBackoffSecs,
		AppTelemetryPingIntervalSecs: cfg.AppTelemetryPingIntervalSecs,
		AppTelemetryPingTimeoutSecs:  cfg.AppTelemetryPingTimeoutSecs,
	}

	if cfg.Transcoder != nil {
		// The backend rejects this (no cluster-level transcoder in PHP); that's its call.
		opts := &phpbackend.Options{}
		if err := setTranscoder(opts, cfg.Transcoder); err != nil {
			return nil, err
		}
		out.Transcoder = opts.Transcoder
	}

	return out, nil
}
