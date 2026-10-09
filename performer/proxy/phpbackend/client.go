package phpbackend

import (
	"bytes"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"net/http"
)

type Client struct {
	baseURL string
	http    *http.Client
}

// Must be >= the driver's workload concurrency or latencies measure proxy queueing; PHP workers must match.
const maxBackendConns = 128

func New(baseURL string) *Client {
	// Default MaxIdleConnsPerHost is 2, which churns connections under load.
	transport := http.DefaultTransport.(*http.Transport).Clone()
	transport.MaxIdleConns = maxBackendConns
	transport.MaxIdleConnsPerHost = maxBackendConns
	transport.MaxConnsPerHost = maxBackendConns

	return &Client{
		baseURL: baseURL,
		// No Timeout: slow ops (durability etc.) must not be misreported as performer errors.
		http: &http.Client{Transport: transport},
	}
}

// CheckConnection surfaces bad options or an unreachable cluster at create time; it is not a readiness check.
func (c *Client) CheckConnection(params ConnectionParams) error {
	var out connectionResponse
	if err := c.post("/connection/check", connectionRequest{Connection: params}, &out); err != nil {
		return err
	}
	if !out.OK {
		return fmt.Errorf("php backend rejected connection: %s: %s", out.Exception.GetName(), out.Exception.GetSerialized())
	}
	return nil
}

// CloseConnection is best-effort: the SDK's persistent-connection cache has no evict API.
func (c *Client) CloseConnection(params ConnectionParams) error {
	return c.post("/connection/close", connectionRequest{Connection: params}, nil)
}

func (c *Client) Execute(req ExecuteRequest) (*ExecuteResponse, error) {
	var out ExecuteResponse
	if err := c.post("/execute", req, &out); err != nil {
		return nil, err
	}
	return &out, nil
}

func (c *Client) ExecuteStream(req ExecuteRequest) (*Stream, error) {
	buf, err := json.Marshal(req)
	if err != nil {
		return nil, fmt.Errorf("marshalling streaming request: %w", err)
	}

	resp, err := c.http.Post(c.baseURL+"/execute/stream", "application/json", bytes.NewReader(buf))
	if err != nil {
		return nil, fmt.Errorf("php backend streaming request failed: %w", err)
	}
	if resp.StatusCode != http.StatusOK {
		data, _ := io.ReadAll(resp.Body)
		resp.Body.Close()
		return nil, fmt.Errorf("php backend /execute/stream returned status %d: %s", resp.StatusCode, string(data))
	}

	return &Stream{body: resp.Body, decoder: json.NewDecoder(resp.Body)}, nil
}

// Stream is an NDJSON response; reading pace drives the backend's SDK iteration (approximately, due to buffering).
type Stream struct {
	body    io.ReadCloser
	decoder *json.Decoder
}

// Next returns io.EOF when the body ends; EOF before a Complete line means the backend died.
func (s *Stream) Next() (*StreamLine, error) {
	var line StreamLine
	if err := s.decoder.Decode(&line); err != nil {
		if errors.Is(err, io.EOF) {
			return nil, io.EOF
		}
		return nil, fmt.Errorf("decoding streamed line from php backend: %w", err)
	}
	return &line, nil
}

// Close aborts the backend script on its next write.
func (s *Stream) Close() error {
	return s.body.Close()
}

func (c *Client) post(path string, body interface{}, out interface{}) error {
	buf, err := json.Marshal(body)
	if err != nil {
		return fmt.Errorf("marshalling request to %s: %w", path, err)
	}

	resp, err := c.http.Post(c.baseURL+path, "application/json", bytes.NewReader(buf))
	if err != nil {
		return fmt.Errorf("php backend request to %s failed: %w", path, err)
	}
	defer resp.Body.Close()

	data, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("reading php backend response from %s: %w", path, err)
	}

	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("php backend %s returned status %d: %s", path, resp.StatusCode, string(data))
	}

	if out == nil {
		return nil
	}
	if err := json.Unmarshal(data, out); err != nil {
		return fmt.Errorf("decoding php backend response from %s: %w", path, err)
	}
	return nil
}
