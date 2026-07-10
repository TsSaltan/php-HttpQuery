<?php
namespace HttpQuery;

class HttpResponse {
	/**
	 * Curl resourse
	 */
	protected object $ch;

	/**
	 * @var string|null|bool
	 */
	protected string|null|bool $result;

	/**
	 * Query info
	 * @var array
	 */
	protected array $info;

	public function __construct(object $ch){
		$this->ch = $ch;
		$this->result = curl_exec($this->ch);
		$this->info = curl_getinfo($this->ch);
	}

	public function getError(): string {
		return curl_error($this->ch);
	}

	public function hasError(): bool {
		return $this->result === false || $this->getResponseCode() >= 500 || $this->getResponseCode() == 0 || strlen($this->getError()) > 0;
	}

	public function getInfo(): array {
		return $this->info;
	}

	public function isRedirected(): bool {
		return ($this->info['redirect_count'] ?? 0) > 0;
	}

	public function getRedirectedURI(): string {
		return curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);
	}

	public function getRequestHeader(): string {
		return curl_getinfo($this->ch, CURLINFO_HEADER_OUT);
	}

	public function getRequestMethod(): string {
		$headers = $this->getRequestHeader();
		if(preg_match('#([A-Z]{3,5})\s#U', $headers, $match)){
			return $match[1];
		}

		return 'GET';
	}

	public function getResponseBody(): mixed {
		return $this->result;
	}

	public function getResponseCode(): int {
		return $this->info['http_code'];
	}

	public function getResponseLength(): int {
		return $this->info['download_content_length'] ?? 0;
	}

	public function getResponseContentType(): ?string {
		return $this->info['content_type'];
	}

	public function __toString(): string {
		return (string) $this->getResponseBody();
	}

	public function getResponseJson(?bool $as_array = true): mixed {
		$contentType = $this->getResponseContentType();
		if(
			$contentType === 'application/json' ||
			$contentType === 'text/json'
		){
			$response = (string) $this->getResponseBody();
			$json = json_decode($response, $as_array);

			if(json_last_error() === JSON_ERROR_NONE){
				return $json;
			} else {
				throw new \Exception('Invalid response: ' . $response . '; JSON parse error: ' . json_last_error_msg());
			}
		} else {
			throw new \Exception('Response content-type is not valid JSON: ' . $contentType);
		}
	}

	public function __get(string $name): mixed {	
		$getter = 'getResponse' . ucfirst($name);
		if(method_exists($this, $getter)){
			return $this->$getter();
		}

		return null;
	}

	public function save(string $file){
		return file_put_contents($file, $this->getResponseBody());
	}
}