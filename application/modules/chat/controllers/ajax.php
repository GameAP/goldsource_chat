<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Chat module for GameAP
 *
 * 
 *
 * @package		Online Chat
 * @author		Nikita Kuznetsov (ET-NiK)
 * @copyright	Copyright (c) 2013-2014, Nikita Kuznetsov (http://hldm.org)
 * @license		http://www.gameap.ru/license.html
 * @link		http://www.gameap.ru
 * @filesource
*/

/**
 * Ajax controller. Отправляет сообщение на сервер.
 * Обрабатывает логи на сервере и отправляет готовый результат.
 *
 * @package		Online Chat
 * @category	Controllers
 * @author		Nikita Kuznetsov (ET-NiK)
 */
class Ajax extends MX_Controller {
	
	var $servers_data = array();
	
	/* Автоматически загружаемые модели */
	var $autoload = array(
        'model' => array('users', 'servers', 'servers/logs'),
    );

	public function __construct()
    {
        parent::__construct();
        
		$this->load->database();
		
		if (!$this->users->check_user()) {
			show_404();
		}

	}
	
	// -------------------------------------------------------------
	
	/**
	 * Проверка сервера. Функция проверяет, существует ли сервер,
	 * настроен ли у него ftp и задана ли команда отправки сообщения в чат.
	 * 
	 * @param int - id сервера 
	 * 
	*/
	function _check_server($server_id) 
	{
		/* Получение данных сервера */
		if ($server_id) {
			$this->servers->get_server_data($server_id);
		} else {
			/* id сервера не задан */
			show_404();
		}
		
		if (!$this->servers->server_data) {
			/* Сервера с таким id не существет */
			show_404();
		}

		/* Если не задана команда отправки чата */
		if (!$this->servers->server_data['sendmsg_cmd']) {
			show_404();
		}
	}
	
	// -------------------------------------------------------------
	
	/**
	 * Отправляет сообщение на сервер с помощью rcon
	 * 
	 */
	function send()
    {
		/* С кириллицей работало */
		//$this->load->helper('translit');
		
		$server_id = (int)$this->input->post('server_id');
		$this->_check_server($server_id);
		
		$message = $this->users->auth_data['login'] . ': ' . $this->input->post('message_text');
		
		$this->load->driver('rcon');
		
		$this->rcon->set_variables(
					$this->servers->server_data['server_ip'],
					$this->servers->server_data['rcon_port'],
					$this->servers->server_data['rcon'], 
					$this->servers->servers->server_data['engine'],
					$this->servers->servers->server_data['engine_version']
		);
		
		if ($this->rcon->connect()) {
			$rcon_command = $this->servers->server_data['sendmsg_cmd'];
			$rcon_command = str_replace('{msg}', $message, $rcon_command);
			
			$this->rcon->command($rcon_command);
			$this->output->set_output('success');
		} else {
			$this->output->append_output('failed');
		}
	}
	
	// -------------------------------------------------------------
    
    /**
     * Парсит чат с последних 5 логов на сервере и выводит данные в json 
     * 
    */
    function get()
    {
		$this->load->helper('ds');
		
		$server_id = (int)$this->input->post('server_id');
		$this->_check_server($server_id);
		
		$game_code = $this->servers->server_data['start_code'];
		
		$list_logs = $this->logs->list_server_log('', array('log'), $game_code . '/logs', 5);
		
		$list_logs = array_reverse($list_logs);
		
		
		$dir = get_ds_file_path($this->servers->server_data) . '/' . $game_code . '/logs/';
		
		// Перебираем логи
		$logs_data = '';
		foreach ($list_logs as $arr) {
			try {
				$logs_data .= read_ds_file($dir . $arr['file_name'], $this->servers->server_data);
			} catch (Exception $e) {
				// Блаблабла
			}
		}
		
		$logs_data = iconv("UTF-8", "UTF-8//IGNORE", $logs_data);
		
		$logs_data_strings = explode("\n", $logs_data);
		unset($logs_data);
		
		/*
		 * Пример строк, которые обрабатываем:
		 * L 02/25/2012 - 21:36:05: "[UMI7EPATOP] Devastator<7><STEAM_0:0:474356155><robo>" say "Bах :-)"';
		*/
		$pattern = '/^L (\d\d)\/(\d\d)\/(\d\d\d\d) - (\d\d)\:(\d\d)\:(\d\d)\:(.*?) "(.*?)<(\d*)><([a-zA-Z0-9\_\:]*?)><(.*?)>" (say|triggered \"amx_say\" \(text) \"(.*?)\"/';
		$messages = '';
		foreach($logs_data_strings as &$str) {
			$preg_match = preg_match($pattern, $str, $matches);
			
			if ($preg_match) {
				/*
				 * $matches[1]-$matches[6] - дата
				 * $matches[8] - Ник
				 * $matches[9] - id
				 * $matches[10] - SteamID
				 * $matches[13] - Сообщение
				*/
				$date = $matches[4] . ':' . $matches[5] . ':' . $matches[6];
				$messages .= '<p class="chat_post_other"> [' . $date . '] &nbsp;&nbsp;<strong>' . htmlspecialchars($matches[8]) . '</strong>: ' . htmlspecialchars($matches[13]) . '</p>';
			}
		}
		
		$data_str = json_encode(array('messages' => $messages));

        $this->output->set_output($data_str);
	}

}

/* End of file ajax.php */
/* Location: ./application/modules/chat/controllers/ajax.php */
