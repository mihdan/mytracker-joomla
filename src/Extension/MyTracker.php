<?php
declare(strict_types=1);
/**
 * Основной класс плагина.
 *
 * @package   MyTracker
 * @copyright 2023 VK Team
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link      https://jpath.ru/docs/output/js-css/kak-pravilno-podklyuchat-javascript-i-css-v-joomla-4
 * @link      https://habr.com/ru/articles/736412/
 * @link      https://habr.com/ru/articles/677262/
 * @link      https://habr.com/ru/articles/672020/
 * @link      https://docs.joomla.org/Plugin/Events/System
 */

namespace Joomla\Plugin\System\MyTracker\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\Event\DispatcherInterface;
use Joomla\CMS\Log\Log;
use Joomla\Http\HttpFactory;
use Joomla\Http\Http;

/**
 * Class MyTracker.
 *
 * @package MyTracker
 *
 * @since 0.1.0
 */
final class MyTracker extends CMSPlugin implements SubscriberInterface
{
	/**
	 * Отключает старый тип вызова событий через рефлексию.
	 *
	 * @var boolean
	 * @since version 0.1.1
	 */
	protected $allowLegacyListeners = false;

	/**
	 * Базовый URL для S2S API.
	 *
	 * @since 0.1.0
	 */
	const API_BASE = 'https://tracker-s2s.my.com/v1/%s/?idApp=%d';

	/**
	 * Идентификатор приложения.
	 *
	 * @var integer $app_id
	 *
	 * @since 0.1.0
	 */
	private int $app_id;

	/**
	 * Ключ (токен) приложения.
	 *
	 * @var string $api_key
	 *
	 * @since 0.1.0
	 */
	private string $api_key;

	/**
	 * Load the language file on instantiation
	 *
	 * @var    boolean
	 * @since  3.1
	 */
	protected $autoloadLanguage = true;

	private const DOMAINS = [
		'ru'  => 'top-fwz1.mail.ru',
		'com' => 'mytopf.com',
	];

	/**
	 * Идентификатор счётчика.
	 *
	 * @var integer $counter_id
	 *
	 * @since 0.1.0
	 */
	private int $counter_id;

	/**
	 * Домен счётчика.
	 *
	 * @var string $domain
	 *
	 * @since 0.1.0
	 */
	private string $domain;

	/**
	 * Идентификатор пользователя.
	 *
	 * @var integer $user_id
	 *
	 * @since 0.1.0
	 */
	private int $user_id;

	/**
	 * Отслеживать ли пользователя.
	 *
	 * @var boolean $tracking_user
	 *
	 * @since 0.1.0
	 */
	private bool $tracking_user;

	/**
	 * Отслеживать ли входы.
	 *
	 * @var boolean $tracking_login
	 *
	 * @since 0.1.0
	 */
	private bool $tracking_login;

	/**
	 * Отслеживать ли регистрацию.
	 *
	 * @var boolean $tracking_registration
	 *
	 * @since 0.1.0
	 */
	private bool $tracking_registration;

	/**
	 * Включать ли отладку в лог.
	 *
	 * @var boolean $debugging
	 *
	 * @since 0.1.0
	 */
	private bool $debugging;

	/**
	 * Фабрика для работы с HTTP-запросами.
	 *
	 * @var Http $http
	 *
	 * @since 0.1.0
	 */
	private Http $http;

	/**
	 * Returns an array of events this subscriber will listen to.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			'onUserAfterLogin' => 'onUserLogin',
			'onUserAfterSave'  => 'onUserAfterSave',
			'onAfterRender'    => 'onAfterRender',
		];
	}

	/**
	 * This event is triggered after the user is authenticated against the Joomla! user-base.
	 * If you need to abort the login process (authentication), you will need to use onUserAuthenticate instead.
	 *
	 * @param   Event  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onUserLogin(Event $event)
	{
		$this->request(
			'login',
			[
				'customUserId' => $event[0]['user']->id,
			]
		);
	}

	/**
	 * This event is triggered after an update of a user record, or when a new user has been stored in the database.
	 * Password in $user array is already hashed at this point. You may retrieve the cleartext password using $_POST['password'].
	 *
	 * @param   Event  $event  The event object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function onUserAfterSave(Event $event)
	{
		$this->request(
			'registration',
			[
				'customUserId' => $event[0]['id'],
			]
		);
	}

	/**
	 * Получает идентификатор счётчика.
	 *
	 * @return integer
	 *
	 * @since 0.1.0
	 */
	private function get_counter_id(): int
	{
		return $this->counter_id;
	}

	/**
	 * Получает идентификатор пользователя.
	 *
	 * @return integer
	 *
	 * @since 0.1.0
	 */
	private function get_user_id(): int
	{
		return $this->user_id;
	}

	/**
	 * Проверяет нужно ли трекать пользователя.
	 *
	 * @return boolean
	 *
	 * @since 0.1.0
	 */
	private function get_tracking_user(): bool
	{
		return $this->tracking_user;
	}

	/**
	 * Проверяет нужно ли трекать входы.
	 *
	 * @return boolean
	 *
	 * @since 0.1.0
	 */
	private function get_tracking_login(): bool
	{
		return $this->tracking_login;
	}

	/**
	 * Проверяет нужно ли трекать регистрации.
	 *
	 * @return boolean
	 *
	 * @since 0.1.0
	 */
	private function get_tracking_registration(): bool
	{
		return $this->tracking_registration;
	}

