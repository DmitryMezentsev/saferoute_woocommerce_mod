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
     * @param array $headers Дополнительные заголовки запроса
     * @return string
     */
    public function submit($url, $headers = [])
    {
        $url = preg_replace('/:key/', $this->apiKey, $url);
        
        if ($this->method === 'GET')
            $url .= '?' . http_build_query($this->data);
        
        $curl = curl_init($url);
        
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $this->method);
        
        if ($this->method === 'POST' || $this->method === 'PUT')
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($this->data));
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(['Content-Type:application/json'], $headers));
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        return $response;
    }
}