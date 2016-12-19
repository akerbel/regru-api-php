<?php
namespace regruapi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RegruClient
{
    const REGRU_URL = 'https://api.reg.ru/api/regru2/';
    
    /**
     * @var string Формат запроса
     */
    protected $inputFormat;
    
    /**
     * @var string Формат ответа
     */
    protected $outputFormat;
    
    /**
     * @var string Язык ответа
     */
    protected $lang;
    
    /**
     * @var string Логин пользователя в системе
     */
    protected $username;
    
    /**
     * @var string Пароль пользователя в системе
     */
    protected $password;
    
    /**
     * @var string Имя категории функций апи
     */
    protected $categoryName;
    
    /**
     * @var Logger Класс для логирования
     */
    protected $logger;
    
    /**
     * Магический метод __get
     *
     * @param string $name - имя параметра
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->$name;
    }
    
    /**
     * Магический метод __set
     *
     * @param string $name - имя параметра
     * @param mixed  $value - значение параметра
     *
     * @return $this
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
        return $this;
    }
    
    /**
     * Конструктор
     *
     * @param array $options - параметры соединения:
     *              username - логин пользователя в системе.
     *              password - проль пользователя в системе.
     *           inputFormat - формат запроса(json|xml|plain)
     *          outputFormat - формат ответа
     */
    public function __construct($options)
    {
        $this->username = $options['username'];
        $this->password = $options['password'];
        
        if (!empty($options['inputFormat'])) {
            $this->inputFormat = $options['inputFormat'];
        } else {
            $this->inputFormat = 'json';
        }
        
        if (!empty($options['outputFormat'])) {
            $this->outputFormat = $options['outputFormat'];
        } else {
            $this->outputFormat = 'json';
        }
        
        if (!empty($options['lang'])) {
            $this->lang = $options['lang'];
        } else {
            $this->lang = 'ru';
        }
        
        $log = new Logger('main');
        $log->pushHandler(new StreamHandler(__DIR__ . '/../log/main.log', Logger::DEBUG));

        $log->pushProcessor(
            new \Monolog\Processor\ProcessIdProcessor()
        );
        $log->pushProcessor(
            new \Monolog\Processor\IntrospectionProcessor()
        );
        $this->logger = $log;
    }
    
    /**
     * Выполнить запрос к regru api
     *
     * @param string $categoryName - имя категории функции апи
     * @param string $method       - имя функции апи
     * @param array $params        - параметры запроса
     *
     * @todo Добавить поддержку форматов XML и plain
     *
     * @throw RegruException
     *
     * @return array
     */
    public function request($categoryName, $method, $params)
    {
        // Формируем адрес
        $url = self::REGRU_URL."$categoryName/$method";
        
        // Собираем общие параметры
        $post_params = [
            'input_format' => $this->inputFormat,
            'output_format' => $this->outputFormat,
            'lang' => $this->lang,
            'username' => $this->username,
            'password' => $this->password,
        ];
        
        // Кодируем параметры запроса
        if (count($params)) {
            if ($this->inputFormat == 'json') {
                $post_params['input_data'] = json_encode($params);
            } else {
                throw new RegruException('Unsupported inputFormat', 'UNSUPPORTED_INPUTFORMAT', $post_params);
            }
        }
        
        // Отправляем запрос через guzzle
        try {
            
            $HttpClient = new Client();
            
            $this->logger->debug("Calling $url with options: ".print_r(self::censor($post_params), true));
            
            $raw = (string)$HttpClient->post($url, array('body' => $post_params))->getBody();
            
            $this->logger->debug("Got result: ".$raw);
            
        } catch (RequestException $e) {
            // Ловим эксепшоны guzzle и переводим их в наши
            $this->logger->error("Got exception: " . $e->getMessage());
            throw new RegruException($e->getMessage(), $e->getCode());
        }
        
        if ($this->outputFormat == 'json') {
            
            // Раскодируем ответ
            $result = json_decode($raw, 1);
            
            // Сбросим ошибку раскодировки
            if ((json_last_error() !==  JSON_ERROR_NONE) and  ($result === null) and (!is_array($result))) {
                throw new RegruException('Can`t decode answer.', json_last_error(), $raw);
            }
            
            // Сбрасываем ответы с ошибками
            if ($result['result'] != 'success') {
                throw new RegruException($result['error_text'], $result['error_code'], $result['error_params']);
            }
            
        } else {
            throw new RegruException('Unsupported outputFormat', 'UNSUPPORTED_OUTPUTFORMAT', $raw);
        }
        
        return $result;
    }
    
    /**
     * Вырезаем из параметров пароль
     *
     * @param array @array
     *
     * @return array
     */
    public static function censor($array)
    {
        if (!empty($array['password'])) {
            $array['password'] = '***';
        }
        
        if (!empty($array['REGRU_API_PASSWORD'])) {
            $array['REGRU_API_PASSWORD'] = '***';
        }
        
        if (!empty($array['original']['REGRU_API_PASSWORD'])) {
            $array['original']['REGRU_API_PASSWORD'] = '***';
        }
        
        return $array;
    }
    
}