	/**
	 * Получает идентификатор счётчика.
	 *
	 * @return string
	 *
	 * @since 0.1.0
	 */
	private function get_domain(): string
	{
		return self::DOMAINS[$this->domain];
	}

	/**
	 * Проверяет включена ли отладка.
	 *
	 * @return boolean
	 *
	 * @since 0.1.0
	 */
	private function get_debugging(): bool
	{
		return $this->debugging;
	}

	/**
	 * Возвращает фабрику для работы с HTTP-запросами.
	 *
	 * @return Http
	 *
	 * @since 0.1.0
	 */
	private function get_http(): Http
	{
		return $this->http;
	}

	/**
	 * Получает идентификатор приложения.
	 *
	 * @return integer
	 *
	 * @since 0.1.0
	 */
	private function get_app_id(): int
	{
		return $this->app_id;
	}

	/**
	 * Получает токен приложения.
	 *
	 * @return string
	 *
	 * @since 0.1.0
	 */
	private function get_api_key(): string
	{
		return $this->api_key;
	}

	/**
	 * Констурктор класса.
	 *
	 * @param   DispatcherInterface $dispatcher Диспетчер.
	 * @param   array               $config     Конифгурация.
	 *
	 * @since 0.1.0
	 *
	 * @throws \Exception
	 */
	public function __construct(DispatcherInterface $dispatcher, array $config)
	{
		parent::__construct($dispatcher, $config);

		$app  = Factory::getApplication();
		$http = (new HttpFactory)->getHttp([]);

		$this->debugging             = (bool) $this->params->get('debugging', false);
		$this->app_id                = (int) $this->params->get('app_id', 0);
		$this->api_key               = $this->params->get('api_key', '');
		$this->counter_id            = (int) $this->params->get('counter_id', 0);
		$this->domain                = $this->params->get('domain', 'ru');
		$this->tracking_user         = (bool) $this->params->get('tracking_user', false);
		$this->tracking_login        = (bool) $this->params->get('tracking_login', false);
		$this->tracking_registration = (bool) $this->params->get('tracking_registration', false);
		$this->user_id               = (int) $app->getIdentity()->id;
		$this->http                  = $http;
	}

	/**
	 * Выводит код трекинга во фронтенде.
	 *
	 * @throws \Exception
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function onAfterRender(): void
	{
		$app  = Factory::getApplication();

		if (!$app->isClient('site'))
		{
			return;
		}

		$buffer = $app->getBody();

		$counter_id    = $this->get_counter_id();
		$user_id       = $this->get_user_id();
		$domain        = $this->get_domain();
		$tracking_user = $this->get_tracking_user();

		if (!$counter_id)
		{
			return;
		}

		ob_start();
		?>
		<!-- Top.Mail.Ru counter -->
		<script type="text/javascript">
			var _tmr = window._tmr || (window._tmr = []);

			<?php if ($user_id && $tracking_user) : // phpcs:ignore Joomla.ControlStructures.ControlStructuresBrackets.OpenBraceNewLine ?>
				_tmr.push({ type: 'setUserID', userid: "<?php echo $user_id; ?>" });
			<?php endif; ?>

			_tmr.push({id: "<?php echo $counter_id; ?>", type: "pageView", start: (new Date()).getTime()});

			(function (d, w, id) {
				if (d.getElementById(id)) return;
				var ts = d.createElement("script");

				ts.type = "text/javascript";
				ts.async = true;
				ts.id = id;
				ts.src = "https://<?php echo $domain; ?>/js/code.js";

				var f = function () {
					var s = d.getElementsByTagName("script")[0];
					s.parentNode.insertBefore(ts, s);
				};

				if (w.opera === "[object Opera]") {
					d.addEventListener("DOMContentLoaded", f, false);
				} else {
					f();
				}
			})(document, window, "tmr-code");
		</script>
		<noscript>
			<div>
				<img
					src="https://<?php echo $domain; ?>/counter?id=<?php echo $counter_id; ?>;js=na"
					style="position:absolute;left:-9999px;"
					alt="Top.Mail.Ru"
				/>
			</div>
		</noscript>
		<!-- /Top.Mail.Ru counter -->
		<?php
		$insert = ob_get_contents();

		$buffer = preg_replace('/<body[^>]+>\K/i', $insert, $buffer);

		// Use the replaced HTML body.
		$app->setBody($buffer);

		ob_end_clean();

	}

	/**
	 * Отправка запроса в API.
	 *
	 * @param   string $method Название метода.
	 * @param   array  $data   Данные по пользователю.
	 *
	 * @return true
	 *
	 * @since 0.1.0
	 */
	private function request(string $method, array $data): bool
	{
		$defaults = [
			'eventTimestamp' => time(),
		];

		$data = array_merge($defaults, $data);

		$headers = [
			'Content-Type'  => 'application/json',
			'Authorization' => $this->get_api_key(),
		];

		$url = sprintf(self::API_BASE, $method, $this->get_app_id());

		$response = $this->get_http()->post(
			$url,
			json_encode($data),
			$headers
		);

		if ($this->get_debugging())
		{
			Log::add(print_r($data, true), Log::DEBUG, 'mytracker');
			Log::add(print_r($headers, true), Log::DEBUG, 'mytracker');
			Log::add(print_r($response, true), Log::DEBUG, 'mytracker');
		}

		if ($response->getStatusCode() !== 200)
		{
			return false;
		}

		return true;
	}
}
