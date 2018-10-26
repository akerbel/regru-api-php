<?php

namespace regruapi;

class RegruException extends \Exception
{
    /**
     * @var string Код ошибки regru api
     */
    protected $regruErrorCode;

    /**
     * @var mixed Парметры ошибки regru api
     */
    protected $regruErrorParams;

    /**
     * Получить код ошибки regru api
     *
     * @return string
     */
    public function getRegruErrorCode()
    {
        return $this->regruErrorCode;
    }

    /**
     * Получить парметры ошибки regru api
     *
     * @return mixed
     */
    public function getRegruErrorParams()
    {
        return $this->regruErrorParams;
    }

    /**
     * Конструктор
     *
     * @param string $message - текст ошибки
     * @param string    $code - код ошибки
     * @param mixed   $params - параметры ошибки
     */
    public function __construct($message, $code = null, $params = null)
    {
        parent::__construct($message);

        $this->regruErrorCode = $code;

        $this->regruErrorParams = $params;
    }

    /**
     * Получить ошибки валидации в виде массива
     *
     * @return array
     */
    public function getValidateError()
    {

        $res = [];
        foreach ($this->regruErrorParams['error_detail'] as $key=>$value) {

            if (!in_array($value['error_text'], $res)) {
                $res[] = $value['error_text'];
            }

        }

        if (!count($res)) {
            $res[] = "{$this->getRegruErrorCode()}: {$this->getMessage()}";
        }

        return $res;
    }

}
