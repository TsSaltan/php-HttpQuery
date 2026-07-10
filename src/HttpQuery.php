<?php
namespace HttpQuery;

class HttpQuery {

	/**
	 * Curl resourse
	 */
	protected object $ch;

	/**
	 * Curl params
	 * @var array
	 */
	protected $params = [];

	public function __construct(?string $url = null){
		$this->ch = curl_init();
		$this->resetParams();
		if(!is_null($url)){
			$this->setURL($url);
		}
	}

	/**
	 * Сбросить параметры curl по умолчанию
	 */
	public function resetParams(){
		$this->params = [];
		return $this->setParams([
			CURLOPT_CONNECTTIMEOUT => 60,
			CURLOPT_TIMEOUT => 60,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLINFO_HEADER_OUT => true,
			CURLOPT_PROXY => null,
			CURLOPT_HTTPPROXYTUNNEL => 0
		]);
	}

	/**
	 * Установить URL
	 * @param string $url
	 */
	public function setURL(string $url){
		return $this->setParams([CURLOPT_URL => $url]);
	}

	/**
	 * Установить User-Agent
	 */
	public function setUserAgent(string $ua){	
		return $this->setParams([CURLOPT_USERAGENT => $ua]);
	}

	/**
	 * Генерация случайного браузерного UserAgent
	 */
	public function generateUserAgent(){
		$os = ['Windows NT 10.0; Win64; x64', 'Windows NT 10.0; WOW64', 'Windows NT 6.3; WOW64; rv:52.0', 'Macintosh; Intel Mac OS X 10_13_4', 'Macintosh; Intel Mac OS X 10_13_6'];
		$engine = [
			'Gecko/20100101 Firefox/52.0', 
			'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/' . rand(60,70) . '.0.' . rand(1000, 9999) . '.' . rand(100, 999) . ' Safari/537.36 OPR/43.0.' . rand(1000, 9999) . '.' . rand(100, 999),
			'AppleWebKit/605.1.15 (KHTML, like Gecko) Version/11.1 Safari/605.1.' . rand(0,20),
			'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/' . rand(60,70) . '.0.' . rand(1000, 9999) . '.' . rand(100, 999) . ' Safari/537.36'
		];

		$ua = 'Mozilla/5.0 ('. $os[array_rand($os)] .') ' . $engine[array_rand($engine)];
		return $this->setUserAgent($ua);
	}

	/**
	 * Получить список установленных параметров
	 * @return array [string_constant => value]
	 */
	public function getParams(bool $textKeys = true): array {
		if(!$textKeys) return $this->params;

		$params = [];
		$consts = get_defined_constants(true)['curl'] ?? [];
		foreach ($this->params as $key => $value) {
			// Сначала ищем curlopt
			foreach ($consts as $ckey => $cvalue) {
				if(strpos($ckey, 'CURLOPT_') === 0 && $cvalue == $key){
					$params[$ckey] = $value;
					continue 2;
				}
			}

			// Но также могут быть и curlinfo
			foreach ($consts as $ckey => $cvalue) {
				if(strpos($ckey, 'CURLINFO_') === 0 && $cvalue == $key){
					$params[$ckey] = $value;
					continue 2;
				}
			}
			
			$params[$key] = $value;
		}

		return $params;
	}

	/**
	 * Установить curl параметры
	 * @param array $params
	 */
	public function setParams(array $params = []){
		$this->params = $params + $this->params;
		curl_setopt_array($this->ch, $this->params);
		return $this;
	}

	/**
	 * Проверка SSL
	 */
	public function setSSLVerify(bool $value){
		return $this->setParams([
			CURLOPT_SSL_VERIFYHOST => ($value ? 2 : 0),
			CURLOPT_SSL_VERIFYPEER => $value
		]);
	}

	public function setFollowLocation(bool $value){
		return $this->setParams([
			CURLOPT_FOLLOWLOCATION => $value
		]);
	}

	/**
	 * Установить файл для хранения кук
	 */
	public function setCookieFile(string $cookieFile){
		$this->setParams([
			CURLOPT_COOKIEJAR => realpath($cookieFile),
			CURLOPT_COOKIEFILE => realpath($cookieFile)
		]);
	
		return $this;
	}

	/**
	 * Установить куки
	 * @param array $cookies [key => value, ...]
	 */
	public function setCookies(array $cookies){
		return $this->setParams([CURLOPT_COOKIE => http_build_query($cookies)]);
	}

	/**
	 * Установить заголовки
	 * @param array $headers [header1, header2, ...]
	 */
	public function setHeaders(array $headers){
		return $this->setParams([CURLOPT_HTTPHEADER => $headers]);
	}

	const PROXY_HTTP = CURLPROXY_HTTP;
	const PROXY_SOCKS4 = CURLPROXY_SOCKS4;
	const PROXY_SOCKS4A = CURLPROXY_SOCKS4A;
	const PROXY_SOCKS5 = CURLPROXY_SOCKS5;
	const PROXY_SOCKS5HOST = CURLPROXY_SOCKS5_HOSTNAME;
	
	/**
	 * Установить прокси для запроса
	 * @param string $proxy прокси-сервер в виде login:password@domain:port
	 * @param int $type тип прокси-сервера
	 * @param bool $tunnel 
	 * 
	 */
	public function setProxy(string $proxy, int $type = CURLPROXY_HTTP, bool $tunnel = false){
		return $this->setParams([
			CURLOPT_PROXY => $proxy,
			CURLOPT_PROXYTYPE => $type,
			CURLOPT_HTTPPROXYTUNNEL => ($tunnel ? 1 : 0)
		]);
	}

		/**
	 * Установить метод HTTP запроса
	 * @param string $method GET|POST|PUT|etc...
	 */
	public function setRequestMethod(string $method){
		$method = strtoupper($method);

		unset($this->params[CURLOPT_CUSTOMREQUEST]);
		unset($this->params[CURLOPT_POST]);
		unset($this->params[CURLOPT_PUT]);
		unset($this->params[CURLOPT_POSTFIELDS]);

		switch ($method) {
			case 'GET':
				$this->setParams();
				break;
			
			case 'POST':
				$this->setParams([CURLOPT_POST => true]);
				break;

			case 'PUT':
				$this->setParams([CURLOPT_PUT => true]);
				break;

			default:
				$this->setParams([CURLOPT_CUSTOMREQUEST => $method]);
				break;
		}

		return $this;
	}

	/**
	 * Запустить выполнение запроса
	 * @param int|integer $attempts Количество попыток
	 * @param bool $throw_exception Выбрасывать исключение при неудачном завершении запроса
	 * @return HttpResponse
	 */
	public function exec(int $attempts = 1, bool $throw_exception = true): HttpResponse {
		$attempt = 0;
		do {
			$response = new HttpResponse($this->ch);
		}
		while($attempt++ < $attempts && $response->hasError());

		if($throw_exception && $response->hasError()){
			throw new HttpException('Invalid query: ' . $response->getError());
		}

		return $response;
	}


	/**
	 * Выполнить GET запрос
	 * @return HttpResponse
	 */
	public function get(int $attempts = 1){
		$this->setRequestMethod('GET');
		return $this->exec($attempts);
	}

	/**
	 * Выполнить POST запрос
	 * @return HttpResponse
	 */
	public function post(array|string $data, int $attempts = 1){
		$this->setRequestMethod('POST');
		$data = is_array($data) ? http_build_query($data) : $data;
		$this->setParams([CURLOPT_POSTFIELDS => $data]);
		return $this->exec($attempts);
	}
}