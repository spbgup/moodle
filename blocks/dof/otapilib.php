<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * otapilib.php - OpenTechnology services API library
 *
 * @package    mdlotdistr
 * @subpackage lib
 * @copyright  2013 Alex Djachenko, Kirill Krasnoschekov, Ilya Fastenko
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Класс для стандартных операций по получению серийника
 *
 * Требуются следующие языковые строки:
 *  1 already_has_serial
 *  2 pageheader
 *  3 serial_check_ok
 *  4 serial_check_fail
 *  5 otserial
 *  6 otkey
 *  7 reset_serial
 *  8 get_serial
 *
 * @author krasnoschekov
 *
 */


/**
 * Класс доступа к OT API
 *
 * @author krasnoschekov
 * @version 2013082900
 *
 */
class block_dof_otserial_base
{
    public static $version = 2013082900;

    private $clientsurl = 'https://clients.opentechnology.ru/';
    private $requesturl = 'https://api.opentechnology.ru/';
    private $otserialuri = 'otserial/index.php';

    /** @var string Код продукта */
    protected $pcode;
    /** @var string Версия продукта */
    protected $pversion;
    /** @var string URL продукта */
    protected $purl;

    protected $otserial = '';
    protected $otkey = '';

    public function __construct($pcode, $pversion, $purl)
    {
        $this->pcode = $pcode;
        $this->pversion = $pversion;
        $this->purl = $purl;
    }

    /**
     * Получить серийник
     *
     * @return object stdClass
     * ->otserial
     * ->otkey
     *
     * @author Ilya Fastenko 2013
     */
    public function get_otserial()
    {
        //время отправки запроса
        $time = 10000*microtime(true);

        // данные для передачи
        $data = array(
                'pcode' => $this->pcode,
                'pversion' => $this->pversion,
                'time' => $time,
                'purl' => $this->purl,
        );

        //url запроса
        $url = $this->requesturl . $this->otserialuri;
        //параметры запроса
        $params = array('do' => 'get_serial');

        //Если у базового приложения есть серийник
        if ($bdata = $this->get_bproduct_data()) 
        {
            //серийник базового приложения
            $data['bpotserial'] = $bdata->otserial;
            $data['hash'] = $this->calculate_hash($bdata->otkey, $time, array(
                    $data['pcode'], $data['pversion'], $data['purl'], $bdata->otserial
            ));
        }

        //отправляем запрос на получение серийника
        try 
        {
            $response = json_decode($this->request($url, array_merge($data, $params)));
        } catch (Exception $e) 
        {
            $response = new stdClass();
            $response->status = "error_connection";
            $response->message = "Looks like your internet connection is down.";
        }
        return $response;
    }

    /**
     * Проверить статус продукта
     * Возвращает полученный ответ
     * @param object $otdata stdClass
     * ->otserial
     * ->otkey
     * @return string $response - статус серийника
     *
     * @author Ilya Fastenko 2013
     */
    public function get_otserial_status($otserial, $otkey)
    {
        $this->otserial = $otserial;
        $this->otkey = $otkey;

        //url запроса
        $url = $this->requesturl . $this->otserialuri;

        ////////////////////////////////////////////////////////////
        // Общая часть
        ////////////////////////////////////////////////////////////
        $time = 10000*microtime(true);

        $hash = $this->calculate_hash($otkey, $time, array($otserial));

        //параметры запроса
        $params = array(
                'otserial' => $otserial,
                'time' => $time,
                'hash' => $hash,
                'do' => 'get_status'
        );

        //отправляем запрос на получение серийника
        try 
        {
            $response = json_decode($this->request($url, $params));
        } catch (Exception $e) 
        {
            $response = new stdClass();
            $response->status = "error_connection";
            $response->message = "Looks like your internet connection is down.";
        }
        return $response;
    }

    /**
     * Получить информацию о базовом продукте (moodle otserial)
     */
    protected function get_bproduct_data()
    {
        $data = new stdClass();
        $data->otserial = get_config('core', 'otserial');
        $data->otkey = get_config('core', 'otkey');

        if ( !empty($data->otserial) AND !empty($data->otkey) ) 
        {
            return $data;
        }

        return false;
    }

    /**
     * Сформировать ссылку и добавить к ней хеш из key, time, otserial
     * @param unknown_type $str
     * @param array $params
     * @return moodle_url
     */
    public function url($str, array $params = array(), $prefix='clients')
    {
        $params['time'] = 10000*microtime(true);
        $params['otserial'] = $this->otserial;
        $params['hash'] = $this->calculate_hash($this->otkey, $params['time'], array($this->otserial));
        switch ($prefix)
        {
            case 'clients':
                $baseurl = $this->clientsurl;
                break;
            case 'api':
            default:
                $baseurl = $this->requesturl;
        }
        return new moodle_url($baseurl.$str, $params);
    }

    /**
     * Считает хеш от параметров запроса, ключа продукта OT и метки времени
     * @param string $otkey Ключ продукта ОТ
     * @param int $counter Метка времени
     * @param array $data Параметры запроса
     */
    private function calculate_hash($otkey, $counter, array $data)
    {
        return sha1("{$otkey}{$counter}" . implode('', $data));
    }

    /**
     * Выполнить запрос по указанному url с указанными параметрами
     *
     * @param string $url
     * @param array $get
     * @param array $post
     */
    private function request($url, array $get = array(), array $post=array())
    {
        // GET-параметры
        if ( !empty($get) )
        {
            $url .= "?";
            foreach ($get as $key => $value)
            {
                $url .= "{$key}={$value}&";
            }
        }

        $ch = curl_init($url);

        // Опции cURL
        $options = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSLVERSION => 1,
        );

        // POST-параметры
        if ( !empty($post) )
        {
            $options['CURLOPT_POST'] = 1;
            $options['CURLOPT_POSTFIELDS'] = http_build_query($post);
        }
        curl_setopt_array($ch, $options);

        // Выполняем запрос и получаем результат
        if ( !($rawret = curl_exec($ch)) )
        {// Ошибка
            $error = (string) curl_errno($ch);
            $error .= curl_error($ch);
            throw new Exception($error);
            return false;
        }
        // Завершаем соединеие
        curl_close($ch);

        return $rawret;
    }

}


/**
 * Класс, реализующий взаимодействие с apiot
 * @author Ilya Fastenko 2013
 */
class block_dof_otserial extends block_dof_otserial_base
{
    public function __construct()
    {
        global $CFG;
        $plugin = new stdClass();
        require($CFG->dirroot . '/blocks/dof/version.php');
        //URL приложения
        $purl = $CFG->wwwroot;

        parent::__construct($plugin->component, $plugin->version, $purl);
    }
}

?>
