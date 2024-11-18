<?php

use Glpi\Plugin\Hooks;
require_once 'vendor/autoload.php';

use TelegramBot\Api\BotApi;

define('PLUGIN_TELEBOY_VERSION', '1.0.0');

// Minimal GLPI version, inclusive
define("PLUGIN_TELEBOY_MIN_GLPI_VERSION", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_TELEBOY_MAX_GLPI_VERSION", "10.0.99");


/**
 * Init hooks of the plugin.
 * REQUIRED
 *
 * @return void
 */
function plugin_init_teleboy()
{
    global $PLUGIN_HOOKS;
    Plugin::registerClass(Config::class, ['addtabon' => 'Config']);
    // Config page
    if (Session::haveRight('config', UPDATE)) {
        $PLUGIN_HOOKS['config_page']['teleboy'] = 'front/config.php';
    }

    $PLUGIN_HOOKS['csrf_compliant']['teleboy'] = true;
    $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['teleboy'] = [Ticket::class => 'ticket_update'];
    $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['teleboy'] = [Ticket::class => 'ticket_add'];
}

function sendTG($message)
{
    $chatId = 321;   //CHAT ID PLACE
    $bot = new BotApi('TOKEN PLACE');

    try {
        $bot->sendMessage($chatId, $message);
    } catch (Exception $e) {
        error_log('Ошибка отправки сообщения в Telegram: ' . $e->getMessage());
    }
}
function get_username_by_id($id)
{
    global $DB;
    $iterator = $DB->request(
        'glpi_users',
        [
            'WHERE' => ['id' => $id]
        ]
    );
    if (count($iterator) === 1) {
        $data     = $iterator->current();
        if (!empty($data['realname'])) {
            $formatted = $data['realname'];

            if (!empty($data['firstname'])) {
                $formatted .= " " . $data["firstname"];
            }
        } else {
            $formatted = $data["name"];
        }
        return $formatted;
    }
}
function get_ticket_by_id($ticketId) {
    global $DB;
    $result = $DB->request(
        'glpi_tickets',
        [
            'WHERE' => ['id' => $ticketId]
        ]
    );
    $ticketData = $result->current();

    return $ticketData;
}
function ticket_exists($ticketId)
{
    global $DB;
    $result = $DB->request(
        'glpi_tickets',
        [
            'WHERE' => ['id' => $ticketId]
        ]
    );
    $ticketData = $result->current();
    if($ticketData == null) return false;
    else return true;
}
function get_status_by_id($id)
{
    switch($id){
        case 1:
            return " 'Новая' ";
            break;
        case 2:
            return " 'Назначенная' ";
            break;
        case 3:
            return " 'В работе 'запланирована'' ";
            break;
        case 4:
            break;
        case 5:
            return " 'Решенная' ";
            break; 
        case 6:
            return " 'Закрытая' ";
            break;
        default:
            break;
    }
}
$already_sent = 0;
function ticket_add(Ticket $tk)
{
    global $already_sent;
    $already_sent = 0;
    $fields = $tk->input;
    $id__ = $fields['id'];
    file_put_contents("/var/www/glpi/plugins/teleboy/debugMYPHP_tk.log", print_r($tk, true), FILE_APPEND);
    $formatted_date = date("H:i d.m.Y", strtotime($fields['date']));
    $result = "Статус:" . get_status_by_id($fields['status']) . "\n"; 
    $result .= "Дата создания: " . $formatted_date . "\n";
    $result .= "Пользователь: "  . get_username_by_id($fields['_users_id_requester']) . "\n";
    $desc = str_replace(["</div>", "\r\n"], ["\n", "\n"], htmlspecialchars_decode($fields['content']));
    $desc = strip_tags($desc);
    $result .= "Описание: " . $desc ."\n";
    sendTG($result);
}
function ticket_update(Ticket $tk)
{
    if(isset($tk->input['status'])) {
        $current_ticket_data = get_ticket_by_id($tk->input['id']);
        $result = get_username_by_id($current_ticket_data['users_id_lastupdater']) . " изменил статус на: " . get_status_by_id($current_ticket_data['status']) . "\n";
        $result .= "Содержание: " . strip_tags(str_replace("</div>", "\n", htmlspecialchars_decode($current_ticket_data['content']))) . "\n";
        $result .= $ticket_link;
        sendTG($result);
	}
}
/**
 * Get the name and the version of the plugin
 * REQUIRED
 *
 * @return array
 */
function plugin_version_teleboy()
{


    return [
        'name'           => 'teleboy',
        'version'        => PLUGIN_TELEBOY_VERSION,
        'author'         => 'Kritskiy Egor',
        'license'        => '',
        'homepage'       => '',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_TELEBOY_MIN_GLPI_VERSION,
                'max' => PLUGIN_TELEBOY_MAX_GLPI_VERSION,
            ]
        ]
    ];
}

/**
 * Check pre-requisites before install
 * OPTIONNAL, but recommanded
 *
 * @return boolean
 */
function plugin_teleboy_check_prerequisites()
{
    return true;
}

/**
 * Check configuration process
 *
 * @param boolean $verbose Whether to display message on failure. Defaults to false
 *
 * @return boolean
 */
function plugin_teleboy_check_config($verbose = false)
{
    if (true) { // Your configuration check
        return true;
    }

    if ($verbose) {
        echo __('Installed / not configured', 'teleboy');
    }
    return false;
}
if (isset($_POST['submit'])) {
    save_teleboy_config();
}
