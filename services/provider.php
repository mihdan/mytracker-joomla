<?php
/**
 * @package   MyTracker
 * @copyright 2023 VK Team
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') || die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Plugin\System\MyTracker\Extension\MyTracker;

return new class implements ServiceProviderInterface {
	/**
	 * Registers the service provider with a DI container.
	 *
	 * @param   Container  $container  The DI container.
	 *
	 * @return void
	 *
	 * @since 4.0.0
	 */
	public function register(Container $container)
	{
		$container->set(
			PluginInterface::class,
			function (Container $container) {
				$subject = $container->get(DispatcherInterface::class);
				$config  = (array) PluginHelper::getPlugin('system', 'mytracker');

				$app = Factory::getApplication();

				/**
				 * @var \Joomla\CMS\Plugin\CMSPlugin $plugin
				 */
				$plugin = new MyTracker($subject, $config);
				$plugin->setApplication($app);

				return $plugin;
			}
		);
	}
};
