<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Chat module for GameAP
 *
 * 
 *
 * @package		Chat
 * @author		Nikita Kuznetsov (ET-NiK)
 * @copyright	Copyright (c) 2013-2014, Nikita Kuznetsov (http://hldm.org)
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
    
	// -----------------------------------------------------------------

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
    
	// -----------------------------------------------------------------
    
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
    
	// -----------------------------------------------------------------
    
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
		
		/* Если не задана команда отправки чата */
		if (!$this->servers->server_data['sendmsg_cmd']) {
			$this->_show_message('Команда отправки сообщения не задана', site_url('chat'));
			return FALSE;
		}
		
		return TRUE;
	}
	
	// -----------------------------------------------------------------
	
	/**
	 * Получение данных фильтра для вставки в шаблон
	 */
	private function _get_tpl_filter($filter = false)
	{
		if (!$filter) {
			$filter = $this->users->get_filter('servers_list');
		}
		
		if (empty($this->games->games_list)) {
			$this->games->get_games_list();
		}
		
		$games_option[0] = '---';
		foreach($this->games->games_list as &$game) {
			$games_option[ $game['code'] ] = $game['name'];
		}
		
		$tpl_data['filter_name']			= isset($filter['name']) ? $filter['name'] : '';
		$tpl_data['filter_ip']				= isset($filter['ip']) ? $filter['ip'] : '';
		
		$default = isset($filter['game']) ? $filter['game'] : null;
		$tpl_data['filter_games_dropdown'] 	= form_dropdown('filter_game', $games_option, $default);
		
		return $tpl_data;
	}
	
	// -----------------------------------------------------------------
    
    /**
     * Получение данных сервера для шаблона
    */
    private function _get_servers_tpl($filter, $limit = 10000, $offset = 0)
    {
		$tpl_data = array();
		
		$this->servers->set_filter($filter);
		
		/* Получение игровых серверов GoldSource */
		if (!isset($this->servers_list)) {
			$this->servers->get_servers_list($this->users->auth_id, 'VIEW', array('enabled' => '1', 'installed' => '1'), $limit, $offset, 'goldsource');
		}
		
		$tpl_data['url'] 			= site_url('admin/servers_files/server');
		$tpl_data['games_list'] 	= servers_list_to_games_list($this->servers->servers_list);

		return $tpl_data;
	}
    
    // -----------------------------------------------------------------
    
    /**
     * Главная страница чата. Выбор сервера.
     * Перед пользователем появляется таблица, с серверами, к которым у него есть доступ.
     * 
     * @param int - ID сервера
    */
    function index()
    {
		$this->load->helper('games');
		
		$filter = $this->users->get_filter('servers_list');
		$local_tpl_data = $this->_get_tpl_filter($filter);
		
		$local_tpl_data 		+= $this->_get_servers_tpl($filter);
		$local_tpl_data['url'] 	= site_url('chat/server');
			
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
