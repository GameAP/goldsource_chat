<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Chat module for GameAP
 *
 * 
 *
 * @package		Chat
 * @author		Nikita Kuznetsov (ET-NiK)
 * @copyright	Copyright (c) 2013, Nikita Kuznetsov (http://hldm.org)
 * @license		http://www.gameap.ru/license.html
 * @link		http://www.gameap.ru
 * @filesource
*/

class Chat extends MX_Controller {
	
	var $servers_data 	= array();
	var $tpl_data 		= array();
	
	/* Автоматически загружаемые модели */
	var $autoload = array(
        'model' => array('users', 'servers', 'servers/dedicated_servers', 'servers/games', 'servers/game_types'),
    );

	function __construct()
    {
        parent::__construct();
        
        /* Авторизован ли юзер */
        if (!$this->users->check_user()) {
			redirect('auth/in');
		}
        
        /* Load DataBase */
		$this->load->database();
		
		/* Загрузка языковых файлов */
		$this->lang->load('server_files');
        $this->lang->load('server_command');
		
		/* Templates */
		$this->tpl_data['title'] = 'GameAP :: GoldSource chat';
		$this->tpl_data['heading'] = 'GoldSource chat';
		$this->tpl_data['menu'] = $this->parser->parse('menu.html', $this->tpl_data, TRUE);
		$this->tpl_data['profile'] = $this->parser->parse('profile.html', $this->users->tpl_userdata(), true);
    }
    
    // Отображение информационного сообщения
    function _show_message($message = FALSE, $link = FALSE, $link_text = FALSE)
    {
        
        if (!$message) {
			$message = lang('error');
		}
		
        if (!$link) {
			$link = 'javascript:history.back()';
		}
		
		if (!$link_text) {
			$link_text = lang('back');
		}

        $local_tpl_data['message'] = $message;
        $local_tpl_data['link'] = $link;
        $local_tpl_data['back_link_txt'] = $link_text;
        $this->tpl_data['content'] = $this->parser->parse('info.html', $local_tpl_data, TRUE);
        $this->parser->parse('main.html', $this->tpl_data);
    }
    
    function _check_server($server_id) {
		/* Получение данных сервера */
		if ($server_id) {
			$this->servers->get_server_data($server_id);
		} else {
			/* id сервера не задан */
			redirect('chat');
		}
		
		if (!$this->servers->server_data) {
			/* Сервера с таким id не существет */
			redirect('chat');
		}
		
		/* Если сервер не локальный и не настроен FTP, то выдаем ошибку */
		if ($this->servers->server_data['ds_id'] && !$this->servers->server_data['ftp_host']){
			$this->_show_message(lang('server_files_ftp_not_set'), site_url('chat'));
			return FALSE;
		}
		
		/* Если не задана команда отправки чата */
		if (!$this->servers->server_data['sendmsg_cmd']) {
			$this->_show_message('Команда отправки сообщения не задана', site_url('chat'));
			return FALSE;
		}
		
		return TRUE;
	}
    
    // ----------------------------------------------------------------
    
    /**
     * Главная страница чата. Выбор сервера.
     * Перед пользователем появляется таблица, с серверами, к которым у него есть доступ.
     * 
     * @param int - ID сервера
    */
    function index()
    {
		$this->servers->get_server_list($this->users->auth_id);
		
		$local_tpl_data['servers_list'] = $this->servers->tpl_data();
		$local_tpl_data['url'] 			= site_url('chat/server');
			
		$this->tpl_data['content'] = $this->parser->parse('servers/select_server.html', $local_tpl_data, TRUE);
		$this->parser->parse('main.html', $this->tpl_data);

	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Отображение чата
	 * 
	 */
	function server($server_id) 
	{
		$server_id = (int)$server_id;
		
		
		if (!$this->_check_server($server_id)) {
			return FALSE;
		}
		
		/* У пользователя должны быть привилегии отправки сообщений в чат */
		$this->users->get_server_privileges($server_id);
		
		if (!$this->users->auth_servers_privileges['SERVER_CHAT_MSG']) {
			$this->_show_message('Отсутствуют привилегии отправки сообщений', site_url('chat'), lang('next'));
			return FALSE;
		}

		$local_tpl_data = array();
		$local_tpl_data['server_id'] = $server_id;
		
		$this->tpl_data['content'] = $this->parser->parse('chat.html', $local_tpl_data, TRUE);
		$this->parser->parse('main.html', $this->tpl_data);
	}
	
	// ----------------------------------------------------------------
	
	/**
	 * Выводит js скрипта чата
	 * 
	 * Конечно, можно было скрипт засунуть в папку templates,
	 * но установка чата тогда будет усложнена (один файл в одну директорию, другой в другую),
	 * лучше оставим в покое пользователя, и не утомляем дополнительными установками и правками.
	 * 
	*/
	function js()
	{
		$this->parser->parse('chat.js', array());
	}

}
