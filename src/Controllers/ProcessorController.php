<?php
/**
 * Processor Controller
 *
 * PHP Version 8.2
 *
 * @package mwf_canvas
 * @author  Bob Moore <bob@bobmoore.dev>
 * @license GPL-2.0+ <http://www.gnu.org/licenses/gpl-2.0.txt>
 * @link    https://github.com/bob-moore/github-plugin-updater
 * @since   0.1.0
 */

namespace MarkedEffect\GHPluginUpdater\Controllers;

use MarkedEffect\GHPluginUpdater\Processors,
	MarkedEffect\GHPluginUpdater\Services\ServiceLocator,
	MarkedEffect\GHPluginUpdater\Core\Abstracts;

use DI\Attribute\Inject;
/**
 * Controls the registration and execution of "processor"
 *
 * @subpackage Controllers
 */
class ProcessorController extends Abstracts\Controller
{
	/**
	 * Get definitions that should be added to the service container
	 *
	 * @return array<string, mixed>
	 */
	public static function getServiceDefinitions(): array
	{
		return [
			Processors\PluginHeaders::class  => ServiceLocator::autowire(),
			Processors\UpdateResponse::class => ServiceLocator::autowire(),
		];
	}
	/**
	 * Mount processor to filter the update response
	 *
	 * @param Processors\UpdateResponse $processor instance of UpdateResponse processor.
	 *
	 * @return void
	 */
	#[Inject]
	public function mountUpdateResponse( Processors\UpdateResponse $processor ): void
	{
		add_action( "{$this->package}_update_response", [ $processor, 'mergeUpdateResponse' ] );
	}
}
