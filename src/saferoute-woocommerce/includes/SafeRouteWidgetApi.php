<?php

/**
 * API-скрипт виджета
 */
class SafeRouteWidgetApi
{
    /**
     * @var string API-ключ магазина
     */
    private $apiKey;

    /**
     * @var array Данные запроса
     */
    private $data;

    /**
     * @var string HTTP-метод запроса
     */
    private $method = 'POST';



    /**
     * @param string $apiKey API-ключ магазина
     */
    public function __construct($apiKey = '')
    {
        if ($apiKey) $this->setApiKey($apiKey);
    }

    /**
     * Сеттер API-ключа.
     *
     * @param string $apiKey API-ключ магазина
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * Сеттер данных запроса.
     *
     * @param array $data Данные запроса
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    /**
     * Сеттер метода запроса.
     *
     * @param string $method Метод запроса
     */
    public function setMethod($method)
    {
        $this->method = strtoupper($method);
    }

    /**
     * Отправляет запрос.
     *
     * @param string $url URL
     * @return string
     */
    public function submit($url)
    {
        $url = preg_replace('/:key/', $this->apiKey, $url);

        if ($this->method === 'GET')
        {
            $res = wp_remote_get($url . '?' . http_build_query($this->data));
        }
        else
        {
            $res = wp_remote_post($url, ['body' => $this->data]);
        }

        return $res['body'];
    }
}